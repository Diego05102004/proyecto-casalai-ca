<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;


class modelo extends BD{
    private $id_marca;
    private $conex;
    private $nombre_modelo;
    private $id_modelo;
    
    // Constantes para validaciones
    const MAX_NOMBRE_MODELO = 100;
    const MIN_NOMBRE_MODELO = 2;
    const MAX_ID_MODELO = 999999999;
    const MIN_ID_MODELO = 1;
    const MAX_ID_MARCA = 999999999;
    const MIN_ID_MARCA = 1;

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
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
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
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function registrarModelo() {
        return $this->r_modelos();
    }
    private function r_modelos() {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "INSERT INTO tbl_modelos (nombre_modelo, id_marca)
                    VALUES (:nombre_modelo, :id_marca)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':nombre_modelo', $this->nombre_modelo);
            $stmt->bindParam(':id_marca', $this->id_marca);
            return $stmt->execute();
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function obtenerUltimoModelo() {
        return $this->obtUltimoModelo();
    }
    private function obtUltimoModelo() {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
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
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function obtenerModeloPorId($id_modelo) {
        return $this->obtModeloPorId($id_modelo);
    }
    private function obtModeloPorId($id_modelo) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "SELECT * FROM tbl_modelos WHERE id_modelo = ?";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([$id_modelo]);
            $modelo = $stmt->fetch(PDO::FETCH_ASSOC);
            return $modelo;
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function getmarcas() {
        return $this->g_marcas();
    }
    private function g_marcas() {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        $query = 'SELECT id_marca, nombre_marca FROM tbl_marcas';
        try {
            $stmt = $this->conex->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function modificarModelo($id_modelo) {
        return $this->m_modelo($id_modelo);
    }
    private function m_modelo($id_modelo) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "UPDATE tbl_modelos SET nombre_modelo = :nombre_modelo, id_marca = :id_marca WHERE id_modelo = :id_modelo";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_modelo', $id_modelo);
            $stmt->bindParam(':id_marca', $this->id_marca);
            $stmt->bindParam(':nombre_modelo', $this->nombre_modelo);
            return $stmt->execute();
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
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
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "DELETE FROM tbl_modelos WHERE id_modelo = :id_modelo";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_modelo', $id_modelo);
            $result = $stmt->execute();
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
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
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
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
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function obtenerModeloConMarcaPorId($id_modelo) {
        return $this->obtModeloConMarcaPorId($id_modelo);
    }
    private function obtModeloConMarcaPorId($id_modelo) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "SELECT m.id_modelo, m.nombre_modelo, m.id_marca, ma.nombre_marca
                FROM tbl_modelos m
                JOIN tbl_marcas ma ON m.id_marca = ma.id_marca
                WHERE m.id_modelo = ?";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute([$id_modelo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    public function getModelos() {
        return $this->g_modelos();
    }
    private function g_modelos() {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
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
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    // ==================== VALIDACIONES DE BACKEND ====================

    /**
     * Valida los datos para operaciones CRUD de modelos
     */
    private function validarModelo($datos, $esModificacion = false) {
        $errores = [];

        // Validar nombre del modelo (solo para registro y modificación)
        if (!isset($datos['accion_eliminar'])) {
            if (!isset($datos['nombre_modelo'])) {
                $errores['nombre_modelo'] = 'El nombre del modelo es obligatorio';
            } else {
                $nombre_modelo = trim($datos['nombre_modelo']);
                if (empty($nombre_modelo)) {
                    $errores['nombre_modelo'] = 'El nombre del modelo es obligatorio';
                } elseif (mb_strlen($nombre_modelo) < self::MIN_NOMBRE_MODELO || mb_strlen($nombre_modelo) > self::MAX_NOMBRE_MODELO) {
                    $errores['nombre_modelo'] = 'El nombre del modelo debe tener entre ' . self::MIN_NOMBRE_MODELO . ' y ' . self::MAX_NOMBRE_MODELO . ' caracteres';
                } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_modelo)) {
                    $errores['nombre_modelo'] = 'El nombre del modelo solo puede contener letras, números, espacios y caracteres especiales comunes';
                }
            }
        }

        // Validar ID de la marca (solo para registro y modificación)
        if (!isset($datos['accion_eliminar'])) {
            if (!isset($datos['id_marca'])) {
                $errores['id_marca'] = 'Debe seleccionar una marca válida';
            } elseif (!is_numeric($datos['id_marca']) || $datos['id_marca'] < self::MIN_ID_MARCA || $datos['id_marca'] > self::MAX_ID_MARCA) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número entre ' . self::MIN_ID_MARCA . ' y ' . self::MAX_ID_MARCA;
            }
        }

        // Validar ID del modelo (solo para modificaciones y eliminaciones)
        if ($esModificacion || isset($datos['accion_eliminar'])) {
            if (!isset($datos['id_modelo'])) {
                $errores['id_modelo'] = 'El ID del modelo es obligatorio';
            } elseif (!is_numeric($datos['id_modelo']) || $datos['id_modelo'] < self::MIN_ID_MODELO || $datos['id_modelo'] > self::MAX_ID_MODELO) {
                $errores['id_modelo'] = 'El ID del modelo debe ser un número entre ' . self::MIN_ID_MODELO . ' y ' . self::MAX_ID_MODELO;
            }
        }

        return $errores;
    }

    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================

