<?php
ob_start();

require_once 'modelo/modelo.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

define('MODULO_MODELOS', 5);

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('modelos'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('modelos'));
            echo json_encode($permisosActualizados);
            exit;
            
        case 'registrar':
            header('Content-Type: application/json; charset=utf-8');
            $modelo = new modelo();
            $modelo->setnombre_modelo($_POST['nombre_modelo']);
            $modelo->setid_marca($_POST['id_marca']);

            if ($modelo->existeNombreModelo($_POST['nombre_modelo'])) {
                
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El modelo ya existe'
                ]);
                exit;
            }

            if ($modelo->registrarModelo()) {
                $modeloRegistrado = $modelo->obtenerUltimoModelo();
                
                // Registrar éxito en bitácora
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora(); 
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        '5',
                        'INCLUIR',
                        'El usuario incluyó un nuevo modelo: ' . $_POST['nombre_modelo'],
                        'media'
                    );
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'modelo registrado correctamente',
                    'modelo' => $modeloRegistrado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar el modelo'
                ]);
            }
            exit;

        case 'obtener_modelo':
            $id_modelo = $_POST['id_modelo'];
            if ($id_modelo !== null) {
                $modelo = new modelo();
                $modelo = $modelo->obtenerModeloPorId($id_modelo);
                if ($modelo !== null) {
                    echo json_encode($modelo);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'modelo no encontrado']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de modelo no proporcionado']);
            }
            exit;

        case 'modificar':
            $id_modelo = $_POST['id_modelo'];
            $modelo = new modelo();
            $modelo->setIdModelo($id_modelo);
            $modelo->setnombre_modelo($_POST['nombre_modelo']);
            $modelo->setid_marca($_POST['id_marca']);
            
            if ($modelo->existeNombreModelo($_POST['nombre_modelo'], $id_modelo)) {
                
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El modelo ya existe'
                ]);
                exit;
            }

            if ($modelo->modificarModelo($id_modelo)) {
                $modeloActualizado = $modelo->obtenerModeloConMarcaPorId($id_modelo);
                
                // Registrar modificación exitosa
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        '5',
                        'MODIFICAR',
                        'El usuario modifico el modelo ' . $modeloActualizado['nombre_modelo'] . '',
                        'media'
                    );
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Modelo modificado correctamente',
                    'modelo' => $modeloActualizado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al modificar el modelo'
                ]);
            }
            exit;

        case 'eliminar':
            $id_modelo = $_POST['id_modelo'];
            $modelo = new modelo();
            $resultado = $modelo->eliminarModelo($id_modelo);

            if (is_array($resultado) && $resultado['status'] === 'error') {
                // Registrar en bitácora el intento fallido
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_MODELOS,
                        'ELIMINAR_FALLIDO',
                        'Intento de eliminación fallido de modelo (ID: ' . $id_modelo . '): ' . $resultado['mensaje'],
                        'media'
                    );
                }
                
                echo json_encode([
                    'status' => 'error', 
                    'message' => $resultado['mensaje'],
                    'productos' => $resultado['productos'] ?? [],
                    'total_productos' => $resultado['total_productos'] ?? 0
                ]);
            } else if ($resultado['status'] === 'success') {
                // Registrar eliminación exitosa
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_MODELOS,
                        'ELIMINAR',
                        'El usuario eliminó el modelo ID: ' . $id_modelo,
                        'media'
                    );
                }
                
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el modelo']);
            }
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
    exit;
}

function getModelos() {
    $modelo = new modelo();
    return $modelo->getModelos();
}

function getmarcas() {
    $marcas = new modelo();
    return $marcas->getmarcas();
}

$pagina = "modelo";
if (is_file("vista/" . $pagina . ".php")) {
    $modelos = getModelos();
    $marcas = getmarcas();
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        '5',
        'ACCESAR',
        'El usuario accedió al modulo de Modelos',
        'media'
    );
}
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
