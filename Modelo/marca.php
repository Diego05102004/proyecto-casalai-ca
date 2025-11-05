<?php
require_once 'Config/Config.php';

class marca extends BD {
    private $tablemarcas = 'tbl_marcas';
    private $nombre_marca;
    private $id_marca;

    public function __construct() {
        parent::__construct();
    }

    public function getnombre_marca() {
        return $this->nombre_marca;
    }
    public function setnombre_marca($nombre_marca) {
        $this->nombre_marca = $nombre_marca;
    }
    
    public function getIdMarca() {
        return $this->id_marca;
    }
    public function setIdMarca($id_marca) {
        $this->id_marca = $id_marca;
    }

    public function existeNombreMarca($nombre_marca, $excluir_id = null) {
        return $this->existeNomMarca($nombre_marca, $excluir_id); 
    }
    private function existeNomMarca($nombre_marca, $excluir_id) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT COUNT(*) FROM tbl_marcas WHERE nombre_marca = ?";
            $params = [$nombre_marca];
            if ($excluir_id !== null) {
                $sql .= " AND id_marca != ?";
                $params[] = $excluir_id;
            }
            $stmt = $co->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }
    public function registrarMarca() {
        return $this->r_marca();
    }
    private function r_marca() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "INSERT INTO tbl_marcas (nombre_marca)
                    VALUES (:nombre_marca)";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':nombre_marca', $this->nombre_marca);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function obtenerUltimaMarca() {
        return $this->obtUltimaMarca(); 
    }
    private function obtUltimaMarca() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT * FROM tbl_marcas ORDER BY id_marca DESC LIMIT 1";
            $stmt = $co->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function tieneModelosAsociados($id_marca) {
        return $this->verificarModelosAsociados($id_marca);
    }
    
    private function verificarModelosAsociados($id_marca) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT COUNT(*) FROM tbl_modelos WHERE id_marca = :id_marca";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_marca', $id_marca, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return true;
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function obtenermarcasPorId($id_marca) {
        return $this->obtmarcasPorId($id_marca);
    }
    private function obtmarcasPorId($id_marca) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $query = "SELECT id_marca, nombre_marca FROM tbl_marcas WHERE id_marca = ?";
            $stmt = $co->prepare($query);
            $stmt->execute([$id_marca]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function modificarmarcas($id_marca) {
        return $this->m_marcas($id_marca);
    }
    private function m_marcas($id_marca) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_marcas SET nombre_marca = :nombre_marca WHERE id_marca = :id_marca";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_marca', $id_marca);
            $stmt->bindParam(':nombre_marca', $this->nombre_marca);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function eliminarmarcas($id_marca) {
        return $this->e_marcas($id_marca);
    }
    private function e_marcas($id_marca) {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $sql = "DELETE FROM tbl_marcas WHERE id_marca = :id_marca";
            $stmt = $co->prepare($sql);
            $stmt->bindParam(':id_marca', $id_marca);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }

    public function getmarcas() {
        return $this->g_marcas();
    }
    private function g_marcas() {
        $conexion = new BD('P');
        $co = $conexion->getConexion();
        try {
            $querymarcas = 'SELECT id_marca, nombre_marca FROM ' . $this->tablemarcas. ' ORDER BY id_marca DESC';
            $stmtmarcas = $co->prepare($querymarcas);
            $stmtmarcas->execute();
            return $stmtmarcas->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar();
            }
            $co = null;
        }
    }
}
?>