<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Para producción, puedes desactivar los errores:
// error_reporting(0);
// ini_set('display_errors', 0);

require_once 'Modelo/usuario.php';
require_once 'Modelo/bitacora.php';
define('MODULO_PERFIL', 22);

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

$usuarioModel = new Usuarios();
$bitacoraModel = new Bitacora();
$usuario = $usuarioModel->obtenerUsuarioPorId($_SESSION['id_usuario']);

// Manejo de actualizaciones específicas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'general';
    
    switch($tipo) {
        case 'personal':
            $respuesta = handlePersonalUpdate($usuarioModel, $bitacoraModel, $usuario);
            break;
        case 'email':
            $respuesta = handleEmailUpdate($usuarioModel, $bitacoraModel, $usuario);
            break;
        case 'password':
            $respuesta = handlePasswordUpdate($usuarioModel, $bitacoraModel, $usuario);
            break;
        case 'avatar':
            $respuesta = handleAvatarUpdate($usuarioModel, $bitacoraModel, $usuario);
            break;
        default:
            $respuesta = ['status' => 'error', 'message' => 'Tipo de acción no válido'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

// Función para actualización de información personal
function handlePersonalUpdate($usuarioModel, $bitacoraModel, $usuario) {
    // Validar contraseña actual
    if (empty($_POST['clave_actual'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es requerida'];
    }
    
    if (!password_verify($_POST['clave_actual'], $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es incorrecta'];
    }

    $datosActualizar = [];
    $camposEditables = ['username', 'nombres', 'apellidos', 'telefono'];

    foreach ($camposEditables as $campo) {
        if (isset($_POST[$campo]) && $_POST[$campo] != $usuario[$campo]) {
            $datosActualizar[$campo] = trim($_POST[$campo]);
        }
    }

    // Validar username único
    if (isset($datosActualizar['username']) && $usuarioModel->existeUsuario($datosActualizar['username'], $usuario['id_usuario'])) {
        return ['status' => 'error', 'message' => 'El nombre de usuario ya está en uso'];
    }

    if (empty($datosActualizar)) {
        return ['status' => 'info', 'message' => 'No se realizaron cambios en la información personal'];
    }

    if ($usuarioModel->actualizarPerfil($_SESSION['id_usuario'], $datosActualizar)) {
        // Actualizar sesión
        foreach ($datosActualizar as $campo => $valor) {
            $_SESSION[$campo] = $valor;
        }
        $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_PERFIL, 'Actualizó su información personal', 'UPDATE', 'tbl_usuarios');
        return ['status' => 'success', 'message' => 'Información personal actualizada correctamente'];
    }

    return ['status' => 'error', 'message' => 'Error al actualizar la información personal'];
}

// Función para cambio de foto de perfil
function handleAvatarUpdate($usuarioModel, $bitacoraModel, $usuario) {
    // Validar contraseña actual
    if (empty($_POST['clave_actual'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es requerida'];
    }
    
    if (!password_verify($_POST['clave_actual'], $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es incorrecta'];
    }

    // Manejo de la imagen de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        // Validar tipo de archivo
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
        $tipoArchivo = $_FILES['foto_perfil']['type'];
        
        if (!in_array($tipoArchivo, $tiposPermitidos)) {
            return ['status' => 'error', 'message' => 'Solo se permiten archivos JPG, PNG o GIF'];
        }
        
        // Validar tamaño (2MB máximo)
        if ($_FILES['foto_perfil']['size'] > 2 * 1024 * 1024) {
            return ['status' => 'error', 'message' => 'La imagen debe ser menor a 2MB'];
        }
        
        $nombreArchivo = uniqid('avatar_') . '_' . basename($_FILES['foto_perfil']['name']);
        $rutaDestino = __DIR__ . '/../uploads/' . $nombreArchivo;
        
        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $rutaDestino)) {
            // Eliminar foto anterior si existe
            if (!empty($usuario['foto_perfil']) && file_exists(__DIR__ . '/../uploads/' . $usuario['foto_perfil'])) {
                unlink(__DIR__ . '/../uploads/' . $usuario['foto_perfil']);
            }
            
            if ($usuarioModel->actualizarPerfil($_SESSION['id_usuario'], ['foto_perfil' => $nombreArchivo])) {
                $_SESSION['foto_perfil'] = $nombreArchivo;
                $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_PERFIL, 'Cambió su foto de perfil', 'UPDATE', 'tbl_usuarios');
                return ['status' => 'success', 'message' => 'Foto de perfil actualizada correctamente'];
            }
        }
        
        return ['status' => 'error', 'message' => 'Error al guardar la imagen de perfil'];
    }

    return ['status' => 'error', 'message' => 'No se seleccionó ninguna imagen'];
}

// Función para cambio de correo
function handleEmailUpdate($usuarioModel, $bitacoraModel, $usuario) {
    $nuevo_correo = trim($_POST['new_email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validaciones
    if (empty($nuevo_correo) || empty($password)) {
        return ['status' => 'error', 'message' => 'Todos los campos son obligatorios'];
    }

    if (!filter_var($nuevo_correo, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'El formato del correo no es válido'];
    }

    // Verificar contraseña actual
    if (!password_verify($password, $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es incorrecta'];
    }

    // Verificar si el correo ya existe
    if ($usuarioModel->existeCorreo($nuevo_correo, $usuario['id_usuario'])) {
        return ['status' => 'error', 'message' => 'El correo electrónico ya está en uso'];
    }

    // Actualizar correo
    if ($usuarioModel->actualizarPerfil($_SESSION['id_usuario'], ['correo' => $nuevo_correo])) {
        // Actualizar sesión
        $_SESSION['correo'] = $nuevo_correo;
        $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_PERFIL, 'Cambió su correo electrónico', 'UPDATE', 'tbl_usuarios');
        return ['status' => 'success', 'message' => 'Correo electrónico actualizado correctamente'];
    }

    return ['status' => 'error', 'message' => 'Error al actualizar el correo electrónico'];
}

// Función para cambio de contraseña
function handlePasswordUpdate($usuarioModel, $bitacoraModel, $usuario) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validaciones
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        return ['status' => 'error', 'message' => 'Todos los campos son obligatorios'];
    }

    // Verificar contraseña actual
    if (!password_verify($current_password, $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La contraseña actual es incorrecta'];
    }

    // Validar que las contraseñas coincidan
    if ($new_password !== $confirm_password) {
        return ['status' => 'error', 'message' => 'Las contraseñas nuevas no coinciden'];
    }

    // Validar fortaleza de la nueva contraseña
    if (strlen($new_password) < 6) {
        return ['status' => 'error', 'message' => 'La nueva contraseña debe tener al menos 6 caracteres'];
    }

    // Verificar que no sea igual a la actual
    if (password_verify($new_password, $usuario['password'])) {
        return ['status' => 'error', 'message' => 'La nueva contraseña no puede ser igual a la actual'];
    }

    // Actualizar contraseña (el modelo realizará el hash)
    if ($usuarioModel->actualizarPerfil($_SESSION['id_usuario'], ['password' => $new_password])) {
        $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_PERFIL, 'Cambió su contraseña', 'UPDATE', 'tbl_usuarios');
        return ['status' => 'success', 'message' => 'Contraseña actualizada correctamente'];
    }

    return ['status' => 'error', 'message' => 'Error al actualizar la contraseña'];
}

// Cargar vista
$pagina = "perfil";
if (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}