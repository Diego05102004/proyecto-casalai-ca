<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
class Rol extends BD {

    private $id_rol;
    private $nombre_rol;

    // Constantes de validación
    const MAX_ID_ROL = 999999999;
    const MIN_ID_ROL = 1;
    const MAX_NOMBRE_ROL = 100;
    const MIN_NOMBRE_ROL = 2;

    public function getIdRol() { 
        return $this->id_rol; 
    }
    public function setIdRol($id_rol) { 
        $this->id_rol = $id_rol; 
    }

    public function getNombreRol() { 
        return $this->nombre_rol; 
    }
    public function setNombreRol($nombre_rol) { 
        $this->nombre_rol = $nombre_rol; 
    }

    public function __construct($tipo = 'S') {
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
        try {
            parent::__construct('S'); 
            $pdo = parent::getConexion(); 

            if (!$pdo instanceof \PDO) {
                throw new \RuntimeException("La conexión PDO no es válida o es nula.");
            }

            $pdo->beginTransaction();
            $resultado = $operation($pdo);
            $pdo->commit();
            
            return $resultado;
        } catch (\Exception $e) {
            $pdo = parent::getConexion();
            if ($pdo instanceof \PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new \RuntimeException("Error en operación de base de datos: " . $e->getMessage());
        } finally {
            $this->cerrar();
        }
    }

    private function validarRol($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['rol'] = 'Los datos del rol deben ser un arreglo';
            return $errores;
        }
        
        // Validar ID del rol
        if (isset($datos['id_rol'])) {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
            }
        }
        
        // Validar nombre del rol
        if (isset($datos['nombre_rol'])) {
            $nombre_rol = trim((string)$datos['nombre_rol']);
            if ($nombre_rol === '') {
                $errores['nombre_rol'] = 'El nombre del rol es obligatorio';
            } elseif (mb_strlen($nombre_rol) < self::MIN_NOMBRE_ROL || mb_strlen($nombre_rol) > self::MAX_NOMBRE_ROL) {
                $errores['nombre_rol'] = 'El nombre del rol debe tener entre ' . self::MIN_NOMBRE_ROL . ' y ' . self::MAX_NOMBRE_ROL . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_rol)) {
                $errores['nombre_rol'] = 'El nombre del rol solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        return $errores;
    }
    
    // Métodos públicos de validación
    public function validarConsultarRol($datos) {
        $errores = [];
        
        // Para consultar, podemos validar por ID o sin filtros
        if (isset($datos['id_rol'])) {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
            }
        }
        
        return $errores;
    }
    
    public function validarRegistrarRol($datos) {
        $errores = [];
        
        // Para registrar, requerimos campos obligatorios
        if (!isset($datos['nombre_rol'])) {
            $errores['nombre_rol'] = 'El nombre del rol es obligatorio';
        }
        
        // Validar el rol completo
        $errores_rol = $this->validarRol($datos);
        if (!empty($errores_rol)) {
            $errores = array_merge($errores, $errores_rol);
        }
        
        return $errores;
    }
    
    public function validarModificarRol($datos) {
        $errores = [];
        
        // Para modificar, el ID es obligatorio
        if (!isset($datos['id_rol'])) {
            $errores['id_rol'] = 'El ID del rol es obligatorio';
        }
        
        // Para modificar, el nombre también es obligatorio
        if (!isset($datos['nombre_rol'])) {
            $errores['nombre_rol'] = 'El nombre del rol es obligatorio';
        }
        
        // Validar el rol completo
        $errores_rol = $this->validarRol($datos);
        if (!empty($errores_rol)) {
            $errores = array_merge($errores, $errores_rol);
        }
        
        return $errores;
    }
    
    public function validarEliminarRol($datos) {
        $errores = [];
        
        // Para eliminar, el ID es obligatorio
        if (!isset($datos['id_rol'])) {
            $errores['id_rol'] = 'El ID del rol es obligatorio';
        } else {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
            }
        }
        
        return $errores;
    }

    public function registrarRol() {
        return $this->r_Rol();
    }
    private function r_Rol() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "INSERT INTO tbl_rol (nombre_rol) VALUES (:nombre_rol)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':nombre_rol', $this->nombre_rol);
                $stmt->execute();

                $id_rol = $pdo->lastInsertId();

                $sqlModulos = "SELECT id_modulo FROM tbl_modulos";
                $stmtMod = $pdo->prepare($sqlModulos);
                $stmtMod->execute();
                $modulos = $stmtMod->fetchAll(PDO::FETCH_COLUMN);

                $acciones = ['Ingresar', 'Incluir', 'Consultar', 'Modificar', 'Eliminar', 'Reportar'];

                $sqlPermiso = "INSERT INTO tbl_permisos (accion, id_rol, id_modulo, estatus) 
                            VALUES (:accion, :id_rol, :id_modulo, 'No Permitido')";
                $stmtPermiso = $pdo->prepare($sqlPermiso);

                foreach ($modulos as $id_modulo) {
                    foreach ($acciones as $accion) {
                        $stmtPermiso->execute([
                            ':accion' => $accion,
                            ':id_rol' => $id_rol,
                            ':id_modulo' => $id_modulo
                        ]);
                    }
                }
                return true;

            } catch (PDOException $e) {
                echo "Error al registrar el rol y asignar permisos: " . $e->getMessage();
                return false;
            }
        });
    }

    private function existeNombreRol($nombre_rol, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nombre_rol, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_rol WHERE nombre_rol = ?";
            $params = [$nombre_rol];
            if ($excluir_id !== null) {
                $sql .= " AND id_rol != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $ret = $stmt->fetchColumn() > 0;
            return $ret;
        });
    }

    public function obtenerUltimoRol() {
        return $this->obtUltimoRol(); 
    }
    private function obtUltimoRol() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT * FROM tbl_rol ORDER BY id_rol DESC LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $rol = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $rol ? $rol : null;
            } catch (PDOException $e) {
                
                return null;
            }
        });
    }

    public function obtenerRolPorId($id_rol) {
        return $this->rolporid($id_rol); 
    }
    private function rolporid($id_rol) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol) {
            $query = "SELECT * FROM tbl_rol WHERE id_rol = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id_rol]);
            $rol_obt = $stmt->fetch(PDO::FETCH_ASSOC);
            return $rol_obt;
        });
    }

    public function consultarRoles() {
        return $this->c_roles(); 
    }
    private function c_roles() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_rol, nombre_rol FROM tbl_rol ORDER BY id_rol DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $roles_obt = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $roles_obt;
        });
    }

    public function modificarRol($id_rol) {
        return $this->m_rol($id_rol); 
    }
    private function m_rol($id_rol) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol) {
            $sql = "UPDATE tbl_rol SET nombre_rol = :nombre_rol WHERE id_rol = :id_rol";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_rol', $id_rol);
            $stmt->bindParam(':nombre_rol', $this->nombre_rol);
            $ok = $stmt->execute();
            return $ok;
        });
    }

    public function eliminarRol($id_rol) {
        return $this->e_rol($id_rol); 
    }
    private function e_rol($id_rol) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol) {
            $sql = "DELETE FROM tbl_rol WHERE id_rol = :id_rol";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_rol', $id_rol);
            $result = $stmt->execute();
            return $result;
        });
    }

    public function tieneUsuariosAsignados($id_rol) {
        return $this->tieneUsuAsignados($id_rol); 
    }
    private function tieneUsuAsignados($id_rol) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_rol) {
            $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE id_rol = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_rol]);
            $ret = $stmt->fetchColumn() > 0;
            return $ret;
        });
    }
}
?>