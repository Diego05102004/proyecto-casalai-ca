<?php
require_once 'config/config.php';

class Factura extends BD
{
    private $id;
    private $fecha;
    private $cliente;
    private $descuento;
    private $estatus;
    private $id_producto;
    private $cantidad;

    private $cedula;
    private $conex;

    // Constructor sin conexión persistente
public function __construct() {
    $this->conex = null;
}

    // Getters y Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getFecha() { return $this->fecha; }
    public function setFecha($fecha) { $this->fecha = $fecha; }

    public function getCliente() { return $this->cliente; }
    public function setCliente($cliente) { $this->cliente = $cliente; }

    public function getDescuento() { return $this->descuento; }
    public function setDescuento($descuento) { $this->descuento = $descuento; }

    public function getEstatus() { return $this->estatus; }
    public function setEstatus($estatus) { $this->estatus = $estatus; }

    public function getIdProducto() { return $this->id_producto; }
    public function setIdProducto($id_producto) { $this->id_producto = $id_producto; }

    public function getCantidad() { return $this->cantidad; }
    public function setCantidad($cantidad) { $this->cantidad = $cantidad; }
    public function getCedula() { return $this->cedula; }
    public function setCedula($cedula) { $this->cedula = $cedula; }
    public function registrar() {
        return $this->r_registrar();
    }
    private function r_registrar() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([
                $this->fecha,
                $this->cliente,
                $this->descuento,
                $this->estatus
            ]);
            return $this->conex->lastInsertId();
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

public function agregarProducto($idFactura, $idProducto, $cantidad) {
    return $this->a_agregarProducto($idFactura, $idProducto, $cantidad);
}
private function a_agregarProducto($idFactura, $idProducto, $cantidad) {
    $conexion = new BD('P');
    $this->conex = $conexion->getConexion();
    try {
        $sql = "INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad) 
                VALUES (?, ?, ?)";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([$idFactura, $idProducto, $cantidad]);
        return true;
    } finally {
        if (isset($conexion)) { $conexion->cerrar(); }
        $this->conex = null;
    }
}
    // Transacciones
    public function facturaTransaccion($transaccion) {
        switch ($transaccion) {
            case 'Ingresar':
                return $this->facturaIngresar();                 
            case 'Consultar':
                return $this->facturaConsultar();
            case 'ConsultarTodas':
                return $this->facturaConsultarTodas();
            case 'Cancelar':
                return $this->facturaCancelar($this->id);
            case 'Procesar':
                return $this->facturaProcesar($this->id, $this->estatus);
            case 'DescargarFactura':
                return $this->facturaDescargar($this->id);
            default:
                throw new Exception("Transacción no válida.");
        }
    }

