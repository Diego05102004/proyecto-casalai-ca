<?php
/**
 * Endpoint API para extender el token JWT
 * 
 * Este endpoint permite al usuario extender su sesión por un tiempo adicional
 * cuando el token está por expirar.
 */

// Cargar autoload de Composer para Firebase JWT
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión para acceder a cookies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Importar clase de autenticación JWT
require_once __DIR__ . '/../Modelo/Config/Auth.php';

use Usuario\ProyectoCasalaiCa\Config\Auth;

try {
    // Verificar el token JWT actual
    $payload = Auth::validateToken();
    
    if (!$payload) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token inválido o expirado'
        ]);
        exit;
    }
    
    // Extender el token por 5 minutos (300 segundos)
    $extensionTime = 300; // 5 minutos
    $newToken = Auth::extendToken($extensionTime);
    
    if ($newToken) {
        // Establecer el nuevo token en la cookie
        Auth::setTokenCookie($newToken, $extensionTime);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sesión extendida exitosamente',
            'extended_by' => $extensionTime,
            'expires_in' => $extensionTime
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al extender la sesión'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al extender token: ' . $e->getMessage()
    ]);
}
