<?php
/**
 * Endpoint de Login para la API
 * POST /api/login.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/Clases/Login.php';

use Usuario\ProyectoCasalaiCa\Login;

// Configure RECAPTCHA_SECRET_KEY in the Apache/PHP environment.
const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
const RECAPTCHA_ALLOWED_HOSTNAMES = ['localhost'];

/**
 * Función para verificar el token de reCAPTCHA mediante cURL
 */
function validarReCaptcha($token) {
    $secretKey = getenv('RECAPTCHA_SECRET_KEY') ?: ($_SERVER['RECAPTCHA_SECRET_KEY'] ?? '');
    if (!$secretKey) {
        return ['success' => false, 'error' => 'RECAPTCHA_SECRET_KEY no está configurada'];
    }

    if (!is_string($token) || trim($token) === '') {
        return ['success' => false, 'error' => 'Token no recibido'];
    }

    $ch = curl_init(RECAPTCHA_VERIFY_URL);
    if ($ch === false) {
        return ['success' => false, 'error' => 'No se pudo iniciar cURL'];
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret'   => $secretKey,
            'response' => trim($token),
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'error' => "Error de conexión con Google: $curlError"];
    }

    $result = json_decode($response, true);
    if (!is_array($result)) {
        return ['success' => false, 'error' => "Respuesta inválida de Google (HTTP $httpCode)"];
    }

    $errorCodes = $result['error-codes'] ?? [];
    $hostname = $result['hostname'] ?? '';
    $isValidHostname = in_array($hostname, RECAPTCHA_ALLOWED_HOSTNAMES, true);

    return [
        'success'     => isset($result['success']) && $result['success'] === true,
        'hostname'    => $hostname,
        'hostname_ok' => $isValidHostname,
        'error_codes' => $errorCodes,
    ];
}

// Solo permitir método POST
validateMethod(['POST']);

try {
    $data = getRequestData();
    if (!is_array($data)) {
        errorResponse('El cuerpo de la solicitud no es válido', 400);
    }
    
    $usernameOrEmail = $data['email'] ?? $data['username'] ?? '';
    $recaptchaToken  = $data['recaptcha_token'] ?? $data['recaptchaToken'] ?? '';
    
    if (empty($usernameOrEmail) || empty($data['password'])) {
        errorResponse('El correo/usuario y contraseña son obligatorios', 400);
    }

    if (empty($recaptchaToken)) {
        errorResponse('Es necesario completar la verificación del reCAPTCHA', 400);
    }

    // Validar el token de reCAPTCHA con Google
    $resCaptcha = validarReCaptcha($recaptchaToken);
    if (!$resCaptcha['success'] || !$resCaptcha['hostname_ok']) {
        $detalles = !empty($resCaptcha['error_codes']) 
            ? ' [' . implode(', ', $resCaptcha['error_codes']) . ']' 
            : '';
        error_log('reCAPTCHA rechazado: ' . json_encode([
            'error_codes' => $resCaptcha['error_codes'] ?? [],
            'hostname' => $resCaptcha['hostname'] ?? '',
            'hostname_ok' => $resCaptcha['hostname_ok'] ?? false,
        ]));
        errorResponse('La verificación de reCAPTCHA ha fallado' . $detalles, 400, [
            'captcha_error_codes' => $resCaptcha['error_codes'] ?? [],
            'hostname_validado' => $resCaptcha['hostname_ok'] ?? false,
        ]);
    }
    
    // Crear instancia de Login
    $login = new Login();
    
    $datosValidacion = [
        'username' => $usernameOrEmail,
        'password' => $data['password']
    ];
    
    $errores = $login->validarInicioSesionDatos($datosValidacion);
    
    if (!empty($errores)) {
        errorResponse('Error de validación', 400, $errores);
    }
    
    $login->setUsername($usernameOrEmail);
    $login->setPassword($data['password']);
    
    $resultado = $login->existe();
    
    if ($resultado['resultado'] == 'existe') {
        $userData = [
            'id_usuario' => $resultado['id_usuario'],
            'username'   => $resultado['mensaje'],
            'nombre_rol' => $resultado['nombre_rol'],
            'id_rol'     => $resultado['id_rol'],
            'cedula'     => $resultado['cedula'],
            'foto_perfil' => $resultado['foto_perfil']
        ];
        
        $token = Auth::generateToken($resultado['id_usuario'], $userData);
        
        successResponse([
            'token'      => $token,
            'user'       => $userData,
            'expires_in' => 86400
        ], 'Login exitoso');
        
    } elseif ($resultado['resultado'] == 'bloqueado') {
        errorResponse($resultado['mensaje'], 403);
    } else {
        errorResponse($resultado['mensaje'], 401);
    }
    
} catch (Exception $e) {
    errorResponse('Error en el servidor: ' . $e->getMessage(), 500);
}