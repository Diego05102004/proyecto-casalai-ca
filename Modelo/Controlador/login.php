<?php
// Incluir autoload de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ⚠️ Si ya hay una sesión activa, redirigir a acceso-denegado
if (isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario'])) {
    header('Location: ?pagina=acceso-denegado');
    exit;
}

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Login;
// Verificar si se ha enviado el formulario
if (!empty($_POST)) {
    $o = new Login();
    $h = $_POST['accion'] ?? '';
    
if ($h == 'acceder') {
    // Validar datos de entrada
    $datosValidacion = [
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? ''
    ];
    
    $errores = $o->validarInicioSesionDatos($datosValidacion);
    if (!empty($errores)) {
        $mensaje = '<div class="error">Por favor corrija los siguientes errores:<br>';
        foreach ($errores as $campo => $error) {
            $mensaje .= "• $error<br>";
        }
        $mensaje .= '</div>';
    } else {
        $o->setUsername($_POST['username'] ?? '');
        $o->setPassword($_POST['password'] ?? '');
        $captcha = $_POST['g-recaptcha-response'] ?? '';
        $clavesecreta = "6Le6TT8sAAAAABzR4qMhotOlQ2_5EOlbGTzzdPVl";
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (empty($captcha) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
            $captchaValido = true;
        } else {
            $url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $clavesecreta . "&response=" . $captcha . "&remoteip=" . $ip;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($response === false || $httpCode !== 200) {
                $mensaje = '<div class="error">Error en la verificación del reCAPTCHA: No se pudo conectar con el servidor de verificación. Por favor, inténtalo de nuevo más tarde.</div>';
                $captchaValido = false;
            } else {
                $responseKeys = json_decode($response, true);
                $captchaValido = isset($responseKeys['success']) && $responseKeys['success'] === true;
                
                if (!$captchaValido) {
                    $errorCodes = $responseKeys['error-codes'] ?? [];
                    $errorMessage = 'Error desconocido';
                    
                    if (in_array('missing-input-secret', $errorCodes)) {
                        $errorMessage = 'Configuración del servidor incorrecta';
                    } elseif (in_array('invalid-input-secret', $errorCodes)) {
                        $errorMessage = 'Clave secreta inválida';
                    } elseif (in_array('missing-input-response', $errorCodes)) {
                        $errorMessage = 'Por favor completa el reCAPTCHA';
                    } elseif (in_array('invalid-input-response', $errorCodes)) {
                        $errorMessage = 'Respuesta de reCAPTCHA inválida';
                    } elseif (in_array('timeout-or-duplicate', $errorCodes)) {
                        $errorMessage = 'La verificación expiró, por favor inténtalo de nuevo';
                    }
                    
                    $mensaje = '<div class="error">Error en la verificación del reCAPTCHA: ' . $errorMessage . '</div>';
                }
            }
        }
        
        if (!$captchaValido) {
            // El mensaje ya fue establecido en el bloque anterior
        } else {
            $m = $o->existe();
            
            if ($m['resultado'] == 'existe') {
                session_destroy();
                session_start();

                $_SESSION['name'] = $m['mensaje'] ?? '';
                $_SESSION['nombre_rol'] = $m['nombre_rol'] ?? '';
                $_SESSION['id_usuario'] = $m['id_usuario'] ?? '';
                $_SESSION['id_rol'] = $m['id_rol'] ?? '';
                $_SESSION['cedula'] = $m['cedula'] ?? '';
                $_SESSION['foto_perfil'] = $m['foto_perfil'] ?? '';
                
                ob_start();
                require_once __DIR__ . '/../../verificar_websocket.php';
                
                verificarEIniciarWebSocket();
                ob_end_clean();
                
                $_SESSION['websocket_init_message'] = 'Servidor WebSocket iniciado automáticamente';
                
                header('Location: ?pagina=' . ($_SESSION['nombre_rol'] === 'Cliente' ? 'catalogo' : 'dashboard'));
                exit;
            } elseif ($m['resultado'] == 'bloqueado') {
                $mensaje = '<div class="error">' . ($m['mensaje'] ?? 'Usuario bloqueado') . '</div>';
            } else {
                $mensaje = '<div class="error">' . ($m['mensaje'] ?? 'Error en las credenciales') . '</div>';
            }
        }
    }
}

    if ($h == 'solicitar_recuperacion') {
        $email = $_POST['email'] ?? '';
        $resultado = $o->solicitarRecuperacion($email);
        
        if ($resultado['status'] == 'success') {
            $mensaje = "Se ha enviado un enlace de recuperación a tu correo electrónico.";
        } else {
            $mensaje = $resultado['mensaje'] ?? 'Error al procesar la solicitud';
        }
    }

    // Registro de usuario
    if ($h == 'registrar') {
        $datos = [
            'nombre_usuario' => $_POST['nombre_usuario'] ?? '',
            'clave' => $_POST['clave'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'correo' => $_POST['correo'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'cedula' => $_POST['cedula'] ?? '',
            'direccion' => $_POST['direccion'] ?? ''
        ];
        
        // Validar datos de entrada
        $errores = $o->validarRegistroUsuarioDatos($datos);
        if (!empty($errores)) {
            $mensaje = '<div class="error">Por favor corrija los siguientes errores:<br>';
            foreach ($errores as $campo => $error) {
                $mensaje .= "• $error<br>";
            }
            $mensaje .= '</div>';
        } else {
            $resultado = $o->registrarUsuarioYCliente($datos);
            if ($resultado['status'] == 'success') {
                $mensaje = '<span class="success">' . $resultado['mensaje'] . '</span>';
            } else {
                $mensaje = '<span class="error">' . $resultado['mensaje'] . '</span>';
            }
        }
    }
}

// Cargar la vista
$vista = __DIR__ . '/../../Vista/login.php';
if (file_exists($vista)) {
    require_once $vista;
} else {
    die("Error: No se pudo cargar la vista de login");
}
