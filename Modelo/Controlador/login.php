<?php

use Usuario\ProyectoCasalaiCa\Clases\Login;
// Verificar si se ha enviado el formulario
if (!empty($_POST)) {
    $o = new Login();
    $h = $_POST['accion'] ?? '';

    if ($h == 'acceder') {
        $o->setUsername($_POST['username'] ?? '');
        $o->setPassword($_POST['password'] ?? '');
        $m = $o->existe();
        
        if ($m['resultado'] == 'existe') {
            session_destroy();
            session_start();
            $_SESSION['name'] = $m['mensaje'] ?? '';
            $_SESSION['nombre_rol'] = $m['nombre_rol'] ?? '';
            $_SESSION['id_usuario'] = $m['id_usuario'] ?? '';
            $_SESSION['id_rol'] = $m['id_rol'] ?? '';
            $_SESSION['cedula'] = $m['cedula'] ?? '';
            
            header('Location: ?pagina=' . ($_SESSION['nombre_rol'] === 'Cliente' ? 'catalogo' : 'dashboard'));
            exit;
        } else {
            $mensaje = $m['mensaje'] ?? 'Error en las credenciales';
        }
    }

    // Resto del código para recuperación de contraseña y registro...
    if ($h == 'solicitar_recuperacion') {
        $email = $_POST['email'] ?? '';
        $resultado = $o->solicitarRecuperacion($email);
        
        if ($resultado['status'] == 'success') {
            // Código de envío de correo...
            $mensaje = "Se ha enviado un enlace de recuperación a tu correo electrónico.";
        } else {
            $mensaje = $resultado['mensaje'] ?? 'Error al procesar la solicitud';
        }
    }

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
        
        $resultado = $o->registrarUsuarioYCliente($datos);
        if ($resultado['status'] == 'success') {
            $mensaje = '<span class="success">' . $resultado['mensaje'] . '</span>';
        } else {
            $mensaje = '<span class="error">' . $resultado['mensaje'] . '</span>';
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