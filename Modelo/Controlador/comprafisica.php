<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Página y dependencias
$pagina = 'comprafisica';
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Comprafisica;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\OrdenDespacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Cuentabanco;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Finanza;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Config\BD;
// Constante de módulo
define('MODULO_DESPACHO', 3);

// Inicializaciones
$k = new Comprafisica('P');
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
$id_rol = $_SESSION['id_rol'] ?? 0; // valor por defecto seguro

$cuentaModel = new Cuentabanco();
$despacho = new OrdenDespacho();
$facturaModel = new Factura();

// Usar un nombre de módulo consistente sin acentos para evitar desajustes con la BD
$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, 'compra fisica');

if (is_file("Vista/" . $pagina . ".php")) {
    $accion = $_POST['accion'] ?? '';

    function getdespacho()
    {
        $despacho = new Comprafisica();
        return $despacho->getdespacho();
    }

    function parsearCantidadFormateada($cantidadFormateada) {
        if (is_numeric($cantidadFormateada)) {
            return floatval($cantidadFormateada);
        }
        
        $cantidadLimpia = str_replace('.', '', $cantidadFormateada);
        $cantidadLimpia = str_replace(',', '.', $cantidadLimpia);
        
        return floatval($cantidadLimpia);
    }

    if (!empty($_POST)) {
        switch ($accion) {
            case 'listado':
                $respuesta = $k->listadoproductos();
                echo json_encode($respuesta);
                break;

            case 'registrar':
                // Forzar salida limpia y JSON
                if (ob_get_length()) {
                    ob_clean();
                }
                header('Content-Type: application/json; charset=utf-8');

                $idCliente = $_POST['cliente'] ?? null;
                $productos = $_POST['producto'] ?? [];
                $cantidades = $_POST['cantidad'] ?? [];
                $pagos = $_POST['pagos'] ?? [];
                $montoTotal = $_POST['monto_total'] ?? 0;
                $cambio = $_POST['cambio_efectivo'] ?? 0;

                // Preparar productos
                $detalleProductos = [];
                foreach ($productos as $i => $prod) {
                    $cantidadFormateada = $cantidades[$i] ?? '0';
                    $cantidadNumerica = parsearCantidadFormateada($cantidadFormateada);
                    $detalleProductos[] = [
                        'id_producto' => $prod,
                        'cantidad' => $cantidadNumerica
                    ];
                }

                // Preparar pagos
                $detallePagos = [];
                foreach ($pagos as $idx => $pago) {
                    $detalle = [
                        'tipo' => $pago['tipo'] ?? '',
                        'cuenta' => $pago['cuenta'] ?? '',
                        'referencia' => $pago['referencia'] ?? '',
                        'fecha' => date("Y-m-d H:i:s"),
                        'monto' => $pago['monto'] ?? 0
                    ];

                    // Subida de comprobante (si existe)
                    if (!empty($_FILES['pagos']['name'][$idx]['comprobante'])) {
                        $tmpName = $_FILES['pagos']['tmp_name'][$idx]['comprobante'];
                        $fileName = time() . '_' . basename($_FILES['pagos']['name'][$idx]['comprobante']);
                        $uploadDir = "assets/img/uploads/comprobantes/";
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        move_uploaded_file($tmpName, $uploadDir . $fileName);
                        $detalle['comprobante'] = $uploadDir . $fileName;
                    } else {
                        // asignar imágenes por defecto segun tipo
                        if ($detalle['tipo'] === 'Efectivo') {
                            $detalle['comprobante'] = 'assets/img/uploads/comprobantes/bolivar.png';
                            $detalle['cuenta'] = 0;
                        } elseif ($detalle['tipo'] === 'Efectivo en $') {
                            $detalle['comprobante'] = 'assets/img/uploads/comprobantes/dolar.png';
                            $detalle['cuenta'] = 1;
                        } elseif ($detalle['tipo'] === 'Zelle') {
                            $detalle['comprobante'] = 'assets/img/uploads/comprobantes/zelle.png';
                        } else {
                            $detalle['comprobante'] = null;
                        }
                    }

                    $detallePagos[] = $detalle;
                }

                $datosVenta = [
                    'cliente' => $idCliente,
                    'monto_total' => $montoTotal,
                    'cambio' => $cambio,
                    'productos' => $detalleProductos,
                    'pagos' => $detallePagos
                ];

                // Validar datos de entrada
                $errores = $k->validarRegistrarVenta($datosVenta);
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error en los datos de la venta',
                        'errors' => $errores
                    ]);
                    exit;
                }

                // Registrar en el modelo
                $resultado = $k->registrarCompraFisica($datosVenta);

                // Verificar si el registro fue exitoso
                if (isset($resultado['status']) && $resultado['status'] === 'error') {
                    // Hubo un error en el modelo
                    $response = [
                        'resultado' => 'error',
                        'mensaje' => $resultado['mensaje'] ?? 'Error al registrar la compra'
                    ];
                } else {
                    // Registro exitoso
                    $correlativo = $facturaModel->obtenerUltimaFactura() ?? 'N/A';
                    $name = $_SESSION['name'] ?? 'Desconocido';
                    
                    // Registrar en bitácora
                    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                        $bitacoraModel = new Bitacora();
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_DESPACHO,
                            'INCLUIR',
                            'El usuario '.$name.' incluyó la compra física: ' . $correlativo,
                            'alta'
                        );
                    }
                    
                    // Crear notificación
                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'],
                        'despacho',
                        'Nueva compra física registrada',
                        "Se ha registrado una nueva compra física #" . $correlativo . " con " . array_sum($cantidades) . " unidades por el usuario " . $_SESSION['name'],
                        null,
                        'media',
                        MODULO_DESPACHO,
                        'ingresar'
                    );

                    // Preparar respuesta exitosa
                    $response = [
                        'resultado' => 'registrar',
                        'mensaje' => 'Venta registrada correctamente',
                        'venta' => $resultado // Asegurar que $resultado tenga la estructura correcta
                    ];
                }

                echo json_encode($response);
                break;

            case 'buscar_clientes':
                $query = $_POST['query'] ?? '';
                $clientes = $k->buscarClientes($query);
                echo json_encode($clientes);
                break;

            case 'permisos_tiempo_real':
                header('Content-Type: application/json; charset=utf-8');
                $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, 'compra fisica');
                echo json_encode($permisosActualizados);
                exit;

            case 'obtener_detalles':
                // Validar datos de entrada
                $datosValidacion = [
                    'id_despacho' => $_POST['id_despachos'] ?? null
                ];
                
                $errores = $k->validarDetallarVenta($datosValidacion);
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Datos inválidos',
                        'errors' => $errores
                    ]);
                    exit;
                }
                
                $idDespacho = $_POST['id_despachos'] ?? null;
                if ($idDespacho) {
                    $respuesta = $k->obtenerDetallesPorDespacho($idDespacho);
                    echo json_encode($respuesta);
                } else {
                    echo json_encode(['error' => true, 'mensaje' => 'ID de recepción no recibido']);
                }
                break;

            default:
                echo json_encode(['status' => 'error', 'message' => 'Acción no válida ' . $accion . '']);
        }
        exit;
    }

    // vista inicial
    $compras = $k->getCompras();
    $proveedores = $k->obtenercliente();
    $productos = $k->consultarproductos();

    $permisos = new Permisos();
    $permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
    $listadocuentas = $cuentaModel->consultarCuentabanco();
    require_once("Vista/" . $pagina . ".php");
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_DESPACHO,
            'ACCESAR',
            'El usuario accedió al módulo de Compra Física',
            'media'
        );
    }
} else {
    echo "pagina en construccion";
}
?>