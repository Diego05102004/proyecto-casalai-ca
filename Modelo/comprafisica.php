<?php
require_once 'Config/Config.php';

class Compra extends BD{
    private $idcliente;
    private $correlativo;
    private $desc;
    private $fecha;
    private $tablerecepcion = 'tbl_despachos';

    public function __construct() {
        parent::__construct();
    }

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

   public function registrarCompraFisica($datos) {
    return $this->r_compraFisica($datos);
    }

    private function r_compraFisica($datos) {
        $d = [];
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        $co->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $co->beginTransaction();

            // 1️⃣ Insertar despacho
            $sqlDespacho = "INSERT INTO tbl_despachos (id_clientes, fecha_despacho, activo) 
                            VALUES (:id_cliente, :fecha, 1)";
            $stmt = $co->prepare($sqlDespacho);
            $stmt->execute([
                ':id_cliente' => $datos['cliente'],
                ':fecha' => date('Y-m-d'),
            ]);
            $idDespacho = $co->lastInsertId();

            $descripcion = "Venta: ";
            $monto_total = 0;
            $productosVenta = [];

            // 2️⃣ Insertar productos en tbl_despacho_detalle y preparar para factura_detalle
            foreach ($datos['productos'] as $p) {
                $cantidad = $this->parsearCantidadFormateada($p['cantidad']);
                
                // Insertar en despacho_detalle
                $sqlDetalle = "INSERT INTO tbl_despacho_detalle (id_despacho, id_producto, cantidad) 
                            VALUES (:id_despacho, :id_producto, :cantidad)";
                $stmtDet = $co->prepare($sqlDetalle);
                $stmtDet->execute([
                    ':id_despacho' => $idDespacho,
                    ':id_producto' => $p['id_producto'],
                    ':cantidad' => $cantidad
                ]);

                // Obtener información del producto
                $stmtProd = $co->prepare("
                    SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial, p.precio
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
                    $descripcion .= "{$prod['nombre_producto']} (x{$cantidad}), ";

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

            // 3️⃣ Insertar en tbl_facturas
            $sqlFactura = "INSERT INTO tbl_facturas (cliente, fecha, descuento) 
                        VALUES (:cliente, :fecha, 0)";
            $stmtFactura = $co->prepare($sqlFactura);
            $stmtFactura->execute([
                ':cliente' => $datos['cliente'],
                ':fecha' => date('Y-m-d'),
            ]);
            $idFactura = $co->lastInsertId();

            // 4️⃣ Insertar en tbl_factura_detalle
            foreach ($productosVenta as $prod) {
                $sqlFacturaDet = "INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad) 
                                VALUES (:factura_id, :id_producto, :cantidad)";
                $stmtFacturaDet = $co->prepare($sqlFacturaDet);
                $stmtFacturaDet->execute([
                    ':factura_id' => $idFactura,
                    ':id_producto' => $prod['id_producto'],
                    ':cantidad' => $prod['cantidad']
                ]);
            }

            // 5️⃣ Obtener datos del cliente
            $stmtCliente = $co->prepare("
                SELECT id_clientes, nombre, cedula, telefono, correo 
                FROM tbl_clientes 
                WHERE id_clientes = ?
            ");
            $stmtCliente->execute([$datos['cliente']]);
            $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

            // 6️⃣ Insertar pagos si existen
            $pagosVenta = [];
            if (!empty($datos['pagos'])) {
                foreach ($datos['pagos'] as $pago) {
                    // Insertar en tbl_detalles_pago
                    $sqlPago = "INSERT INTO tbl_detalles_pago (id_factura, tipo, id_cuenta, referencia, monto, comprobante, fecha) 
                                VALUES (:id_factura, :tipo, :id_cuenta, :referencia, :monto, :comprobante, NOW())";
                    $stmtPago = $co->prepare($sqlPago);
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
                        'estatus' => 'Aprobado'
                    ];
                }
            }

            // 7️⃣ Preparar datos para retornar al AJAX - ESTRUCTURA CORRECTA
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

            $co->commit();
            return $resultado;

        } catch (Exception $e) {
            if ($co->inTransaction()) {
                $co->rollBack();
            }
            return [
                'status' => 'error',
                'mensaje' => $e->getMessage()
            ];
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
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
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $co->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $p = $co->prepare("SELECT id_clientes, nombre, cedula FROM tbl_clientes WHERE activo = 1 ORDER BY nombre");
            $p->execute();
            return $p->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function listadoproductos() {
        return $this->list_productos(); 
    }
    private function list_productos() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $co->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $resultado = $co->query("SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial, p.precio
                FROM tbl_productos AS p 
                INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca;");
            
            $respuesta = '';
            $totalFilas = 0;
            
            if($resultado){
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
            
            return [
                'resultado' => 'listado',
                'mensaje' => $respuesta,
                'modalSize' => $modalSize ?? 'modal-md'
            ];
        } catch(Exception $e) {
            return [
                'resultado' => 'error',
                'mensaje' => $e->getMessage(),
                'modalSize' => 'modal-md'
            ];
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function buscarClientes($query) {
        return $this->buscar_clientes($query);
    }

    private function buscar_clientes($query) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $co->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
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
            
            $stmt = $co->prepare($sql);
            $searchTerm = "%$query%";
            $exactTerm = "$query%";
            
            $stmt->execute([
                ':query' => $searchTerm,
                ':query_exact' => $exactTerm
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function consultarproductos() {
        return $this->consul_productos(); 
    }
    private function consul_productos() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            // Obtener tasa de cambio del dólar
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
                
                $conexionCache->cerrar();
            } catch (Exception $e) {
                error_log('Error al obtener cache del dólar: ' . $e->getMessage());
            }

            // Obtener productos con precios convertidos
            $stmt = $co->prepare("
                SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial, 
                       (p.precio * :tasa) as precio
                FROM tbl_productos AS p 
                INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca;
            ");
            
            $stmt->execute([':tasa' => $tasa]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }



    public function getCompras() {
        return $this->g_Compras(); 
    }
    private function g_Compras() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
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

            $stmt = $co->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function getdespacho() {
        return $this->g_despacho(); 
    }
    private function g_despacho() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
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
            ORDER BY r.id_despachos DESC;';
            
            $stmtdespachos = $co->prepare($querydespachos);
            $stmtdespachos->execute();
            return $stmtdespachos->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function getDetallesCompra($idDespacho) {
        return $this->g_detallesCompra($idDespacho); 
    }
    private function g_detallesCompra($idDespacho) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            // Productos
            $sqlProductos = "
                SELECT p.nombre_producto, d.cantidad, p.precio, (d.cantidad * p.precio) AS subtotal
                FROM tbl_despacho_detalle d
                INNER JOIN tbl_productos p ON p.id_producto = d.id_producto
                WHERE d.id_despacho = ?
            ";
            $stmtProd = $co->prepare($sqlProductos);
            $stmtProd->execute([$idDespacho]);
            $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

            // Pagos
            $sqlPagos = "
                SELECT dp.tipo, dp.monto, dp.fecha, dp.referencia, dp.comprobante
                FROM tbl_detalles_pago dp
                INNER JOIN tbl_facturas f ON f.id_factura = dp.id_factura
                WHERE f.id_despacho = ?
            ";
            $stmtPagos = $co->prepare($sqlPagos);
            $stmtPagos->execute([$idDespacho]);
            $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

            return [
                'productos' => $productos,
                'pagos' => $pagos
            ];
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function obtenerDetallesPorDespacho($idDespacho) {
        return $this->obt_detallesPorDespacho($idDespacho); 
    }
    private function obt_detallesPorDespacho($idDespacho) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $co->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Consultar productos de esa recepción
            $sql = "SELECT dr.id_producto, dr.cantidad, dr.costo, p.nombre 
                    FROM tbl_detalle_recepcion_productos dr
                    INNER JOIN tbl_productos p ON dr.id_producto = p.id
                    WHERE dr.id_recepcion = :idRecepcion";

            $stmt = $co->prepare($sql);
            $stmt->bindParam(':idRecepcion', $idDespacho, PDO::PARAM_INT);
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Consultar todos los productos (para el <select>)
            $sqlProductos = "SELECT id, nombre FROM tbl_productos";
            $productosTodos = $co->query($sqlProductos)->fetchAll(PDO::FETCH_ASSOC);

            // Agregar opciones al array de productos
            foreach ($productos as &$producto) {
                $opciones = '';
                foreach ($productosTodos as $item) {
                    $selected = ($item['id'] == $producto['id_producto']) ? 'selected' : '';
                    $opciones .= "<option value='{$item['id']}' $selected>" . 
                                htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') . 
                                "</option>";
                }
                $producto['opciones'] = $opciones;
            }

            return $productos;

        } catch (Exception $e) {
            return [
                'error' => true,
                'mensaje' => $e->getMessage()
            ];
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }
}
?>