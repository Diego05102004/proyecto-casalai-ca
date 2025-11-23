<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class OrdenDespacho extends BD {
    
    private $conex;
    private $id;
    private $factura;
    private $cliente;
    private $fecha;
    private $estado;
    private $activo = 1;
    private $tableordendespacho = 'tbl_orden_despachos';

    public function __construct() {
        $this->conex = null;
    }

    // Getters y Setters
    public function getId() {
        return $this->id;
    }
    public function setId($id) {
        $this->id = $id;
    }

    public function getFactura() {
        return $this->factura;
    }
    public function setFactura($factura) {
        $this->factura = $factura;
    }

    public function getCliente() {
        return $this->cliente;
    }
    public function setCliente($cliente) {
        $this->cliente = $cliente;
    }

    public function getFecha() {
        return $this->fecha;
    }
    public function setFecha($fecha) {
        $this->fecha = $fecha;
    }

    public function getEstado() {
        return $this->estado;
    }
    public function setEstado($estado) {
        $this->estado = $estado;
    }

    public function obtenerFacturasDisponibles() {
        return $this->obt_facturasDisponibles(); 
    }
    private function obt_facturasDisponibles() {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "SELECT f.id_factura, f.fecha, c.nombre
                    FROM tbl_facturas f
                    INNER JOIN tbl_clientes c ON f.cliente = c.id_clientes
                    WHERE f.estatus = 'Pagada'
                    AND f.id_factura NOT IN (
                        SELECT DISTINCT id_factura 
                        FROM tbl_orden_despachos 
                        WHERE id_factura IS NOT NULL
                    )";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function obtenerOrdenPorId($id) {
        return $this->obt_ordenPorId($id); 
    }
    private function obt_ordenPorId($id) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $query = "SELECT * FROM tbl_orden_despachos WHERE id_orden_despachos = ?";
            $stmt = $this->conex->prepare($query);
            $stmt->execute([$id]);
            $ordendespacho = $stmt->fetch(PDO::FETCH_ASSOC);
            return $ordendespacho;
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function cambiarEstatus($nuevoEstatus) {
        return $this->cambiar_Estatus($nuevoEstatus); 
    }
    private function cambiar_Estatus($nuevoEstatus) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "UPDATE tbl_usuarios SET estatus = :estatus WHERE id_usuario = :id";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':estatus', $nuevoEstatus);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function getordendespacho() {
        return $this->g_ordenesDespacho();
    }

  private function g_ordenesDespacho() {
    $created = false;
    if (!($this->conex instanceof PDO)) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        $created = true;
    }
    try {
    $query = "
        SELECT 
            od.id_orden_despachos,
            od.id_factura,
            c.cedula,
            od.cliente,
            od.fecha_despacho,
            od.estado,
            od.activo
        FROM tbl_orden_despachos AS od
        INNER JOIN tbl_facturas f ON f.id_factura = od.id_factura
        INNER JOIN tbl_clientes c ON c.id_clientes = f.cliente
        WHERE od.activo = 1
        ORDER BY od.fecha_despacho DESC, od.id_orden_despachos DESC;
    ";

    $stmt = $this->conex->prepare($query);
    $stmt->execute();
    $ordendespacho = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // obtener los productos por cada orden de despacho
    foreach ($ordendespacho as &$despacho) {
        $sqlProd = "
            SELECT 
            p.imagen,
                p.id_producto AS codigo,
                p.nombre_producto AS producto,
                m.nombre_modelo AS modelo,
                mar.nombre_marca AS marca,
                p.serial,
                d.cantidad,
                d.id AS id_detalle,
                p.precio AS precio_unitario,
                (d.cantidad * p.precio) AS subtotal
            FROM tbl_factura_detalle AS d
            INNER JOIN tbl_productos AS p ON p.id_producto = d.id_producto
            INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo
            INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
            WHERE d.factura_id = ?
        ";
        $stmtProd = $this->conex->prepare($sqlProd);
        $stmtProd->execute([$despacho['id_factura']]); // corregido
        $despacho['productos'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
    }

    return $ordendespacho;
    } finally {
        if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
        if (isset($created) && $created) { $this->conex = null; }
    }
}

 // En la clase OrdenDespacho.php, agrega este nuevo método:
