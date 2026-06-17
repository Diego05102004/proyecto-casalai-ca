<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;

class Comprafisica extends BD{
    private $idcliente;
    private $correlativo;
    private $desc;
    private $fecha;
    private $tablerecepcion = 'tbl_despachos';
    private $encryption;
    
    // Campos cifrados de clientes
    const CAMPOS_CIFRADOS_CLIENTES = ['nombre', 'direccion', 'telefono', 'correo'];
    
    // Constantes para validaciones
    const MAX_DESCRIPCION = 500;
    const MAX_REFERENCIA = 100;
    const MAX_CANTIDAD_PRODUCTO = 999999;
    const MAX_MONTO_PAGO = 99999999.99;
    const MIN_CANTIDAD_PRODUCTO = 0.01;
    const TIPOS_PAGO_PERMITIDOS = ['Efectivo', 'Transferencia', 'Zelle', 'Pago Movil', 'Tarjeta', 'Cheque'];
    const TIPOS_PAGO_CON_REFERENCIA = ['Transferencia', 'Zelle', 'Pago Movil'];
    const ESTADOS_PERMITIDOS = ['activo', 'inactivo', 'pendiente'];

    public function getidcliente() {
        return $this->idcliente;
    }
    public function setidcliente($idcliente) {
        $this->idcliente = $idcliente;
    }

    public function getfecha() {
        return $this->fecha;
    }
    public function setfecha($fecha) {
        $this->fecha = $fecha;
    }

    public function getdesc() {
        return $this->desc;
    }
    public function setdesc($desc) {
        $this->desc = $desc;
    }

    public function __construct($tipo = 'P') {
        $this->encryption = new Encryption();
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

    // ==================== VALIDACIONES DE BACKEND ====================
    
    private function sanitizarDatos($datos) {
        if (!is_array($datos)) {
            return $datos;
        }
        
        $datos_sanitizados = [];
        
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                $valor = trim($valor);
                
                $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
                
                $valor = addslashes($valor);
                
                $datos_sanitizados[$clave] = $valor;
            } else {
                $datos_sanitizados[$clave] = $valor;
            }
        }
        
