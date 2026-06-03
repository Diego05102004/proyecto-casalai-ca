<?php
/**
 * Endpoint de Login para la API
 * POST /api/login.php
 * 
 * Body esperado:
 * {
 *   "username": "nombre_usuario",
 *   "password": "contraseña"
 * }
 * O también acepta:
 * {
 *   "email": "correo@ejemplo.com",
 *   "password": "contraseña"
 * }
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../Modelo/Config/database.php';
require_once __DIR__ . '/../Modelo/Config/Config.php';
require_once __DIR__ . '/../Modelo/Usuario/ProyectoCasalaiCa/Clases/Login.php';

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Login;

// Solo permitir método POST
validateMethod(['POST']);

try {
    // Obtener datos de la petición
    $data = getRequestData();
    
    // Aceptar tanto email como username para compatibilidad con la app móvil
    $usernameOrEmail = $data['email'] ?? $data['username'] ?? '';
    
    // Validar datos requeridos
    if (empty($usernameOrEmail) || empty($data['password'])) {
        errorResponse('El correo/usuario y contraseña son obligatorios', 400);
    }
    
    // Crear instancia de Login
    $login = new Login();
    
    // Validar datos de entrada
    $datosValidacion = [
        'username' => $usernameOrEmail,
        'password' => $data['password']
    ];
    
    $errores = $login->validarInicioSesionDatos($datosValidacion);
    
    if (!empty($errores)) {
        errorResponse('Error de validación', 400, $errores);
    }
    
    // Establecer credenciales
    $login->setUsername($usernameOrEmail);
    $login->setPassword($data['password']);
    
    // Verificar credenciales
    $resultado = $login->existe();
    
    if ($resultado['resultado'] == 'existe') {
        // Login exitoso - generar token
        $userData = [
            'id_usuario' => $resultado['id_usuario'],
            'username' => $resultado['mensaje'],
            'nombre_rol' => $resultado['nombre_rol'],
            'id_rol' => $resultado['id_rol'],
            'cedula' => $resultado['cedula'],
            'foto_perfil' => $resultado['foto_perfil']
        ];
        
        $token = Auth::generateToken($resultado['id_usuario'], $userData);
        
        successResponse([
            'token' => $token,
            'user' => $userData,
            'expires_in' => 86400 // 24 horas en segundos
        ], 'Login exitoso');
        
    } elseif ($resultado['resultado'] == 'bloqueado') {
        errorResponse($resultado['mensaje'], 403);
        
    } else {
        errorResponse($resultado['mensaje'], 401);
    }
    
} catch (Exception $e) {
    errorResponse('Error en el servidor: ' . $e->getMessage(), 500);
}
