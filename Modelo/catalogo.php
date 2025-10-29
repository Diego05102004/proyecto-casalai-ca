<?php
require_once __DIR__ . '/../config/config.php';

class Catalogo extends BD {
    private $tablaCombo = 'tbl_combo';
    private $conex;
    private $cantidad;
    private $id_producto;

    public function __construct() {
        $this->conex = null;
    }


    // Getters and Setters

    public function setIdProducto($id_producto){
        $this->id_producto = $id_producto;
    }

    public function setCantidad($cantidad){
        $this->cantidad = $cantidad;
    }


    public function insertarCombo(){
        return $this->i_insertarCombo();
    }
    private function i_insertarCombo(){
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "INSERT INTO {$this->tablaCombo} (id_producto, cantidad)
                    VALUES (:id_producto, :cantidad)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_producto', $this->id_producto);
            $stmt->bindParam(':cantidad', $this->cantidad);
            return $stmt->execute();
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function obtenerProductos() {
        return $this->o_obtenerProductos();
    }
    private function o_obtenerProductos() {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, c.nombre_caracteristicas AS categoria, p.stock, p.precio
                    FROM productos p
                    INNER JOIN modelo m ON p.id_modelo = m.id_modelo
                    INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                    WHERE p.estado = 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function obtenerCombos() {
        return $this->o_obtenerCombos();
    }
    private function o_obtenerCombos() {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "SELECT c.id_combo, GROUP_CONCAT(p.nombre_producto SEPARATOR ', ') AS productos,
                    SUM(p.precio * c.cantidad) AS precio_total
                    FROM tbl_combo c
                    INNER JOIN productos p ON c.id_producto = p.id_producto
                    GROUP BY c.id_combo
                    ORDER BY c.id_combo DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function eliminarCombo($id_combo){
        return $this->d_eliminarCombo($id_combo);
    }
    private function d_eliminarCombo($id_combo){
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "DELETE FROM {$this->tablaCombo} WHERE id_combo = :id_combo";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            return $stmt->execute();
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    public function obtenerUltimoIdCombo(){
        return $this->o_ultimoIdCombo();
    }
    private function o_ultimoIdCombo(){
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "SELECT MAX(id_combo) AS ultimo_id FROM {$this->tablaCombo}";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['ultimo_id'];
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    // Function to create a new combo and return its ID
    public function crearNuevoCombo() {
        return $this->c_crearNuevoCombo();
    }
    private function c_crearNuevoCombo() {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "INSERT INTO tbl_combo (fecha_creacion) VALUES (NOW())";
            $stmt = $this->conex->prepare($sql);
            if($stmt->execute()) {
                return $this->conex->lastInsertId();
            }
            return false;
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }

    // Function to insert a product into a specific combo
    public function insertarProductoEnCombo($id_combo, $id_producto, $cantidad) {
        return $this->i_insertarProductoEnCombo($id_combo, $id_producto, $cantidad);
    }
    private function i_insertarProductoEnCombo($id_combo, $id_producto, $cantidad) {
        $bd = new BD('P');
        $this->conex = $bd->getConexion();
        try {
            $sql = "INSERT INTO {$this->tablaCombo} (id_combo, id_producto, cantidad) VALUES (:id_combo, :id_producto, :cantidad)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            $stmt->bindParam(':id_producto', $id_producto);
            $stmt->bindParam(':cantidad', $cantidad);
            return $stmt->execute();
        } finally {
            $bd->cerrar();
            $this->conex = null;
        }
    }
}
?>