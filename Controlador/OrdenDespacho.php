<?php
ob_start();
require __DIR__ . '/../public/fpdf/fpdf.php';
require_once __DIR__ . '/../modelo/ordendespacho.php';
require_once __DIR__ . '/../modelo/permiso.php';
require_once __DIR__ . '/../modelo/bitacora.php';
require_once __DIR__ . '/../modelo/notificacion.php';
define('MODULO_ORDEN_DESPACHO', 14);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

$id_rol = $_SESSION['id_rol'];

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('Ordenes de despacho'));


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtiene la acción enviada en la solicitud POST
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, 'Ordenes de despacho');
            echo json_encode($permisosActualizados);
            exit;

        case 'obtenerOrden':
            $id = $_POST['id_despachos'] ?? null; // Usa 'id' para que coincida con el JS
        
            if ($id !== null) {
                $ordenModel = new OrdenDespacho();
                $orden = $ordenModel->obtenerOrdenPorId($id);
        
                if ($orden !== null) {
                    echo json_encode([
                        'status' => 'success',
                        'datos' => $orden
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Orden de despacho no encontrada'
                    ]);
                }
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ID de la orden no proporcionado'
                ]);
            }
            break;
            
        // Cambiar estatus
        case 'cambiar_estatus':
            $id = $_POST['id_despachos'];
            $nuevoEstatus = $_POST['nuevo_estatus'];
            
            // Validación básica
            if (!in_array($nuevoEstatus, ['habilitado', 'inhabilitado'])) {
                echo json_encode(['status' => 'error', 'message' => 'Estatus no válido']);
                exit;
            }
            
            $ordendespacho = new OrdenDespacho();
            $ordendespacho->setId($id);
            
            if ($ordendespacho->cambiarEstatus($nuevoEstatus)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_ORDEN_DESPACHO,
                        'CAMBIAR ESTATUS',
                        'El usuario cambió el estatus de la orden de despacho ID ' . $id . ' a ' . $nuevoEstatus,
                        'media'
                    );
                }
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus']);
            }
            break;
        
        case 'cambiar_estado_orden':
            $id = $_POST['id'];
            $estado_actual = $_POST['estado_actual'];
            $nuevo_estado = ($estado_actual === 'Por Entregar') ? 'Entregada' : 'Por Entregar';
            $ordenModel = new OrdenDespacho();
            if ($ordenModel->cambiarEstadoOrden($id, $nuevo_estado)) {
                echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo cambiar el estado']);
            }
            break;
        
        case 'anularOrden':
            $ordenModel = new OrdenDespacho();
            $idOrden = $_POST['id_orden_despachos'];
            $resultado = $ordenModel->anularOrdenDespacho($idOrden);

            if ($resultado['status'] === 'success') {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    // Bitácora
                    $bitacora = new Bitacora();
                    $bitacora->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_ORDEN_DESPACHO,
                        'ANULAR',
                        'El usuario anuló la orden de despacho con ID: ' . $idOrden,
                        'media'
                    );

                    // Notificación
                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'],
                        'orden_despacho',
                        'Orden de despacho anulada',
                        "Se ha anulado la orden de despacho con ID ".$idOrden." por parte del usuario ".($_SESSION['name'] ?? ''),
                        'media',
                        MODULO_ORDEN_DESPACHO,
                        'eliminar',
                        $idOrden
                    );
                }
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resultado);
            break;
    }
    exit;
}

function getordendespacho() {
    $ordendespacho = new OrdenDespacho();
    return $ordendespacho->getordendespacho();
}


    if (isset($_POST['DescargarOrdenDespacho'])) {
        $idOrden = $_POST['DescargarOrdenDespacho'];
        $ordenModel = new OrdenDespacho();
        $orden = $ordenModel->DescargarOrdenDespacho($idOrden);
    
        if ($orden) {
            ob_start(); // Iniciar buffer de salida
   
        } else {
            echo "<script>alert('No se encontró la Orden de Despacho.');</script>";
        }
    }

$pagina = "ordendespacho";
if (is_file("vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_ORDEN_DESPACHO,
            'ACCESAR',
            'El usuario accedió al modulo de Ordenes de Despacho',
            'media'
        );
    }
    $ordendespacho = getordendespacho();
    
    // Obtener facturas disponibles
    $ordenModel = new OrdenDespacho();
    $facturas = $ordenModel->obtenerFacturasDisponibles();

    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
ob_end_flush();?>