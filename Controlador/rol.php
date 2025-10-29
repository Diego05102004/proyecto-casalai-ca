<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'modelo/rol.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';
define('MODULO_ROLES', 18);

// Inicializaciones de clases
$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$id_rol = $_SESSION['id_rol'] ?? 0;

$permisosObj = new Permisos();
$bitacoraModel = new Bitacora();
$permisosUsuario = $permisosObj->getPermisosUsuarioModulo($id_rol, 'Roles');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
                case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisosObj->getPermisosUsuarioModulo($id_rol, 'Roles');
            echo json_encode($permisosActualizados);
            exit;
        case 'registrar':
            $rol = new Rol();
            $rol->setNombreRol($_POST['nombre_rol']);

            if ($rol->existeNombreRol($_POST['nombre_rol'])) {

                

                echo json_encode([
                    'status' => 'error',
                    'message' => 'El nombre del rol ya existe'
                ]);
                exit;
            }

            if ($rol->registrarRol()) {
                $rolRegistrado = $rol->obtenerUltimoRol();
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_ROLES,
                        'REGISTRAR',
                        'El usuario registró un nuevo rol: ' . $_POST['nombre_rol'],
                        'media'
                    );
                }
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Rol registrado correctamente',
                    'rol' => $rolRegistrado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar el rol'
                ]);
            }
            exit;
        
        case 'consultar_roles':
            $rol = new Rol();
            $roles_obt = $rol->consultarRoles();

            echo json_encode($roles_obt);
            exit;
        
        case 'obtener_rol':
            $id_rol = $_POST['id_rol'];

            if ($id_rol !== null) {
                $rol = new Rol();
                $rol_obt = $rol->obtenerRolPorId($id_rol);

                if ($rol_obt !== null) {
                    echo json_encode($rol_obt);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Rol no encontrado']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de rol no proporcionado']);
            }
            exit;

        case 'modificar':
            $id_rol  = $_POST['id_rol'];
            $rol = new Rol();
            $rol->setIdRol($id_rol);
            $rol->setNombreRol($_POST['nombre_rol']);
            
            if ($rol->existeNombreRol($_POST['nombre_rol'], $id_rol)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El nombre del rol ya existe'
                ]);
                exit;
            }
                 $rolViejo = $rol->obtenerRolPorId($id_rol);
            if ($rol->modificarRol($id_rol)) {
                $rolActualizado = $rol->obtenerRolPorId($id_rol);
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_ROLES,
                        'MODIFICAR',
                        'El usuario modificó el rol: ' . $_POST['nombre_rol']. ' (Antes: ' . ($rolViejo ? $rolViejo['nombre_rol'] : 'Desconocido') . ')',
                        'media'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'rol' => $rolActualizado
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar el rol']);
            }
            exit;

        case 'eliminar':
            $id_rol = $_POST['id_rol'];
            $rol = new Rol();
            if ($rol->tieneUsuariosAsignados($id_rol)) {
                echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar el rol porque tiene usuarios asignados']);
                exit;
            } else {
                // Obtener datos antes de eliminar para la bitácora
                $rolEliminado = $rol->obtenerRolPorId($id_rol);
                if ($rol->eliminarRol($id_rol)) {
                    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_ROLES,
                            'ELIMINAR',
                            'El usuario eliminó el rol: ' . ($rolEliminado ? $rolEliminado['nombre_rol'] : 'ID ' . $id_rol),
                            'media'
                        );
                    }
                echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el rol']);
                }
            }
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        exit;
    }
}

function consultarRoles() {
    $rol = new Rol();
    return $rol->consultarRoles();
}

$pagina = "rol";
if (is_file("vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_ROLES,
            'ACCESAR',
            'El usuario accedió al modulo de Roles',
            'media'
        );
    }
    $roles = consultarRoles();
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
