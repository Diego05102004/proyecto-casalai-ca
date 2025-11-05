<?php
require_once 'Config/Config.php';

class Catalogo extends BD {
    private $tablaCombo = 'tbl_combo';
    private $cantidad;
    private $id_producto;

    public function __construct() {
        parent::__construct();
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
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "INSERT INTO {$this->tablaCombo} (id_producto, cantidad)
                    VALUES (:id_producto, :cantidad)";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_producto', $this->id_producto);
            $stmt->bindParam(':cantidad', $this->cantidad);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function obtenerProductos() {
        return $this->o_obtenerProductos();
    }
    private function o_obtenerProductos() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, c.nombre_caracteristicas AS categoria, p.stock, p.precio
                    FROM productos p
                    INNER JOIN modelo m ON p.id_modelo = m.id_modelo
                    INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                    WHERE p.estado = 1";
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

    public function obtenerCombos() {
        return $this->o_obtenerCombos();
    }
    private function o_obtenerCombos() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT c.id_combo, GROUP_CONCAT(p.nombre_producto SEPARATOR ', ') AS productos,
                    SUM(p.precio * c.cantidad) AS precio_total
                    FROM tbl_combo c
                    INNER JOIN productos p ON c.id_producto = p.id_producto
                    GROUP BY c.id_combo
                    ORDER BY c.id_combo DESC";
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

    public function eliminarCombo($id_combo){
        return $this->d_eliminarCombo($id_combo);
    }
    private function d_eliminarCombo($id_combo){
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "DELETE FROM {$this->tablaCombo} WHERE id_combo = :id_combo";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function obtenerUltimoIdCombo(){
        return $this->o_ultimoIdCombo();
    }
    private function o_ultimoIdCombo(){
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT MAX(id_combo) AS ultimo_id FROM {$this->tablaCombo}";
            $stmt = $co->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['ultimo_id'];
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    // Function to create a new combo and return its ID
    public function crearNuevoCombo() {
        return $this->c_crearNuevoCombo();
    }
    private function c_crearNuevoCombo() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "INSERT INTO tbl_combo (fecha_creacion) VALUES (NOW())";
            $stmt = $co->prepare($sql);
            if($stmt->execute()) {
                return $co->lastInsertId();
            }
            return false;
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    // Function to insert a product into a specific combo
    public function insertarProductoEnCombo($id_combo, $id_producto, $cantidad) {
        return $this->i_insertarProductoEnCombo($id_combo, $id_producto, $cantidad);
    }
    private function i_insertarProductoEnCombo($id_combo, $id_producto, $cantidad) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "INSERT INTO {$this->tablaCombo} (id_combo, id_producto, cantidad) VALUES (:id_combo, :id_producto, :cantidad)";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            $stmt->bindParam(':id_producto', $id_producto);
            $stmt->bindParam(':cantidad', $cantidad);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }
}
?>