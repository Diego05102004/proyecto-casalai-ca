<?php
/**
 * Endpoint API para verificar el token JWT en tiempo real
 * 
 * Este endpoint es usado por el JavaScript para verificar periódicamente
 * si el token JWT del usuario sigue siendo válido.
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
    // Debug: Verificar si hay sesión activa
    $debugInfo = [
        'session_active' => session_status() === PHP_SESSION_ACTIVE,
        'session_id' => session_id(),
        'cookie_name' => Auth::getCookieName(),
        'cookie_exists' => isset($_COOKIE[Auth::getCookieName()]),
        'cookie_value_prefix' => isset($_COOKIE[Auth::getCookieName()]) ? substr($_COOKIE[Auth::getCookieName()], 0, 20) . '...' : 'N/A'
    ];
    
    // Verificar el token JWT
    $payload = Auth::validateToken();
    
    if ($payload) {
        // Token válido
        $timeUntilExpiry = $payload['exp'] - time();
        
        echo json_encode([
            'success' => true,
            'valid' => true,
            'expires_in' => $timeUntilExpiry,
            'user_id' => $payload['sub'] ?? null,
            'role' => $payload['role'] ?? null,
            'message' => 'Token válido',
            'debug' => $debugInfo
        ]);
    } else {
        // Token inválido o expirado
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'valid' => false,
            'message' => 'Token inválido o expirado',
            'redirect' => '?pagina=login',
            'debug' => $debugInfo
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'valid' => false,
        'message' => 'Error al verificar token: ' . $e->getMessage(),
        'debug' => $debugInfo ?? []
    ]);
}
