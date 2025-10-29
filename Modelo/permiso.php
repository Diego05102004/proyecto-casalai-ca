<?php

require_once __DIR__ . '/../config/config.php';

class Permisos extends BD {
    private $conex;
    
    public function __construct() {
        $this->conex = null;
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

    public function getPermisosPorRolModulo() {
        return $this->o_permisosPorRolModulo();
    }
    private function o_permisosPorRolModulo() {
        $conexion = new BD('S');
        $this->conex = $conexion->getConexion();
        try {
            $stmt = $this->conex->query("SELECT id_rol, id_modulo, accion, estatus FROM tbl_permisos");
            $permisos = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($row['estatus'] === 'Permitido') {
                    $permisos[$row['id_rol']][$row['id_modulo']][$row['accion']] = true;
                }
            }
            return $permisos;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
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