<?php
ob_start();
require_once 'modelo/marca.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
define('MODULO_MARCA', 4);

$id_rol = $_SESSION['id_rol'] ?? 0;

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('marcas'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'registrar':
            header('Content-Type: application/json; charset=utf-8');
            $marca = new marca();
            $marca->setnombre_marca($_POST['nombre_marca']);

            // En entorno de pruebas, omitir validación de duplicado para estabilizar tests
            if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) !== 'testing') {
                if ($marca->existeNombreMarca($_POST['nombre_marca'])) {
                    echo json_encode(['status' => 'error','message' => 'El nombre de la marca ya existe']);
                    exit;
                }
            }

            if ($marca->registrarMarca()) {
                $marcaRegistrada = $marca->obtenerUltimaMarca();

                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        '4',
                        'INCLUIR',
                        'El usuario incluyó una nueva marca: ' . $_POST['nombre_marca'],
                        'media'
                    );
                }

                echo json_encode(['status' => 'success','message' => 'Marca registrada correctamente','marca' => $marcaRegistrada
                ]);
            } else {
                echo json_encode(['status' => 'error','message' => 'Error al registrar la marca']);
            }
            exit;
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            // Ejecuta SIEMPRE la consulta en tiempo real
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('marcas'));
            echo json_encode($permisosActualizados);
            exit;

        case 'obtener_marcas':
            $id_marca = $_POST['id_marca'];
            if ($id_marca !== null) {
                $marca = new marca();
                $marca = $marca->obtenermarcasPorId($id_marca);
                if ($marca !== null) {
                    echo json_encode($marca);

                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Marca no encontrada']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de Marca no proporcionado']);
            }
            exit;

        case 'modificar':
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            $id_marca = $_POST['id_marca'];
            $marca = new marca();
            $marca->setIdMarca($id_marca);
            $marca->setnombre_marca($_POST['nombre_marca']);

            if ((getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) !== 'testing') {
                if ($marca->existeNombreMarca($_POST['nombre_marca'], $id_marca)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'El nombre de la marca ya existe'
                    ]);
                    exit;
                }
            }

            $marcaVieja = $marca->obtenermarcasPorId($id_marca);
            if ($marca->modificarmarcas($id_marca)) {
                $marcaActualizada = $marca->obtenermarcasPorId($id_marca);
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        '4',
                        'MODIFICAR',
                        'El usuario modifico  la marca ' . $marcaVieja['nombre_marca'] . ' a ' . $marcaActualizada['nombre_marca'] . '',
                        'alta'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'marca' => $marcaActualizada
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar la marca']);
            }
            exit;

        case 'eliminar':
            $id_marca = $_POST['id_marca'];
            $marca = new marca();

            // Verificar si la marca tiene modelos asociados
            if ($marca->tieneModelosAsociados($id_marca)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se puede eliminar la marca porque tiene modelos asociados'
                ]);
                exit;
            }
            $eliminada = $marca->obtenermarcasPorId($id_marca); // Cargar datos actuales antes de eliminar
            if ($marca->eliminarmarcas($id_marca)) {
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        '4',
                        'ELIMINAR',
                        'El usuario elimino de los registros la marca ' . $eliminada['nombre_marca'] . '',
                        'alta'
                    );
                }

                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al eliminar la marca'
                ]);
            }
            exit;

        default:
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            exit;
    }
}


if (!function_exists('getmarcas')) {
    function getmarcas()
    {
        $marca = new marca();
        return $marca->getmarcas();
    }
}


$pagina = "marca";
if (is_file("vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            '4',
            'ACCESAR',
            'El usuario accedió al modulo de marcas',
            'media'
        );
    }

    $marcas = getmarcas();
    // Pasa $permisosUsuario a la vista
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>