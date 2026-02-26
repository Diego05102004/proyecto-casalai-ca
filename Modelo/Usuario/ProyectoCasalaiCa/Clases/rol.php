<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
class Rol extends BD {

    private $id_rol;
    private $nombre_rol;
    private $conex;

    // Constantes de validación
    const MAX_ID_ROL = 999999999;
    const MIN_ID_ROL = 1;
    const MAX_NOMBRE_ROL = 100;
    const MIN_NOMBRE_ROL = 2;

    public function __construct() {
        $this->conex = null;
    }

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

    // Métodos de validación centralizados
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
    
    // Métodos auxiliares
    private function verificarRolExistente($id_rol) {
        $conexion = null;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        try {
            $stmt = $this->conex->prepare("SELECT COUNT(*) FROM tbl_rol WHERE id_rol = ?");
            $stmt->execute([$id_rol]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    } 

    public function registrarRol() {
        return $this->r_Rol();
    }
    private function r_Rol() {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        try {
            // 1. Insertar el nuevo rol
            $sql = "INSERT INTO tbl_rol (nombre_rol) VALUES (:nombre_rol)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':nombre_rol', $this->nombre_rol);
            $stmt->execute();

            // 2. Obtener el ID del nuevo rol
            $id_rol = $this->conex->lastInsertId();

            // 3. Obtener todos los módulos
            $sqlModulos = "SELECT id_modulo FROM tbl_modulos";
            $stmtMod = $this->conex->prepare($sqlModulos);
            $stmtMod->execute();
            $modulos = $stmtMod->fetchAll(PDO::FETCH_COLUMN);

            // 4. Acciones disponibles
            $acciones = ['Ingresar', 'Incluir', 'Consultar', 'Modificar', 'Eliminar', 'Reportar'];

            // 5. Insertar permisos como "No Permitido" (usar autoincrement del ID)
            $sqlPermiso = "INSERT INTO tbl_permisos (accion, id_rol, id_modulo, estatus) 
                        VALUES (:accion, :id_rol, :id_modulo, 'No Permitido')";
            $stmtPermiso = $this->conex->prepare($sqlPermiso);

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
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }

    public function existeNombreRol($nombre_rol, $excluir_id = null) {
        return $this->existeNomRol($nombre_rol, $excluir_id); 
    }
    private function existeNomRol($nombre_rol, $excluir_id) {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        $sql = "SELECT COUNT(*) FROM tbl_rol WHERE nombre_rol = ?";
        $params = [$nombre_rol];
        if ($excluir_id !== null) {
            $sql .= " AND id_rol != ?";
            $params[] = $excluir_id;
        }
        $stmt = $this->conex->prepare($sql);
        $stmt->execute($params);
        $ret = $stmt->fetchColumn() > 0;
        if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        return $ret;
    }

    public function obtenerUltimoRol() {
        return $this->obtUltimoRol(); 
    }
    private function obtUltimoRol() {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        try {
            $sql = "SELECT * FROM tbl_rol ORDER BY id_rol DESC LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $rol = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $rol ? $rol : null;
        } catch (PDOException $e) {
            
            return null;
        } finally { if ($conexion) { $conexion->cerrar(); $this->conex = null; } }
    }

    public function obtenerRolPorId($id_rol) {
        return $this->rolporid($id_rol); 
    }
    private function rolporid($id_rol) {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        $query = "SELECT * FROM tbl_rol WHERE id_rol = ?";
        $stmt = $this->conex->prepare($query);
        $stmt->execute([$id_rol]);
        $rol_obt = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        return $rol_obt;
    }

    public function consultarRoles() {
        return $this->c_roles(); 
    }
    private function c_roles() {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        $sql = "SELECT id_rol, nombre_rol FROM tbl_rol ORDER BY id_rol DESC";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute();
        $roles_obt = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        return $roles_obt;
    }

    public function modificarRol($id_rol) {
        return $this->m_rol($id_rol); 
    }
    private function m_rol($id_rol) {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        $sql = "UPDATE tbl_rol SET nombre_rol = :nombre_rol WHERE id_rol = :id_rol";
        $stmt = $this->conex->prepare($sql);
        $stmt->bindParam(':id_rol', $id_rol);
        $stmt->bindParam(':nombre_rol', $this->nombre_rol);
        $ok = $stmt->execute();
        if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        return $ok;
    }

    public function eliminarRol($id_rol) {
        return $this->e_rol($id_rol); 
    }
    private function e_rol($id_rol) {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        $sql = "DELETE FROM tbl_rol WHERE id_rol = :id_rol";
        $stmt = $this->conex->prepare($sql);
        $stmt->bindParam(':id_rol', $id_rol);
        $result = $stmt->execute();
        if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        return $result;
    }

    public function tieneUsuariosAsignados($id_rol) {
        return $this->tieneUsuAsignados($id_rol); 
    }
    private function tieneUsuAsignados($id_rol) {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        $sql = "SELECT COUNT(*) FROM tbl_usuarios WHERE id_rol = ?";
        $stmt = $this->conex->prepare($sql);
        $stmt->execute([$id_rol]);
        $ret = $stmt->fetchColumn() > 0;
        if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        return $ret;
    }

}
?>
