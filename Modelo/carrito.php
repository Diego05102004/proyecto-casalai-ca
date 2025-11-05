<?php

require_once 'Config/Config.php';

class Carrito extends BD{
    private $conex;

    public function __construct() {
        parent::__construct();
    }


    // Métodos básicos del carrito
    public function crearCarrito($id_cliente) {
        return $this->c_crearCarrito($id_cliente);
    }
    private function c_crearCarrito($id_cliente) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "INSERT INTO tbl_carrito (id_cliente) VALUES (:id_cliente)";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_cliente', $id_cliente);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function obtenerCarritoPorCliente($id_cliente) {
        return $this->o_carritoPorCliente($id_cliente);
    }
    private function o_carritoPorCliente($id_cliente) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT id_carrito, id_cliente FROM tbl_carrito WHERE id_cliente = :id_cliente";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_cliente', $id_cliente);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function agregarProductoAlCarrito($id_carrito, $id_producto, $cantidad = 1) {
        return $this->a_agregarProducto($id_carrito, $id_producto, $cantidad);
    }
    private function a_agregarProducto($id_carrito, $id_producto, $cantidad = 1) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sqlCheck = "SELECT id_carrito_detalle, cantidad FROM tbl_carritodetalle 
                         WHERE id_carrito = :id_carrito AND id_producto = :id_producto";
            $stmtCheck = $co->prepare($sqlCheck);
            $stmtCheck->bindParam(':id_carrito', $id_carrito);
            $stmtCheck->bindParam(':id_producto', $id_producto);
            $stmtCheck->execute();
            $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($existente) {
                $nuevaCantidad = $existente['cantidad'] + $cantidad;
                $sqlUpdate = "UPDATE tbl_carritodetalle SET cantidad = :cantidad 
                              WHERE id_carrito_detalle = :id_carrito_detalle";
                $stmtUpdate = $co->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':cantidad', $nuevaCantidad);
                $stmtUpdate->bindParam(':id_carrito_detalle', $existente['id_carrito_detalle']);
                return $stmtUpdate->execute();
            } else {
                $sqlInsert = "INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad) 
                              VALUES (:id_carrito, :id_producto, :cantidad)";
                $stmtInsert = $co->prepare($sqlInsert);
                $stmtInsert->bindParam(':id_carrito', $id_carrito);
                $stmtInsert->bindParam(':id_producto', $id_producto);
                $stmtInsert->bindParam(':cantidad', $cantidad);
                return $stmtInsert->execute();
            }
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function obtenerProductosDelCarrito($id_carrito) {
        return $this->o_productosCarrito($id_carrito);
    }
    private function o_productosCarrito($id_carrito) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT cd.id_carrito_detalle, p.id_producto,p.imagen, p.nombre_producto AS nombre, mo.nombre_modelo, ma.nombre_marca,
                           cd.cantidad, p.precio, (cd.cantidad * p.precio) AS subtotal
                    FROM tbl_carritodetalle cd
                    INNER JOIN tbl_productos p ON cd.id_producto = p.id_producto
                    INNER JOIN tbl_modelos mo on mo.id_modelo = p.id_modelo
                    INNER JOIN tbl_marcas ma ON ma.id_marca = mo.id_marca
                    WHERE cd.id_carrito = :id_carrito";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_carrito', $id_carrito);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function actualizarCantidadProducto($id_carrito_detalle, $cantidad) {
        return $this->u_actualizarCantidad($id_carrito_detalle, $cantidad);
    }
    private function u_actualizarCantidad($id_carrito_detalle, $cantidad) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_carritodetalle SET cantidad = :cantidad 
                    WHERE id_carrito_detalle = :id_carrito_detalle";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':id_carrito_detalle', $id_carrito_detalle);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function eliminarProductoDelCarrito($id_carrito_detalle) {
        return $this->d_eliminarProducto($id_carrito_detalle);
    }
    private function d_eliminarProducto($id_carrito_detalle) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "DELETE FROM tbl_carritodetalle WHERE id_carrito_detalle = :id_carrito_detalle";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_carrito_detalle', $id_carrito_detalle);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function eliminarTodoElCarrito($id_carrito) {
        return $this->d_eliminarTodo($id_carrito);
    }
    private function d_eliminarTodo($id_carrito) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "DELETE FROM tbl_carritodetalle WHERE id_carrito = :id_carrito";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_carrito', $id_carrito);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    // Métodos para combos
    public function agregarComboAlCarrito($id_carrito, $id_combo) {
        return $this->a_agregarCombo($id_carrito, $id_combo);
    }
    private function a_agregarCombo($id_carrito, $id_combo) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $co->beginTransaction();
            $sqlDetalles = "SELECT id_producto, cantidad FROM combo_detalle WHERE id_combo = :id_combo";
            $stmtDetalles = $co->prepare($sqlDetalles);
            $stmtDetalles->bindParam(':id_combo', $id_combo);
            $stmtDetalles->execute();
            $detalles = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
            foreach ($detalles as $detalle) {
                $sqlInsert = "INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad) 
                              VALUES (:id_carrito, :id_producto, :cantidad)";
                $stmtInsert = $co->prepare($sqlInsert);
                $stmtInsert->bindParam(':id_carrito', $id_carrito);
                $stmtInsert->bindParam(':id_producto', $detalle['id_producto']);
                $stmtInsert->bindParam(':cantidad', $detalle['cantidad']);
                $stmtInsert->execute();
            }
            $co->commit();
            return true;
        } catch (PDOException $e) {
            if ($co->inTransaction()) { $co->rollBack(); }
            return false;
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function obtenerCantidadProductosCarrito($id_cliente) {
        return $this->o_cantidadProductos($id_cliente);
    }
    private function o_cantidadProductos($id_cliente) {
        $bd = new BD('P');
        $pdo = $bd->getConexion();
        try {
            $sql = "SELECT COUNT(cd.id_carrito_detalle) as total
                    FROM tbl_carrito c 
                    INNER JOIN tbl_carritodetalle cd ON c.id_carrito = cd.id_carrito 
                    WHERE c.id_cliente = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_cliente]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? (int)$resultado['total'] : 0;
        } finally {
            $bd->cerrar();
        }
    }

    public function obtenerResumenCarrito($id_cliente) {
        return $this->o_resumenCarrito($id_cliente);
    }
    private function o_resumenCarrito($id_cliente) {
        $bd = new BD('P');
        $pdo = $bd->getConexion();
        try {
            $sql = "SELECT c.id_carrito, 
                           COUNT(cd.id_carrito_detalle) as total_productos, 
                           COALESCE(SUM(cd.cantidad * p.precio),0) as total_precio
                    FROM tbl_carrito c 
                    LEFT JOIN tbl_carritodetalle cd ON c.id_carrito = cd.id_carrito 
                    LEFT JOIN tbl_productos p ON p.id_producto = cd.id_producto
                    WHERE c.id_cliente = ?
                    GROUP BY c.id_carrito
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_cliente]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            $bd->cerrar();
        }
    }

    // Métodos para compras
    public function registrarCompra($id_carrito, $id_cliente, $productos)
    {
        return $this->r_registrarCompra($id_carrito, $id_cliente, $productos);
    }

    private function r_registrarCompra($id_carrito, $id_cliente, $productos)
    {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            foreach ($productos as $detalle) {
                if (empty($detalle['id_producto']) || empty($detalle['cantidad']) || !is_numeric($detalle['cantidad'])) {
                    throw new Exception("Uno o más productos tienen datos incompletos o inválidos.");
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
        } catch (Exception $e) {
            if ($this->conex && $this->conex->inTransaction()) { $this->conex->rollBack(); }
            return ['error' => $e->getMessage()];
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }
}