    /**
     * Valida los datos para registrar modelo (método público)
     */
    public function validarRegistrarModelo($datos) {
        return $this->validarModelo($datos, false);
    }

    /**
     * Valida los datos para consultar modelo (método público)
     */
    public function validarConsultarModelo($datos) {
        $errores = [];

        // Validar ID del modelo (opcional para consulta)
        if (isset($datos['id_modelo'])) {
            if (!is_numeric($datos['id_modelo']) || $datos['id_modelo'] < self::MIN_ID_MODELO || $datos['id_modelo'] > self::MAX_ID_MODELO) {
                $errores['id_modelo'] = 'El ID del modelo debe ser un número entre ' . self::MIN_ID_MODELO . ' y ' . self::MAX_ID_MODELO;
            }
        }

        // Validar ID de la marca (opcional para consulta)
        if (isset($datos['id_marca'])) {
            if (!is_numeric($datos['id_marca']) || $datos['id_marca'] < self::MIN_ID_MARCA || $datos['id_marca'] > self::MAX_ID_MARCA) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número entre ' . self::MIN_ID_MARCA . ' y ' . self::MAX_ID_MARCA;
            }
        }

        return $errores;
    }

    /**
     * Valida los datos para modificar modelo (método público)
     */
    public function validarModificarModelo($datos) {
        return $this->validarModelo($datos, true);
    }

    /**
     * Valida los datos para eliminar modelo (método público)
     */
    public function validarEliminarModelo($datos) {
        $datos['accion_eliminar'] = true; // Marcar como acción de eliminación
        return $this->validarModelo($datos, true);
    }

    /**
     * Verifica si un modelo existe por ID
     */
    private function verificarModeloExistente($idModelo) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "SELECT COUNT(*) FROM tbl_modelos WHERE id_modelo = :id_modelo";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(':id_modelo', $idModelo, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarModeloExistente: ' . $e->getMessage());
            return false;
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }

    /**
     * Verifica si una marca existe por ID
     */
    private function verificarMarcaExistente($idMarca) {
        $created = false;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $created = true;
        }
        try {
            $sql = "SELECT COUNT(*) FROM tbl_marcas WHERE id_marca = :id_marca";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(':id_marca', $idMarca, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarMarcaExistente: ' . $e->getMessage());
            return false;
        } finally {
            if (isset($created) && $created && isset($conexion)) { $conexion->cerrar(); }
            if (isset($created) && $created) { $this->conex = null; }
        }
    }
}
?>
