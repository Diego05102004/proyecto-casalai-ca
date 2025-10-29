<?php
session_start();
require_once '../config/config.php';
require_once '../modelo/carrito.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(['success' => false, 'count' => 0, 'message' => 'Usuario no autenticado']);
        exit;
    }

    $carritoObj = new Carrito();
    $carritoCliente = $carritoObj->obtenerCarritoPorCliente($_SESSION['id_usuario']);
    
    $count = 0;
    if ($carritoCliente) {
        $productosCarrito = $carritoObj->obtenerProductosDelCarrito($carritoCliente['id_carrito']);
        $count = count($productosCarrito);
    }

    echo json_encode(['success' => true, 'count' => $count]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'count' => 0, 'error' => $e->getMessage()]);
}
?>