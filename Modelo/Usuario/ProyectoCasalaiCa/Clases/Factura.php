<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

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
    
    // Constantes para validaciones
    const MAX_DESCUENTO = 100;
    const MIN_DESCUENTO = 0;
    const MAX_CANTIDAD = 999999;
    const MIN_CANTIDAD = 1;
    const MAX_CLIENTE = 50;
    const MIN_CLIENTE = 3;
    const ESTADOS_PERMITIDOS = ['Borrador', 'Pagada Presencialmente', 'Pagada', 'Cancelada'];
    const ESTADOS_PAGO = ['En Proceso', 'Pago Incompleto', 'Pago Procesado', 'Pago No Encontrado'];

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

    public function __construct($tipo = 'P') {
    }
    
    /**
     * @return PDO
     */
    public function getConexion() {
        return $this->pdo;
    }
    
    /**
     * @param callable
     * @return mixed
     */

    protected function ejecutarConConexionSegura($operation) {
        try {
            parent::__construct('P'); 
            $pdo = parent::getConexion(); 

            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException("La conexión PDO no es válida o es nula.");
            }

            $pdo->beginTransaction();
            $resultado = $operation($pdo);
            $pdo->commit();
            
            return $resultado;
        } catch (\Exception $e) {
            $pdo = parent::getConexion();
            if ($pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \RuntimeException("Error en operación de base de datos: " . $e->getMessage());
        } finally {
            $this->cerrar();
        }
    }

    public function registrar() {
        return $this->r_registrar();
    }
    private function r_registrar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $this->fecha,
                $this->cliente,
                $this->descuento,
                $this->estatus
            ]);
            return $pdo->lastInsertId();
        });
    }

    public function agregarProducto($idFactura, $idProducto, $cantidad) {
        return $this->a_agregarProducto($idFactura, $idProducto, $cantidad);
    }
    private function a_agregarProducto($idFactura, $idProducto, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($idFactura, $idProducto, $cantidad){
            $sql = "INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad) 
                    VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idFactura, $idProducto, $cantidad]);
            return true;
        });
    }
    
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
                throw new PDOException("Transacción no válida.");
        }
    }

    private function facturaIngresar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            // Validar datos básicos antes de iniciar la transacción
            $erroresValidacion = $this->validarDatosRegistro();
            if (!empty($erroresValidacion)) {
                return ['error' => implode(' ', $erroresValidacion)];
            }

            // Buscar ID del cliente por su cédula
            $stmtCliente = $pdo->prepare("SELECT id_clientes FROM tbl_clientes WHERE cedula = ?");
            $stmtCliente->execute([$this->cliente]); // aquí $this->cliente sería la cédula
            $clienteData = $stmtCliente->fetch(PDO::FETCH_ASSOC);

            if (!$clienteData) {
                throw new PDOException("No se encontró un cliente con la cédula indicada.");
            }

            $id_cliente = $clienteData['id_clientes'];

            // Insertar en tabla factura
            $stmt = $pdo->prepare("INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus) VALUES (?, ?, ?, ?)");
            $stmt->execute([$this->fecha, $id_cliente, $this->descuento, $this->estatus]);

            $factura_id = $pdo->lastInsertId();
            if (!$factura_id) {
                throw new PDOException("No se pudo insertar la factura.");
            }

            // Validar que $this->id_producto y $this->cantidad sean arrays y tengan la misma longitud
            if (!is_array($this->id_producto) || !is_array($this->cantidad) || count($this->id_producto) !== count($this->cantidad)) {
                throw new PDOException("Datos de productos y cantidades inválidos o no coinciden.");
            }

            $detalle_insertados = 0;
            $stmt2 = $pdo->prepare("INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad) VALUES (?, ?, ?)");

            foreach ($this->id_producto as $index => $id_producto) {
                $cantidad = $this->cantidad[$index];

                if (empty($id_producto) || empty($cantidad)) {
                    throw new PDOException("Producto o cantidad vacío en el índice $index.");
                }

                $stmt2->execute([$factura_id, $id_producto, $cantidad]);
                $detalle_insertados += $stmt2->rowCount();
            }

            if ($detalle_insertados !== count($this->id_producto)) {
                throw new PDOException("No se insertaron todos los detalles de la factura.");
            }

            return true;
        });
    }

    public function obtenerUltimaFactura() {
        return $this->o_ultimaFactura();
    }
    private function o_ultimaFactura() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT MAX(id_factura) AS ultima_factura FROM tbl_facturas";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['ultima_factura'] : null;
        });
    }

    private function facturaConsultarTodas() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            // Primero obtenemos información de pagos para validar después
            $sqlPagos = "SELECT id_factura, estatus FROM tbl_detalles_pago";
            $stmtPagos = $pdo->prepare($sqlPagos);
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

            $stmt = $pdo->prepare($sqlDetalles);
            $stmt->execute();
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$detalles) {
                return ['resultado' => 'error', 'mensaje' => 'No hay facturas registradas.'];
            }
            
            try {
                $stmt = $pdo->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && (time() - strtotime($result['fecha'])) < 86400) {
                    $tasa = floatval($result['precio']);
                }
            } catch (PDOException $e) {
                error_log('Error al obtener cache del dólar: ' . $e->getMessage());
            }

            // Obtener los estatus y observaciones desde tbl_detalles_pago
            $stmtPagos = $pdo->prepare("SELECT id_factura, estatus, observaciones FROM tbl_detalles_pago");
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

                $datosCliente = '<p><strong>Cliente:</strong> ' . htmlspecialchars((string)($fila['nombre'] ?? '')) .  ' | ';
                $datosCliente .= '<strong>Cédula:</strong> ' . htmlspecialchars((string)($fila['cedula'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Teléfono:</strong> ' . htmlspecialchars((string)($fila['telefono'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Dirección:</strong> ' . htmlspecialchars((string)($fila['direccion'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Descuento:</strong> ' . htmlspecialchars((string)($fila['descuento'] ?? '')) . '% | ';
                
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
                            $mensajePago = '<div class="alert alert-danger"><strong>Pago incompleto:</strong> ' . htmlspecialchars((string)($observaciones ?? '')) . '</div>';
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
                            $mensajePago = '<div class="alert alert-danger"><strong>Pago no encontrado:</strong> ' . htmlspecialchars((string)($observaciones ?? '')) . '<br>Por favor, verifique los datos registrados en la pasarela de pago.</div>';
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
                    $html .= '<button class="accordion-button collapsed w-100  text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
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
        return $resultadoListado;
        });
    }

    private function facturaConsultar() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            if (empty($this->cedula)) {
                $pdo->cerrar();
                $pdo = null;
                return ['resultado' => 'error', 'mensaje' => 'No se ha proporcionado la cédula para consultar facturas.'];
            }
            
            // Primero obtenemos información de pagos para validar después
            $sqlPagos = "SELECT id_factura, estatus FROM tbl_detalles_pago";
            $stmtPagos = $pdo->prepare($sqlPagos);
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

            $stmt = $pdo->prepare($sqlDetalles);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->execute();
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$detalles) {
                return ['resultado' => 'error', 'mensaje' => 'No hay facturas registradas para esta cédula.'];
            }
            
            try {
                $stmt = $pdo->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && (time() - strtotime($result['fecha'])) < 86400) {
                    $tasa = floatval($result['precio']);
                }
            } catch (PDOException $e) {
                error_log('Error al obtener cache del dólar: ' . $e->getMessage());
            }
            
            // Obtener los estatus y observaciones desde tbl_detalles_pago
            $stmtPagos = $pdo->prepare("SELECT id_factura, estatus, observaciones FROM tbl_detalles_pago");
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

                $datosCliente = '<p><strong>Cliente:</strong> ' . htmlspecialchars((string)($fila['nombre'] ?? '')) .  ' | ';
                $datosCliente .= '<strong>Cédula:</strong> ' . htmlspecialchars((string)($fila['cedula'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Teléfono:</strong> ' . htmlspecialchars((string)($fila['telefono'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Dirección:</strong> ' . htmlspecialchars((string)($fila['direccion'] ?? '')) . ' | ';
                $datosCliente .= '<strong>Descuento:</strong> ' . htmlspecialchars((string)($fila['descuento'] ?? '')) . '% | ';
                
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
                            $mensajePago = '<div class="alert alert-danger"><strong>Pago incompleto:</strong> ' . htmlspecialchars((string)($observaciones ?? '')) . '</div>';
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
                            $mensajePago = '<div class="alert alert-danger"><strong>Pago no encontrado:</strong> ' . htmlspecialchars((string)($observaciones ?? '')) . '<br>Por favor, verifique los datos registrados en la pasarela de pago.</div>';
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

                // Definir tasa de conversión (1.0 por defecto para mantener los valores originales)
                $tasa = 1.0;
                
                $contenido = '<div class="w-100">' . $datosCliente . $mensajePago;
                $contenido .= '<div class="table-responsive w-100">';
                $contenido .= '<table class="table table-bordered w-100">';
                $contenido .= '<thead><tr><th>Producto</th><th>modelo</th><th>Marca</th><th>Cantidad</th><th>Precio</th></tr></thead><tbody>';

                $total = 0;
                foreach ($items as $item) {
                    $subtotal = $item['precio'] * $tasa * $item['cantidad'];
                    $total += $subtotal;

                    $contenido .= '<tr>';
                    $contenido .= '<td>' . htmlspecialchars($item['producto'] ?? '') . '</td>';
                    $contenido .= '<td>' . htmlspecialchars($item['nombre_modelo'] ?? '') . '</td>';
                    $contenido .= '<td>' . htmlspecialchars($item['nombre_marca'] ?? '') . '</td>';
                    $contenido .= '<td>' . ($item['cantidad'] ?? '0') . '</td>';
                    $contenido .= '<td>' . number_format(($item['precio'] ?? 0) * $tasa, 2) . ' BS</td>';
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
                    $html .= '<button class="accordion-button collapsed w-100  text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $id_factura . '" aria-expanded="false" aria-controls="collapse' . $id_factura . '">';
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

            $resulListado = [
                'resultado' => 'listado',
                'mensaje' => $html
            ];
        return $resulListado;
        });
    }
    
    public function facturaCancelar($id) {
        return $this->c_facturaCancelar($id);
    }
    private function c_facturaCancelar($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id){
            $stmt = $pdo->prepare("UPDATE tbl_facturas SET estatus = 'Cancelada' WHERE id_factura = ?");
            return $stmt->execute([$id]);
        });
    }

    public function facturaProcesar($id, $estatus) {
        return $this->p_facturaProcesar($id, $estatus);
    }
    private function p_facturaProcesar($id, $estatus) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id, $estatus){
            $stmt = $pdo->prepare("UPDATE tbl_facturas SET estatus = ? WHERE id_factura = ?");
            return $stmt->execute([$estatus, $id]);
        });
    }

    public function obtenerMontoTotalFactura($id_factura) {
        return $this->o_montoTotalFactura($id_factura);
    }
    private function o_montoTotalFactura($id_factura) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_factura) {
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
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_factura', $id_factura, PDO::PARAM_INT);
                $stmt->execute();
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$resultado) {
                    return 0;
                }

                $tasa = 1;
                try {
                    $stmtDolar = $pdo->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
                    $stmtDolar->execute();
                    $resultDolar = $stmtDolar->fetch(PDO::FETCH_ASSOC);
                    
                    if ($resultDolar && (time() - strtotime($resultDolar['fecha'])) < 86400) {
                        $tasa = floatval($resultDolar['precio']);
                    }
                } catch (PDOException $e) {
                    error_log('Error al obtener cache del dólar: ' . $e->getMessage());
                }

                $total = $resultado['total_con_impuesto'] * $tasa;
                return $total;

            } catch (PDOException $e) {
                error_log('Error al obtener monto total de factura: ' . $e->getMessage());
                return false;
            }
        });
    }
    
    public function facturaDescargar($id_factura) {
        return $this->f_descargar($id_factura);
    }
    private function f_descargar($id_factura) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Validar si la tasa está vigente (menos de 24 horas) o asignar una por defecto (1)
                $tasa = 1;
                if ($result && (time() - strtotime($result['fecha'])) < 86400) {
                    $tasa = floatval($result['precio']);
                }
            } catch (PDOException $e) {
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

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_factura', $id_factura, PDO::PARAM_INT);
            $stmt->bindParam(':tasa', $tasa);
            $stmt->execute();
            $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $facturas;
        });
    }
    
    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para el registro de una nueva factura
     */
    private function validarDatosRegistro() {
        $errores = [];
        
        // Validar fecha
        if (empty($this->fecha)) {
            $errores[] = 'La fecha es obligatoria';
        } elseif (!strtotime($this->fecha)) {
            $errores[] = 'La fecha no tiene un formato válido';
        }
        
        // Validar cliente (cedula)
        if (empty($this->cliente)) {
            $errores[] = 'La cédula del cliente es obligatoria';
        } elseif (mb_strlen($this->cliente) < self::MIN_CLIENTE || mb_strlen($this->cliente) > self::MAX_CLIENTE) {
            $errores[] = 'La cédula del cliente debe tener entre ' . self::MIN_CLIENTE . ' y ' . self::MAX_CLIENTE . ' caracteres';
        }
        
        // Validar descuento
        if (!is_numeric($this->descuento)) {
            $errores[] = 'El descuento debe ser un número';
        } elseif ($this->descuento < self::MIN_DESCUENTO || $this->descuento > self::MAX_DESCUENTO) {
            $errores[] = 'El descuento debe estar entre ' . self::MIN_DESCUENTO . ' y ' . self::MAX_DESCUENTO;
        }
        
        // Validar estatus
        if (empty($this->estatus)) {
            $errores[] = 'El estatus es obligatorio';
        } elseif (!in_array($this->estatus, self::ESTADOS_PERMITIDOS)) {
            $errores[] = 'El estatus no es válido. Valores permitidos: ' . implode(', ', self::ESTADOS_PERMITIDOS);
        }
        
        // Validar productos
        if (empty($this->id_producto) || !is_array($this->id_producto)) {
            $errores[] = 'Debe seleccionar al menos un producto';
        }
        
        // Validar cantidades
        if (empty($this->cantidad) || !is_array($this->cantidad)) {
            $errores[] = 'Debe especificar las cantidades de los productos';
        } elseif (count($this->id_producto) !== count($this->cantidad)) {
            $errores[] = 'El número de productos y cantidades debe coincidir';
        } else {
            // Validar cada cantidad
            foreach ($this->cantidad as $index => $cantidad) {
                if (!is_numeric($cantidad) || $cantidad < self::MIN_CANTIDAD || $cantidad > self::MAX_CANTIDAD) {
                    $errores[] = 'La cantidad del producto ' . ($index + 1) . ' debe estar entre ' . self::MIN_CANTIDAD . ' y ' . self::MAX_CANTIDAD;
                }
            }
        }
        
        return $errores;
    }

    /**
     * Valida los datos para consultar facturas
     */
    private function validarConsultar($datos) {
        $errores = [];
        
        // Validar ID de la factura (opcional)
        if (isset($datos['id_factura'])) {
            if (!is_numeric($datos['id_factura']) || $datos['id_factura'] <= 0) {
                $errores['id_factura'] = 'El ID de la factura debe ser un número positivo';
            }
        }
        
        // Validar cédula del cliente (opcional)
        if (isset($datos['cedula'])) {
            $cedula = trim($datos['cedula']);
            if (empty($cedula)) {
                $errores['cedula'] = 'La cédula del cliente es obligatoria';
            } elseif (mb_strlen($cedula) < self::MIN_CLIENTE || mb_strlen($cedula) > self::MAX_CLIENTE) {
                $errores['cedula'] = 'La cédula del cliente debe tener entre ' . self::MIN_CLIENTE . ' y ' . self::MAX_CLIENTE . ' caracteres';
            }
        }
        
        // Validar límite de resultados (opcional)
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }
        
        // Validar fecha de inicio (opcional)
        if (isset($datos['fecha_inicio'])) {
            $fechaInicio = trim($datos['fecha_inicio']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaInicio);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_inicio'] = 'La fecha de inicio no es válida';
                }
            }
        }
        
        // Validar fecha de fin (opcional)
        if (isset($datos['fecha_fin'])) {
            $fechaFin = trim($datos['fecha_fin']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaFin);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_fin'] = 'La fecha de fin no es válida';
                }
            }
        }
        
        // Validar que la fecha de fin no sea anterior a la de inicio
        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin']) && !isset($errores['fecha_inicio']) && !isset($errores['fecha_fin'])) {
            $fechaInicio = new \DateTime($datos['fecha_inicio']);
            $fechaFin = new \DateTime($datos['fecha_fin']);
            if ($fechaFin < $fechaInicio) {
                $errores['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
            }
        }
        
        // Validar estatus de la factura (opcional)
        if (isset($datos['estatus'])) {
            $estatus = trim($datos['estatus']);
            if (!empty($estatus) && !in_array($estatus, self::ESTADOS_PERMITIDOS)) {
                $errores['estatus'] = 'El estatus debe ser uno de: ' . implode(', ', self::ESTADOS_PERMITIDOS);
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para cancelar facturas
     */
    private function validarCancelar($datos) {
        $errores = [];
        
        // Validar ID de la factura
        if (!isset($datos['id_factura'])) {
            $errores['id_factura'] = 'El ID de la factura es obligatorio';
        } elseif (!is_numeric($datos['id_factura']) || $datos['id_factura'] <= 0) {
            $errores['id_factura'] = 'El ID de la factura debe ser un número positivo';
        }
        
        // Validar motivo de cancelación (opcional)
        if (isset($datos['motivo_cancelacion'])) {
            $motivo = trim($datos['motivo_cancelacion']);
            if (mb_strlen($motivo) > 200) {
                $errores['motivo_cancelacion'] = 'El motivo de cancelación no debe exceder los 200 caracteres';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para consultar (método público)
     */
    public function validarConsultarFacturas($datos) {
        return $this->validarConsultar($datos);
    }
    
    /**
     * Valida los datos para cancelar (método público)
     */
    public function validarCancelarFactura($datos) {
        return $this->validarCancelar($datos);
    }
    
    /**
     * Verifica si una factura existe por ID
     */
    private function verificarFacturaExistente($idFactura) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try{
                $sql = "SELECT COUNT(*) FROM tbl_facturas WHERE id_factura = :id_factura";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id_factura', $idFactura, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                error_log('Error en verificarFacturaExistente: ' . $e->getMessage());
                return false;
            }
        });
    }
    
    /**
     * Verifica si un cliente existe por cédula
     */
    private function verificarClienteExistente($cedula) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($cedula){
            try{
                $sql = "SELECT COUNT(*) FROM tbl_clientes WHERE cedula = :cedula AND activo = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':cedula', $cedula, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                error_log('Error en verificarClienteExistente: ' . $e->getMessage());
                return false;
            }
        });
    }
    
    /**
     * Verifica si una factura está en un estado que permite cancelación
     */
    private function verificarFacturaCancelable($idFactura) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try{
                $sql = "SELECT estatus FROM tbl_facturas WHERE id_factura = :id_factura";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id_factura', $idFactura, PDO::PARAM_INT);
                $stmt->execute();
                $estatus = $stmt->fetchColumn();
                return $estatus === 'Borrador';
            } catch (PDOException $e) {
                error_log('Error en verificarFacturaCancelable: ' . $e->getMessage());
                return false;
            }
        });
    }
}
?>