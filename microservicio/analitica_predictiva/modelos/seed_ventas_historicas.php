<?php
/**
 * Script para generar datos de prueba históricos de ventas
 * Crea 1000+ facturas con productos y clientes aleatorios
 * Fechas aleatorias desde hoy hasta 30 semanas atrás
 * 
 * USO: php seed_ventas_historicas.php
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'casalai_principal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de generación de datos
define('NUM_VENTAS', 1000);              // Número de facturas a generar
define('SEMANAS_HISTORICAS', 30);        // Semanas hacia atrás desde hoy
define('PRODUCTOS_POR_FACTURA_MIN', 1);  // Mínimo de productos por factura
define('PRODUCTOS_POR_FACTURA_MAX', 5);  // Máximo de productos por factura

try {
    // Conexión a la base de datos
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Conectado a la base de datos: " . DB_NAME . "\n\n";

    // Verificar si hay datos suficientes
    $numClientes = $pdo->query("SELECT COUNT(*) FROM tbl_clientes")->fetchColumn();
    $numProductos = $pdo->query("SELECT COUNT(*) FROM tbl_productos")->fetchColumn();

    echo "Clientes disponibles: $numClientes\n";
    echo "Productos disponibles: $numProductos\n\n";

    if ($numClientes == 0) {
        die("ERROR: No hay clientes en la base de datos. Primero debe crear clientes.\n");
    }

    if ($numProductos == 0) {
        die("ERROR: No hay productos en la base de datos. Primero debe crear productos.\n");
    }

    // Obtener IDs de clientes y productos
    $clientes = $pdo->query("SELECT id_clientes FROM tbl_clientes WHERE activo = 1")->fetchAll(PDO::FETCH_COLUMN);
    $productos = $pdo->query("SELECT id_producto, precio FROM tbl_productos WHERE estado = 'habilitado'")->fetchAll();

    echo "Clientes activos: " . count($clientes) . "\n";
    echo "Productos activos: " . count($productos) . "\n\n";

    // Obtener el último ID de factura para continuar
    $ultimoIdFactura = $pdo->query("SELECT MAX(id_factura) FROM tbl_facturas")->fetchColumn();
    $ultimoIdFactura = $ultimoIdFactura ? $ultimoIdFactura : 0;
    $ultimoIdDetalle = $pdo->query("SELECT MAX(id) FROM tbl_factura_detalle")->fetchColumn();
    $ultimoIdDetalle = $ultimoIdDetalle ? $ultimoIdDetalle : 0;

    echo "Último ID factura: $ultimoIdFactura\n";
    echo "Último ID detalle: $ultimoIdDetalle\n\n";

    // Configurar rango de fechas
    $fechaHoy = new DateTime();
    $fechaInicio = clone $fechaHoy;
    $fechaInicio->sub(new DateInterval('P' . SEMANAS_HISTORICAS . 'W'));

    echo "Rango de fechas: " . $fechaInicio->format('Y-m-d') . " a " . $fechaHoy->format('Y-m-d') . "\n";
    echo "Generando " . NUM_VENTAS . " facturas...\n\n";

    // Iniciar transacción
    $pdo->beginTransaction();

    // Preparar statements
    $stmtFactura = $pdo->prepare("
        INSERT INTO tbl_facturas (id_factura, fecha, cliente, descuento, estatus)
        VALUES (?, ?, ?, ?, 'Completada')
    ");

    $stmtDetalle = $pdo->prepare("
        INSERT INTO tbl_factura_detalle (id, factura_id, id_producto, cantidad, precio_unitario)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmtUltimoIdFactura = $pdo->prepare("SELECT MAX(id_factura) FROM tbl_facturas");
    $stmtUltimoIdDetalle = $pdo->prepare("SELECT MAX(id) FROM tbl_factura_detalle");

    $facturasCreadas = 0;
    $detallesCreados = 0;

    // Generar facturas
    for ($i = 1; $i <= NUM_VENTAS; $i++) {
        // Generar fecha aleatoria dentro del rango
        $timestampAleatorio = mt_rand($fechaInicio->getTimestamp(), $fechaHoy->getTimestamp());
        $fechaVenta = date('Y-m-d', $timestampAleatorio);

        // Seleccionar cliente aleatorio
        $idCliente = $clientes[array_rand($clientes)];

        // Generar descuento aleatorio (0-20%)
        $descuento = mt_rand(0, 20);

        // Obtener el siguiente ID de factura
        $stmtUltimoIdFactura->execute();
        $ultimoIdFactura = $stmtUltimoIdFactura->fetchColumn();
        $nuevoIdFactura = $ultimoIdFactura + 1;

        // Insertar factura
        $stmtFactura->execute([
            $nuevoIdFactura,
            $fechaVenta,
            $idCliente,
            $descuento
        ]);

        $facturasCreadas++;

        // Generar detalles de la factura (productos aleatorios)
        $numProductosFactura = mt_rand(PRODUCTOS_POR_FACTURA_MIN, PRODUCTOS_POR_FACTURA_MAX);
        $productosUsados = [];

        for ($j = 0; $j < $numProductosFactura; $j++) {
            // Seleccionar producto aleatorio (evitar duplicados en la misma factura)
            do {
                $producto = $productos[array_rand($productos)];
                $idProducto = $producto['id_producto'];
            } while (in_array($idProducto, $productosUsados));

            $productosUsados[] = $idProducto;

            // Generar cantidad aleatoria (1-5)
            $cantidad = mt_rand(1, 5);

            // Usar el precio del producto (convertir de float a int según la tabla)
            $precioUnitario = (int)$producto['precio'];

            // Obtener el siguiente ID de detalle
            $stmtUltimoIdDetalle->execute();
            $ultimoIdDetalle = $stmtUltimoIdDetalle->fetchColumn();
            $nuevoIdDetalle = $ultimoIdDetalle + 1;

            // Insertar detalle
            $stmtDetalle->execute([
                $nuevoIdDetalle,
                $nuevoIdFactura,
                $idProducto,
                $cantidad,
                $precioUnitario
            ]);

            $detallesCreados++;
        }

        // Mostrar progreso cada 100 facturas
        if ($i % 100 == 0) {
            echo "Progreso: $i/" . NUM_VENTAS . " facturas creadas\n";
        }
    }

    // Confirmar transacción
    $pdo->commit();

    echo "\n=== RESUMEN ===\n";
    echo "Facturas creadas: $facturasCreadas\n";
    echo "Detalles creados: $detallesCreados\n";
    echo "¡Script completado exitosamente!\n\n";

    // Mostrar estadísticas
    $stmtStats = $pdo->query("
        SELECT 
            COUNT(*) as total_facturas,
            MIN(fecha) as fecha_antigua,
            MAX(fecha) as fecha_reciente,
            AVG(descuento) as descuento_promedio
        FROM tbl_facturas
    ");
    $stats = $stmtStats->fetch();

    echo "=== ESTADÍSTICAS DE FACTURAS ===\n";
    echo "Total de facturas en BD: " . $stats['total_facturas'] . "\n";
    echo "Fecha más antigua: " . $stats['fecha_antigua'] . "\n";
    echo "Fecha más reciente: " . $stats['fecha_reciente'] . "\n";
    echo "Descuento promedio: " . number_format($stats['descuento_promedio'], 2) . "%\n\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("ERROR de base de datos: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("ERROR: " . $e->getMessage() . "\n");
}
