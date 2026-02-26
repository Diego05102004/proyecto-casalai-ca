<?php

namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
use RuntimeException;
class Permisos extends BD {
    private $conex;
    
    // Constantes de validación
    const MAX_ID_ROL = 999999999;
    const MIN_ID_ROL = 1;
    const MAX_ID_MODULO = 999999999;
    const MIN_ID_MODULO = 1;
    const MAX_NOMBRE = 100;
    const MIN_NOMBRE = 1;
    const ACCIONES_VALIDAS = ['ingresar', 'consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];
    const PERMISOS_VALORES = ['on', 'off'];
    
    public function __construct() {
        $this->conex = null;
    }

    // Métodos de validación centralizados
    private function validarPermisos($datos) {
        $errores = [];
        
        // Validar estructura de permisos
        if (!is_array($datos)) {
            $errores['permisos'] = 'Los datos de permisos deben ser un arreglo';
            return $errores;
        }
        
        foreach ($datos as $id_rol => $permisosRol) {
            if (!is_numeric($id_rol) || (int)$id_rol < self::MIN_ID_ROL || (int)$id_rol > self::MAX_ID_ROL) {
                $errores['rol_id'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
                break;
            }
            
            if (!is_array($permisosRol)) {
                $errores['permisos_rol'] = 'Los permisos por rol deben ser un arreglo';
                break;
            }
            
            foreach ($permisosRol as $id_modulo => $permisosModulo) {
                if (!is_numeric($id_modulo) || (int)$id_modulo < self::MIN_ID_MODULO || (int)$id_modulo > self::MAX_ID_MODULO) {
                    $errores['modulo_id'] = 'El ID del módulo debe ser un número entre ' . self::MIN_ID_MODULO . ' y ' . self::MAX_ID_MODULO;
                    break 2;
                }
                
                if (!is_array($permisosModulo)) {
                    $errores['permisos_modulo'] = 'Los permisos por módulo deben ser un arreglo';
                    break 2;
                }
                
                foreach ($permisosModulo as $accion => $valor) {
                    if (!in_array($accion, self::ACCIONES_VALIDAS)) {
                        $errores['accion'] = "La acción '$accion' no es válida. Acciones permitidas: " . implode(', ', self::ACCIONES_VALIDAS);
                        break 3;
                    }
                    
                    if (!in_array($valor, self::PERMISOS_VALORES)) {
                        $errores['valor_permiso'] = 'El valor del permiso debe ser "on" u "off"';
                        break 3;
                    }
                }
            }
        }
        
        return $errores;
    }
    
    private function validarRol($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['rol'] = 'Los datos del rol deben ser un arreglo';
            return $errores;
        }
        
        if (!isset($datos['id_rol'])) {
            $errores['id_rol'] = 'El ID del rol es obligatorio';
        } else {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
            }
        }
        
        if (!isset($datos['nombre_rol'])) {
            $errores['nombre_rol'] = 'El nombre del rol es obligatorio';
        } else {
            $nombre_rol = trim((string)$datos['nombre_rol']);
            if (mb_strlen($nombre_rol) < self::MIN_NOMBRE) {
                $errores['nombre_rol'] = 'El nombre del rol debe tener al menos ' . self::MIN_NOMBRE . ' caracteres';
            } elseif (mb_strlen($nombre_rol) > self::MAX_NOMBRE) {
                $errores['nombre_rol'] = 'El nombre del rol no debe exceder los ' . self::MAX_NOMBRE . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    private function validarModulo($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['modulo'] = 'Los datos del módulo deben ser un arreglo';
            return $errores;
        }
        
        if (!isset($datos['id_modulo'])) {
            $errores['id_modulo'] = 'El ID del módulo es obligatorio';
        } else {
            $id_modulo = (int)$datos['id_modulo'];
            if ($id_modulo < self::MIN_ID_MODULO || $id_modulo > self::MAX_ID_MODULO) {
                $errores['id_modulo'] = 'El ID del módulo debe ser un número entre ' . self::MIN_ID_MODULO . ' y ' . self::MAX_ID_MODULO;
            }
        }
        
        if (!isset($datos['nombre_modulo'])) {
            $errores['nombre_modulo'] = 'El nombre del módulo es obligatorio';
        } else {
            $nombre_modulo = trim((string)$datos['nombre_modulo']);
            if (mb_strlen($nombre_modulo) < self::MIN_NOMBRE) {
                $errores['nombre_modulo'] = 'El nombre del módulo debe tener al menos ' . self::MIN_NOMBRE . ' caracteres';
            } elseif (mb_strlen($nombre_modulo) > self::MAX_NOMBRE) {
                $errores['nombre_modulo'] = 'El nombre del módulo no debe exceder los ' . self::MAX_NOMBRE . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    private function validarAccion($accion) {
        $errores = [];
        
        if (!is_string($accion)) {
            $errores['accion'] = 'La acción debe ser una cadena de texto';
            return $errores;
        }
        
        $accion = trim($accion);
        if ($accion === '') {
            $errores['accion'] = 'La acción no puede estar vacía';
        } elseif (!in_array($accion, self::ACCIONES_VALIDAS)) {
            $errores['accion'] = "La acción '$accion' no es válida. Acciones permitidas: " . implode(', ', self::ACCIONES_VALIDAS);
        }
        
        return $errores;
    }
    
    private function validarListas($roles, $modulos, $acciones) {
        $errores = [];
        
        // Validar roles
        if (!is_array($roles)) {
            $errores['roles'] = 'Los datos de roles deben ser un arreglo';
        } elseif (empty($roles)) {
            $errores['roles'] = 'Debe existir al menos un rol';
        } else {
            foreach ($roles as $rol) {
                $errores_rol = $this->validarRol($rol);
                if (!empty($errores_rol)) {
                    $errores = array_merge($errores, $errores_rol);
                    break;
                }
            }
        }
        
        // Validar módulos
        if (!is_array($modulos)) {
            $errores['modulos'] = 'Los datos de módulos deben ser un arreglo';
        } elseif (empty($modulos)) {
            $errores['modulos'] = 'Debe existir al menos un módulo';
        } else {
            foreach ($modulos as $modulo) {
                $errores_modulo = $this->validarModulo($modulo);
                if (!empty($errores_modulo)) {
                    $errores = array_merge($errores, $errores_modulo);
                    break;
                }
            }
        }
        
        // Validar acciones
        if (!is_array($acciones)) {
            $errores['acciones'] = 'Los datos de acciones deben ser un arreglo';
        } elseif (empty($acciones)) {
            $errores['acciones'] = 'Debe existir al menos una acción';
        } else {
            foreach ($acciones as $accion) {
                $errores_accion = $this->validarAccion($accion);
                if (!empty($errores_accion)) {
                    $errores = array_merge($errores, $errores_accion);
                    break;
                }
            }
        }
        
        return $errores;
    }
    
    // Métodos públicos de validación
    public function validarConsultarPermisos($datos) {
        $errores = [];
        
        // Para consultar permisos, podemos validar por rol o por módulo
        // Solo validar si se proporcionan los datos, no son obligatorios
        if (isset($datos['id_rol'])) {
            $id_rol = (int)$datos['id_rol'];
            if ($id_rol < self::MIN_ID_ROL || $id_rol > self::MAX_ID_ROL) {
                $errores['id_rol'] = 'El ID del rol debe ser un número entre ' . self::MIN_ID_ROL . ' y ' . self::MAX_ID_ROL;
            }
        }
        
        if (isset($datos['id_modulo'])) {
            $id_modulo = (int)$datos['id_modulo'];
            if ($id_modulo < self::MIN_ID_MODULO || $id_modulo > self::MAX_ID_MODULO) {
                $errores['id_modulo'] = 'El ID del módulo debe ser un número entre ' . self::MIN_ID_MODULO . ' y ' . self::MAX_ID_MODULO;
            }
        }
        
        return $errores;
    }
    
    public function validarSeleccionarRol($datos) {
        return $this->validarRol($datos);
    }
    
    public function validarSeleccionarModulo($datos) {
        return $this->validarModulo($datos);
    }
    
    public function validarModificarPermisos($permisosForm, $roles, $modulos, $acciones) {
        $errores = [];
        
        // Validar las listas
        $errores_listas = $this->validarListas($roles, $modulos, $acciones);
        if (!empty($errores_listas)) {
            return $errores_listas;
        }
        
        // Validar la estructura de permisos
        $errores_permisos = $this->validarPermisos($permisosForm);
        if (!empty($errores_permisos)) {
            return $errores_permisos;
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
    
    private function verificarModuloExistente($id_modulo) {
        $conexion = null;
        if (!($this->conex instanceof PDO)) {
            $conexion = new BD('S');
            $this->conex = $conexion->getConexion();
        }
        try {
            $stmt = $this->conex->prepare("SELECT COUNT(*) FROM tbl_modulos WHERE id_modulo = ?");
            $stmt->execute([$id_modulo]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }

    public function getRoles() {
        return $this->o_roles();
    }
    private function o_roles() {
        $conexion = new BD('S');
        $this->conex = $conexion->getConexion();
        try {
            $stmt = $this->conex->query("SELECT id_rol, nombre_rol FROM tbl_rol");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function getModulos() {
        return $this->o_modulos();
    }
    private function o_modulos() {
        $conexion = new BD('S');
        $this->conex = $conexion->getConexion();
        try {
            $stmt = $this->conex->query("SELECT id_modulo, nombre_modulo FROM tbl_modulos");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public static function getPermisosPorRolModulo() {
        return self::o_permisosPorRolModulo();
    }
    private static function o_permisosPorRolModulo() {
        $conexion = new BD('S');
        $conex = $conexion->getConexion();
        try {
            $stmt = $conex->query("SELECT id_rol, id_modulo, accion, estatus FROM tbl_permisos");
            $permisos = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($row['estatus'] === 'Permitido') {
                    $permisos[$row['id_rol']][$row['id_modulo']][$row['accion']] = true;
                }
            }
            return $permisos;
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar(); 
            }
            // Eliminamos la referencia a $this->conex ya que es un método estático
        }
    }

    public function getPermisosUsuarioModulo($id_rol, $nombre_modulo) {
        return $this->o_permisosUsuarioModulo($id_rol, $nombre_modulo);
    }
    private function o_permisosUsuarioModulo($id_rol, $nombre_modulo) {
        // SuperUsuario (id_rol = 6) tiene todos los permisos
        if ((int)$id_rol === 6) {
            return [
                'consultar' => true,
                'incluir' => true,
                'modificar' => true,
                'eliminar' => true,
                'generar reporte' => true
            ];
        }
        $conexion = new BD('S');
        $this->conex = $conexion->getConexion();
        try {
            // Busca el id_modulo por nombre (comparación case-insensitive)
            $stmt = $this->conex->prepare("SELECT id_modulo FROM tbl_modulos WHERE LOWER(nombre_modulo) = LOWER(?) LIMIT 1");
            $stmt->execute([$nombre_modulo]);
            $modulo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$modulo) {
                return [
                    'consultar' => false,
                    'incluir' => false,
                    'modificar' => false,
                    'eliminar' => false,
                    'generar reporte' => false
                ];
            }

            $id_modulo = $modulo['id_modulo'];

            // Obtiene los permisos para ese rol y módulo
            $stmt = $this->conex->prepare("SELECT accion, estatus FROM tbl_permisos WHERE id_rol = ? AND id_modulo = ?");
            $stmt->execute([$id_rol, $id_modulo]);
            $permisos = [
                'consultar' => false,
                'incluir' => false,
                'modificar' => false,
                'eliminar' => false,
                'generar reporte' => false
            ];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $perm) {
                $permisos[$perm['accion']] = ($perm['estatus'] === 'Permitido');
            }
            return $permisos;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function guardarPermisos($permisosForm, $roles, $modulos, $acciones) {
        return $this->g_guardarPermisos($permisosForm, $roles, $modulos, $acciones);
    }
    private function g_guardarPermisos($permisosForm, $roles, $modulos, $acciones) {
        $conexion = new BD('S');
        $this->conex = $conexion->getConexion();
        try {
            // Borra todos los permisos actuales EXCEPTO los del SuperUsuario (id_rol = 6)
            $this->conex->exec("DELETE FROM tbl_permisos WHERE id_rol <> 6");
            // Inserta todos los permisos posibles, EXCEPTO para el SuperUsuario
            $stmt = $this->conex->prepare("INSERT INTO tbl_permisos (id_rol, id_modulo, accion, estatus) VALUES (?, ?, ?, ?)");
            foreach ($roles as $rol) {
                if ($rol['id_rol'] == 6) continue; // Saltar SuperUsuario
                foreach ($modulos as $modulo) {
                    foreach ($acciones as $accion) {
                        $estatus = (isset($permisosForm[$rol['id_rol']][$modulo['id_modulo']][$accion]) && $permisosForm[$rol['id_rol']][$modulo['id_modulo']][$accion] == 'on')
                            ? 'Permitido' : 'No Permitido';
                        $stmt->execute([$rol['id_rol'], $modulo['id_modulo'], $accion, $estatus]);
                    }
                }
            }
            return true;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }
}
?>
