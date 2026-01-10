<?php

namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;

use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
use RuntimeException;
class Permisos extends BD {
    private $conex;
    
    public function __construct() {
        $this->conex = null;
    }

    public function validarDatos($permisosForm, $roles, $modulos, $acciones) {
        $errores = [];

        // Validar que los datos de entrada sean arrays
        if (!is_array($permisosForm)) {
            $errores['permisos_form'] = 'Los datos de permisos deben ser un arreglo';
        }

        if (!is_array($roles)) {
            $errores['roles'] = 'Los datos de roles deben ser un arreglo';
        }

        if (!is_array($modulos)) {
            $errores['modulos'] = 'Los datos de módulos deben ser un arreglo';
        }

        if (!is_array($acciones)) {
            $errores['acciones'] = 'Los datos de acciones deben ser un arreglo';
        }

        // Validar que no estén vacíos
        if (empty($roles)) {
            $errores['roles_vacios'] = 'Debe existir al menos un rol';
        }

        if (empty($modulos)) {
            $errores['modulos_vacios'] = 'Debe existir al menos un módulo';
        }

        if (empty($acciones)) {
            $errores['acciones_vacias'] = 'Debe existir al menos una acción';
        }

        // Validar estructura de roles
        if (is_array($roles)) {
            foreach ($roles as $rol) {
                if (!is_array($rol) || !isset($rol['id_rol']) || !isset($rol['nombre_rol'])) {
                    $errores['roles_estructura'] = 'La estructura de los roles es inválida';
                    break;
                }

                $id_rol = (int)$rol['id_rol'];
                if ($id_rol <= 0) {
                    $errores['rol_id_invalido'] = 'El ID del rol debe ser un número positivo';
                }

                $nombre_rol = trim((string)$rol['nombre_rol']);
                if ($nombre_rol === '') {
                    $errores['rol_nombre_vacio'] = 'El nombre del rol no puede estar vacío';
                } elseif (mb_strlen($nombre_rol) > 100) {
                    $errores['rol_nombre_largo'] = 'El nombre del rol no debe exceder los 100 caracteres';
                }
            }
        }

        // Validar estructura de módulos
        if (is_array($modulos)) {
            foreach ($modulos as $modulo) {
                if (!is_array($modulo) || !isset($modulo['id_modulo']) || !isset($modulo['nombre_modulo'])) {
                    $errores['modulos_estructura'] = 'La estructura de los módulos es inválida';
                    break;
                }

                $id_modulo = (int)$modulo['id_modulo'];
                if ($id_modulo <= 0) {
                    $errores['modulo_id_invalido'] = 'El ID del módulo debe ser un número positivo';
                }

                $nombre_modulo = trim((string)$modulo['nombre_modulo']);
                if ($nombre_modulo === '') {
                    $errores['modulo_nombre_vacio'] = 'El nombre del módulo no puede estar vacío';
                } elseif (mb_strlen($nombre_modulo) > 100) {
                    $errores['modulo_nombre_largo'] = 'El nombre del módulo no debe exceder los 100 caracteres';
                }
            }
        }

        // Validar estructura de acciones
        if (is_array($acciones)) {
            $acciones_validas = ['ingresar', 'consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];
            foreach ($acciones as $accion) {
                if (!is_string($accion)) {
                    $errores['accion_tipo'] = 'Las acciones deben ser cadenas de texto';
                    break;
                }

                $accion = trim($accion);
                if ($accion === '') {
                    $errores['accion_vacia'] = 'Las acciones no pueden estar vacías';
                } elseif (!in_array($accion, $acciones_validas)) {
                    $errores['accion_invalida'] = "La acción '$accion' no es válida";
                }
            }
        }

        // Validar estructura de permisos del formulario
        if (is_array($permisosForm)) {
            foreach ($permisosForm as $id_rol => $permisosRol) {
                if (!is_numeric($id_rol) || (int)$id_rol <= 0) {
                    $errores['permiso_rol_id'] = 'El ID del rol en los permisos debe ser un número positivo';
                    break;
                }

                if (!is_array($permisosRol)) {
                    $errores['permiso_rol_estructura'] = 'Los permisos por rol deben ser un arreglo';
                    break;
                }

                foreach ($permisosRol as $id_modulo => $permisosModulo) {
                    if (!is_numeric($id_modulo) || (int)$id_modulo <= 0) {
                        $errores['permiso_modulo_id'] = 'El ID del módulo en los permisos debe ser un número positivo';
                        break 2;
                    }

                    if (!is_array($permisosModulo)) {
                        $errores['permiso_modulo_estructura'] = 'Los permisos por módulo deben ser un arreglo';
                        break 2;
                    }

                    foreach ($permisosModulo as $accion => $valor) {
                        if (!in_array($accion, $acciones_validas)) {
                            $errores['permiso_accion_invalida'] = "La acción '$accion' en los permisos no es válida";
                            break 3;
                        }

                        if (!is_string($valor) || !in_array($valor, ['on', 'off'])) {
                            $errores['permiso_valor_invalido'] = 'El valor del permiso debe ser "on" u "off"';
                            break 3;
                        }
                    }
                }
            }
        }

        return $errores;
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
