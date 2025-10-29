<?php
if (!is_file("modelo/" . $pagina . ".php")) {
    echo "Falta el modelo";
    exit;
}
require_once("modelo/" . $pagina . ".php");
if (is_file("vista/" . $pagina . ".php")) {
    if (!empty($_POST)) {
        $o = new Login();
        $h = $_POST['accion'];

        if ($h == 'acceder') {
            $o->set_username($_POST['username']);
            $o->set_password($_POST['password']);
            $m = $o->existe();
            if ($m['resultado'] == 'existe') {
                session_destroy();
                session_start();
                $_SESSION['name'] = $m['mensaje'];
                $_SESSION['nombre_rol'] = $m['nombre_rol'];
                $_SESSION['id_usuario'] = $m['id_usuario'];
                $_SESSION['id_rol'] = $m['id_rol'];
                $_SESSION['cedula'] = $m['cedula'];
                if ($_SESSION['nombre_rol'] === 'Cliente') {
                 header(header: 'Location: ?pagina=catalogo');  
                }else{

                header(header: 'Location: ?pagina=dashboard');    
                }
                
                die();
            } else {
                $mensaje = $m['mensaje'];
            }
        }

        if ($h == 'solicitar_recuperacion') {
    $email = $_POST['email'];
    $resultado = $o->solicitarRecuperacion($email);
    
    if ($resultado['status'] == 'success') {
        // Enviar correo con el enlace de recuperación
        $enlace = "http://localhost/proyecto-casalai-ca/?pagina=recuperar&id=".$resultado['id_usuario']."&token=".$resultado['token'];
        
        // En el controlador, después de generar el token
        $asunto = "Recuperación de contraseña";
        $mensaje = "Haz clic en el siguiente enlace para restablecer tu contraseña: \n\n";
        $mensaje .= "http://localhost/proyecto-casalai-ca/?pagina=recuperar&id=".$resultado['id_usuario']."&token=".$resultado['token'];
        $mensaje .= "\n\nEste enlace expirará en 1 hora.";

        $headers = "From: no-reply@tusitio.com\r\n";
        $headers .= "Reply-To: no-reply@tusitio.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($email, $asunto, $mensaje, $headers);
        
        $mensaje = "Se ha enviado un enlace de recuperación a tu correo electrónico.";
    } else {
        $mensaje = $resultado['mensaje'];
    }
}

if ($h == 'actualizar_password') {
    $id_usuario = $_POST['id_usuario'];
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirmar = $_POST['confirmar'];
    
    if ($password !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden";
    } else {
        $valido = $o->validarToken($id_usuario, $token);
        
        if ($valido) {
            $exito = $o->actualizarPassword($id_usuario, $password);
            $mensaje = $exito ? "Contraseña actualizada correctamente" : "Error al actualizar la contraseña";
        } else {
            $mensaje = "El enlace de recuperación no es válido o ha expirado";
        }
    }
}

        // NUEVO: Registro doble usuario + cliente
        if ($h == 'registrar') {
            // Recibe los datos del formulario
            $datos = [
                'nombre_usuario' => $_POST['nombre_usuario'],
                'clave' => $_POST['clave'],
                'nombre' => $_POST['nombre'],
                'apellido' => $_POST['apellido'],
                'correo' => $_POST['correo'],
                'telefono' => $_POST['telefono'],
                'cedula' => $_POST['cedula'],
                'direccion' => $_POST['direccion']
            ];
            // Llama al método del modelo
            $resultado = $o->registrarUsuarioYCliente($datos);
            if ($resultado['status'] == 'success') {
    $mensaje = '<span class="success">' . $resultado['mensaje'] . '</span>';
} else {
    $mensaje = '<span class="error">' . $resultado['mensaje'] . '</span>';
}
        }
    }

    

    require_once("vista/" . $pagina . ".php");
} else {
    echo "Falta la vista";
}