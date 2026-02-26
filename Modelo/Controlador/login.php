<?php
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
        $url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $clavesecreta . "&response=" . $captcha . "&remoteip=" . $ip;
        $response = file_get_contents($url);
        $responseKeys = json_decode($response, true);
        
        // Si el reCAPTCHA no es válido, mostrar mensaje y detener la ejecución
        if(!$responseKeys['success']) {
            $mensaje = '<div class="error">Error en la verificación del reCAPTCHA: ' . ($responseKeys['error-codes'][0] ?? 'Error desconocido') . '</div>';
        } else {
            $m = $o->existe();
            
            if ($m['resultado'] == 'existe') {
                // Reiniciar la sesión por seguridad
                session_destroy();
                session_start();

                $_SESSION['name'] = $m['mensaje'] ?? '';
                $_SESSION['nombre_rol'] = $m['nombre_rol'] ?? '';
                $_SESSION['id_usuario'] = $m['id_usuario'] ?? '';
                $_SESSION['id_rol'] = $m['id_rol'] ?? '';
                $_SESSION['cedula'] = $m['cedula'] ?? '';
                $_SESSION['foto_perfil'] = $m['foto_perfil'] ?? '';
                
                // Iniciar servidor WebSocket automáticamente después del login exitoso
                ob_start(); // Capturar salida para evitar interferir con headers
                require_once __DIR__ . '/../../verificar_websocket.php';
                
                // Ejecutar la verificación y inicio del servidor
                verificarEIniciarWebSocket();
                ob_end_clean(); // Limpiar salida capturada
                
                // Guardar mensaje para mostrar después del redirect
                $_SESSION['websocket_init_message'] = 'Servidor WebSocket iniciado automáticamente';
                
                header('Location: ?pagina=' . ($_SESSION['nombre_rol'] === 'Cliente' ? 'catalogo' : 'dashboard'));
                exit;
            } else {
                $mensaje = '<div class="error">' . ($m['mensaje'] ?? 'Error en las credenciales') . '</div>';
            }
        }
    }
}

    // Recuperación de contraseña
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
