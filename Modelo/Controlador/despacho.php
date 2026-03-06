<?php  
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Despacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Config\BD;
define('MODULO_DESPACHO', "Despacho");

$id_rol = $_SESSION['id_rol'];

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('despacho'));


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $k = new Despacho();

    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'listado':
            $respuesta = $k->listadoproductos();
            echo json_encode($respuesta);
        break;

        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('despacho'));
            echo json_encode($permisosActualizados);
        break;

        case 'obtener_detalles':
            // Validar datos de entrada
            $datosValidacion = [
                'id_despacho' => $_POST['id_despachos'] ?? null
            ];
            
            $errores = $k->validarDetallarDespacho($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                break;
            }
            
            $idDespacho = isset($_POST['id_despachos']) ? (int)$_POST['id_despachos'] : 0;
            if ($idDespacho > 0) {
                $respuesta = $k->obtenerDetallesPorDespacho($idDespacho);
                echo json_encode($respuesta);
            } else {
                echo json_encode(['error' => true, 'mensaje' => 'ID de despacho no válido']);
            }
        break;

        case 'cambiar_estado_despacho':
            // Validar datos de entrada
            $datosValidacion = [
                'id_despacho' => $_POST['id'] ?? null,
                'estado_actual' => $_POST['estado_actual'] ?? null
            ];
            
            $errores = $k->validarCambiarEstado($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                break;
            }
            
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $estado_actual = isset($_POST['estado_actual']) ? trim($_POST['estado_actual']) : '';

            if ($id <= 0 || !in_array($estado_actual, ['Por Despachar', 'Despachado'], true)) {
                echo json_encode(['status' => 'error', 'message' => 'Datos inválidos para cambiar el estado del despacho']);
                break;
            }

            $nuevo_estado = ($estado_actual === 'Por Despachar') ? 'Despachado' : 'Por Despachar';
            $despachoModel = new Despacho();
            if ($despachoModel->cambiarEstadoDespacho($id, $nuevo_estado)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacora = new Bitacora();
                    $bitacora->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_DESPACHO,
                        'CAMBIAR ESTADO',
                        'El usuario cambió el estado del despacho con ID: ' . $id . ' a ' . $nuevo_estado,
                        'media'
                    );

                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'],
                        'despacho',
                        'Estado de despacho actualizado',
                        "Se ha cambiado el estado del despacho con ID ".$id." a '".$nuevo_estado."' por el usuario ".($_SESSION['name'] ?? ''),
                        'media',
                        MODULO_DESPACHO,
                        'actualizar',
                        $id
                    );
                }
                echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo cambiar el estado']);
            }
            break;

        case 'anular':
            // Validar datos de entrada
            $datosValidacion = [
                'id_despacho' => $_POST['id_despachos'] ?? null
            ];
            
            $errores = $k->validarAnularDespacho($datosValidacion);
            if (!empty($errores)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                break;
            }
            
            $idDespacho = isset($_POST['id_despachos']) ? (int)$_POST['id_despachos'] : 0;
            if ($idDespacho <= 0) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ID de despacho no válido'
                ]);
                break;
            }

            $resultado = $k->anularDespacho($idDespacho);

            if ($resultado['status'] === 'success') {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_DESPACHO,
                        'ANULAR',
                        'El usuario anuló el despacho con ID: ' . $idDespacho,
                        'media'
                    );

                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'],
                        'despacho',
                        'Despacho anulado',
                        "Se ha anulado el despacho con ID ".$idDespacho." por parte del usuario ".($_SESSION['name'] ?? ''),
                        'media',
                        MODULO_DESPACHO,
                        'eliminar',
                        $idDespacho
                    );
                }
            }

            header('Content-Type: application/json');
            echo json_encode($resultado);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida '.$accion.'']);
    }
    exit;
}

function getdespacho() {
    $despacho = new Despacho();
    return $despacho->getdespacho();
}

// vista inicial

// Reportes de despacho (restaurados)
$despacho = new Despacho();
$despachoEstado = $despacho->getDespachosEstado();
$despachoMes = $despacho->getProductosDespachadosPorMes();

$despachos = getdespacho();

$k = new Despacho();
$proveedores = $k->obtenercliente();
$productos = $k->consultarproductos();

// Total de despachos
$totalDespachos = count($despachos);

$pagina = "despacho";
if (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        '3',
        'ACCESAR',
        'El usuario accedió al módulo de Despachos',
        'media'
    );}
    $despachos = getdespacho();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "pagina en construccion";
}
?>