        return $datos_sanitizados;
    }
    
    private function validarEsquema($datos, $operacion = 'registrar') {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['esquema'] = 'Los datos deben ser un array';
            return $errores;
        }
        
        if ($operacion === 'registrar') {
            // Validar campos obligatorios para registrar
            if (!isset($datos['cliente']) || $datos['cliente'] === '' || $datos['cliente'] === null) {
                $errores['cliente'] = 'El ID del cliente es obligatorio';
            }
            
            if (!isset($datos['productos']) || !is_array($datos['productos']) || empty($datos['productos'])) {
                $errores['productos'] = 'Debe agregar al menos un producto';
            }
            
            if (!isset($datos['pagos']) || !is_array($datos['pagos']) || empty($datos['pagos'])) {
                $errores['pagos'] = 'Debe registrar al menos un método de pago';
            }
        }
        
        return $errores;
    }
    
    private function validarFormato($datos) {
        $errores = [];
        
        // Validar ID del cliente
        if (isset($datos['cliente'])) {
            if (!is_numeric($datos['cliente']) || (int)$datos['cliente'] <= 0) {
                $errores['cliente'] = 'El ID del cliente debe ser un número positivo';
            }
        }
        
        // Validar productos
        if (isset($datos['productos']) && is_array($datos['productos'])) {
            foreach ($datos['productos'] as $index => $producto) {
                if (!is_array($producto)) {
                    $errores["productos_$index"] = 'El producto en la posición ' . $index . ' debe ser un array';
                    continue;
                }
                
                // Validar ID del producto
                if (!isset($producto['id_producto']) || !is_numeric($producto['id_producto']) || $producto['id_producto'] <= 0) {
                    $errores["productos_{$index}_id_producto"] = 'El ID del producto en la posición ' . $index . ' debe ser un número positivo';
                }
                
                // Validar cantidad
                if (!isset($producto['cantidad'])) {
                    $errores["productos_{$index}_cantidad"] = 'La cantidad del producto en la posición ' . $index . ' es obligatoria';
                } else {
                    $cantidad = (float)$producto['cantidad'];
                    if ($cantidad <= self::MIN_CANTIDAD_PRODUCTO) {
                        $errores["productos_{$index}_cantidad"] = 'La cantidad debe ser mayor a ' . self::MIN_CANTIDAD_PRODUCTO;
                    } elseif ($cantidad > self::MAX_CANTIDAD_PRODUCTO) {
                        $errores["productos_{$index}_cantidad"] = 'La cantidad no debe exceder ' . self::MAX_CANTIDAD_PRODUCTO;
                    }
                }
            }
        }
        
        // Validar pagos
        if (isset($datos['pagos']) && is_array($datos['pagos'])) {
            foreach ($datos['pagos'] as $index => $pago) {
                if (!is_array($pago)) {
                    $errores["pagos_$index"] = 'El pago en la posición ' . $index . ' debe ser un array';
                    continue;
                }
                
                // Validar tipo de pago
                if (!isset($pago['tipo']) || !is_string($pago['tipo'])) {
                    $errores["pagos_{$index}_tipo"] = 'El tipo de pago en la posición ' . $index . ' es obligatorio';
                } else {
                    $tipo = trim($pago['tipo']);
                    if (empty($tipo)) {
                        $errores["pagos_{$index}_tipo"] = 'El tipo de pago en la posición ' . $index . ' no puede estar vacío';
                    } elseif (!in_array($tipo, self::TIPOS_PAGO_PERMITIDOS)) {
                        $errores["pagos_{$index}_tipo"] = 'El tipo de pago debe ser uno de: ' . implode(', ', self::TIPOS_PAGO_PERMITIDOS);
                    }
                }
                
                // Validar monto
                if (!isset($pago['monto'])) {
                    $errores["pagos_{$index}_monto"] = 'El monto del pago en la posición ' . $index . ' es obligatorio';
                } else {
                    $monto = (float)$pago['monto'];
                    if ($monto <= 0) {
                        $errores["pagos_{$index}_monto"] = 'El monto debe ser mayor a 0';
                    } elseif ($monto > self::MAX_MONTO_PAGO) {
                        $errores["pagos_{$index}_monto"] = 'El monto no debe exceder ' . self::MAX_MONTO_PAGO;
                    }
                }
                
                // Validar referencia para tipos que lo requieren
                if (isset($pago['tipo']) && in_array($pago['tipo'], self::TIPOS_PAGO_CON_REFERENCIA)) {
                    if (!isset($pago['referencia'])) {
                        $errores["pagos_{$index}_referencia"] = 'La referencia es obligatoria para el tipo ' . $pago['tipo'];
                    } else {
                        $referencia = trim($pago['referencia']);
                        if (empty($referencia)) {
                            $errores["pagos_{$index}_referencia"] = 'La referencia no puede estar vacía';
                        } elseif (mb_strlen($referencia) > self::MAX_REFERENCIA) {
                            $errores["pagos_{$index}_referencia"] = 'La referencia no debe exceder los ' . self::MAX_REFERENCIA . ' caracteres';
                        }
                    }
                }
            }
        }
        
        // Validar monto total (opcional)
        if (isset($datos['monto_total'])) {
            $montoTotal = (float)$datos['monto_total'];
            if ($montoTotal <= 0) {
                $errores['monto_total'] = 'El monto total debe ser mayor a 0';
            } elseif ($montoTotal > self::MAX_MONTO_PAGO * 10) {
                $errores['monto_total'] = 'El monto total es muy grande';
            }
        }
        
        // Validar cambio (opcional)
        if (isset($datos['cambio'])) {
            $cambio = (float)$datos['cambio'];
            if ($cambio < 0) {
                $errores['cambio'] = 'El cambio no puede ser negativo';
            } elseif ($cambio > self::MAX_MONTO_PAGO) {
                $errores['cambio'] = 'El cambio es muy grande';
            }
        }
        
        // Validar descripción (opcional)
        if (isset($datos['descripcion'])) {
            $descripcion = trim($datos['descripcion']);
            if (mb_strlen($descripcion) > self::MAX_DESCRIPCION) {
                $errores['descripcion'] = 'La descripción no debe exceder los ' . self::MAX_DESCRIPCION . ' caracteres';
            }
        }
        return $errores;
    }
    
    private function validarFiltros($filtros) {
        $errores = [];
        
        if (isset($filtros['limite'])) {
            $limite = (int)$filtros['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe estar entre 1 y 100 registros';
            }
        }
        
        if (isset($filtros['pagina'])) {
            $pagina = (int)$filtros['pagina'];
            if ($pagina < 1) {
                $errores['pagina'] = 'La página debe ser un número positivo';
            }
        }
        
        if (isset($filtros['id_cliente'])) {
            $id_cliente = $filtros['id_cliente'];
            if (!is_numeric($id_cliente) || (int)$id_cliente <= 0) {
                $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
            }
        }
        
        if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
            $fecha_inicio = strtotime($filtros['fecha_inicio']);
            $fecha_fin = strtotime($filtros['fecha_fin']);
            
            if ($fecha_inicio && $fecha_fin) {
                if ($fecha_inicio > $fecha_fin) {
                    $errores['fechas'] = 'La fecha de inicio no puede ser mayor a la fecha fin';
                }
                
                $dias_diferencia = ($fecha_fin - $fecha_inicio) / (60 * 60 * 24);
                if ($dias_diferencia > 365) {
                    $errores['rango_fechas'] = 'El rango de fechas no puede exceder 365 días';
                }
            }
        }
        
        if (isset($filtros['busqueda']) && is_string($filtros['busqueda'])) {
            $busqueda = trim($filtros['busqueda']);
            if (mb_strlen($busqueda) > 100) {
                $errores['busqueda'] = 'La búsqueda no debe exceder los 100 caracteres';
            }
        }
        
        return $errores;
    }
    
    public function validarId($id_venta) {
        $errores = [];
        
        if ($id_venta === null || $id_venta === '') {
            $errores['id_venta'] = 'El ID de la venta es obligatorio';
        } elseif (!is_numeric($id_venta) || (int)$id_venta <= 0) {
            $errores['id_venta'] = 'El ID de la venta debe ser un número positivo';
        }
        
        return $errores;
    }
    
    private function obtenerVentaPorId($id_venta) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_venta) {
            $sql = "SELECT * FROM {$this->tablerecepcion} WHERE id_despachos = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_venta]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }
    
    public function validarRegistrar($datos) {
        $datos = $this->sanitizarDatos($datos);
        
        $errores = $this->validarEsquema($datos, 'registrar');
        if (!empty($errores)) {
            return $errores;
        }
        
        $errores = $this->validarFormato($datos);
        if (!empty($errores)) {
            return $errores;
        }
        
        // TODO: Implementar verificación de cliente cuando el método esté disponible
        // $cliente_existe = $this->verificarClienteExistente($datos['cliente']);
        // if (!$cliente_existe) {
        //     $errores['cliente'] = 'El cliente seleccionado no existe';
        // }
        
        // TODO: Implementar verificación de productos cuando el método esté disponible
        // if (isset($datos['productos']) && is_array($datos['productos'])) {
        //     foreach ($datos['productos'] as $index => $producto) {
        //         if (isset($producto['id_producto'])) {
        //             $producto_existe = $this->verificarProductoExistente($producto['id_producto']);
        //             if (!$producto_existe) {
        //                 $errores["productos_{$index}_id_producto"] = 'El producto en la posición ' . $index . ' no existe';
        //             }
        //         }
        //     }
        // }
        
        return $errores;
    }
    
    public function validarConsultar($filtros = []) {
        $filtros_default = [
            'pagina' => 1,
            'limite' => 50,
            'orden' => 'fecha',
            'direccion' => 'DESC'
        ];
        
        $filtros = array_merge($filtros_default, $filtros);
        
        return $this->validarFiltros($filtros);
    }
    
    public function validarDetallar($id_venta) {
        $errores = $this->validarId($id_venta);
        if (!empty($errores)) {
            return $errores;
        }
        
        $venta = $this->obtenerVentaPorId($id_venta);
        if (!$venta) {
            $errores['existencia'] = 'La venta solicitada no existe';
        }
        
        return $errores;
    }

    public function registrarCompraFisica($datos) {
        return $this->r_compraFisica($datos);
    }

    private function r_compraFisica($datos) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($datos){
            try{
                error_log("[COMPRFISICA-MODELO] Iniciando r_compraFisica");
                error_log("[COMPRFISICA-MODELO] Datos recibidos: " . json_encode($datos));
                
                // Solo iniciar transacción si no hay una activa
                $transaccionIniciada = false;
                if (!$pdo->inTransaction()) {
                    $pdo->beginTransaction();
                    $transaccionIniciada = true;
                    error_log("[COMPRFISICA-MODELO] Transacción iniciada");
                } else {
                    error_log("[COMPRFISICA-MODELO] Usando transacción existente");
                }

                // 1️⃣ Crear despacho
                $sqlDespacho = "INSERT INTO tbl_despachos (id_clientes, fecha_despacho, tipocompra, activo) 
                                VALUES (:id_cliente, :fecha, :tipocompra, 1)";
                $stmt = $pdo->prepare($sqlDespacho);
                $stmt->execute([
                    ':id_cliente' => $datos['cliente'],
                    ':fecha' => date('Y-m-d'),
                    ':tipocompra' => 'Presencial',
                ]);
                $idDespacho = $pdo->lastInsertId();

                $descripcion = "Venta: ";
                $monto_total = 0;
                $productosVenta = [];

                // 2️⃣ Insertar productos en despacho + preparar para factura
                foreach ($datos['productos'] as $p) {
                    $cantidad = $this->parsearCantidadFormateada($p['cantidad']);

                    // Insertar detalle de despacho
                    $sqlDetalle = "INSERT INTO tbl_despacho_detalle (id_despacho, id_producto, cantidad) 
                                VALUES (:id_despacho, :id_producto, :cantidad)";
                    $stmtDet = $pdo->prepare($sqlDetalle);
                    $stmtDet->execute([
                        ':id_despacho' => $idDespacho,
                        ':id_producto' => $p['id_producto'],
                        ':cantidad' => $cantidad
                    ]);

                    // Consultar producto
                    $stmtProd = $pdo->prepare("
                        SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca,
                            p.serial, p.precio
                        FROM tbl_productos p
                        INNER JOIN tbl_modelos m ON p.id_modelo = m.id_modelo
                        INNER JOIN tbl_marcas mar ON m.id_marca = mar.id_marca
                        WHERE p.id_producto = ?
                    ");
                    $stmtProd->execute([$p['id_producto']]);
                    $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

                    if ($prod) {
                        $subtotal = floatval($prod['precio']) * $cantidad;
                        $monto_total += $subtotal;

                        // DESCRIPCIÓN → nombre (xCantidad)
                        $descripcion .= "{$prod['nombre_producto']} ({$cantidad}), ";

                        $productosVenta[] = [
                            'id_producto' => $prod['id_producto'],
                            'codigo' => $prod['id_producto'],
                            'nombre' => $prod['nombre_producto'],
                            'modelo' => $prod['nombre_modelo'],
                            'marca' => $prod['nombre_marca'],
                            'serial' => $prod['serial'],
                            'precio' => $prod['precio'],
                            'cantidad' => $cantidad,
                            'subtotal' => $subtotal
                        ];
                    }
                }

                $descripcion = rtrim($descripcion, ', ');

                // 3️⃣ Crear factura
                $sqlFactura = "INSERT INTO tbl_facturas (cliente, fecha, descuento, estatus) 
                            VALUES (:cliente, :fecha, 0, 'Pagada en Oficina')";
                $stmtFactura = $pdo->prepare($sqlFactura);
                $stmtFactura->execute([
                    ':cliente' => $datos['cliente'],
                    ':fecha' => date('Y-m-d')
                ]);
                $idFactura = $pdo->lastInsertId();

                // 4️⃣ Factura detalle
                foreach ($productosVenta as $prod) {
                    $sqlFacturaDet = "INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad)
                                    VALUES (:factura_id, :id_producto, :cantidad)";
                    $stmtFacturaDet = $pdo->prepare($sqlFacturaDet);
                    $stmtFacturaDet->execute([
                        ':factura_id' => $idFactura,
                        ':id_producto' => $prod['id_producto'],
                        ':cantidad' => $prod['cantidad']
                    ]);
                }

                // 5️⃣ Cliente
                $stmtCliente = $pdo->prepare("
                    SELECT id_clientes, nombre, cedula, telefono, correo 
                    FROM tbl_clientes 
                    WHERE id_clientes = ?
                ");
                $stmtCliente->execute([$datos['cliente']]);
                $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

                // 6️⃣ Registrar pagos
                $pagosVenta = [];
                if (!empty($datos['pagos'])) {
                    foreach ($datos['pagos'] as $pago) {
                        $sqlPago = "INSERT INTO tbl_detalles_pago 
                                    (id_factura, tipo, id_cuenta, referencia, monto, comprobante, fecha) 
                                    VALUES (:id_factura, :tipo, :id_cuenta, :referencia, :monto, :comprobante, NOW())";
                        $stmtPago = $pdo->prepare($sqlPago);
                        $stmtPago->execute([
                            ':id_factura' => $idFactura,
                            ':tipo' => $pago['tipo'],
                            ':id_cuenta' => $pago['cuenta'] ?? null,
                            ':referencia' => $pago['referencia'] ?? null,
                            ':monto' => $pago['monto'],
                            ':comprobante' => $pago['comprobante'] ?? null
                        ]);

                        $pagosVenta[] = [
                            'tipo' => $pago['tipo'] ?? '',
                            'monto' => $pago['monto'] ?? 0,
                            'referencia' => $pago['referencia'] ?? '',
                            'id_cuenta' => $pago['id_cuenta'] ?? '',
                            'comprobante' => $pago['comprobante'] ?? '',
                            'estatus' => 'Procesado'
                        ];
                    }
                }

                // 7️⃣ Ingresos → CORREGIDO COMPLETAMENTE
                $sqlFinanzas = "INSERT INTO tbl_ingresos_egresos (
                    id_despacho,
                    tipo,
                    monto,
                    descripcion,
                    fecha,
                    estado
                ) VALUES (
                    :id_despacho,
                    :tipo,
                    :monto,
                    :descripcion,
                    NOW(),
                    1
                )";

                $stmtFinanzas = $pdo->prepare($sqlFinanzas);
                $stmtFinanzas->execute([
                    ':id_despacho' => $idDespacho,
                    ':tipo' => 'ingreso',
                    ':monto' => $monto_total,
                    ':descripcion' => 'Venta presencial #' . $idFactura . ' - ' . $descripcion
                ]);

                // 8️⃣ Respuesta para AJAX
                $resultado = [
                    'id_factura' => $idFactura,
                    'fecha_factura' => date('Y-m-d'),
                    'nombre_cliente' => $cliente['nombre'] ?? '',
                    'cedula' => $cliente['cedula'] ?? '',
                    'telefono' => $cliente['telefono'] ?? '',
                    'correo' => $cliente['correo'] ?? '',
                    'productos' => $productosVenta,
                    'pagos' => $pagosVenta,
                    'total' => $monto_total
                ];

                error_log("[COMPRFISICA-MODELO] Operación exitosa, preparando respuesta");
                // Solo hacer commit si iniciamos la transacción
                if ($transaccionIniciada && $pdo->inTransaction()) {
                    $pdo->commit();
                    error_log("[COMPRFISICA-MODELO] Transacción confirmada");
                }
                return $resultado;

            } catch (Exception $e) {
                error_log("[COMPRFISICA-MODELO] Error capturado: " . $e->getMessage());
                error_log("[COMPRFISICA-MODELO] Stack trace: " . $e->getTraceAsString());
                if ($transaccionIniciada && $pdo->inTransaction()) {
                    $pdo->rollBack();
                    error_log("[COMPRFISICA-MODELO] Transacción revertida");
                }
                return [
                    'status' => 'error',
                    'mensaje' => $e->getMessage()
                ];
            }
        });
    }


    public function parsearCantidadFormateada($cantidadFormateada) {
        return $this->parsearCantidadForm($cantidadFormateada);
    }
    private function parsearCantidadForm($cantidadFormateada) {
        if (is_numeric($cantidadFormateada)) {
            return floatval($cantidadFormateada);
        }
        
        // Remover puntos de miles y convertir coma decimal a punto
        $cantidadLimpia = str_replace('.', '', $cantidadFormateada);
        $cantidadLimpia = str_replace(',', '.', $cantidadLimpia);
        
        return floatval($cantidadLimpia);
    }

    public function obtenercliente() {
        return $this->obt_cliente();
    }
    private function obt_cliente() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $p = $pdo->prepare("SELECT id_clientes, nombre, cedula FROM tbl_clientes WHERE activo = 1 ORDER BY nombre");
            $p->execute();
            $rows = $p->fetchAll(PDO::FETCH_ASSOC);
            return $rows;
        });
        
        // Descifrar datos personales de los clientes
        if (is_array($resultado) && !empty($resultado)) {
            $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS_CLIENTES);
        }
        
        return $resultado;
    }

    public function listadoproductos() {
        return $this->list_productos(); 
    }
    private function list_productos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $resultado = $pdo->query("SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial,p.precio
                FROM tbl_productos AS p 
                INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca;");
            
            if($resultado){
                $respuesta = '';
                $totalColumnas = 6; // Número de columnas de la tabla
                $totalFilas = 0;
                foreach($resultado as $r){
                    $respuesta .= "<tr style='cursor:pointer' onclick='colocaproducto(this);'>";
                    $respuesta .= "<td style='display:none'>{$r['id_producto']}</td>";
                    $respuesta .= "<td>{$r['id_producto']}</td>";
                    $respuesta .= "<td>{$r['nombre_producto']}</td>";
                    $respuesta .= "<td>{$r['nombre_modelo']}</td>";
                    $respuesta .= "<td>{$r['nombre_marca']}</td>";
                    $respuesta .= "<td>{$r['precio']}</td>";
                    $respuesta .= "</tr>";
                    $totalFilas++;
                }
                
                $modalSize = 'modal-md';
                if ($totalFilas > 8) {
                    $modalSize = 'modal-lg';
                }
                if ($totalFilas > 20) {
                    $modalSize = 'modal-xl';
                }
            }
            $r = [
                'resultado' => 'listado',
                'mensaje' => $respuesta,
                'modalSize' => isset($modalSize) ? $modalSize : 'modal-md'
            ];
            return $r;
        });
    }

    public function buscarClientes($query) {
        return $this->buscar_clientes($query);
    }

    private function buscar_clientes($query) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($query) {
            $sql = "SELECT id_clientes, nombre, cedula, telefono 
                    FROM tbl_clientes 
                    WHERE activo = 1 
                    AND (nombre LIKE :query OR cedula LIKE :query)
                    ORDER BY 
                        CASE 
                            WHEN nombre LIKE :query_exact THEN 1
                            WHEN cedula LIKE :query_exact THEN 2
                            ELSE 3
                        END,
                        nombre
                    LIMIT 20";
            
            $stmt = $pdo->prepare($sql);
            $searchTerm = "%$query%";
            $exactTerm = "$query%";
            
            $stmt->execute([
                ':query' => $searchTerm,
                ':query_exact' => $exactTerm
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows;
        });
        
        // Descifrar datos personales de los clientes
        if (is_array($resultado) && !empty($resultado)) {
            $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS_CLIENTES);
        }
        
        return $resultado;
    }

    public function consultarproductos() {
        return $this->consul_productos(); 
    }
    private function consul_productos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
        $stmt = $pdo->prepare("
            SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial, p.precio
            FROM tbl_productos AS p 
            INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
            INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca;
        ");
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tasa = 1; // Valor por defecto si no se encuentra la tasa

        try {
            $conexionCache = new BD('P');
            $db = $conexionCache->getConexion();
            
            $stmtCache = $db->prepare("SELECT precio, fecha FROM dolar_cache ORDER BY fecha DESC LIMIT 1");
            $stmtCache->execute();
            
            $result = $stmtCache->fetch(PDO::FETCH_ASSOC);
            
            if ($result && (time() - strtotime($result['fecha'])) < 86400) { // Cache válida si tiene menos de 24 horas
                $tasa = floatval($result['precio']);
            }
        } catch (Exception $e) {
            error_log('Error al obtener cache del dólar: ' . $e->getMessage());
        }

        // Multiplicar el precio por la tasa
        foreach ($registros as &$producto) {
            $producto['precio'] = floatval($producto['precio']) * $tasa;
        }

        return $registros;
        });
    }

    public function getCompras() {
        return $this->g_Compras(); 
    }
    private function g_Compras() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "
                SELECT 
                    f.id_factura,
                    f.fecha AS fecha_factura,
                    f.descuento,
                    c.id_clientes,
                    c.cedula,
                    c.nombre AS nombre_cliente,
                    c.direccion,
                    c.telefono,
                    c.correo,
                    d.id_despachos,
                    
                    -- Productos agrupados como JSON
                    GROUP_CONCAT(
                        CONCAT(
                            '{',
                            '\"id_producto\":\"', p.id_producto, '\",',
                            '\"codigo\":\"', p.id_producto, '\",',
                            '\"nombre\":\"', p.nombre_producto, '\",',
                            '\"descripcion\":\"', p.descripcion_producto, '\",',
                            '\"modelo\":\"', m.nombre_modelo, '\",',
                            '\"marca\":\"', mar.nombre_marca, '\",',
                            '\"serial\":\"', p.serial, '\",',
                            '\"precio\":\"', p.precio, '\",',
                            '\"cantidad\":\"', fd.cantidad, '\"',
                            '}'
                        ) SEPARATOR ','
                    ) AS productos,

                    -- Pagos agrupados como JSON
                    GROUP_CONCAT(
                        CONCAT(
                            '{',
                            '\"id_detalles\":\"', dp.id_detalles, '\",',
                            '\"cuenta\":\"', dp.id_cuenta, '\",',
                            '\"referencia\":\"', dp.referencia, '\",',
                            '\"fecha\":\"', dp.fecha, '\",',
                            '\"tipo\":\"', dp.tipo, '\",',
                            '\"monto\":\"', dp.monto, '\",',
                            '\"comprobante\":\"', COALESCE(dp.comprobante, ''), '\",',
                            '\"estatus\":\"', dp.estatus, '\",',
                            '\"observaciones\":\"', COALESCE(dp.observaciones, ''), '\"',
                            '}'
                        ) SEPARATOR ','
                    ) AS pagos

                FROM tbl_facturas f
                INNER JOIN tbl_clientes c ON f.cliente = c.id_clientes
                INNER JOIN tbl_despachos d ON d.id_clientes = c.id_clientes AND d.fecha_despacho = f.fecha
                INNER JOIN tbl_despacho_detalle dd ON d.id_despachos = dd.id_despacho
                INNER JOIN tbl_productos p ON dd.id_producto = p.id_producto
                INNER JOIN tbl_modelos m ON p.id_modelo = m.id_modelo
                INNER JOIN tbl_marcas mar ON m.id_marca = mar.id_marca
                INNER JOIN tbl_factura_detalle fd ON f.id_factura = fd.factura_id AND p.id_producto = fd.id_producto
                INNER JOIN tbl_detalles_pago dp ON f.id_factura = dp.id_factura

                GROUP BY f.id_factura
                ORDER BY f.fecha DESC, f.id_factura DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows;
        });
        
        // Descifrar datos personales de los clientes
        if (is_array($resultado) && !empty($resultado)) {
            // Incluir alias en la lista de campos a descifrar
            $camposADescifrar = array_merge(self::CAMPOS_CIFRADOS_CLIENTES, ['nombre_cliente']);
            $resultado = $this->encryption->decryptResults($resultado, $camposADescifrar);
        }
        
        return $resultado;
    }

    public function getdespacho() {
        return $this->g_despacho(); 
    }
    private function g_despacho() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $querydespachos = 
            'SELECT 
                d.id_detalle,
                r.id_despachos,
                pro.id_producto,
                c.id_clientes,
                r.fecha_despacho,
                c.nombre AS nombre_cliente,
                pro.nombre_producto,
                d.cantidad
            FROM tbl_despachos AS r
            INNER JOIN tbl_despacho_detalle AS d ON d.id_despacho = r.id_despachos
            INNER JOIN tbl_clientes AS c ON c.id_clientes = r.id_clientes
            INNER JOIN tbl_productos AS pro ON pro.id_producto = d.id_producto
            ORDER BY r.id_despachos DESC;
            ';
            $stmtdespachos = $pdo->prepare($querydespachos);
            $stmtdespachos->execute();
            $despachos = $stmtdespachos->fetchAll(PDO::FETCH_ASSOC);
            return $despachos;
        });
        
        // Descifrar datos personales de los clientes
        if (is_array($resultado) && !empty($resultado)) {
            $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS_CLIENTES);
        }
        
        return $resultado;
    }

    public function getDetallesCompra($idDespacho) {
        return $this->g_detallesCompra($idDespacho); 
    }
    private function g_detallesCompra($idDespacho) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sqlProductos = "
                SELECT p.nombre_producto, d.cantidad, p.precio, (d.cantidad * p.precio) AS subtotal
                FROM tbl_despacho_detalle d
                INNER JOIN tbl_productos p ON p.id_producto = d.id_producto
                WHERE d.id_despacho = ?
            ";
            $stmtProd = $pdo->prepare($sqlProductos);
            $stmtProd->execute([$idDespacho]);
            $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

            // Pagos
            $sqlPagos = "
                SELECT dp.tipo, dp.monto, dp.fecha, dp.referencia, dp.comprobante
                FROM tbl_detalles_pago dp
                INNER JOIN tbl_facturas f ON f.id_factura = dp.id_factura
                WHERE f.id_despacho = ?
            ";
            $stmtPagos = $pdo->prepare($sqlPagos);
            $stmtPagos->execute([$idDespacho]);
            $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

            $ret = [
                'productos' => $productos,
                'pagos' => $pagos
            ];
        });
    }

    public function obtenerDetallesPorDespacho($idDespacho) {
        return $this->obt_detallesPorDespacho($idDespacho); 
    }
    private function obt_detallesPorDespacho($idDespacho) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $this->conex->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Consultar productos de esa recepción
                $sql = "SELECT dr.id_producto, dr.cantidad, dr.costo, p.nombre 
                        FROM tbl_detalle_recepcion_productos dr
                        INNER JOIN tbl_productos p ON dr.id_producto = p.id
                        WHERE dr.id_recepcion = :idRecepcion";

                $stmt = $this->conex->prepare($sql);
                $stmt->bindParam(':idRecepcion', $idDespacho, PDO::PARAM_INT);
                $stmt->execute();
                $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Consultar todos los productos (para el <select>)
                $sqlProductos = "SELECT id, nombre FROM tbl_productos";
                $productosTodos = $this->conex->query($sqlProductos)->fetchAll(PDO::FETCH_ASSOC);

                // Agregar opciones al array de productos
                foreach ($productos as &$producto) {
                    $opciones = '';
                    foreach ($productosTodos as $item) {
                        $selected = ($item['id'] == $producto['id_producto']) ? 'selected' : '';
                        $opciones .= "<option value='{$item['id']}' $selected>{$item['nombre']}</option>";
                    }
                    $producto['opciones'] = $opciones;
                }

                $datos = $productos;

            } catch (Exception $e) {
                $datos = [
                    'error' => true,
                    'mensaje' => $e->getMessage()
                ];
            } finally {
                if ($conexion) { $conexion->cerrar(); $this->conex = null; }
            }

            return $datos;
        });
    }
}
?>