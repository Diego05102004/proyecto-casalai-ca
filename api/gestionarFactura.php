<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_helper.php';
require_once __DIR__ . '/Clases/Factura.php';

use Usuario\ProyectoCasalaiCa\Factura;

$factura = new Factura();

$request = $_REQUEST;

// Si la petición viene en formato form-urlencoded o raw body, se normaliza
if (empty($request)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        parse_str($rawInput, $request);
    }
}

// Normalizar la cédula desde varios nombres posibles
$cedula = $request['cedula']
    ?? $request['cliente']
    ?? $request['cedula_cliente']
    ?? null;

if (!$cedula) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Cédula requerida'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$factura->setCedula($cedula);


$funcionRaw = $_REQUEST['funcion'] ?? $_REQUEST['function'] ?? 'default';

$funcion = strtolower(trim($funcionRaw));
$funcion = str_replace([' ', '-', '_'], '', $funcion);

$operations = [
    'default' => ['method' => 'GET', 'handler' => 'facturaConsultarMovil'],
    'facturaingresarmovil' => ['method' => 'POST', 'handler' => 'facturaIngresarMovil'],
    'facturaingresar' => ['method' => 'POST', 'handler' => 'facturaIngresarMovil'],
    'facturadescargar' => ['method' => 'GET', 'handler' => 'facturaDescargarMovil'],
    'descargar' => ['method' => 'GET', 'handler' => 'facturaDescargarMovil'],
];

if (!isset($operations[$funcion])) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Función no reconocida: ' . $funcion
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$operation = $operations[$funcion] ?? $operations['default'];

$methodActual = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Validación de método HTTP
if ($methodActual !== $operation['method']) {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido para esta operación.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

RecibirPeticion($factura, $operations);