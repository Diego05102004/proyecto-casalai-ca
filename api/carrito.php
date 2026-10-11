<?php
/**
 * API móvil para consultar y modificar el carrito del usuario autenticado.
 * GET: funcion=consultar
 * POST: funcion=agregar|actualizar|eliminar|vaciar
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/auth.php';

use Usuario\ProyectoCasalaiCa\Config\BD;

function normalizeIdentityNumber(string $identityNumber): string
{
    return preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($identityNumber)));
}

function resolveClientId(PDO $principalPdo, int $userId): int
{
    $securityDatabase = new BD('S');

    try {
        $userStatement = $securityDatabase->getConexion()->prepare(
            'SELECT cedula
             FROM tbl_usuarios
             WHERE id_usuario = :id_usuario
             LIMIT 1'
        );
        $userStatement->execute([':id_usuario' => $userId]);
        $userIdentityNumber = $userStatement->fetchColumn();
    } finally {
        $securityDatabase->cerrar();
    }

    if (!is_string($userIdentityNumber) || normalizeIdentityNumber($userIdentityNumber) === '') {
        errorResponse('No se encontró una cédula válida para el usuario autenticado.', 403);
    }

    $clientStatement = $principalPdo->query(
        "SELECT id_clientes, cedula
         FROM tbl_clientes
         WHERE cedula IS NOT NULL AND TRIM(cedula) <> ''"
    );
    $normalizedUserIdentityNumber = normalizeIdentityNumber($userIdentityNumber);
    $matchingClientIds = [];

    foreach ($clientStatement->fetchAll(PDO::FETCH_ASSOC) as $client) {
        if (normalizeIdentityNumber((string) $client['cedula']) === $normalizedUserIdentityNumber) {
            $matchingClientIds[] = (int) $client['id_clientes'];
        }
    }

    if (count($matchingClientIds) !== 1) {
        errorResponse(
            count($matchingClientIds) === 0
                ? 'No existe un cliente asociado a la cédula del usuario autenticado.'
                : 'La cédula del usuario está asociada a más de un cliente. Contacta al administrador.',
            count($matchingClientIds) === 0 ? 403 : 409
        );
    }

    return $matchingClientIds[0];
}

function cartResponse(PDO $pdo, int $clientId): array
{
    $cartStatement = $pdo->prepare(
        'SELECT id_carrito
         FROM tbl_carrito
         WHERE id_cliente = :id_cliente
         ORDER BY fecha_creacion DESC
         LIMIT 1'
    );
    $cartStatement->execute([':id_cliente' => $clientId]);
    $cartId = $cartStatement->fetchColumn();

    if (!$cartId) {
        return ['items' => [], 'total' => 0];
    }

    $itemsStatement = $pdo->prepare(
        "SELECT cd.id_carrito_detalle, cd.id_producto, cd.cantidad,
                p.nombre_producto AS name, COALESCE(p.precio, 0) AS price, p.stock,
                p.imagen AS image
         FROM tbl_carritodetalle cd
         INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
         WHERE cd.id_carrito = :id_carrito AND cd.estatus = 'pendiente'
         ORDER BY cd.id_carrito_detalle"
    );
    $itemsStatement->execute([':id_carrito' => $cartId]);
    $items = $itemsStatement->fetchAll(PDO::FETCH_ASSOC);

    $total = array_reduce(
        $items,
        static function (float $sum, array $item): float {
            return $sum + ((float) $item['price'] * (int) $item['cantidad']);
        },
        0.0
    );

    return ['items' => $items, 'total' => round($total, 2)];
}

function requirePositiveInteger($value, string $field, int $maximum = PHP_INT_MAX): int
{
    $number = filter_var($value, FILTER_VALIDATE_INT);
    if ($number === false || $number < 1 || $number > $maximum) {
        errorResponse("El campo {$field} debe ser un entero válido.", 422);
    }

    return $number;
}

try {
    $payload = Auth::checkAuth();
    $userId = is_array($payload)
        ? filter_var($payload['user_id'] ?? null, FILTER_VALIDATE_INT)
        : false;

    if ($userId === false || $userId < 1) {
        errorResponse('Debes iniciar sesión para acceder al carrito.', 401);
    }

    $database = new BD();
    $pdo = $database->getConexion();
    $clientId = resolveClientId($pdo, $userId);

    $requestData = getRequestData();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $requestData = array_merge($requestData, $_GET);
    }
    $action = strtolower(trim((string) ($requestData['funcion'] ?? 'consultar')));

    if ($action === 'consultar') {
        validateMethod(['GET']);
        successResponse(cartResponse($pdo, $clientId), 'Carrito consultado correctamente');
    }

    validateMethod(['POST']);

    if ($action === 'agregar') {
        $requestedItems = $requestData['items'] ?? null;
        if (!is_array($requestedItems)) {
            $requestedItems = [[
                'id_producto' => $requestData['id_producto'] ?? null,
                'cantidad' => $requestData['cantidad'] ?? 1,
            ]];
        }
        if ($requestedItems === []) {
            errorResponse('Debes indicar al menos un producto para agregar.', 422);
        }

        $quantitiesByProduct = [];
        foreach ($requestedItems as $requestedItem) {
            if (!is_array($requestedItem)) {
                errorResponse('El formato de los productos del carrito no es válido.', 422);
            }

            $productId = requirePositiveInteger($requestedItem['id_producto'] ?? null, 'id_producto');
            $quantity = requirePositiveInteger($requestedItem['cantidad'] ?? null, 'cantidad', 999);
            $quantitiesByProduct[$productId] = ($quantitiesByProduct[$productId] ?? 0) + $quantity;
            if ($quantitiesByProduct[$productId] > 999) {
                errorResponse('La cantidad por producto no puede superar 999 unidades.', 422);
            }
        }

        $pdo->beginTransaction();
        $cartStatement = $pdo->prepare(
            'SELECT id_carrito
             FROM tbl_carrito
             WHERE id_cliente = :id_cliente
             ORDER BY fecha_creacion DESC
             LIMIT 1
             FOR UPDATE'
        );
        $cartStatement->execute([':id_cliente' => $clientId]);
        $cartId = $cartStatement->fetchColumn();

        if (!$cartId) {
            $createCart = $pdo->prepare('INSERT INTO tbl_carrito (id_cliente) VALUES (:id_cliente)');
            $createCart->execute([':id_cliente' => $clientId]);
            $cartId = (int) $pdo->lastInsertId();
        }

        foreach ($quantitiesByProduct as $productId => $quantity) {
            $productStatement = $pdo->prepare(
                "SELECT stock
                 FROM tbl_productos
                 WHERE id_producto = :id_producto AND estado = 'habilitado'
                 FOR UPDATE"
            );
            $productStatement->execute([':id_producto' => $productId]);
            $stock = $productStatement->fetchColumn();
            if ($stock === false) {
                throw new DomainException('Uno de los productos ya no está disponible.', 404);
            }

            $existingStatement = $pdo->prepare(
                "SELECT id_carrito_detalle, cantidad
                 FROM tbl_carritodetalle
                 WHERE id_carrito = :id_carrito
                   AND id_producto = :id_producto
                   AND estatus = 'pendiente'
                 LIMIT 1
                 FOR UPDATE"
            );
            $existingStatement->execute([
                ':id_carrito' => $cartId,
                ':id_producto' => $productId,
            ]);
            $existing = $existingStatement->fetch(PDO::FETCH_ASSOC);
            $nextQuantity = (int) ($existing['cantidad'] ?? 0) + $quantity;

            if ($nextQuantity > (int) $stock) {
                throw new DomainException('No hay stock suficiente para la cantidad solicitada.', 409);
            }

            if ($existing) {
                $updateStatement = $pdo->prepare(
                    'UPDATE tbl_carritodetalle
                     SET cantidad = :cantidad
                     WHERE id_carrito_detalle = :id_carrito_detalle'
                );
                $updateStatement->execute([
                    ':cantidad' => $nextQuantity,
                    ':id_carrito_detalle' => $existing['id_carrito_detalle'],
                ]);
            } else {
                $insertStatement = $pdo->prepare(
                    "INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad, estatus)
                     VALUES (:id_carrito, :id_producto, :cantidad, 'pendiente')"
                );
                $insertStatement->execute([
                    ':id_carrito' => $cartId,
                    ':id_producto' => $productId,
                    ':cantidad' => $quantity,
                ]);
            }
        }

        $pdo->commit();
        successResponse(cartResponse($pdo, $clientId), 'Producto agregado al carrito correctamente');
    }

    if ($action === 'actualizar') {
        $detailId = requirePositiveInteger($requestData['id_carrito_detalle'] ?? null, 'id_carrito_detalle');
        $quantity = requirePositiveInteger($requestData['cantidad'] ?? null, 'cantidad', 999);

        $detailStatement = $pdo->prepare(
            "SELECT cd.id_producto, p.stock
             FROM tbl_carritodetalle cd
             INNER JOIN tbl_carrito c ON c.id_carrito = cd.id_carrito
             INNER JOIN tbl_productos p ON p.id_producto = cd.id_producto
             WHERE cd.id_carrito_detalle = :id_carrito_detalle
               AND c.id_cliente = :id_cliente
               AND cd.estatus = 'pendiente'
             LIMIT 1"
        );
        $detailStatement->execute([
            ':id_carrito_detalle' => $detailId,
            ':id_cliente' => $clientId,
        ]);
        $detail = $detailStatement->fetch(PDO::FETCH_ASSOC);

        if (!$detail) {
            errorResponse('El producto no existe en tu carrito.', 404);
        }
        if ($quantity > (int) $detail['stock']) {
            errorResponse('No hay stock suficiente para la cantidad solicitada.', 409);
        }

        $updateStatement = $pdo->prepare(
            'UPDATE tbl_carritodetalle
             SET cantidad = :cantidad
             WHERE id_carrito_detalle = :id_carrito_detalle'
        );
        $updateStatement->execute([
            ':cantidad' => $quantity,
            ':id_carrito_detalle' => $detailId,
        ]);
        successResponse(cartResponse($pdo, $clientId), 'Cantidad actualizada correctamente');
    }

    if ($action === 'eliminar') {
        $detailId = requirePositiveInteger($requestData['id_carrito_detalle'] ?? null, 'id_carrito_detalle');
        $deleteStatement = $pdo->prepare(
            'DELETE cd
             FROM tbl_carritodetalle cd
             INNER JOIN tbl_carrito c ON c.id_carrito = cd.id_carrito
             WHERE cd.id_carrito_detalle = :id_carrito_detalle
               AND c.id_cliente = :id_cliente'
        );
        $deleteStatement->execute([
            ':id_carrito_detalle' => $detailId,
            ':id_cliente' => $clientId,
        ]);

        if ($deleteStatement->rowCount() === 0) {
            errorResponse('El producto no existe en tu carrito.', 404);
        }
        successResponse(cartResponse($pdo, $clientId), 'Producto eliminado del carrito');
    }

    if ($action === 'vaciar') {
        $deleteStatement = $pdo->prepare(
            'DELETE cd
             FROM tbl_carritodetalle cd
             INNER JOIN tbl_carrito c ON c.id_carrito = cd.id_carrito
             WHERE c.id_cliente = :id_cliente'
        );
        $deleteStatement->execute([':id_cliente' => $clientId]);
        successResponse(cartResponse($pdo, $clientId), 'Carrito vaciado correctamente');
    }

    errorResponse('La operación solicitada para el carrito no existe.', 404);
} catch (DomainException $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    errorResponse($error->getMessage(), $error->getCode() ?: 400);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[API carrito] ' . $error->getMessage());
    errorResponse('No se pudo procesar el carrito. Inténtalo de nuevo.', 500);
}