public function obtenerDatosParaPDF($id) {
    $created = false;
    try {
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }

        // Obtener datos básicos de la orden
        $query = "SELECT od.*, c.nombre as cliente, c.cedula 
                 FROM tbl_orden_despachos od
                 JOIN tbl_facturas f ON od.id_factura = f.id_factura
                 JOIN tbl_clientes c ON f.cliente = c.id_clientes
                 WHERE od.id_orden_despachos = ?";
        
        $stmt = $this->conex->prepare($query);
        $stmt->execute([$id]);
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$orden) {
            return null;
        }

        // Obtener productos
        $queryProductos = "SELECT p.codigo, p.nombre as producto, p.marca, 
                          df.cantidad, df.precio_unitario as precio,
                          (df.cantidad * df.precio_unitario) as subtotal
                         FROM tbl_detalle_factura df
                         JOIN tbl_productos p ON df.id_producto = p.id_productos
                         WHERE df.id_factura = ?";
        
        $stmtProductos = $this->conex->prepare($queryProductos);
        $stmtProductos->execute([$orden['id_factura']]);
        $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

        // Calcular total
        $total = array_sum(array_column($productos, 'subtotal'));

        return [
            'id_orden_despachos' => $orden['id_orden_despachos'],
            'id_factura' => $orden['id_factura'],
            'cliente' => $orden['cliente'],
            'cedula' => $orden['cedula'],
            'fecha_despacho' => $orden['fecha_despacho'],
            'estado' => $orden['estado'],
            'productos' => $productos,
            'total' => $total
        ];

    } catch (PDOException $e) {
        error_log("Error al obtener datos para PDF: " . $e->getMessage());
        return null;
    } finally {
        if ($created && isset($conexion)) {
            $conexion->cerrar();
        }
    }
}


    public function getDetallesCompra($idDespacho) {
        return $this->g_detallesCompra($idDespacho); 
    }
    private function g_detallesCompra($idDespacho) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
        // Productos
        $sqlProductos = "
            SELECT p.nombre_producto, d.cantidad, p.precio, (d.cantidad * p.precio) AS subtotal
            FROM tbl_despacho_detalle d
            INNER JOIN tbl_productos p ON p.id_producto = d.id_producto
            WHERE d.id_despacho = ?
        ";
        $stmtProd = $this->conex->prepare($sqlProductos);
        $stmtProd->execute([$idDespacho]);
        $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

        return [
            'productos' => $productos
        ];
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function cambiarEstadoOrden($id, $nuevoEstado) {
        return $this->cam_estadoOrden($id, $nuevoEstado); 
    }
    private function cam_estadoOrden($id, $nuevoEstado) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "UPDATE tbl_orden_despachos SET estado = :estado WHERE id_orden_despachos = :id";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':estado', $nuevoEstado);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function anularOrdenDespacho($idOrden) {
        return $this->an_orden_despacho($idOrden); 
    }

    private function an_orden_despacho($idOrden) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "UPDATE tbl_orden_despachos SET activo = 0 WHERE id_orden_despachos = :id";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id', $idOrden, PDO::PARAM_INT);
            $result = $stmt->execute();
            return $result 
                ? ['status' => 'success'] 
                : ['status' => 'error', 'message' => 'No se pudo anular la orden de despacho'];
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    // Crear una orden de despacho para una factura específica con estado 'Por Entregar'
    public function crearPorFactura($idFactura) {
        return $this->c_PorFactura($idFactura); 
    }
    private function c_PorFactura($idFactura) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            // Validar duplicado (orden activa existente para la factura)
            $sqlDup = "SELECT id_orden_despachos FROM tbl_orden_despachos WHERE id_factura = :id AND activo = 1 LIMIT 1";
            $stmtD = $this->conex->prepare($sqlDup);
            $stmtD->bindParam(':id', $idFactura, PDO::PARAM_INT);
            $stmtD->execute();
            if ($stmtD->fetch(PDO::FETCH_ASSOC)) {
                return ['status' => 'exists'];
            }

            // Obtener cliente (ID y nombre) de la factura
            $sqlCliente = "SELECT f.cliente AS id_cliente, c.nombre AS nombre_cliente
                           FROM tbl_facturas f
                           INNER JOIN tbl_clientes c ON c.id_clientes = f.cliente
                           WHERE f.id_factura = :id";
            $stmtC = $this->conex->prepare($sqlCliente);
            $stmtC->bindParam(':id', $idFactura, PDO::PARAM_INT);
            $stmtC->execute();
            $row = $stmtC->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['status' => 'error', 'message' => 'Factura no encontrada'];
            }
            $clienteId = (int)$row['id_cliente'];
            $clienteNombre = $row['nombre_cliente'];
            
            // Si el nombre del cliente es null, usar un valor por defecto
            if ($clienteNombre === null || $clienteNombre === '') {
                $clienteNombre = 'Cliente Desconocido';
            }

            // Insertar orden
            $sqlIns = "INSERT INTO tbl_orden_despachos (id_factura, cliente, fecha_despacho, estado, activo)
                       VALUES (:id_factura, :cliente, NOW(), 'Por Entregar', 1)";
            $stmt = $this->conex->prepare($sqlIns);
            $stmt->bindParam(':id_factura', $idFactura, PDO::PARAM_INT);
            // Guardar NOMBRE del cliente como lo requiere la vista/reportes
            $stmt->bindParam(':cliente', $clienteNombre, PDO::PARAM_STR);
            $ok = $stmt->execute();
            if ($ok) {
                return ['status' => 'success', 'id' => $this->conex->lastInsertId()];
            }
            return ['status' => 'error', 'message' => 'No se pudo crear la orden de despacho'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }
}
