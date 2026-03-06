<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Usuarios;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Rol;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
define('MODULO_USUARIO', "Usuario");

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('usuario'));

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
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('usuario'));
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
            
            // Preparar datos para validación
            $datos_validacion = [
                'username' => $_POST['nombre_usuario'],
                'clave' => $_POST['clave_usuario'],
                'nombre' => $_POST['nombre'],
                'apellido' => $_POST['apellido_usuario'],
                'correo' => $_POST['correo_usuario'],
                'telefono' => $_POST['telefono_usuario'],
                'cedula' => $_POST['cedula'],
                'id_rol' => $_POST['rango']
            ];
            
            // Validar datos del usuario usando las nuevas validaciones centralizadas
            $errores = $usuario->validarRegistrarUsuario($datos_validacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Errores de validación',
                    'errors' => $errores
                ]);
                exit;
            }

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
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_USUARIO,
                        'INCLUIR',
                        'El usuario incluyó un nuevo usuario: ' . $_POST['nombre_usuario'],
                        'media'
                    );
                }
                
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
            
            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $datos_validacion = ['estatus' => $estatus];
            $usuario = new Usuarios();
            $errores = $usuario->validarReporte($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para filtrar usuarios',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $usuarios = $usuario->getusuarios($estatus);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'usuarios' => $usuarios]);
            exit;

        case 'obtener_usuario':
            $id_usuario = $_POST['id_usuario'] ?? null;
            
            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $datos_validacion = ['id_usuario' => $id_usuario];
            $usuario = new Usuarios();
            $errores = $usuario->validarConsultarUsuario($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para consultar el usuario',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $usuarioData = $usuario->obtenerUsuarioPorId($id_usuario);
            
            if ($usuarioData !== null) {
                echo json_encode($usuarioData);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
            }
            exit;

        case 'modificar':
            $id_usuario = $_POST['id_usuario'];
            
            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $datos_validacion = [
                'id_usuario' => $id_usuario,
                'username' => $_POST['nombre_usuario'],
                'nombre' => $_POST['nombre'],
                'apellido' => $_POST['apellido_usuario'],
                'correo' => $_POST['correo_usuario'],
                'telefono' => $_POST['telefono_usuario'],
                'cedula' => $_POST['cedula'],
                'id_rol' => $_POST['rango'],
                'clave' => $_POST['clave_usuario'] ?? ''
            ];
            
            $usuario = new Usuarios();
            $errores = $usuario->validarModificarUsuario($datos_validacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Errores de validación',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $usuario->setId($id_usuario);
            $usuario->setUsername($_POST['nombre_usuario']);
            $usuario->setNombre($_POST['nombre']);
            $usuario->setApellido($_POST['apellido_usuario']);
            $usuario->setCorreo($_POST['correo_usuario']);
            $usuario->setTelefono($_POST['telefono_usuario']);
            $usuario->setRango($_POST['rango']);
            $usuario->setCedula($_POST['cedula']);
            $usuario->setClave($_POST['clave_usuario'] ?? '');
            
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
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $detalle = sprintf(
                        'Actualización de usuario (ID: %d). Usuario: %s %s (antes) -> %s %s (después)',
                        $id_usuario,
                        $usuarioViejo['nombres'] ?? 'N/A',
                        $usuarioViejo['apellidos'] ?? 'N/A',
                        $usuarioActualizado['nombres'] ?? 'N/A',
                        $usuarioActualizado['apellidos'] ?? 'N/A'
                    );
                    
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_USUARIO,
                        'MODIFICAR',
                        $detalle,
                        'media'
                    );
                }
                
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
            
            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $datos_validacion = ['id_usuario' => $id_usuario];
            $usuarioModel = new Usuarios();
            $errores = $usuarioModel->validarEliminarUsuario($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para eliminar el usuario',
                    'errors' => $errores
                ]);
                exit;
            }
            
            // Obtener datos del usuario antes de eliminarlo
            $usuarioAEliminar = $usuarioModel->obtenerUsuarioPorId($id_usuario);
            
            if ($usuarioModel->eliminarUsuario($id_usuario)) {
                // Registrar en bitácora
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_USUARIO,
                        'ELIMINAR',
                        'Eliminación del usuario: ' . $usuarioAEliminar . '',
                        'media'
                    );
                }
                
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el usuario']);
            }
            exit;

        case 'cambiar_estatus':
            $id_usuario = $_POST['id_usuario'];
            $nuevoEstatus = $_POST['nuevo_estatus'];
            
            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $datos_validacion = [
                'id_usuario' => $id_usuario,
                'estatus' => $nuevoEstatus
            ];
            
            $usuario = new Usuarios();
            $errores = $usuario->validarCambiarEstatus($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para cambiar estatus',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $usuario->setId($id_usuario);
            
            if ($usuario->cambiarEstatus($nuevoEstatus)) {
                // Registrar en bitácora
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_USUARIO,
                        'MODIFICAR',
                        'Cambio de estatus de usuario: ' . $id_usuario . ' a ' . $nuevoEstatus,
                        'media'
                    );
                }
                
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
    return $usuario->getusuarios('todos');
}

$usuarioModel = new Usuarios();
$rolModel = new Rol();
$reporteRoles = $usuarioModel->obtenerReporteRoles();
$selecionarRol = $rolModel->consultarRoles();

// --- Datos para reportes (igual que en reporteUsuario.php) ---
$usuariosHabilitados = $usuarioModel->getusuarios('habilitado');
$usuariosDeshabilitados = $usuarioModel->getusuarios('inhabilitado');
$usuariosTodos = array_merge($usuariosHabilitados ?? [], $usuariosDeshabilitados ?? []);

$totalRoles = array_sum(array_column($reporteRoles, 'cantidad'));
foreach ($reporteRoles as &$rol) {
    $rol['porcentaje'] = $totalRoles > 0 ? round(($rol['cantidad'] / $totalRoles) * 100, 2) : 0;
}
unset($rol);
// -------------------------------------------------------------

$usuarios = getusuarios();
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_USUARIO,
            'ACCESAR',
            'El usuario accedió al módulo de Usuarios',
            'media'
        );
    }
    $usuarios = getusuarios();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
