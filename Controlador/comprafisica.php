<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Página y dependencias
$pagina = 'comprafisica';
require_once 'modelo/comprafisica.php';
require_once 'modelo/permiso.php';
require_once 'modelo/ordendespacho.php';
require_once 'modelo/bitacora.php';
require_once 'modelo/cuenta.php';
require_once 'modelo/DolarService.php';
require_once 'modelo/notificacion.php';
require_once 'modelo/Factura.php';

// Constante de módulo
define('MODULO_DESPACHO', 3);

// Inicializaciones
$k = new Compra();
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
$permisosObj = new Permisos();
$bitacoraModel = new Bitacora();
$cuentaModel = new Cuentabanco();
$despacho = new OrdenDespacho();
$facturaModel = new Factura();

// Usar un nombre de módulo consistente sin acentos para evitar desajustes con la BD
$permisosUsuario = $permisosObj->getPermisosUsuarioModulo($id_rol, 'compra fisica');

if (is_file("vista/" . $pagina . ".php")) {
    $accion = $_POST['accion'] ?? '';

    function getdespacho()
    {
        $despacho = new Compra();
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

                if (!$idCliente || empty($productos)) {
                    echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos obligatorios']);
                    exit;
                }

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
                        $uploadDir = "uploads/comprobantes/";
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        move_uploaded_file($tmpName, $uploadDir . $fileName);
                        $detalle['comprobante'] = $uploadDir . $fileName;
                    } else {
                        // asignar imágenes por defecto segun tipo
                        if ($detalle['tipo'] === 'Efectivo') {
                            $detalle['comprobante'] = 'uploads/comprobantes/bolivar.png';
                            $detalle['cuenta'] = 0;
                        } elseif ($detalle['tipo'] === 'Efectivo en $') {
                            $detalle['comprobante'] = 'uploads/comprobantes/dolar.png';
                            $detalle['cuenta'] = 1;
                        } elseif ($detalle['tipo'] === 'Zelle') {
                            $detalle['comprobante'] = 'uploads/comprobantes/zelle.png';
                        } else {
                            $detalle['comprobante'] = null;
                        }
                    }

                    $detallePagos[] = $detalle;
                }

                // Registrar en el modelo
                $resultado = $k->registrarCompraFisica([
                    'cliente' => $idCliente,
                    'monto_total' => $montoTotal,
                    'cambio' => $cambio,
                    'productos' => $detalleProductos,
                    'pagos' => $detallePagos
                ]);

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
                $permisosActualizados = $permisosObj->getPermisosUsuarioModulo($id_rol, 'compra fisica');
                echo json_encode($permisosActualizados);
                exit;

            case 'obtener_detalles':
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
    require_once("vista/" . $pagina . ".php");
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_DESPACHO,
            'ACCESAR',
            'El usuario accedió al modulo de Compra Física',
            'media'
        );
    }
} else {
    echo "pagina en construccion";
}
?>