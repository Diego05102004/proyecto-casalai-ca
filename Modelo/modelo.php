<?php
require_once 'config/config.php';

class modelo extends BD{
    private $id_marca;
    private $conex;
    private $nombre_modelo;
    private $id_modelo;

    public function __construct() {
        $this->conex = null;
    }

    public function getnombre_modelo() {
        return $this->nombre_modelo;
    }

    public function setnombre_modelo($nombre_modelo) {
        $this->nombre_modelo = $nombre_modelo;
    }

    public function getid_marca() {
        return $this->id_marca;
    }

    public function setid_marca($id_marca) {
        $this->id_marca = $id_marca;
    }

    public function getIdModelo() {
        return $this->id_modelo;
    }
    public function setIdModelo($id_modelo) {
        $this->id_modelo = $id_modelo;
    }

    public function existeNombreModelo($nombre_modelo, $excluir_id = null) {
        return $this->existeNomModelo($nombre_modelo, $excluir_id);
    }
    private function existeNomModelo($nombre_modelo, $excluir_id) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT COUNT(*) FROM tbl_modelos WHERE nombre_modelo = ?";
            $params = [$nombre_modelo];
            if ($excluir_id !== null) {
                $sql .= " AND id_modelo != ?";
                $params[] = $excluir_id;
            }
            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function registrarModelo() {
        return $this->r_modelos();
    }
    private function r_modelos() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "INSERT INTO tbl_modelos (nombre_modelo, id_marca)
                    VALUES (:nombre_modelo, :id_marca)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':nombre_modelo', $this->nombre_modelo);
            $stmt->bindParam(':id_marca', $this->id_marca);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function obtenerUltimoModelo() {
        return $this->obtUltimoModelo();
    }
    private function obtUltimoModelo() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT m.id_modelo, m.nombre_modelo, m.id_marca, ma.nombre_marca
                FROM tbl_modelos m
                JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
                ORDER BY m.id_modelo DESC LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $modelo = $stmt->fetch(PDO::FETCH_ASSOC);
            return $modelo ? $modelo : null;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function obtenerModeloPorId($id_modelo) {
        return $this->obtModeloPorId($id_modelo);
    }
    private function obtModeloPorId($id_modelo) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT * FROM tbl_modelos WHERE id_modelo = ?";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([$id_modelo]);
            $modelo = $stmt->fetch(PDO::FETCH_ASSOC);
            return $modelo;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function getmarcas() {
        return $this->g_marcas();
    }
    private function g_marcas() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $query = "SELECT id_marca, nombre_marca FROM tbl_marcas";
            $stmt = $this->conex->query($query);

            if ($stmt) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $errorInfo = $this->conex->errorInfo();
                echo "Debug: Error en el query: " . $errorInfo[2] . "\n";
                return [];
            }
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function modificarModelo($id_modelo) {
        return $this->m_modelo($id_modelo);
    }
    private function m_modelo($id_modelo) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_modelos SET nombre_modelo = :nombre_modelo, id_marca = :id_marca WHERE id_modelo = :id_modelo";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_modelo', $id_modelo);
            $stmt->bindParam(':id_marca', $this->id_marca);
            $stmt->bindParam(':nombre_modelo', $this->nombre_modelo);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function eliminarModelo($id_modelo) {
        return $this->e_modelo($id_modelo);
    }

    private function e_modelo($id_modelo) {
        // Primero verificar si hay productos asociados a este modelo
        $productosAsociados = $this->tieneProductosAsociados($id_modelo);
        
        if ($productosAsociados['tiene_productos']) {
            return [
                'status' => 'error', 
                'mensaje' => 'No se puede eliminar el modelo porque tiene productos asociados.',
                'productos' => $productosAsociados['productos'],
                'total_productos' => $productosAsociados['total']
            ];
        }

        // Si no hay productos, proceder con la eliminación
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "DELETE FROM tbl_modelos WHERE id_modelo = :id_modelo";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_modelo', $id_modelo);
            $result = $stmt->execute();
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
        
        if ($result) {
            return ['status' => 'success'];
        } else {
            return [
                'status' => 'error', 
                'mensaje' => 'Error al eliminar el modelo',
                'productos' => [],
                'total_productos' => 0
            ];
        }
    }

    public function tieneProductosAsociados($id_modelo) {
        return $this->tieneProductosAso($id_modelo);
    }
    private function tieneProductosAso($id_modelo) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            // Verificar si hay productos asociados al modelo
            $sql = "SELECT COUNT(*) as total FROM tbl_productos WHERE id_modelo = :id_modelo";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_modelo', $id_modelo, PDO::PARAM_INT);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $resultado['total'];

            if ($count > 0) {
                // Obtener información de los productos asociados
                $sqlProductos = "SELECT nombre_producto, codigo_producto 
                            FROM tbl_productos 
                            WHERE id_modelo = :id_modelo 
                            ORDER BY nombre_producto 
                            LIMIT 5";
                $stmtProductos = $this->conex->prepare($sqlProductos);
                $stmtProductos->bindParam(':id_modelo', $id_modelo, PDO::PARAM_INT);
                $stmtProductos->execute();
                $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);
                
                return [
                    'tiene_productos' => true,
                    'productos' => $productos,
                    'total' => $count
                ];
            }

            return ['tiene_productos' => false];
        } catch (PDOException $e) {
            // Por seguridad, asumimos que hay productos si hay error
            error_log("Error al verificar productos asociados: " . $e->getMessage());
            return [
                'tiene_productos' => true,
                'productos' => [],
                'total' => 'Desconocido'
            ];
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function obtenerModeloConMarcaPorId($id_modelo) {
        return $this->obtModeloConMarcaPorId($id_modelo);
    }
    private function obtModeloConMarcaPorId($id_modelo) {
        $sql = "SELECT m.id_modelo, m.nombre_modelo, m.id_marca, ma.nombre_marca
                FROM tbl_modelos m
                JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
                WHERE m.id_modelo = ?";
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([$id_modelo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function getModelos() {
        return $this->g_modelos();
    }
    private function g_modelos() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        $querymodelos = 'SELECT mo.id_modelo,
                                mo.id_marca,
                                mo.nombre_modelo,
                                ma.nombre_marca 
                                FROM tbl_modelos AS mo
                                INNER JOIN tbl_marcas AS ma ON mo.id_marca = ma.id_marca
                                ORDER BY mo.id_modelo DESC';
        try {
            $stmtmodelos = $this->conex->prepare($querymodelos);
            $stmtmodelos->execute();
            $modelos = $stmtmodelos->fetchAll(PDO::FETCH_ASSOC);
            return $modelos;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }
}
?>