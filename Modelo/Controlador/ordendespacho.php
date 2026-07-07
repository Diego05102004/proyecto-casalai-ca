<?php
ob_start();
require __DIR__ . '/../../assets/public/fpdf/fpdf.php';
use Usuario\ProyectoCasalaiCa\Modelo\Clases\OrdenDespacho;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Config\BD;
define('MODULO_ORDEN_DESPACHO', "Ordenes de despacho");

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
            $id = $_POST['id_despachos'] ?? null;
            if ($id !== null) {
                $ordenModel = new OrdenDespacho();
                $orden = $ordenModel->obtenerOrdenPorId($id);
                if ($orden !== null) {
                    echo json_encode(['status' => 'success', 'datos' => $orden]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Orden de despacho no encontrada']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de la orden no proporcionado']);
            }
            break;

        case 'descargar_pdf':
            // 1. Validar la llegada del ID (por POST o GET para mayor flexibilidad)
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'ID de orden no proporcionado']);
                exit;
            }
            
            // 2. Consultar los datos principales de la orden
            $ordenData = $ordenModel->obtenerDatosParaPDF($id);
            if (empty($ordenData)) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'No se encontraron datos para la orden especificada']);
                exit;
            }

            // 3. Consultar el detalle de los artículos vinculados a la orden
            // (Ajusta el método según cómo extraigas las filas de productos de la base de datos)
            $detalles = method_exists($ordenModel, 'obtenerDetallesParaPDF') 
                        ? $ordenModel->obtenerDetallesParaPDF($id) 
                        : [];

            // =================================================================
            // CRUCIAL: Limpiar cualquier buffer previo (espacios, saltos de línea, etc.)
            // Si algo se coló antes de esto, ob_end_clean() lo borrará para no corromper el PDF.
            // =================================================================
            if (ob_get_length()) {
                ob_end_clean();
            }

            // 4. Inicializar FPDF (Asegúrate de que la clase ya esté cargada al inicio del controlador)
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 20);

            // --- ENCABEZADO DEL DOCUMENTO ---
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(100, 10, utf8_decode('CASA LAI, C.A.'), 0, 0, 'L');
            
            $pdf->SetFont('Arial', 'B', 14);
            // Color rojo sutil para el identificador de control
            $pdf->SetTextColor(180, 0, 0); 
            $pdf->Cell(80, 10, utf8_decode('ORDEN DE DESPACHO N° ' . $ordenData['id_orden_despachos']), 0, 1, 'R');
            $pdf->SetTextColor(0, 0, 0); // Resetear a color negro

            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(100, 5, utf8_decode('Sistema de Gestión de Inventario y Ventas'), 0, 0, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(80, 5, 'Fecha de Emision: ' . ($ordenData['fecha'] ?? date('Y-m-d')), 0, 1, 'R');
            
            $pdf->Ln(5);
            $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY()); // Línea divisoria horizontal
            $pdf->Ln(5);

            // --- INFORMACIÓN DE LA TRANSACCIÓN ---
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(180, 6, utf8_decode('Detalles del Movimiento:'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 10);

            // Ajusta los índices del array ($ordenData) según las columnas reales de tus consultas SQL
            $pdf->Cell(40, 6, utf8_decode('Cliente / Destino:'), 0, 0, 'L');
            $pdf->Cell(140, 6, utf8_decode($ordenData['cliente'] ?? $ordenData['nombre_cliente'] ?? 'N/A'), 0, 1, 'L');

            $pdf->Cell(40, 6, utf8_decode('Usuario Responsable:'), 0, 0, 'L');
            $pdf->Cell(140, 6, utf8_decode($ordenData['usuario'] ?? $ordenData['nombre_usuario'] ?? 'N/A'), 0, 1, 'L');

            $pdf->Cell(40, 6, utf8_decode('Estado del Despacho:'), 0, 0, 'L');
            $pdf->Cell(140, 6, utf8_decode($ordenData['estado'] ?? 'Procesado'), 0, 1, 'L');
            
            $pdf->Ln(8);

            // --- TABLA DE ARTÍCULOS ---
            $pdf->SetFont('Arial', 'B', 10);
            // Colores de fondo para el encabezado de la tabla (Gris claro)
            $pdf->SetFillColor(230, 230, 230);
            
            // Estructura de columnas: Código (30mm), Descripción (90mm), Cantidades (30mm c/u) = 180mm total
            $pdf->Cell(30, 7, utf8_decode('Código'), 1, 0, 'C', true);
            $pdf->Cell(90, 7, utf8_decode('Descripción del Artículo'), 1, 0, 'L', true);
            $pdf->Cell(30, 7, utf8_decode('Cant. Pedida'), 1, 0, 'C', true);
            $pdf->Cell(30, 7, utf8_decode('Cant. Enviada'), 1, 1, 'C', true);

            $pdf->SetFont('Arial', '', 10);

            // Renderizar las filas dinámicamente si existen artículos
            if (!empty($detalles) && is_array($detalles)) {
                foreach ($detalles as $item) {
                    $pdf->Cell(30, 6, utf8_decode($item['codigo'] ?? $item['id_articulo'] ?? '-'), 1, 0, 'C');
                    $pdf->Cell(90, 6, utf8_decode($item['descripcion'] ?? $item['nombre_articulo'] ?? 'N/A'), 1, 0, 'L');
                    $pdf->Cell(30, 6, $item['cantidad_solicitada'] ?? '0', 1, 0, 'C');
                    $pdf->Cell(30, 6, $item['cantidad_despachada'] ?? $item['cantidad'] ?? '0', 1, 1, 'C');
                }
            } else {
                // Fallback por si los datos del artículo vinieron unificados en la consulta principal
                if (isset($ordenData['descripcion']) || isset($ordenData['articulo'])) {
                    $pdf->Cell(30, 6, utf8_decode($ordenData['codigo'] ?? '-'), 1, 0, 'C');
                    $pdf->Cell(90, 6, utf8_decode($ordenData['descripcion'] ?? 'N/A'), 1, 0, 'L');
                    $pdf->Cell(30, 6, $ordenData['cantidad_solicitada'] ?? '0', 1, 0, 'C');
                    $pdf->Cell(30, 6, $ordenData['cantidad'] ?? '0', 1, 1, 'C');
                } else {
                    $pdf->Cell(180, 7, utf8_decode('No se encontraron renglones registrados para este despacho.'), 1, 1, 'C');
                }
            }

            $pdf->Ln(20);

            // --- BLOQUE DE FIRMAS (Control de recepción seguro) ---
            // Evitar que las firmas queden huérfanas en el borde inferior de la página
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
            }
            
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(90, 5, '___________________________', 0, 0, 'C');
            $pdf->Cell(90, 5, '___________________________', 0, 1, 'C');
            $pdf->Cell(90, 5, utf8_decode('Despachado Por (Firma/C.I.)'), 0, 0, 'C');
            $pdf->Cell(90, 5, utf8_decode('Recibido Conforme (Firma/C.I.)'), 0, 1, 'C');

            // 5. Forzar la descarga del documento en el navegador con un nombre dinámico limpio
            $filename = 'Orden_Despacho_' . $ordenData['id_orden_despachos'] . '.pdf';
            $pdf->Output('D', $filename);
            exit;
        
        /*case 'descargar_pdf':
            $id = $_POST['id'] ?? null;
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'ID de orden no proporcionado']);
                exit;
            }
            $ordenModel = new OrdenDespacho();
            $ordenData = $ordenModel->obtenerDatosParaPDF($id);
            if (empty($ordenData)) {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró la orden']);
                exit;
            }
            require_once __DIR__ . '/../../assets/public/fpdf/fpdf.php';
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial','B',16);
            $pdf->Cell(0,10,'ORDEN DE DESPACHO',0,1,'C');
            $pdf->Ln(10);
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
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell(30,10,'Codigo',1,0,'C');
            $pdf->Cell(60,10,'Producto',1,0,'C');
            $pdf->Cell(30,10,'Marca',1,0,'C');
            $pdf->Cell(20,10,'Cant.',1,0,'C');
            $pdf->Cell(25,10,'Precio',1,0,'C');
            $pdf->Cell(25,10,'Total',1,1,'C');
            $pdf->SetFont('Arial','',10);
            foreach ($ordenData['productos'] as $producto) {
                $pdf->Cell(30,8,$producto['codigo'],1,0,'C');
                $pdf->Cell(60,8,utf8_decode($producto['producto']),1,0);
                $pdf->Cell(30,8,utf8_decode($producto['marca']),1,0,'C');
                $pdf->Cell(20,8,$producto['cantidad'],1,0,'C');
                $pdf->Cell(25,8,number_format($producto['precio'], 2, ',', '.'),1,0,'R');
                $pdf->Cell(25,8,number_format($producto['subtotal'], 2, ',', '.'),1,1,'R');
            }
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(140,10,'TOTAL:',0,0,'R');
            $pdf->Cell(30,10,number_format($ordenData['total'], 2, ',', '.'),1,1,'R');
            $filename = 'Orden_Despacho_'.$ordenData['id_orden_despachos'].'.pdf';
            $pdf->Output('D', $filename);
            exit;*/

        case 'cambiar_estatus':
            $id = $_POST['id_despachos'];
            $nuevoEstatus = $_POST['nuevo_estatus'];
            if (!in_array($nuevoEstatus, ['habilitado', 'inhabilitado'])) {
                echo json_encode(['status' => 'error', 'message' => 'Estatus no válido']);
                exit;
            }
            $ordendespacho = new OrdenDespacho();
            $ordendespacho->setId($id);
            if ($ordendespacho->cambiarEstatus($nuevoEstatus)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_ORDEN_DESPACHO, 'CAMBIAR ESTATUS', 'El usuario cambió el estatus de la orden de despacho ID ' . $id . ' a ' . $nuevoEstatus, 'media');
                }
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus']);
            }
            break;

        case 'cambiar_estado_orden':
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;
            
            // 1. Verificación estricta de sesión activa
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            // 2. Extracción limpia y segura de parámetros POST (Evita sobreescritura de variables)
            $id = isset($_POST['id_orden_despachos']) ? (int)$_POST['id_orden_despachos'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
            $estado_actual = $_POST['estado_actual'] ?? '';
            
            // 3. Validación de consistencia elemental
            if ($id <= 0 || !in_array($estado_actual, ['Por Entregar', 'Entregada'], true)) {
                echo json_encode(['status' => 'error', 'message' => 'Datos inválidos para cambiar el estado de la orden.']);
                break;
            }
            
            // 4. Conmutación controlada de estados (Toggle)
            $nuevo_estado = ($estado_actual === 'Por Entregar') ? 'Entregada' : 'Por Entregar';
            
            // 5. Instanciación y ejecución atómica
            $ordenModel = new OrdenDespacho();
            $resultado = $ordenModel->cambiarEstadoOrden($id, $nuevo_estado, $id_usuario_sesion);
            
            // 6. Respuesta JSON unificada basada en el retorno del modelo
            if (isset($resultado['status']) && $resultado['status'] === 'success') {

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'], 
                        'orden_despacho', 
                        'Estado de orden de despacho actualizado', 
                        "Se ha cambiado el estado de la orden de despacho con ID " . $id . " a '" . $nuevo_estado . "' por el usuario " . ($_SESSION['name'] ?? ''), 
                        'media', 
                        MODULO_ORDEN_DESPACHO, 
                        'actualizar', 
                        $id
                    );
                }

                echo json_encode(['status' => 'success', 'nuevo_estado' => $nuevo_estado]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => $resultado['message'] ?? 'No se pudo cambiar el estado de la orden.'
                ]);
            }
            break;

        case 'anularOrden':
            header('Content-Type: application/json; charset=utf-8');
            
            $id_usuario_sesion = $_SESSION['id_usuario'] ?? null;

            // 1. Verificación estricta de sesión activa
            if (!$id_usuario_sesion) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error de autenticación: Sesión de usuario no válida.'
                ]);
                exit;
            }

            // 2. Captura segura y casteo a entero en un solo paso
            $idOrden = isset($_POST['id_orden_despachos']) ? (int)$_POST['id_orden_despachos'] : 0;
            
            // 3. Validación de consistencia elemental
            if ($idOrden <= 0) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'ID de orden de despacho no válido.'
                ]);
                break;
            }

            // 4. Instanciación y ejecución de la baja lógica
            $ordenModel = new OrdenDespacho();
            $resultado = $ordenModel->anularOrdenDespacho($idOrden, $id_usuario_sesion);

            // 5. Gestión controlada de efectos secundarios (Notificaciones)
            if (isset($resultado['status']) && $resultado['status'] === 'success') {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'], 
                        'orden_despacho', 
                        'Orden de despacho anulada', 
                        "Se ha anulado la orden de despacho con ID " . $idOrden . " por parte del usuario " . ($_SESSION['name'] ?? ''), 
                        'media', 
                        MODULO_ORDEN_DESPACHO, 
                        'eliminar', 
                        $idOrden
                    );
                }
            }

            // 6. Retorno final estructurado de la operación (Éxito o error del SP)
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
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se encontró la Orden de Despacho.']);
        exit;
    }
}

$pagina = "ordendespacho";
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora($_SESSION['id_usuario'], MODULO_ORDEN_DESPACHO, 'ACCESAR', 'El usuario accedió al módulo de Ordenes de Despacho', 'media');
    }
    $ordendespacho = getordendespacho();
    $ordenModel = new OrdenDespacho();
    $facturas = $ordenModel->obtenerFacturasDisponibles();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
ob_end_flush();
?>