<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'modelo/usuario.php';
require_once 'modelo/rol.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';
define('MODULO_USUARIO', 1); // Define el ID del módulo de cuentas bancarias
$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

$permisosObj = new Permisos();
$bitacoraModel = new Bitacora();
$permisosUsuario = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('usuario'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_usuario_accion = $_SESSION['id_usuario'] ?? null; // Usuario que realiza la acción
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('usuario'));
            echo json_encode($permisosActualizados);
            exit;

        case 'registrar':
            $usuario = new Usuarios();
            $usuario->setUsername($_POST['nombre_usuario']);
            $usuario->setClave($_POST['clave_usuario']);
            $usuario->setNombre($_POST['nombre']);
            $usuario->setApellido($_POST['apellido_usuario']);
            $usuario->setCorreo($_POST['correo_usuario']);
            $usuario->setTelefono($_POST['telefono_usuario']);
            $usuario->setRango($_POST['rango']);
            $usuario->setCedula($_POST['cedula']);

            if ($usuario->existeUsuario($_POST['nombre_usuario'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El nombre de usuario ya existe'
                ]);
                exit;
            }
            
            if ($usuario->existeCedula($_POST['cedula'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'La cedula ingresada pertenece a un usuario que ya existe'
                ]);
                exit;
            }
            
            if ($usuario->existeCorreo($_POST['correo_usuario'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El correo ingresado se encuentra en uso por un usuario que ya existe'
                ]);
                exit;
            }

            if ($usuario->ingresarUsuario()) {
                $usuarioRegistrado = $usuario->obtenerUltimoUsuario();
                
                // Registrar en bitácora
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    '1',
                    'INCLUIR',
                    'El usuario incluyó el usuario: ' . implode(', ', array_map(
                        function($value, $key) { return "$key: $value"; },
                        $usuarioRegistrado,
                        array_keys($usuarioRegistrado)
                    )),
                    'alta'
                );
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Usuario registrado correctamente',
                    'usuario' => $usuarioRegistrado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar el usuario'
                ]);
            }
            exit;

        case 'filtrar_estatus':
            $estatus = $_POST['estatus'] ?? 'habilitado';
            $usuario = new Usuarios();
            $usuarios = $usuario->getusuarios($estatus);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'usuarios' => $usuarios]);
            exit;

        case 'obtener_usuario':
            $id_usuario = $_POST['id_usuario'];
            if ($id_usuario !== null) {
                $usuario = new Usuarios();
                $usuarioData = $usuario->obtenerUsuarioPorId($id_usuario);
                
                if ($usuarioData !== null) {
                    echo json_encode($usuarioData);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID del Usuario no proporcionado']);
            }
            exit;

        case 'modificar':
            $id_usuario = $_POST['id_usuario'];
            $usuario = new Usuarios();
            $usuario->setId($id_usuario);
            $usuario->setUsername($_POST['nombre_usuario']);
            $usuario->setNombre($_POST['nombre']);
            $usuario->setApellido($_POST['apellido_usuario']);
            $usuario->setCorreo($_POST['correo_usuario']);
            $usuario->setTelefono($_POST['telefono_usuario']);
            $usuario->setRango($_POST['rango']);
            $usuario->setCedula($_POST['cedula']);
            
            if ($usuario->existeUsuario($_POST['nombre_usuario'], $id_usuario)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El nombre de usuario ya existe'
                ]);
                exit;
            }
            if ($usuario->existeCedula($_POST['cedula'], $id_usuario)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'La cedula ingresada pertenece a un usuario que ya existe'
                ]);
                exit;
            }
            if ($usuario->existeCorreo($_POST['correo_usuario'], $id_usuario)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El correo ingresado se encuentra en uso por un usuario que ya existe'
                ]);
                exit;
            }
                $usuarioViejo = $usuario->obtenerUsuarioPorId($id_usuario);
            if ($usuario->modificarUsuario($id_usuario)) {
                $usuarioActualizado = $usuario->obtenerUsuarioPorId($id_usuario);
                
                // Registrar en bitácora
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    '1',
                    'MODIFICAR',
                    'Actualización de usuario: ' .$usuarioViejo.' a '.$usuarioActualizado.'',
                    'media'
                );
                
                echo json_encode([
                    'status' => 'success',
                    'usuario' => $usuarioActualizado
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar el Usuario']);
            }
            exit;

        case 'eliminar':
            $id_usuario = $_POST['id_usuario'];
            $usuarioModel = new Usuarios();
            
            // Obtener datos del usuario antes de eliminarlo
            $usuarioAEliminar = $usuarioModel->obtenerUsuarioPorId($id_usuario);
            
            if ($usuarioModel->eliminarUsuario($id_usuario)) {
                // Registrar en bitácora
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    '1',
                    'ELIMINAR',
                    'Eliminación del usuario: ' . $usuarioAEliminar . '',
                    'media'
                );
                
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el usuario']);
            }
            exit;

        case 'cambiar_estatus':
            $id_usuario = $_POST['id_usuario'];
            $nuevoEstatus = $_POST['nuevo_estatus'];
            
            if (!in_array($nuevoEstatus, ['habilitado', 'inhabilitado'])) {
                echo json_encode(['status' => 'error', 'message' => 'Estatus no válido']);
                exit;
            }
            
            $usuario = new Usuarios();
            $usuario->setId($id_usuario);
            
            if ($usuario->cambiarEstatus($nuevoEstatus)) {
                // Registrar en bitácora
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    '1',
                    'MODIFICAR',
                    'Cambio de estatus de usuario: ' . $id_usuario . ' a ' . $nuevoEstatus,
                    'media'
                );
                
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus']);
            }
            exit;
        
        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida: '. $accion.'']);
            exit;
    }
}

function getusuarios() {
    $usuario = new Usuarios();
    return $usuario->getusuarios();
}

$usuarioModel = new Usuarios();
$rolModel = new Rol();
$reporteRoles = $usuarioModel->obtenerReporteRoles();
$selecionarRol = $rolModel->consultarRoles();
$totalRoles = array_sum(array_column($reporteRoles, 'cantidad'));
foreach ($reporteRoles as &$rol) {
    $rol['porcentaje'] = $totalRoles > 0 ? round(($rol['cantidad'] / $totalRoles) * 100, 2) : 0;
}
unset($rol);

$pagina = "usuario";
if (is_file("vista/" . $pagina . ".php")) {
         if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
    $_SESSION['id_usuario'],
    '1',
    'ACCESAR',
    'El usuario accedió al al modulo de Usuarios',
    'media'
);}
    $usuarios = getusuarios();
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
