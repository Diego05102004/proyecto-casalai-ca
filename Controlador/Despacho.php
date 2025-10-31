<?php  
// Requires organizados al inicio
require_once 'modelo/Despacho.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';
require_once 'modelo/notificacion.php';

define('MODULO_DESPACHO', 3);

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

// Permisos compatibles con la vista
$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('despacho'));

// Reportes de despacho (restaurados)
$reporteDespacho = new Despacho();
$despachoEstado = $reporteDespacho->getDespachosEstado();
$despachoMes = $reporteDespacho->getProductosDespachadosPorMes();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Instanciar solo cuando se va a usar
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
            $idDespacho = $_POST['id_despachos'] ?? null;
            if ($idDespacho) {
                $respuesta = $k->obtenerDetallesPorDespacho($idDespacho);
                echo json_encode($respuesta);
            } else {
                echo json_encode(['error' => true, 'mensaje' => 'ID de recepción no recibido']);
            }
        break;

        case 'cambiar_estado_despacho':
            $id = $_POST['id'];
            $estado_actual = $_POST['estado_actual'];
            $nuevo_estado = ($estado_actual === 'Por Despachar') ? 'Despachado' : 'Por Despachar';
            $despachoModel = new Despacho();
            if ($despachoModel->cambiarEstadoDespacho($id, $nuevo_estado)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    // Bitácora
                    $bitacora = new Bitacora();
                    $bitacora->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_DESPACHO,
                        'CAMBIAR ESTADO',
                        'El usuario cambió el estado del despacho con ID: ' . $id . ' a ' . $nuevo_estado,
                        'media'
                    );

                    // Notificación
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
            $idDespacho = $_POST['id_despachos'];
            $resultado = $k->anularDespacho($idDespacho);

            // Bitácora y Notificación (similar a Recepción)
            if ($resultado['status'] === 'success') {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    // Bitácora
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_DESPACHO,
                        'ANULAR',
                        'El usuario anuló el despacho con ID: ' . $idDespacho,
                        'media'
                    );

                    // Notificación
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
$despachos = getdespacho();

$k = new Despacho();
$proveedores = $k->obtenercliente();
$productos = $k->consultarproductos();

// Total de despachos
$totalDespachos = count($despachos);

$pagina = "despacho";
if (is_file("vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        '3',
        'ACCESAR',
        'El usuario accedió al modulo de Despachos',
        'media'
    );}
    $despachos = getdespacho();
    require_once("vista/" . $pagina . ".php");
} else {
    echo "pagina en construccion";
}
?>