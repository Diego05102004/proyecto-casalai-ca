<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

require_once '../Config/database.php';

// Conectar a la base de datos principal
$dbConfig = DB_PRINCIPAL;
$conn = new mysqli(
    $dbConfig['host'],
    $dbConfig['user'],
    $dbConfig['pass'],
    $dbConfig['dbname']
);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos: ' . $conn->connect_error]);
    exit;
}

$conn->set_charset('utf8mb4');

$funcion = $_GET['funcion'] ?? $_POST['funcion'] ?? '';

try {
    switch ($funcion) {
        case 'consultar':
            consultarCarrito();
            break;
        case 'agregar':
            agregarAlCarrito();
            break;
        case 'actualizar':
            actualizarCarrito();
            break;
        case 'eliminar':
            eliminarDelCarrito();
            break;
        case 'vaciar':
            vaciarCarrito();
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Función no especificada']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function consultarCarrito() {
    global $conn;
    
    // Obtener el ID del usuario del token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    
    if (!$token) {
        echo json_encode(['status' => 'error', 'message' => 'No se proporcionó token de autenticación']);
        return;
    }
    
    // Decodificar el token para obtener el user_id
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        echo json_encode(['status' => 'error', 'message' => 'Token inválido']);
        return;
    }
    
    $payload = json_decode(base64_decode($tokenParts[1]), true);
    $userId = $payload['user_id'] ?? $payload['sub'] ?? null;
    
    if (!$userId) {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo identificar el usuario']);
        return;
    }
    
    // Consultar el carrito del usuario
    $query = "SELECT 
                cd.id_carrito_detalle as cartItemId,
                cd.id_producto as id,
                cd.cantidad as quantity,
                cd.precio as price,
                p.nombre as name,
                p.descripcion as description,
                p.stock,
                p.imagen as image,
                m.nombre as marca
              FROM carrito_detalle cd
              INNER JOIN carrito c ON cd.id_carrito = c.id_carrito
              INNER JOIN productos p ON cd.id_producto = p.id_producto
              LEFT JOIN marcas m ON p.id_marca = m.id_marca
              WHERE c.id_usuario = ?
              ORDER BY cd.id_carrito_detalle ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total = 0;
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
        $total += $row['price'] * $row['quantity'];
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'items' => $items,
            'total' => $total
        ]
    ]);
}

function agregarAlCarrito() {
    global $conn;
    
    $items = $_POST['items'] ?? [];
    
    if (empty($items) || !is_array($items)) {
        echo json_encode(['status' => 'error', 'message' => 'No se proporcionaron productos']);
        return;
    }
    
    // Obtener el ID del usuario del token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    
    $tokenParts = explode('.', $token);
    $payload = json_decode(base64_decode($tokenParts[1]), true);
    $userId = $payload['user_id'] ?? $payload['sub'] ?? null;
    
    if (!$userId) {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo identificar el usuario']);
        return;
    }
    
    // Crear o obtener el carrito del usuario
    $query = "SELECT id_carrito FROM carrito WHERE id_usuario = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $cartId = $row['id_carrito'];
    } else {
        $query = "INSERT INTO carrito (id_usuario, fecha_creacion) VALUES (?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $cartId = $conn->insert_id;
    }
    
    // Agregar cada producto al carrito
    foreach ($items as $item) {
        $productId = $item['id_producto'];
        $cantidad = $item['cantidad'];
        
        // Verificar si el producto ya está en el carrito
        $query = "SELECT id_carrito_detalle, cantidad FROM carrito_detalle 
                  WHERE id_carrito = ? AND id_producto = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $cartId, $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Actualizar cantidad
            $newCantidad = $row['cantidad'] + $cantidad;
            $query = "UPDATE carrito_detalle SET cantidad = ? WHERE id_carrito_detalle = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ii', $newCantidad, $row['id_carrito_detalle']);
            $stmt->execute();
        } else {
            // Insertar nuevo
            $query = "SELECT precio FROM productos WHERE id_producto = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $priceResult = $stmt->get_result();
            $priceRow = $priceResult->fetch_assoc();
            $precio = $priceRow['precio'];
            
            $query = "INSERT INTO carrito_detalle (id_carrito, id_producto, cantidad, precio) 
                      VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iiid', $cartId, $productId, $cantidad, $precio);
            $stmt->execute();
        }
    }
    
    // Retornar el carrito actualizado
    consultarCarrito();
}

function actualizarCarrito() {
    global $conn;
    
    $cartItemId = $_POST['id_carrito_detalle'] ?? 0;
    $cantidad = $_POST['cantidad'] ?? 1;
    
    if (!$cartItemId || $cantidad < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
        return;
    }
    
    $query = "UPDATE carrito_detalle SET cantidad = ? WHERE id_carrito_detalle = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $cantidad, $cartItemId);
    $stmt->execute();
    
    // Retornar el carrito actualizado
    consultarCarrito();
}

function eliminarDelCarrito() {
    global $conn;
    
    $cartItemId = $_POST['id_carrito_detalle'] ?? 0;
    
    if (!$cartItemId) {
        echo json_encode(['status' => 'error', 'message' => 'ID de carrito inválido']);
        return;
    }
    
    $query = "DELETE FROM carrito_detalle WHERE id_carrito_detalle = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $cartItemId);
    $stmt->execute();
    
    // Retornar el carrito actualizado
    consultarCarrito();
}

function vaciarCarrito() {
    global $conn;
    
    // Obtener el ID del usuario del token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    
    $tokenParts = explode('.', $token);
    $payload = json_decode(base64_decode($tokenParts[1]), true);
    $userId = $payload['user_id'] ?? $payload['sub'] ?? null;
    
    if (!$userId) {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo identificar el usuario']);
        return;
    }
    
    // Eliminar todos los items del carrito del usuario
    $query = "DELETE cd FROM carrito_detalle cd 
              INNER JOIN carrito c ON cd.id_carrito = c.id_carrito 
              WHERE c.id_usuario = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'items' => [],
            'total' => 0
        ]
    ]);
}
?>
