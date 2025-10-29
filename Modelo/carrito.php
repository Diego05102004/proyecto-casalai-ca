<?php

require_once(__DIR__ . '/../config/config.php');

class Carrito extends BD{
    private $conex;


    public function __construct() {
        $this->conex = null;
    }


    // Métodos básicos del carrito
    public function crearCarrito($id_cliente) {
        return $this->c_crearCarrito($id_cliente);
    }
    private function c_crearCarrito($id_cliente) {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "INSERT INTO tbl_carrito (id_cliente) VALUES (:id_cliente)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_cliente', $id_cliente);
            return $stmt->execute();
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function obtenerCarritoPorCliente($id_cliente) {
        return $this->o_carritoPorCliente($id_cliente);
    }
    private function o_carritoPorCliente($id_cliente) {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "SELECT id_carrito, id_cliente FROM tbl_carrito WHERE id_cliente = :id_cliente";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_cliente', $id_cliente);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function agregarProductoAlCarrito($id_carrito, $id_producto, $cantidad = 1) {
        return $this->a_agregarProducto($id_carrito, $id_producto, $cantidad);
    }
    private function a_agregarProducto($id_carrito, $id_producto, $cantidad = 1) {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
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
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function obtenerProductosDelCarrito($id_carrito) {
        return $this->o_productosCarrito($id_carrito);
    }
    private function o_productosCarrito($id_carrito) {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
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
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function actualizarCantidadProducto($id_carrito_detalle, $cantidad) {
        return $this->u_actualizarCantidad($id_carrito_detalle, $cantidad);
    }
    private function u_actualizarCantidad($id_carrito_detalle, $cantidad) {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "UPDATE tbl_carritodetalle SET cantidad = :cantidad 
                    WHERE id_carrito_detalle = :id_carrito_detalle";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':cantidad', $cantidad);
            $stmt->bindParam(':id_carrito_detalle', $id_carrito_detalle);
            return $stmt->execute();
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function eliminarProductoDelCarrito($id_carrito_detalle) {
        return $this->d_eliminarProducto($id_carrito_detalle);
    }
    private function d_eliminarProducto($id_carrito_detalle) {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "DELETE FROM tbl_carritodetalle WHERE id_carrito_detalle = :id_carrito_detalle";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_carrito_detalle', $id_carrito_detalle);
            return $stmt->execute();
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function eliminarTodoElCarrito($id_carrito) {
        return $this->d_eliminarTodo($id_carrito);
    }
    private function d_eliminarTodo($id_carrito) {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "DELETE FROM tbl_carritodetalle WHERE id_carrito = :id_carrito";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_carrito', $id_carrito);
            return $stmt->execute();
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    // Métodos para combos
    public function agregarComboAlCarrito($id_carrito, $id_combo) {
        return $this->a_agregarCombo($id_carrito, $id_combo);
    }
    private function a_agregarCombo($id_carrito, $id_combo) {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
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
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function obtenerCantidadProductosCarrito($id_usuario) {
        return $this->o_cantidadProductos($id_usuario);
    }
    private function o_cantidadProductos($id_usuario) {
        $bd = new BD('C');
        $pdo = $bd->getConexion();
        try {
            $sql = "SELECT COUNT(dc.id_detalle_carrito) as total
                    FROM tbl_carrito c 
                    INNER JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito 
                    WHERE c.id_usuario = ? AND c.estado = 'activo'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_usuario]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? $resultado['total'] : 0;
        } finally {
            $bd->cerrar();
        }
    }

    public function obtenerResumenCarrito($id_usuario) {
        return $this->o_resumenCarrito($id_usuario);
    }
    private function o_resumenCarrito($id_usuario) {
        $bd = new BD('C');
        $pdo = $bd->getConexion();
        try {
            $sql = "SELECT c.id_carrito, COUNT(dc.id_detalle_carrito) as total_productos, 
                           SUM(dc.cantidad * dc.precio_unitario) as total_precio
                    FROM tbl_carrito c 
                    LEFT JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito 
                    WHERE c.id_usuario = ? AND c.estado = 'activo'
                    GROUP BY c.id_carrito";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_usuario]);
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