<?php
/**
 * Endpoint de Registro de Usuario para la API
 * POST /api/registro.php
 * 
 * Body esperado:
 * {
 *   "nombre_usuario": "nombre_usuario",
 *   "clave": "contraseña",
 *   "nombre": "nombre",
 *   "apellido": "apellido",
 *   "correo": "correo@ejemplo.com",
 *   "telefono": "telefono",
 *   "cedula": "cedula",
 *   "direccion": "direccion"
 * }
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../Modelo/Usuario/ProyectoCasalaiCa/Clases/Login.php';

// Solo permitir método POST
validateMethod(['POST']);

try {
    // Obtener datos de la petición
    $data = getRequestData();
    
    // Validar datos requeridos
    $camposRequeridos = ['nombre_usuario', 'clave', 'nombre', 'apellido', 'correo', 'telefono', 'cedula'];
    foreach ($camposRequeridos as $campo) {
        if (empty($data[$campo])) {
            errorResponse("El campo $campo es obligatorio", 400);
        }
    }
    
    // Crear instancia de Login
    $login = new Login();
    
    // Validar datos de entrada
    $datosValidacion = [
        'nombre_usuario' => $data['nombre_usuario'],
        'clave' => $data['clave'],
        'nombre' => $data['nombre'],
        'apellido' => $data['apellido'],
        'correo' => $data['correo'],
        'telefono' => $data['telefono'],
        'cedula' => $data['cedula'],
        'direccion' => $data['direccion'] ?? ''
    ];
    
    $errores = $login->validarRegistroUsuarioDatos($datosValidacion);
    
    if (!empty($errores)) {
        errorResponse('Error de validación', 400, $errores);
    }
    
    // Registrar usuario
    $resultado = $login->registrarUsuarioYCliente($datosValidacion);
    
    if ($resultado['status'] == 'success') {
        successResponse([], $resultado['mensaje']);
    } else {
        errorResponse($resultado['mensaje'], 400);
    }
    
} catch (Exception $e) {
    errorResponse('Error en el servidor: ' . $e->getMessage(), 500);
}
