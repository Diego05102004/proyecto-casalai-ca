<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class marca extends BD {
    private $tablemarcas = 'tbl_marcas';
    private $conex;
    private $nombre_marca;
    private $id_marca;
    
    const MAX_NOMBRE_MARCA = 100;
    const MIN_NOMBRE_MARCA = 2;
    const MAX_ID_MARCA = 999999999;
    const MIN_ID_MARCA = 1;

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

    public function __construct($tipo = 'P') {
        parent::__construct($tipo);
    }
    
    /**
     * @return PDO
     */

    public function getConexion() {
        return $this->pdo;
    }
    
    /**
     * @param callable
     * @return mixed
     */

    protected function ejecutarConConexionSegura($operation) {
        $conexion = new BD('P');
        
        try {
            $conexion->getConexion()->beginTransaction();
            
            $resultado = $operation($conexion->getConexion());
            
            $conexion->getConexion()->commit();
            
            return $resultado;
        } catch (Exception $e) {
            if (isset($conexion) && $conexion->getConexion()->inTransaction()) {
                $conexion->getConexion()->rollback();
            }
            throw new \RuntimeException("Error en operación de base de datos: " . $e->getMessage());
        } finally {
            if (isset($conexion)) {
                $conexion->cerrar();
            }
        }
    }

    private function existeNombreMarca($nombre_marca, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_marcas WHERE nombre_marca = ?";
            $params = [$nombre_marca];
            if ($excluir_id !== null) {
                $sql .= " AND id_marca != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function registrarMarca() {
        return $this->r_marca();
    }
    private function r_marca() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_marcas (nombre_marca)
                    VALUES (:nombre_marca)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_marca', $this->nombre_marca);
            return $stmt->execute();
        });
    }

    public function obtenerUltimaMarca() {
        return $this->obtUltimaMarca(); 
    }
    private function obtUltimaMarca() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_marcas ORDER BY id_marca DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function tieneModelosAsociados($id_marca) {
        return $this->verificarModelosAsociados($id_marca);
    }
    private function verificarModelosAsociados($id_marca) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_modelos WHERE id_marca = :id_marca";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_marca', $id_marca, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        });
    }

    public function obtenermarcasPorId($id_marca) {
        return $this->obtmarcasPorId($id_marca);
    }
    private function obtmarcasPorId($id_marca) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $query = "SELECT nombre_marca FROM tbl_marcas WHERE id_marca = ?";
            $stmt = $this->conex->prepare($query);
            $stmt->execute([$id_marca]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function modificarmarcas($id_marca) {
        return $this->m_marcas($id_marca);
    }
    private function m_marcas($id_marca) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "UPDATE tbl_marcas SET nombre_marca = :nombre_marca WHERE id_marca = :id_marca";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_marca', $id_marca);
            $stmt->bindParam(':nombre_marca', $this->nombre_marca);
            return $stmt->execute();
        });
    }

    public function eliminarmarcas($id_marca) {
        return $this->e_marcas($id_marca);
    }
    private function e_marcas($id_marca) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "DELETE FROM tbl_marcas WHERE id_marca = :id_marca";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_marca', $id_marca);
            return $stmt->execute();
        });
    }

    public function getmarcas() {
        return $this->g_marcas();
    }
    private function g_marcas() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $querymarcas = 'SELECT id_marca, nombre_marca FROM ' . $this->tablemarcas. ' ORDER BY id_marca DESC';
            $stmtmarcas = $this->conex->prepare($querymarcas);
            $stmtmarcas->execute();
            return $stmtmarcas->fetchAll(PDO::FETCH_ASSOC);
        });
    }
    
    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para operaciones CRUD de marcas
     */
    private function validarMarca($datos, $esModificacion = false) {
        $errores = [];
        
        // Validar nombre de la marca (solo para registro y modificación)
        if (!isset($datos['accion_eliminar'])) {
            if (!isset($datos['nombre_marca'])) {
                $errores['nombre_marca'] = 'El nombre de la marca es obligatorio';
            } else {
                $nombre_marca = trim($datos['nombre_marca']);
                if (empty($nombre_marca)) {
                    $errores['nombre_marca'] = 'El nombre de la marca es obligatorio';
                } elseif (mb_strlen($nombre_marca) < self::MIN_NOMBRE_MARCA || mb_strlen($nombre_marca) > self::MAX_NOMBRE_MARCA) {
                    $errores['nombre_marca'] = 'El nombre de la marca debe tener entre ' . self::MIN_NOMBRE_MARCA . ' y ' . self::MAX_NOMBRE_MARCA . ' caracteres';
                } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_marca)) {
                    $errores['nombre_marca'] = 'El nombre de la marca solo puede contener letras, números, espacios y caracteres especiales comunes';
                }
            }
        }
        
        // Validar ID de la marca (solo para modificaciones y eliminaciones)
        if ($esModificacion || isset($datos['accion_eliminar'])) {
            if (!isset($datos['id_marca'])) {
                $errores['id_marca'] = 'El ID de la marca es obligatorio';
            } elseif (!is_numeric($datos['id_marca']) || $datos['id_marca'] < self::MIN_ID_MARCA || $datos['id_marca'] > self::MAX_ID_MARCA) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número entre ' . self::MIN_ID_MARCA . ' y ' . self::MAX_ID_MARCA;
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para registrar marca (método público)
     */
    public function validarRegistrarMarca($datos) {
        return $this->validarMarca($datos, false);
    }
    
    /**
     * Valida los datos para consultar marca (método público)
     */
    public function validarConsultarMarca($datos) {
        $errores = [];
        
        // Validar ID de la marca (opcional para consulta)
        if (isset($datos['id_marca'])) {
            if (!is_numeric($datos['id_marca']) || $datos['id_marca'] < self::MIN_ID_MARCA || $datos['id_marca'] > self::MAX_ID_MARCA) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número entre ' . self::MIN_ID_MARCA . ' y ' . self::MAX_ID_MARCA;
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para modificar marca (método público)
     */
    public function validarModificarMarca($datos) {
        return $this->validarMarca($datos, true);
    }
    
    /**
     * Valida los datos para eliminar marca (método público)
     */
    public function validarEliminarMarca($datos) {
        $datos['accion_eliminar'] = true; // Marcar como acción de eliminación
        return $this->validarMarca($datos, true);
    }
    
    /**
     * Verifica si una marca existe por ID
     */
    private function verificarMarcaExistente($idMarca) {
        $conexion = null;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
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
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }
}
?>
