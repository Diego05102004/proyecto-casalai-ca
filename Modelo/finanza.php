<?php
require_once 'config/config.php';

class Finanza extends BD {
    private $id_finanzas;
    private $tipo; 
    private $monto;
    private $descripcion;
    private $fecha;
    private $estado;
    private $id_despacho;
    private $id_recepcion;

    public function __construct() {
        $this->conex = null;
    }

    // Getters y setters
    public function getIdFinanzas() { 
        return $this->id_finanzas; 
    }
    public function setIdFinanzas($id_finanzas) {
        $this->id_finanzas = $id_finanzas; 
    }

    public function getTipo() { 
        return $this->tipo; 
    }
    public function setTipo($tipo) { 
        $this->tipo = $tipo; 
    }

    public function getMonto() { 
        return $this->monto; 
    }
    public function setMonto($monto) { 
        $this->monto = $monto; 
    }

    public function getDescripcion() { 
        return $this->descripcion; 
    }
    public function setDescripcion($descripcion) { 
        $this->descripcion = $descripcion; 
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

    public function getIdDespacho() { 
        return $this->id_despacho; 
    }
    public function setIdDespacho($id) { 
        $this->id_despacho = $id; 
    }

    public function getIdRecepcion() { 
        return $this->id_recepcion; 
    }
    public function setIdRecepcion($id) { 
        $this->id_recepcion = $id; 
    }

    // CONSULTAR INGRESOS
    public function consultarIngresos() {
        return $this->c_ingresos(); 
    }
    private function c_ingresos() {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "SELECT * FROM tbl_ingresos_egresos WHERE tipo='ingreso' ORDER BY fecha DESC";
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    // CONSULTAR EGRESOS
    public function consultarEgresos() {
        return $this->c_egresos(); 
    }
    private function c_egresos() {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "SELECT * FROM tbl_ingresos_egresos WHERE tipo='egreso' ORDER BY fecha DESC";
            $stmt = $db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }
}