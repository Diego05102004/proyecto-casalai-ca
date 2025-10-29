<?php
ob_start();
require_once 'modelo/pasareladepago.php';
require_once 'modelo/cuenta.php';
require_once 'modelo/factura.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';
require_once 'modelo/DolarService.php';
require_once 'modelo/notificacion.php';
require_once 'modelo/ordenDespacho.php';
define('MODULO_PASARELA_PAGOS', 16); // Define el ID
$bitacoraModel = new Bitacora();
$id_rol = $_SESSION['id_rol'];


$permisosObj = new Permisos();
$permisosUsuario = $permisosObj->getPermisosPorRolModulo();

$pasarela = new PasareladePago();
$cuentaModel = new Cuentabanco();
$listadocuentas = $cuentaModel->consultarCuentabanco();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    switch ($accion) {
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisosObj->getPermisosUsuarioModulo($id_rol, 'Pasarela de pagos');
            echo json_encode($permisosActualizados);
            exit;
            
        case 'ingresar':
            // Verificar que se hayan enviado pagos
            if (!isset($_POST['pagos']) || empty($_POST['pagos'])) {
                echo json_encode(['status' => 'error', 'message' => 'No se han enviado datos de pagos']);
                exit;
            }
            
            $id_factura = $_POST['id_factura'];
            $pagos = $_POST['pagos'];
            $resultados = [];
            $errores = [];
            
            // Procesar cada pago individualmente
            foreach ($pagos as $index => $pagoData) {
                try {
                    $pasarela = new PasareladePago();
                    
                    // Asignar datos del pago
                    $pasarela->setCuenta($pagoData['cuenta']);
                    $pasarela->setReferencia($pagoData['referencia']);
                    $pasarela->setFecha(date('Y-m-d')); // Fecha actual
                    $pasarela->setTipo($pagoData['tipo']);
                    $pasarela->setFactura($id_factura);
                    $pasarela->setMonto($pagoData['monto']);
                    $pasarela->setObservaciones('');
                    
                    // Validar referencia única
                    if (!$pasarela->validarCodigoReferencia()) {
                        $errores[] = "La referencia {$pagoData['referencia']} ya existe";
                        continue;
                    }
                    
                    // Procesar comprobante de imagen si existe
                    $comprobanteNombre = null;
                    if (isset($_FILES['pagos']['name'][$index]['comprobante']) && 
                        $_FILES['pagos']['name'][$index]['comprobante']) {
                        
                        $comprobante = $_FILES['pagos']['tmp_name'][$index]['comprobante'];
                        $comprobanteNombre = 'comprobante_' . time() . '_' . $index . '.jpg';
                        $destino = 'comprobantes/' . $comprobanteNombre;
                        
                        // Crear directorio si no existe
                        if (!is_dir('comprobantes')) {
                            mkdir('comprobantes', 0755, true);
                        }
                        
                        // Mover archivo
                        if (!move_uploaded_file($comprobante, $destino)) {
                            $errores[] = "Error al subir el comprobante para la referencia {$pagoData['referencia']}";
                            continue;
                        }
                        
                        $pasarela->setComprobante($comprobanteNombre);
                    }
                    
                    // Intentar ingresar el pago
                    if ($pasarela->pasarelaTransaccion('Ingresar')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_PASARELA_PAGOS,
                            'INGRESAR',
                            'El usuario registró la referencia bancaria: ' . $pagoData['referencia'],
                            'media');
                         $resultados[] = [
                            'status' => 'success', 
                            'referencia' => $pagoData['referencia'],
                            'comprobante' => $comprobanteNombre
                        ];
                    } else {
                        $errores[] = "Error al ingresar el pago con referencia {$pagoData['referencia']}";
                    }
                    
                } catch (Exception $e) {
                    $errores[] = "Error procesando pago {$index}: " . $e->getMessage();
                }
            }
            
            // Preparar respuesta
            if (empty($errores)) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Todos los pagos se registraron correctamente',
                    'pagos' => $resultados
                ]);
            } else {
                echo json_encode([
                    'status' => 'partial', 
                    'message' => 'Algunos pagos no se pudieron procesar',
                    'pagos_exitosos' => $resultados,
                    'errores' => $errores
                ]);
            }
            exit;
            
        case 'modificar':
            $id = $_POST['id_detalles'];
            $pasarela->setIdDetalles($id);
            $pasarela->setReferencia($_POST['referencia']);
            $pasarela->setFecha($_POST['fecha']);
            $pasarela->setTipo($_POST['tipo']);
            $pasarela->setFactura($_POST['id_factura']);
            $pasarela->setCuenta($_POST['cuenta']);
            $pasarela->setMonto($_POST['monto']);

            if ($pasarela->pasarelaTransaccion('Modificar')) {
                $pagoActualizado = $pasarela->obtenerPagoPorId($id);
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_PASARELA_PAGOS,
                    'MODIFICAR',
                    'El usuario modificó la referencia bancaria: ' . $pagoActualizado['referencia'],
                    'media'
                );
                echo json_encode(['status' => 'success', 'pago' => $pagoActualizado]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar el producto']);
            }
            break;
            
        case 'modificar_estado':
            $id = $_POST['id_detalles'];
            $nuevoEstatus = $_POST['estatus'];
            $factura = $_POST['id_factura'];
            $pasarela->setIdDetalles($id);
            $pasarela->setEstatus($nuevoEstatus);
            $pasarela->setFactura($factura);

            if ($pasarela->pasarelaTransaccion('Procesar')) {
                $ordenDespacho = new OrdenDespacho();
                $ordenDespacho->crearPorFactura($factura);
                $pagoActualizado = $pasarela->obtenerPagoPorId($id);
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_PASARELA_PAGOS,
                    'MODIFICAR',
                    'El usuario cambió el estatus del pago de la referencia bancaria: ' . $pagoActualizado['referencia'] . ' a ' . $nuevoEstatus,
                    'media'
                );
                echo json_encode(['status' => 'success', 'pago' => $pagoActualizado]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus']);
            }
            
            // Crear notificación
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();
            $notificacionesModel = new NotificacionModel($pdo_seguridad);
            $notificacionesModel->crear(
                $_SESSION['id_usuario'],
                'pago',
                'Estatus de pago actualizado',
                "El estatus del pago con referencia " . $pagoActualizado['referencia'] . " ha sido cambiado a " . $nuevoEstatus . " por el usuario " . $_SESSION['name'],
                null,
                'media',
                MODULO_PASARELA_PAGOS,
                'modificar_estado'
            );
            break;
            
        case 'eliminar':
            $pasarela = new PasareladePago();
            $id = $_POST['id_detalles'];
            $pasarela->setIdDetalles($id);
            if ($pasarela->pasarelaTransaccion('Eliminar')) {
                $pagoEliminado = $pasarela->obtenerPagoPorId($id);
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_PASARELA_PAGOS,
                    'ELIMINAR',
                    'El usuario eliminó la referencia bancaria: ' . ($pagoEliminado ? $pagoEliminado['referencia'] : 'ID ' . $id),
                    'media'
                );
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el producto']);
            }
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
    exit;
}

// Consulta de datos según el rol
if ($_SESSION['nombre_rol'] != 'Cliente') {
    $datos = $pasarela->pasarelaTransaccion('ConsultarTodos');
} else {
    $pasarela->setCedula($_SESSION['cedula']);
    $datos = $pasarela->pasarelaTransaccion('Consultar');
}

$pagina = "pasarela";
if (is_file("vista/" . $pagina . ".php")) {
    require_once("vista/" . $pagina . ".php");
            if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
    $_SESSION['id_usuario'],
    '12',
    'ACCESAR',
    'El usuario accedió al al modulo de Pasarela de pagos',
    'media'
);}
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>