    // Crear factura
private function facturaIngresar() {
    $conexion = new BD('P');
    $this->conex = $conexion->getConexion();
    $pdo = $this->conex;
    try {
        $pdo->beginTransaction();

        // Buscar ID del cliente por su cédula
        $stmtCliente = $pdo->prepare("SELECT id_clientes FROM tbl_clientes WHERE cedula = ?");
        $stmtCliente->execute([$this->cliente]); // aquí $this->cliente sería la cédula
        $clienteData = $stmtCliente->fetch(PDO::FETCH_ASSOC);

        if (!$clienteData) {
            throw new Exception("No se encontró un cliente con la cédula indicada.");
        }

        $id_cliente = $clienteData['id_clientes'];

        // Insertar en tabla factura
        $stmt = $pdo->prepare("INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->fecha, $id_cliente, $this->descuento, $this->estatus]);

        $factura_id = $pdo->lastInsertId();
        if (!$factura_id) {
            throw new Exception("No se pudo insertar la factura.");
        }

        // Validar que $this->id_producto y $this->cantidad sean arrays y tengan la misma longitud
        if (!is_array($this->id_producto) || !is_array($this->cantidad) || count($this->id_producto) !== count($this->cantidad)) {
            throw new Exception("Datos de productos y cantidades inválidos o no coinciden.");
        }

        $detalle_insertados = 0;
        $stmt2 = $pdo->prepare("INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad) VALUES (?, ?, ?)");

        foreach ($this->id_producto as $index => $id_producto) {
            $cantidad = $this->cantidad[$index];

            if (empty($id_producto) || empty($cantidad)) {
                throw new Exception("Producto o cantidad vacío en el índice $index.");
            }

            $stmt2->execute([$factura_id, $id_producto, $cantidad]);
            $detalle_insertados += $stmt2->rowCount();
        }

        if ($detalle_insertados !== count($this->id_producto)) {
            throw new Exception("No se insertaron todos los detalles de la factura.");
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
        return ['error' => $e->getMessage()];
    } finally {
        if (isset($conexion)) { $conexion->cerrar(); }
        $this->conex = null;
    }
}
    public function obtenerUltimaFactura() {
        return $this->o_ultimaFactura();
    }
    private function o_ultimaFactura() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT MAX(id_factura) AS ultima_factura FROM tbl_facturas";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['ultima_factura'] : null;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }
  private function facturaConsultarTodas() {
    $conexion = new BD('P');
    $this->conex = $conexion->getConexion();
    // Primero obtenemos información de pagos para validar después
    $sqlPagos = "SELECT id_factura, estatus FROM tbl_detalles_pago";
    $stmtPagos = $this->conex->prepare($sqlPagos);
    $stmtPagos->execute();
    $todosPagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear un mapa de estatus de pago por factura
    $estatusPorFactura = [];
    foreach ($todosPagos as $pago) {
        $idFactura = $pago['id_factura'];
        if (!isset($estatusPorFactura[$idFactura])) {
            $estatusPorFactura[$idFactura] = [];
        }
        $estatusPorFactura[$idFactura][] = $pago['estatus'];
    }

    $sqlDetalles = "SELECT f.id_factura, f.fecha, c.nombre, c.cedula, c.telefono, c.direccion,
        p.nombre_producto AS producto, m.nombre_modelo, mar.nombre_marca,
        p.precio, df.cantidad, f.descuento, f.estatus
    FROM tbl_factura_detalle df
    JOIN tbl_facturas f ON f.id_factura = df.factura_id
    JOIN tbl_clientes c ON c.id_clientes = f.cliente
    JOIN tbl_productos p ON df.id_producto = p.id_producto
    JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
    JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
    ORDER BY f.id_factura DESC";

    $stmt = $this->conex->prepare($sqlDetalles);
    $stmt->execute();
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$detalles) {
        return ['resultado' => 'error', 'mensaje' => 'No hay facturas registradas.'];
    }
    
    try {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        
        $stmt = $db->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && (time() - strtotime($result['fecha'])) < 86400) {
            $tasa = floatval($result['precio']);
        }
    } catch (Exception $e) {
        error_log('Error al obtener cache del dólar: ' . $e->getMessage());
    }

    // Obtener los estatus y observaciones desde tbl_detalles_pago
    $stmtPagos = $this->conex->prepare("SELECT id_factura, estatus, observaciones FROM tbl_detalles_pago");
    $stmtPagos->execute();
    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
    $mapaPagos = [];
    foreach ($pagos as $pago) {
        $mapaPagos[$pago['id_factura']] = [
            'estatus' => $pago['estatus'],
            'observaciones' => $pago['observaciones']
        ];
    }

    $facturasAgrupadas = [];
    foreach ($detalles as $item) {
        $facturasAgrupadas[$item['id_factura']][] = $item;
    }

    $html = '<div class="accordion w-100" id="accordionFacturas" style="width: 100%; height: 100%;">';

    foreach ($facturasAgrupadas as $id_factura => $items) {
        $fila = $items[0]; // Datos comunes
        $estatus = $fila['estatus'];
        
        // Verificar si es una factura pagada presencialmente y si todos los pagos están procesados
        $esPagadaPresencialmente = ($estatus == 'Pagada Presencialmente');
        $esBorrador = ($estatus == 'Borrador');
        $todosPagosProcesados = true;
        
        if ($esPagadaPresencialmente && isset($estatusPorFactura[$id_factura])) {
            foreach ($estatusPorFactura[$id_factura] as $estatusPago) {
                if ($estatusPago !== 'Pago Procesado') {
                    $todosPagosProcesados = false;
                    break;
                }
            }
        }
        
        // Si es pagada presencialmente pero no todos los pagos están procesados, saltar esta factura
        if ($esPagadaPresencialmente && !$todosPagosProcesados) {
            continue;
        }

        $datosCliente = '<p><strong>Cliente:</strong> ' . htmlspecialchars($fila['nombre']) .  ' | ';
        $datosCliente .= '<strong>Cédula:</strong> ' . htmlspecialchars($fila['cedula']) . ' | ';
        $datosCliente .= '<strong>Teléfono:</strong> ' . htmlspecialchars($fila['telefono']) . ' | ';
        $datosCliente .= '<strong>Dirección:</strong> ' . htmlspecialchars($fila['direccion']) . ' | ';
        $datosCliente .= '<strong>Descuento:</strong> ' . htmlspecialchars($fila['descuento']) . '% | ';
        
        // Aplicar estilo especial para diferentes estatus
        if ($esPagadaPresencialmente) {
            $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-success">' . htmlspecialchars($estatus) . '</span></p>';
        } else if ($esBorrador) {
            $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-warning">' . htmlspecialchars($estatus) . '</span></p>';
        } else {
            $datosCliente .= '<strong>Estatus:</strong> ' . htmlspecialchars($estatus) . '</p>';
        }

        $form = '<input type="hidden" name="id_factura" value="' . $id_factura . '">';
        $mensajePago = '';
        $botones = '';

        $estatusPago = $mapaPagos[$id_factura]['estatus'] ?? null;
        $observaciones = $mapaPagos[$id_factura]['observaciones'] ?? null;

        if ($esBorrador) {
            $mensajePago = '<div class="alert alert-warning"><strong>Borrador:</strong> El pago aún no ha sido enviado para validación.</div>';
            $botones = '
                <form action="?pagina=PasareladePago" method="POST" style="display:inline;">
                    <input type="hidden" name="id_factura" value="' . $id_factura . '">
                    <button type="submit" class="btn btn-success btn-lg">Pagar</button>
                </form>
                <form action="" method="POST" style="display:inline;">
                    <input type="hidden" name="id_factura" value="' . $id_factura . '">
                    <button type="button" class="btn btn-danger btn-lg cancelar" name="accion" value="cancelar">Cancelar</button>
                </form>';
        } else if ($estatusPago) {
            switch ($estatusPago) {
                case 'En Proceso':
                    $mensajePago = '<div class="alert alert-info"><strong>En proceso:</strong> El pago está siendo validado por un administrador. Por favor, espere la confirmación.</div>';
                    break;

                case 'Pago Incompleto':
                    $mensajePago = '<div class="alert alert-danger"><strong>Pago incompleto:</strong> ' . htmlspecialchars($observaciones) . '</div>';
                    break;

                case 'Pago Procesado':
                    // Mensaje especial para facturas pagadas presencialmente
                    if ($esPagadaPresencialmente) {
                        $mensajePago = '<div class="alert alert-success"><strong>Pago presencial procesado correctamente.</strong></div>';
                    } else {
                        $mensajePago = '<div class="alert alert-success"><strong>Pago procesado correctamente.</strong></div>';
                    }
                    $botones = '
                    <form action="" method="POST" target="_blank" style="display:inline;">
                        <input type="hidden" name="descargarFactura" value="' . $id_factura . '">
                        <button type="submit" class="btn btn-primary btn-lg descargar">Descargar</button>
                    </form>';
                    break;

                case 'Pago No Encontrado':
                    $mensajePago = '<div class="alert alert-danger"><strong>Pago no encontrado:</strong> ' . htmlspecialchars($observaciones) . '<br>Por favor, verifique los datos registrados en la pasarela de pago.</div>';
                    break;

                default:
                    $mensajePago = '<div class="alert alert-secondary">Estado de pago no reconocido.</div>';
                    break;
            }
        } else if ($esPagadaPresencialmente) {
            // Caso especial para facturas pagadas presencialmente sin registro en tbl_detalles_pago
            $mensajePago = '<div class="alert alert-success"><strong>Pago presencial procesado correctamente.</strong></div>';
            $botones = '
            <form action="" method="POST" target="_blank" style="display:inline;">
                <input type="hidden" name="descargarFactura" value="' . $id_factura . '">
                <button type="submit" class="btn btn-primary btn-lg descargar">Descargar</button>
            </form>';
        }

        $contenido = '<div class="w-100">' . $datosCliente . $mensajePago;
        $contenido .= '<div class="table-responsive w-100">';
        $contenido .= '<table class="table table-bordered w-100">';
        $contenido .= '<thead><tr><th>Producto</th><th>modelo</th><th>Marca</th><th>Cantidad</th><th>Precio</th></tr></thead><tbody>';

        $total = 0;
        foreach ($items as $item) {
            $subtotal = $item['precio']*$tasa * $item['cantidad'];
            $total += $subtotal;

            $contenido .= '<tr>';
            $contenido .= '<td>' . htmlspecialchars($item['producto']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($item['nombre_modelo']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($item['nombre_marca']) . '</td>';
            $contenido .= '<td>' . $item['cantidad'] . '</td>';
            $contenido .= '<td>' . number_format($item['precio']*$tasa, 2) . ' BS</td>';
            $contenido .= '</tr>';
        }

        $descuento = $total * $fila['descuento'] / 100;
        $subtotalConDescuento = $total - $descuento;
        $impuesto = $subtotalConDescuento * 0.16;
        $montoTotal = $subtotalConDescuento + $impuesto;
        $contenido .= '<tr><td colspan="4"><strong>Total con Impuestos:</strong></td><td>' . number_format($montoTotal, 2) . ' BS</td></tr>';            
        $contenido .= '</tbody></table></div>' . $form . $botones . '</div>';

        $html .= '<div class="accordion-item w-100">';
        $html .= '<h2 class="accordion-header" id="heading' . $id_factura . '">';
        
        // Estilo especial para el encabezado de facturas según su estatus
        if ($esPagadaPresencialmente) {
            $html .= '<button class="accordion-button collapsed w-100 bg-success text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
        } else if ($esBorrador) {
            $html .= '<button class="accordion-button collapsed w-100 bg-warning text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
        } else {
            $html .= '<button class="accordion-button collapsed w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
        }
        
        $html .= 'Pedido #' . $id_factura . ' - Fecha: ' . $fila['fecha'] . '</button></h2>';
        $html .= '<div id="collapse' . $id_factura . '" class="accordion-collapse collapse" aria-labelledby="heading' . $id_factura . '" data-bs-parent="#accordionFacturas">';
        $html .= '<div class="accordion-body w-100">' . $contenido . '</div></div></div>';
    }

    $html .= '</div>';
    $html .= "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";

    $resultadoListado = [
        'resultado' => 'listado',
        'mensaje' => $html
    ];
    if (isset($conexion)) { $conexion->cerrar(); }
    $this->conex = null;
    return $resultadoListado;
}

private function facturaConsultar() {
    $conexion = new BD('P');
    $this->conex = $conexion->getConexion();
    if (empty($this->cedula)) {
        $conexion->cerrar();
        $this->conex = null;
        return ['resultado' => 'error', 'mensaje' => 'No se ha proporcionado la cédula para consultar facturas.'];
    }
    
    // Primero obtenemos información de pagos para validar después
    $sqlPagos = "SELECT id_factura, estatus FROM tbl_detalles_pago";
    $stmtPagos = $this->conex->prepare($sqlPagos);
    $stmtPagos->execute();
    $todosPagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear un mapa de estatus de pago por factura
    $estatusPorFactura = [];
    foreach ($todosPagos as $pago) {
        $idFactura = $pago['id_factura'];
        if (!isset($estatusPorFactura[$idFactura])) {
            $estatusPorFactura[$idFactura] = [];
        }
        $estatusPorFactura[$idFactura][] = $pago['estatus'];
    }
    
    $sqlDetalles = "SELECT f.id_factura, f.fecha, c.nombre, c.cedula, c.telefono, c.direccion,
        p.nombre_producto AS producto, m.nombre_modelo, mar.nombre_marca,
        p.precio, df.cantidad, f.descuento, f.estatus
    FROM tbl_factura_detalle df
    JOIN tbl_facturas f ON f.id_factura = df.factura_id
    JOIN tbl_clientes c ON c.id_clientes = f.cliente
    JOIN tbl_productos p ON df.id_producto = p.id_producto
    JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
    JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
    WHERE c.cedula = :cedula
    ORDER BY f.id_factura DESC";

    $stmt = $this->conex->prepare($sqlDetalles);
    $stmt->bindParam(':cedula', $this->cedula);
    $stmt->execute();
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$detalles) {
        return ['resultado' => 'error', 'mensaje' => 'No hay facturas registradas para esta cédula.'];
    }
    
    try {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        
        $stmt = $db->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && (time() - strtotime($result['fecha'])) < 86400) {
            $tasa = floatval($result['precio']);
        }
    } catch (Exception $e) {
        error_log('Error al obtener cache del dólar: ' . $e->getMessage());
    }
    
    // Obtener los estatus y observaciones desde tbl_detalles_pago
    $stmtPagos = $this->conex->prepare("SELECT id_factura, estatus, observaciones FROM tbl_detalles_pago");
    $stmtPagos->execute();
    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
    $mapaPagos = [];
    foreach ($pagos as $pago) {
        $mapaPagos[$pago['id_factura']] = [
            'estatus' => $pago['estatus'],
            'observaciones' => $pago['observaciones']
        ];
    }

    $facturasAgrupadas = [];
    foreach ($detalles as $item) {
        $facturasAgrupadas[$item['id_factura']][] = $item;
    }

    $html = '<div class="accordion w-100" id="accordionFacturas" style="width: 100%; height: 100%;">';

    foreach ($facturasAgrupadas as $id_factura => $items) {
        $fila = $items[0]; // Datos comunes
        $estatus = $fila['estatus'];
        
        // Verificar si es una factura pagada presencialmente y si todos los pagos están procesados
        $esPagadaPresencialmente = ($estatus == 'Pagada Presencialmente');
        $esBorrador = ($estatus == 'Borrador');
        $todosPagosProcesados = true;
        
        if ($esPagadaPresencialmente && isset($estatusPorFactura[$id_factura])) {
            foreach ($estatusPorFactura[$id_factura] as $estatusPago) {
                if ($estatusPago !== 'Pago Procesado') {
                    $todosPagosProcesados = false;
                    break;
                }
            }
        }
        
        // Si es pagada presencialmente pero no todos los pagos están procesados, saltar esta factura
        if ($esPagadaPresencialmente && !$todosPagosProcesados) {
            continue;
        }

        $datosCliente = '<p><strong>Cliente:</strong> ' . htmlspecialchars($fila['nombre']) .  ' | ';
        $datosCliente .= '<strong>Cédula:</strong> ' . htmlspecialchars($fila['cedula']) . ' | ';
        $datosCliente .= '<strong>Teléfono:</strong> ' . htmlspecialchars($fila['telefono']) . ' | ';
        $datosCliente .= '<strong>Dirección:</strong> ' . htmlspecialchars($fila['direccion']) . ' | ';
        $datosCliente .= '<strong>Descuento:</strong> ' . htmlspecialchars($fila['descuento']) . '% | ';
        
        // Aplicar estilo especial para diferentes estatus
        if ($esPagadaPresencialmente) {
            $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-success">' . htmlspecialchars($estatus) . '</span></p>';
        } else if ($esBorrador) {
            $datosCliente .= '<strong>Estatus:</strong> <span class="badge bg-warning">' . htmlspecialchars($estatus) . '</span></p>';
        } else {
            $datosCliente .= '<strong>Estatus:</strong> ' . htmlspecialchars($estatus) . '</p>';
        }

        $form = '<input type="hidden" name="id_factura" value="' . $id_factura . '">';
        $mensajePago = '';
        $botones = '';

        $estatusPago = $mapaPagos[$id_factura]['estatus'] ?? null;
        $observaciones = $mapaPagos[$id_factura]['observaciones'] ?? null;

        if ($esBorrador) {
            $mensajePago = '<div class="alert alert-warning"><strong>Borrador:</strong> El pago aún no ha sido enviado para validación.</div>';
            $botones = '
                <form action="?pagina=PasareladePago" method="POST" style="display:inline;">
                    <input type="hidden" name="id_factura" value="' . $id_factura . '">
                    <button type="submit" class="btn btn-success btn-lg">Pagar</button>
                </form>
                <form action="" method="POST" style="display:inline;">
                    <input type="hidden" name="id_factura" value="' . $id_factura . '">
                    <button type="button" class="btn btn-danger btn-lg cancelar" name="accion" value="cancelar">Cancelar</button>
                </form>';
        } else if ($estatusPago) {
            switch ($estatusPago) {
                case 'En Proceso':
                    $mensajePago = '<div class="alert alert-info"><strong>En proceso:</strong> El pago está siendo validado por un administrador. Por favor, espere la confirmación.</div>';
                    break;

                case 'Pago Incompleto':
                    $mensajePago = '<div class="alert alert-danger"><strong>Pago incompleto:</strong> ' . htmlspecialchars($observaciones) . '</div>';
                    break;

                case 'Pago Procesado':
                    // Mensaje especial para facturas pagadas presencialmente
                    if ($esPagadaPresencialmente) {
                        $mensajePago = '<div class="alert alert-success"><strong>Pago presencial procesado correctamente.</strong></div>';
                    } else {
                        $mensajePago = '<div class="alert alert-success"><strong>Pago procesado correctamente.</strong></div>';
                    }
                    $botones = '
                    <form action="" method="POST" target="_blank" style="display:inline;">
                        <input type="hidden" name="descargarFactura" value="' . $id_factura . '">
                        <button type="submit" class="btn btn-primary btn-lg descargar">Descargar</button>
                    </form>';
                    break;

                case 'Pago No Encontrado':
                    $mensajePago = '<div class="alert alert-danger"><strong>Pago no encontrado:</strong> ' . htmlspecialchars($observaciones) . '<br>Por favor, verifique los datos registrados en la pasarela de pago.</div>';
                    break;

                default:
                    $mensajePago = '<div class="alert alert-secondary">Estado de pago no reconocido.</div>';
                    break;
            }
        } else if ($esPagadaPresencialmente) {
            // Caso especial para facturas pagadas presencialmente sin registro en tbl_detalles_pago
            $mensajePago = '<div class="alert alert-success"><strong>Pago presencial procesado correctamente.</strong></div>';
            $botones = '
            <form action="" method="POST" target="_blank" style="display:inline;">
                <input type="hidden" name="descargarFactura" value="' . $id_factura . '">
                <button type="submit" class="btn btn-primary btn-lg descargar">Descargar</button>
            </form>';
        }

        $contenido = '<div class="w-100">' . $datosCliente . $mensajePago;
        $contenido .= '<div class="table-responsive w-100">';
        $contenido .= '<table class="table table-bordered w-100">';
        $contenido .= '<thead><tr><th>Producto</th><th>modelo</th><th>Marca</th><th>Cantidad</th><th>Precio</th></tr></thead><tbody>';

        $total = 0;
        foreach ($items as $item) {
            $subtotal = $item['precio']*$tasa * $item['cantidad'];
            $total += $subtotal;

            $contenido .= '<tr>';
            $contenido .= '<td>' . htmlspecialchars($item['producto']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($item['nombre_modelo']) . '</td>';
            $contenido .= '<td>' . htmlspecialchars($item['nombre_marca']) . '</td>';
            $contenido .= '<td>' . $item['cantidad'] . '</td>';
            $contenido .= '<td>' . number_format($item['precio']*$tasa, 2) . 'BS</td>';
            $contenido .= '</tr>';
        }

        $descuento = $total * $fila['descuento'] / 100;
        $subtotalConDescuento = $total - $descuento;
        $impuesto = $subtotalConDescuento * 0.16;
        $montoTotal = $subtotalConDescuento + $impuesto;
        $contenido .= '<tr><td colspan="4"><strong>Total con Impuestos:</strong></td><td>' . number_format($montoTotal, 2) . ' BS</td></tr>';            
        $contenido .= '</tbody></table></div>' . $form . $botones . '</div>';

        $html .= '<div class="accordion-item w-100">';
        $html .= '<h2 class="accordion-header" id="heading' . $id_factura . '">';
        
        // Estilo especial para el encabezado de facturas según su estatus
        if ($esPagadaPresencialmente) {
            $html .= '<button class="accordion-button collapsed w-100 bg-success text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
        } else if ($esBorrador) {
            $html .= '<button class="accordion-button collapsed w-100 bg-warning text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
        } else {
            $html .= '<button class="accordion-button collapsed w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
        }
        
        $html .= 'Pedido #' . $id_factura . ' - Fecha: ' . $fila['fecha'] . '</button></h2>';
        $html .= '<div id="collapse' . $id_factura . '" class="accordion-collapse collapse" aria-labelledby="heading' . $id_factura . '" data-bs-parent="#accordionFacturas">';
        $html .= '<div class="accordion-body w-100">' . $contenido . '</div></div></div>';
    }

    $html .= '</div>';
    $html .= "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>";

    try {
        return [
            'resultado' => 'listado',
            'mensaje' => $html
        ];
    } finally {
        if (isset($conexion)) { $conexion->cerrar(); }
        $this->conex = null;
    }
}
    
    
    
    
    

    // Marcar factura como Cancelada
    public function facturaCancelar($id) {
        return $this->c_facturaCancelar($id);
    }
    private function c_facturaCancelar($id) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $stmt = $this->conex->prepare("UPDATE tbl_facturas SET estatus = 'Cancelada' WHERE id_factura = ?");
            return $stmt->execute([$id]);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    // Marcar factura como Procesada
    public function facturaProcesar($id, $estatus) {
        return $this->p_facturaProcesar($id, $estatus);
    }
    private function p_facturaProcesar($id, $estatus) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $stmt = $this->conex->prepare("UPDATE tbl_facturas SET estatus = ? WHERE id_factura = ?");
            return $stmt->execute([$estatus, $id]);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    public function obtenerMontoTotalFactura($id_factura) {
        return $this->o_montoTotalFactura($id_factura);
    }
    private function o_montoTotalFactura($id_factura) {
        $conexion = new BD('P');
        try {
            $sql = "SELECT 
                    ROUND(
                        (
                            SUM(p.precio * df.cantidad) * (1 - (f.descuento / 100))
                        ) * 1.16, 2
                    ) AS total_con_impuesto
                FROM tbl_factura_detalle df
                JOIN tbl_facturas f ON f.id_factura = df.factura_id
                JOIN tbl_productos p ON df.id_producto = p.id_producto
                WHERE f.id_factura = :id_factura
                GROUP BY f.id_factura";
            $stmt = $conexion->getConexion()->prepare($sql);
            $stmt->bindParam(':id_factura', $id_factura, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            try {
                $conexionTasa = new BD('P');
                $db = $conexionTasa->getConexion();
                
                $stmt = $db->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && (time() - strtotime($result['fecha'])) < 86400) {
                    $tasa = floatval($result['precio']);
                }
            } catch (Exception $e) {
                error_log('Error al obtener cache del dólar: ' . $e->getMessage());
            }
            $total = $resultado['total_con_impuesto'] * $tasa;
            $conexion->cerrar();
            return $total;
        } catch (Exception $e) {
            error_log('Error al obtener monto total de factura: ' . $e->getMessage());
            return false;
        }
    }
    
    public function facturaDescargar($id_factura) {
        return $this->f_descargar($id_factura);
    }
    private function f_descargar($id_factura) {
        $conexionPrincipal = new BD('P');
        try {
            // Conexión para la tasa
            $conexion = new BD('P');
            $db = $conexion->getConexion();
            
            // Obtener la última tasa en cache
            $stmt = $db->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Validar si la tasa está vigente (menos de 24 horas) o asignar una por defecto (1)
            $tasa = 1;
            if ($result && (time() - strtotime($result['fecha'])) < 86400) {
                $tasa = floatval($result['precio']);
            }
            $conexion->cerrar();
        } catch (Exception $e) {
            error_log('Error al obtener cache del dólar: ' . $e->getMessage());
            $tasa = 1; // Valor por defecto en caso de error
        }

        // Consulta de la factura
        $sql = "SELECT f.id_factura, f.fecha, c.nombre, c.cedula, c.telefono, c.direccion,
                       p.nombre_producto AS producto, m.nombre_modelo, mar.nombre_marca,
                       p.precio, 
                       p.precio * :tasa AS precio_convertido,
                       df.cantidad, f.descuento
                FROM tbl_factura_detalle df
                JOIN tbl_facturas f ON f.id_factura = df.factura_id
                JOIN tbl_clientes c ON c.id_clientes = f.cliente
                JOIN tbl_productos p ON df.id_producto = p.id_producto
                JOIN tbl_modelos m ON m.id_modelo = p.id_modelo
                JOIN tbl_marcas mar ON mar.id_marca = m.id_marca
                WHERE f.id_factura = :id_factura";

        $stmt = $conexionPrincipal->getConexion()->prepare($sql);
        $stmt->bindParam(':id_factura', $id_factura, PDO::PARAM_INT);
        $stmt->bindParam(':tasa', $tasa);
        $stmt->execute();
        $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $conexionPrincipal->cerrar();
        return $facturas;
    }
}
