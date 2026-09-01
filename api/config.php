<?php
/**
 * Configuración general para la API
 * Establece headers, CORS y configuraciones base
 */

// Habilitar errores para desarrollo (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers para API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Log para debugging
error_log('API Request: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);
error_log('Request data: ' . file_get_contents('php://input'));

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir configuración de base de datos local
require_once __DIR__ . '/Config/database.php';

// Incluir clase BD directamente para asegurar disponibilidad
require_once __DIR__ . '/Config/BD.php';

// Incluir autoload de Composer local
require_once __DIR__ . '/vendor/autoload.php';

// Configuración de zona horaria
date_default_timezone_set('America/Caracas');

// Respuesta JSON estándar
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Respuesta de error
function errorResponse($message, $statusCode = 400, $errors = null) {
    $response = [
        'status' => 'error',
        'message' => $message
    ];
    
    if ($errors !== null) {
        $response['errors'] = $errors;
    }
    
    jsonResponse($response, $statusCode);
}

// Respuesta de éxito
function successResponse($data, $message = 'Operación exitosa') {
    $response = [
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ];
    
    jsonResponse($response, 200);
}

// Obtener datos de la petición
function getRequestData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $data = json_decode(file_get_contents('php://input'), true);
        return $data ?? [];
    }
    
    return $_POST;
}

// Validar método HTTP
function validateMethod($allowedMethods) {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if (!in_array($method, $allowedMethods)) {
        errorResponse('Método no permitido', 405);
    }
}
