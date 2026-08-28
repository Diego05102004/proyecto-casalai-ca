<?php
/**
 * Endpoint para productos del catálogo
 * GET/POST /api/productos.php
 * 
 * Funciones disponibles:
 * - default: Obtener todos los productos (GET)
 * - por_id: Obtener producto por ID (GET)
 * - por_categoria: Obtener productos por categoría (GET)
 * - detallado: Obtener producto detallado con características (GET)
 * 
 * Parámetros:
 * - funcion: Nombre de la función a ejecutar
 * - id: ID del producto (para por_id, detallado)
 * - categoria: ID de categoría (para por_categoria)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;

// Crear instancia de la clase
$producto = new Productos();

// Definir operaciones disponibles
$operations = [
    'default' => [
        'method' => 'GET',
        'handler' => 'obtenerTodosProductos'
    ],
    'por_id' => [
        'method' => 'GET',
        'handler' => 'apiObtenerProductoPorId'
    ],
    'por_categoria' => [
        'method' => 'GET',
        'handler' => 'apiObtenerProductosPorCategoria'
    ],
    'detallado' => [
        'method' => 'GET',
        'handler' => 'obtenerProductoDetalladoApi'
    ]
];

// Los métodos ahora están definidos como métodos públicos en la clase Productos
// obtenerTodosProductos(), apiObtenerProductoPorId(), apiObtenerProductosPorCategoria(), obtenerProductoDetalladoApi()
// RecibirPeticion() los invocará directamente usando method_exists()

// Procesar la petición
RecibirPeticion($producto, $operations);
