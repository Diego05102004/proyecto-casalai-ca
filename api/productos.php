<?php
/**
 * Endpoint para obtener productos del catálogo
 * GET /api/productos.php
 * 
 * Parámetros opcionales:
 * - categoria: ID de categoría (11 = Impresoras, 12 = Tintas, etc.)
 * - id: ID específico de producto
 */

require_once __DIR__ . '/config.php';

// Solo permitir método GET
validateMethod(['GET']);

try {
    // Configuración de base de datos
    $host = 'localhost';
    $dbname = 'casalai_principal';
    $username = 'root';
    $password = '';

    // Crear conexión
    $conn = new mysqli($host, $username, $password, $dbname);

    // Verificar conexión
    if ($conn->connect_error) {
        errorResponse('Error de conexión a la base de datos', 500);
    }

    // Establecer charset
    $conn->set_charset('utf8mb4');

    // Obtener parámetros
    $categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : null;
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    // Construir consulta
    $sql = "SELECT p.*, c.nombre_categoria 
            FROM tbl_productos p 
            LEFT JOIN tbl_categoria c ON p.id_categoria = c.id_categoria 
            WHERE p.estado = 'habilitado'";
    
    $params = [];
    $types = '';

    if ($categoria) {
        $sql .= " AND p.id_categoria = ?";
        $params[] = $categoria;
        $types .= 'i';
    }

    if ($id) {
        $sql .= " AND p.id_producto = ?";
        $params[] = $id;
        $types .= 'i';
    }

    $sql .= " ORDER BY p.nombre_producto ASC";

    // Preparar statement
    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    // Ejecutar consulta
    $stmt->execute();
    $result = $stmt->get_result();

    // Obtener productos
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        // Procesar imagen para que sea accesible desde la app
        $imagen = $row['imagen'];
        if ($imagen) {
            // Convertir ruta de Windows a URL web
            $imagen = str_replace('\\', '/', $imagen);
            $imagen = str_replace('assets/img/productos/', '', $imagen);
        }

        $productos[] = [
            'id' => $row['id_producto'],
            'serial' => $row['serial'],
            'nombre' => $row['nombre_producto'],
            'descripcion' => $row['descripcion_producto'],
            'categoria_id' => $row['id_categoria'],
            'categoria_nombre' => $row['nombre_categoria'],
            'precio' => floatval($row['precio']),
            'stock' => intval($row['stock']),
            'stock_minimo' => intval($row['stock_minimo']),
            'stock_maximo' => intval($row['stock_maximo']),
            'garantia' => $row['clausula_garantia'],
            'imagen' => $imagen ? "http://localhost/Repositorio%20de%20GITHUB/proyecto-casalai-main/proyecto-casalai-ca/assets/img/productos/$imagen" : null
        ];
    }

    // Cerrar conexión
    $stmt->close();
    $conn->close();

    // Retornar respuesta
    successResponse([
        'productos' => $productos,
        'total' => count($productos)
    ], 'Productos obtenidos exitosamente');

} catch (Exception $e) {
    errorResponse('Error en el servidor: ' . $e->getMessage(), 500);
}
