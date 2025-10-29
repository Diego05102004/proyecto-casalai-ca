<?php 
ob_start();
require_once __DIR__ . '/../modelo/pasareladepago.php';
require_once __DIR__ . '/../modelo/cuenta.php';
require_once __DIR__ . '/../modelo/factura.php';
require_once __DIR__ . '/../modelo/bitacora.php';
require_once __DIR__ . '/../modelo/DolarService.php';
require_once __DIR__ . '/../modelo/notificacion.php';
define('MODULO_PASARELA_PAGOS', 16); // Define el ID del módulo

// Registrar acceso al módulo si hay sesión activa
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}
if (!defined('SKIP_SIDE_EFFECTS')) {
    $bitacoraModel = new Bitacora();
    $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        MODULO_PASARELA_PAGOS,
        'ACCESAR',
        'Acceso al módulo de pasarela de pagos',
        'baja'
    );
}
$data = [];
$dolarService = new DolarService();
$precioDolar = $dolarService->obtenerPrecioDolar();
$dolarService->guardarPrecioCache($precioDolar);

// Manejar generación de reportes PDF
try {
    $dolarService = new DolarService();
    $precioDolar = $dolarService->obtenerPrecioDolar();
    $dolarService->guardarPrecioCache($precioDolar);
    
    // Asignar a $data
    $data['monitors'] = [
        'bcv' => [
            'price' => $precioDolar,
            'updated' => date('Y-m-d H:i:s')
        ]
    ];
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    $data['monitors'] = [
        'bcv' => [
            'price' => 35.50,
            'updated' => date('Y-m-d H:i:s') . ' (valor por defecto)'
        ]
    ];
    error_log('Error obteniendo precio dólar: ' . $e->getMessage());
}
// --- BLOQUE AJAX ---
// Procesar solo si la petición es AJAX y trae la clave "accion"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'];

    switch ($accion) {
        case 'ingresar':
            if (empty($_POST['pagos'])) {
                echo json_encode(['status' => 'error', 'message' => 'No se han enviado datos de pagos']);
                exit;
            }

            $id_factura = $_POST['id_factura'];
            $pagos = $_POST['pagos'];
            $resultados = [];
            $errores = [];

            foreach ($pagos as $index => $pagoData) {
                try {
                    $pasarela = new PasareladePago();
                    $pasarela->setCuenta($pagoData['cuenta']);
                    $pasarela->setReferencia($pagoData['referencia']);
                    $pasarela->setFecha(date('Y-m-d'));
                    $pasarela->setTipo($pagoData['tipo']);
                    $pasarela->setFactura($id_factura);
                    $pasarela->setMonto($pagoData['monto']);
                    $pasarela->setObservaciones('');

                    // Validar referencia duplicada
                    if (!$pasarela->validarCodigoReferencia()) {
                        $errores[] = "La referencia {$pagoData['referencia']} ya existe";
                        continue;
                    }

                    // Manejo de comprobante
                    $comprobanteNombre = null;
if (!empty($_FILES['pagos']['name'][$index]['comprobante'])) {
    $comprobanteTmp = $_FILES['pagos']['tmp_name'][$index]['comprobante'];
    $comprobanteOriginal = $_FILES['pagos']['name'][$index]['comprobante'];

    // Detectar extensión original de forma segura
    $extension = strtolower(pathinfo($comprobanteOriginal, PATHINFO_EXTENSION));

    // Validar que sea una extensión permitida (seguridad)
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    if (!in_array($extension, $extensionesPermitidas)) {
        $errores[] = "Formato de archivo no permitido para la referencia {$pagoData['referencia']}";
        continue;
    }

    // Generar nombre único con la extensión original
    $comprobanteNombre = 'comprobante_' . time() . '_' . $index . '.' . $extension;
    $destino = 'comprobantes/' . $comprobanteNombre;

    // Crear carpeta si no existe
    if (!is_dir('comprobantes')) {
        mkdir('comprobantes', 0755, true);
    }

    // Mover archivo
    if (!move_uploaded_file($comprobanteTmp, $destino)) {
        $errores[] = "Error al subir el comprobante para la referencia {$pagoData['referencia']}";
        continue;
    }

    $pasarela->setComprobante($comprobanteNombre);
}


                    // Guardar pago
                    if ($pasarela->pasarelaTransaccion('Ingresar')) {
                        if (!defined('SKIP_SIDE_EFFECTS')) {
                            $bitacoraModel = new Bitacora();
                            $bitacoraModel->registrarBitacora(
                                $_SESSION['id_usuario'],
                                MODULO_PASARELA_PAGOS,
                                'INGRESAR',
                                'Ingreso de pago con referencia ' . $pagoData['referencia'],
                                'alta'
                            );
                        }
                        // Crear notificación
                        $bd_seguridad = new BD('S');
                        $pdo_seguridad = $bd_seguridad->getConexion();
                        $notificacionModel = new NotificacionModel($pdo_seguridad);
                        $notificacionModel->crear(
                            $_SESSION['id_usuario'],
                            'pago',
                            'Nuevo pago registrado',
                            "Se ha registrado un nuevo pago con referencia " . $pagoData['referencia'] . " por el usuario " . $_SESSION['name'],
                            null,
                            'alta',
                            MODULO_PASARELA_PAGOS,
                            'ingresar'
                        );
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

            // Respuesta JSON según resultados
            if (empty($errores)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Los Datos de los pagos se registraron correctamente ahora debe esperar que su pago sea verificado por un administrador',
                    'pagos' => $resultados
                ]);
            } elseif (!empty($resultados)) {
                echo json_encode([
                    'status' => 'partial',
                    'message' => 'Algunos pagos no se pudieron procesar',
                    'pagos_exitosos' => $resultados,
                    'errores' => $errores
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Ningún pago pudo ser procesado',
                    'errores' => $errores
                ]);
            }
            exit; // DETENER EJECUCIÓN DESPUÉS DE RESPONDER JSON
            

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            exit;
    }
}

// --- BLOQUE VISTA ---
// Si no es AJAX, carga la vista normalmente
if (isset($_POST['id_factura'])) {
    $idFactura = $_POST['id_factura'];
    $facturaModel = new Factura();
    $cuentaModel = new Cuentabanco();
    $listadocuentas = $cuentaModel->consultarCuentabanco();
    $monto = $facturaModel->obtenerMontoTotalFactura($idFactura);

    $pagina = "pasareladepago";
    if (is_file("vista/" . $pagina . ".php")) {
        require_once("vista/" . $pagina . ".php");
    } else {
        echo "Página en construcción";
    }
} else {
    header("Location: ?pagina=gestionarfactura");
    exit;
}

ob_end_flush();
?>
