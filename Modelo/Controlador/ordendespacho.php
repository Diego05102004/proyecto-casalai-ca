<?php
ob_start();
require __DIR__ . '/../../assets/public/fpdf/fpdf.php';
use Usuario\ProyectoCasalaiCa\Modelo\Clases\OrdenDespacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Config\BD;
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

            // En el switch case, reemplaza el caso 'descargar_pdf' con este código:
case 'descargar_pdf':
    $idOrden = $_POST['id'] ?? null;
    
    if (!$idOrden) {
        echo json_encode(['status' => 'error', 'message' => 'ID de orden no proporcionado']);
        exit;
    }

    $ordenModel = new OrdenDespacho();
    $ordenData = $ordenModel->obtenerDatosParaPDF($idOrden);
    
    if (empty($ordenData)) {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró la orden']);
        exit;
    }

    // Incluir la clase FPDF
    require_once __DIR__ . '/../../assets/public/fpdf/fpdf.php';

    // Crear instancia de PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    
    // Título
    $pdf->Cell(0,10,'ORDEN DE DESPACHO',0,1,'C');
    $pdf->Ln(10);
    
    // Datos de la orden
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(50,10,'Nro. Orden:',0,0);
    $pdf->Cell(0,10,$ordenData['id_orden_despachos'],0,1);
    
    $pdf->Cell(50,10,'Cliente:',0,0);
    $pdf->Cell(0,10,utf8_decode($ordenData['cliente']),0,1);
    
    $pdf->Cell(50,10,'Cedula/RIF:',0,0);
    $pdf->Cell(0,10,$ordenData['cedula'],0,1);
    
    $pdf->Cell(50,10,'Fecha:',0,0);
    $pdf->Cell(0,10,$ordenData['fecha_despacho'],0,1);
    $pdf->Ln(10);
    
    // Encabezado de la tabla
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(30,10,'Codigo',1,0,'C');
    $pdf->Cell(60,10,'Producto',1,0,'C');
    $pdf->Cell(30,10,'Marca',1,0,'C');
    $pdf->Cell(20,10,'Cant.',1,0,'C');
    $pdf->Cell(25,10,'Precio',1,0,'C');
    $pdf->Cell(25,10,'Total',1,1,'C');
    
    // Productos
    $pdf->SetFont('Arial','',10);
    foreach ($ordenData['productos'] as $producto) {
        $pdf->Cell(30,8,$producto['codigo'],1,0,'C');
        $pdf->Cell(60,8,utf8_decode($producto['producto']),1,0);
        $pdf->Cell(30,8,utf8_decode($producto['marca']),1,0,'C');
        $pdf->Cell(20,8,$producto['cantidad'],1,0,'C');
        $pdf->Cell(25,8,number_format($producto['precio'], 2, ',', '.'),1,0,'R');
        $pdf->Cell(25,8,number_format($producto['subtotal'], 2, ',', '.'),1,1,'R');
    }
    
    // Total
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(140,10,'TOTAL:',0,0,'R');
    $pdf->Cell(30,10,number_format($ordenData['total'], 2, ',', '.'),1,1,'R');
    
    // Generar el PDF
    $filename = 'Orden_Despacho_'.$ordenData['id_orden_despachos'].'.pdf';
    $pdf->Output('D', $filename);
    exit;


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
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    // Bitácora
                    $bitacora = new Bitacora();
                    $bitacora->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_ORDEN_DESPACHO,
                        'CAMBIAR ESTADO',
                        'El usuario cambió el estado de la orden de despacho con ID: ' . $id . ' a ' . $nuevo_estado,
                        'media'
                    );

                    // Notificación
                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'],
                        'orden_despacho',
                        'Estado de orden de despacho actualizado',
                        "Se ha cambiado el estado de la orden de despacho con ID ".$id." a '".$nuevo_estado."' por el usuario ".($_SESSION['name'] ?? ''),
                        'media',
                        MODULO_ORDEN_DESPACHO,
                        'actualizar',
                        $id
                    );
                }
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


    if (isset($_POST['obtenerDatosOrden'])) {
        error_log("Solicitud de datos de orden recibida. ID de orden: " . $_POST['obtenerDatosOrden']);
        $idOrden = $_POST['obtenerDatosOrden'];
        $ordenModel = new OrdenDespacho();
        $orden = $ordenModel->DescargarOrdenDespacho($idOrden);
        
        if (!empty($orden) && is_array($orden)) {
            $datosOrden = reset($orden);
            
            // Formatear los datos para la respuesta JSON
            $response = [
                'success' => true,
                'orden' => [
                    'id_orden_despachos' => $datosOrden['id_orden_despachos'],
                    'id_factura' => $datosOrden['id_factura'],
                    'cliente' => $datosOrden['cliente'],
                    'cedula' => $datosOrden['cedula'],
                    'fecha_despacho' => date('d/m/Y H:i:s', strtotime($datosOrden['fecha_despacho'])),
                    'productos' => isset($datosOrden['productos']) ? $datosOrden['productos'] : [],
                    'fecha_generacion' => date('d/m/Y H:i:s')
                ]
            ];
            
            // Enviar la respuesta como JSON
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } else {
            $response = [
                'success' => false,
                'message' => 'No se encontró la Orden de Despacho.'
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

$pagina = "ordendespacho";
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_ORDEN_DESPACHO,
            'ACCESAR',
            'El usuario accedió al módulo de Ordenes de Despacho',
            'media'
        );
    }
    $ordendespacho = getordendespacho();
    
    // Obtener facturas disponibles
    $ordenModel = new OrdenDespacho();
    $facturas = $ordenModel->obtenerFacturasDisponibles();

    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
ob_end_flush();?>