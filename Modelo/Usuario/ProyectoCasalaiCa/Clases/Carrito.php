<?php

namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Carrito extends BD{
    private $conex;
    
    // Constantes para validaciones
    const MAX_CANTIDAD_PRODUCTO = 999;
    const MAX_DESCRIPCION = 500;
    const MAX_NOMBRE_PRODUCTO = 200;
    const MAX_PRECIO = 999999.99;
    const ESTADOS_PERMITIDOS = ['activo', 'inactivo', 'pendiente'];
    const ACCIONES_PERMITIDAS = ['agregar', 'actualizar', 'eliminar', 'vaciar', 'comprar', 'consultar'];
    const TIPOS_PRODUCTO_PERMITIDOS = ['simple', 'combo', 'servicio'];

    public function __construct() {
        $this->conex = null;
    }


    // Métodos básicos del carrito
    public function crearCarrito($id_cliente) {
        return $this->c_crearCarrito($id_cliente);
    }
    private function c_crearCarrito($id_cliente) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "INSERT INTO tbl_carrito (id_cliente) VALUES (:id_cliente)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_cliente', $id_cliente);
            return $stmt->execute();
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function obtenerCarritoPorCliente($id_cliente) {
        return $this->o_carritoPorCliente($id_cliente);
    }
    private function o_carritoPorCliente($id_cliente) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "SELECT id_carrito, id_cliente FROM tbl_carrito WHERE id_cliente = :id_cliente";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_cliente', $id_cliente);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function agregarProductoAlCarrito($id_carrito, $id_producto, $cantidad = 1) {
        return $this->a_agregarProducto($id_carrito, $id_producto, $cantidad);
    }
    private function a_agregarProducto($id_carrito, $id_producto, $cantidad = 1) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sqlCheck = "SELECT id_carrito_detalle, cantidad FROM tbl_carritodetalle 
                         WHERE id_carrito = :id_carrito AND id_producto = :id_producto";
            $stmtCheck = $this->conex->prepare($sqlCheck);
            $stmtCheck->bindParam(':id_carrito', $id_carrito);
            $stmtCheck->bindParam(':id_producto', $id_producto);
            $stmtCheck->execute();
            $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                $nuevaCantidad = $existente['cantidad'] + $cantidad;
                $sqlUpdate = "UPDATE tbl_carritodetalle SET cantidad = :cantidad 
                              WHERE id_carrito_detalle = :id_carrito_detalle";
                $stmtUpdate = $this->conex->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':cantidad', $nuevaCantidad);
                $stmtUpdate->bindParam(':id_carrito_detalle', $existente['id_carrito_detalle']);
                return $stmtUpdate->execute();
            } else {
                $sqlInsert = "INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad) 
                              VALUES (:id_carrito, :id_producto, :cantidad)";
                $stmtInsert = $this->conex->prepare($sqlInsert);
                $stmtInsert->bindParam(':id_carrito', $id_carrito);
                $stmtInsert->bindParam(':id_producto', $id_producto);
                $stmtInsert->bindParam(':cantidad', $cantidad);
                return $stmtInsert->execute();
            }
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function obtenerProductosDelCarrito($id_carrito) {
        return $this->o_productosCarrito($id_carrito);
    }
    private function o_productosCarrito($id_carrito) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "SELECT cd.id_carrito_detalle, p.id_producto,p.imagen, p.nombre_producto AS nombre, mo.nombre_modelo, ma.nombre_marca,
                           cd.cantidad, p.precio, (cd.cantidad * p.precio) AS subtotal
                    FROM tbl_carritodetalle cd
                    INNER JOIN tbl_productos p ON cd.id_producto = p.id_producto
                    INNER JOIN tbl_modelos mo on mo.id_modelo = p.id_modelo
                    INNER JOIN tbl_marcas ma ON ma.id_marca = mo.id_marca
                    WHERE cd.id_carrito = :id_carrito";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_carrito', $id_carrito);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function actualizarCantidadProducto($id_carrito_detalle, $cantidad) {
        return $this->u_actualizarCantidad($id_carrito_detalle, $cantidad);
    }
    private function u_actualizarCantidad($id_carrito_detalle, $cantidad) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "UPDATE tbl_carritodetalle SET cantidad = :cantidad 
                    WHERE id_carrito_detalle = :id_carrito_detalle";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':id_carrito_detalle', $id_carrito_detalle);
            return $stmt->execute();
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function eliminarProductoDelCarrito($id_carrito_detalle) {
        return $this->d_eliminarProducto($id_carrito_detalle);
    }
    private function d_eliminarProducto($id_carrito_detalle) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "DELETE FROM tbl_carritodetalle WHERE id_carrito_detalle = :id_carrito_detalle";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_carrito_detalle', $id_carrito_detalle);
            return $stmt->execute();
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function eliminarTodoElCarrito($id_carrito) {
        return $this->d_eliminarTodo($id_carrito);
    }
    private function d_eliminarTodo($id_carrito) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "DELETE FROM tbl_carritodetalle WHERE id_carrito = :id_carrito";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_carrito', $id_carrito);
            return $stmt->execute();
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function obtenerDetallePorId($id_carrito_detalle) {
        return $this->o_detallePorId($id_carrito_detalle);
    }
    private function o_detallePorId($id_carrito_detalle) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "SELECT cd.id_carrito_detalle, cd.id_carrito, cd.id_producto, cd.cantidad, c.id_cliente
                    FROM tbl_carritodetalle cd
                    INNER JOIN tbl_carrito c ON cd.id_carrito = c.id_carrito
                    WHERE cd.id_carrito_detalle = :id_carrito_detalle";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_carrito_detalle', $id_carrito_detalle);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    // Métodos para combos
    public function agregarComboAlCarrito($id_carrito, $id_combo) {
        return $this->a_agregarCombo($id_carrito, $id_combo);
    }
    private function a_agregarCombo($id_carrito, $id_combo) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $this->conex->beginTransaction();
            $sqlDetalles = "SELECT id_producto, cantidad FROM combo_detalle WHERE id_combo = :id_combo";
            $stmtDetalles = $this->conex->prepare($sqlDetalles);
            $stmtDetalles->bindParam(':id_combo', $id_combo);
            $stmtDetalles->execute();
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
            foreach ($detalles as $detalle) {
                $sqlInsert = "INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad) 
                              VALUES (:id_carrito, :id_producto, :cantidad)";
                $stmtInsert = $this->conex->prepare($sqlInsert);
                $stmtInsert->bindParam(':id_carrito', $id_carrito);
                $stmtInsert->bindParam(':id_producto', $detalle['id_producto']);
                $stmtInsert->bindParam(':cantidad', $detalle['cantidad']);
                $stmtInsert->execute();
            }
            $this->conex->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conex->inTransaction()) { $this->conex->rollBack(); }
            return false;
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function obtenerCantidadProductosCarrito($id_usuario) {
        return $this->o_cantidadProductos($id_usuario);
    }
    private function o_cantidadProductos($id_usuario) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('C'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "SELECT COUNT(dc.id_detalle_carrito) as total
                    FROM tbl_carrito c 
                    INNER JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito 
                    WHERE c.id_usuario = ? AND c.estado = 'activo'";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([$id_usuario]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? $resultado['total'] : 0;
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    public function obtenerResumenCarrito($id_usuario) {
        return $this->o_resumenCarrito($id_usuario);
    }
    private function o_resumenCarrito($id_usuario) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('C'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "SELECT c.id_carrito, COUNT(dc.id_detalle_carrito) as total_productos, 
                           SUM(dc.cantidad * dc.precio_unitario) as total_precio
                    FROM tbl_carrito c 
                    LEFT JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito 
                    WHERE c.id_usuario = ? AND c.estado = 'activo'
                    GROUP BY c.id_carrito";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([$id_usuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    // Métodos para compras
    public function registrarCompra($id_carrito, $id_cliente, $productos) {
        return $this->r_registrarCompra($id_carrito, $id_cliente, $productos);
    }
    private function r_registrarCompra($id_carrito, $id_cliente, $productos) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            foreach ($productos as $detalle) {
                if (empty($detalle['id_producto']) || empty($detalle['cantidad']) || !is_numeric($detalle['cantidad'])) {
                    throw new PDOException("Uno o más productos tienen datos incompletos o inválidos.");
                }
            }
            $this->conex->beginTransaction();
            $sqlCompra = "INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus) 
                          VALUES (NOW(), :id_cliente, 0, 'Borrador')";
            $stmtCompra = $this->conex->prepare($sqlCompra);
            $stmtCompra->bindValue(':id_cliente', $id_cliente);
            $stmtCompra->execute();
            $id_factura = $this->conex->lastInsertId();

            $sqlDetalle = "INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad) 
                           VALUES (:id_factura, :id_producto, :cantidad)";
            $stmtDetalle = $this->conex->prepare($sqlDetalle);

            $sqlUpdateStock = "UPDATE tbl_productos SET stock = stock - :cantidad WHERE id_producto = :id_producto";
            $stmtUpdateStock = $this->conex->prepare($sqlUpdateStock);

            foreach ($productos as $detalle) {
                $stmtDetalle->bindValue(':id_factura', $id_factura);
                $stmtDetalle->bindValue(':id_producto', $detalle['id_producto']);
                $stmtDetalle->bindValue(':cantidad', $detalle['cantidad']);
                $stmtDetalle->execute();

                $stmtUpdateStock->bindValue(':cantidad', $detalle['cantidad']);
                $stmtUpdateStock->bindValue(':id_producto', $detalle['id_producto']);
                $stmtUpdateStock->execute();
            }

            $sqlVaciar = "DELETE FROM tbl_carritodetalle WHERE id_carrito = :id_carrito";
            $stmtVaciar = $this->conex->prepare($sqlVaciar);
            $stmtVaciar->bindValue(':id_carrito', $id_carrito);
            $stmtVaciar->execute();

            $this->conex->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conex && $this->conex->inTransaction()) { $this->conex->rollBack(); }
            return ['error' => $e->getMessage()];
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }

    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para agregar producto al carrito
     */
    private function validarAgregarProducto($datos) {
        $errores = [];
        
        // Validar ID del carrito
        if (!isset($datos['id_carrito']) || !is_numeric($datos['id_carrito']) || $datos['id_carrito'] <= 0) {
            $errores['id_carrito'] = 'El ID del carrito debe ser un número positivo';
        }
        
        // Validar ID del producto
        if (!isset($datos['id_producto']) || !is_numeric($datos['id_producto']) || $datos['id_producto'] <= 0) {
            $errores['id_producto'] = 'El ID del producto debe ser un número positivo';
        }
        
        // Validar cantidad
        if (!isset($datos['cantidad'])) {
            $errores['cantidad'] = 'La cantidad es obligatoria';
        } else {
            $cantidad = (int)$datos['cantidad'];
            if ($cantidad <= 0) {
                $errores['cantidad'] = 'La cantidad debe ser un número positivo';
            } elseif ($cantidad > self::MAX_CANTIDAD_PRODUCTO) {
                $errores['cantidad'] = 'La cantidad no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para actualizar cantidad de producto
     */
    private function validarActualizarCantidad($datos) {
        $errores = [];
        
        // Validar ID del detalle del carrito
        if (!isset($datos['id_carrito_detalle']) || !is_numeric($datos['id_carrito_detalle']) || $datos['id_carrito_detalle'] <= 0) {
            $errores['id_carrito_detalle'] = 'El ID del detalle del carrito debe ser un número positivo';
        }
        
        // Validar cantidad
        if (!isset($datos['cantidad'])) {
            $errores['cantidad'] = 'La cantidad es obligatoria';
        } else {
            $cantidad = (int)$datos['cantidad'];
            if ($cantidad <= 0) {
                $errores['cantidad'] = 'La cantidad debe ser un número positivo';
            } elseif ($cantidad > self::MAX_CANTIDAD_PRODUCTO) {
                $errores['cantidad'] = 'La cantidad no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para eliminar producto del carrito
     */
    private function validarEliminarProducto($datos) {
        $errores = [];
        
        // Validar ID del detalle del carrito
        if (!isset($datos['id_carrito_detalle']) || !is_numeric($datos['id_carrito_detalle']) || $datos['id_carrito_detalle'] <= 0) {
            $errores['id_carrito_detalle'] = 'El ID del detalle del carrito debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para vaciar el carrito
     */
    private function validarVaciarCarrito($datos) {
        $errores = [];
        
        // Validar ID del carrito
        if (!isset($datos['id_carrito']) || !is_numeric($datos['id_carrito']) || $datos['id_carrito'] <= 0) {
            $errores['id_carrito'] = 'El ID del carrito debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para registrar compra
     */
    private function validarRegistrarCompra($datos) {
        $errores = [];
        
        // Validar ID del carrito
        if (!isset($datos['id_carrito']) || !is_numeric($datos['id_carrito']) || $datos['id_carrito'] <= 0) {
            $errores['id_carrito'] = 'El ID del carrito debe ser un número positivo';
        }
        
        // Validar ID del cliente
        if (!isset($datos['id_cliente']) || !is_numeric($datos['id_cliente']) || $datos['id_cliente'] <= 0) {
            $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
        }
        
        // Validar productos
        if (!isset($datos['productos']) || !is_array($datos['productos']) || empty($datos['productos'])) {
            $errores['productos'] = 'Debe especificar al menos un producto para la compra';
        } else {
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
                if (!isset($producto['cantidad']) || !is_numeric($producto['cantidad']) || $producto['cantidad'] <= 0) {
                    $errores["productos_{$index}_cantidad"] = 'La cantidad del producto en la posición ' . $index . ' debe ser un número positivo';
                } elseif ($producto['cantidad'] > self::MAX_CANTIDAD_PRODUCTO) {
                    $errores["productos_{$index}_cantidad"] = 'La cantidad del producto en la posición ' . $index . ' no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para consultar carrito
     */
    private function validarConsultarCarrito($datos) {
        $errores = [];
        
        // Validar ID del cliente
        if (!isset($datos['id_cliente']) || !is_numeric($datos['id_cliente']) || $datos['id_cliente'] <= 0) {
            $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
        }
        
        // Validar ID del carrito (opcional)
        if (isset($datos['id_carrito'])) {
            if (!is_numeric($datos['id_carrito']) || $datos['id_carrito'] <= 0) {
                $errores['id_carrito'] = 'El ID del carrito debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para filtrar por marca
     */
    private function validarFiltrarMarca($datos) {
        $errores = [];
        
        // Validar ID de marca (opcional)
        if (isset($datos['id_marca'])) {
            if (!is_numeric($datos['id_marca']) || $datos['id_marca'] <= 0) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para agregar producto al carrito (método público)
     */
    public function validarAgregar($datos) {
        return $this->validarAgregarProducto($datos);
    }
    
    /**
     * Valida los datos para actualizar cantidad de producto (método público)
     */
    public function validarActualizar($datos) {
        return $this->validarActualizarCantidad($datos);
    }
    
    /**
     * Valida los datos para eliminar producto del carrito (método público)
     */
    public function validarEliminar($datos) {
        return $this->validarEliminarProducto($datos);
    }
    
    /**
     * Valida los datos para vaciar el carrito (método público)
     */
    public function validarVaciar($datos) {
        return $this->validarVaciarCarrito($datos);
    }
    
    /**
     * Valida los datos para registrar compra (método público)
     */
    public function validarCompra($datos) {
        return $this->validarRegistrarCompra($datos);
    }
    
    /**
     * Valida los datos para consultar carrito (método público)
     */
    public function validarConsultar($datos) {
        return $this->validarConsultarCarrito($datos);
    }
    
    /**
     * Valida los datos para filtrar por marca (método público)
     */
    public function validarFiltrar($datos) {
        return $this->validarFiltrarMarca($datos);
    }
    
    /**
     * Verifica si un producto existe y tiene stock
     */
    private function verificarProductoExistente($idProducto, $cantidadRequerida = 1) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "SELECT id_producto, stock, estado FROM tbl_productos WHERE id_producto = :id_producto";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->execute();
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                return ['existe' => false, 'mensaje' => 'El producto no existe'];
            }
            
            if ($producto['stock'] < $cantidadRequerida) {
                return ['existe' => false, 'mensaje' => 'No hay stock suficiente para el producto'];
            }
            
            if ($producto['estado'] !== 'activo') {
                return ['existe' => false, 'mensaje' => 'El producto no está disponible'];
            }
            
            return ['existe' => true, 'producto' => $producto];
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }
    
    /**
     * Verifica si un carrito pertenece a un cliente
     */
    private function verificarCarritoCliente($idCarrito, $idCliente) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "SELECT id_carrito FROM tbl_carrito WHERE id_carrito = :id_carrito AND id_cliente = :id_cliente";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(':id_carrito', $idCarrito, PDO::PARAM_INT);
            $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() !== false;
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }
    
    /**
     * Verifica si un detalle del carrito pertenece a un cliente
     */
    private function verificarDetalleCliente($idCarritoDetalle, $idCliente) {
        $bd = null; $created = false;
        if (!($this->conex instanceof PDO)) {
            if (method_exists($this, 'getConexion')) { $this->conex = $this->getConexion(); }
            if (!($this->conex instanceof PDO)) { $bd = new BD('P'); $this->conex = $bd->getConexion(); $created = true; }
        }
        try {
            $sql = "SELECT cd.id_carrito_detalle 
                    FROM tbl_carritodetalle cd
                    INNER JOIN tbl_carrito c ON cd.id_carrito = c.id_carrito
                    WHERE cd.id_carrito_detalle = :id_carrito_detalle AND c.id_cliente = :id_cliente";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(':id_carrito_detalle', $idCarritoDetalle, PDO::PARAM_INT);
            $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() !== false;
        } finally {
            if ($bd) { $bd->cerrar(); }
            if ($created) { $this->conex = null; }
        }
    }
}