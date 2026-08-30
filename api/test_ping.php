<?php
// 1. Cabeceras obligatorias para autorizar peticiones desde la App (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// 2. Manejo de la solicitud previa "Preflight" (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. Obtener los datos JSON enviados por la App Móvil
$inputJSON = file_get_contents("php://input");
$input = json_decode($inputJSON, true);

// Extraer el mensaje enviado desde React Native
$mensajeApp = isset($input['mensaje']) ? $input['mensaje'] : 'No se recibió ningún mensaje';

// 4. Preparar la respuesta del servidor
$respuesta = [
    "status" => "success",
    "code" => 200,
    "mensaje_servidor" => "¡Hola desde el hosting! La API recibió tu mensaje correctamente.",
    "datos_recibidos" => $mensajeApp,
    "timestamp" => date("Y-m-d H:i:s")
];

// 5. Retornar el objeto JSON
echo json_encode($respuesta);
?>