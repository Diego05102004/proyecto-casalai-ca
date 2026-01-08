<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
class Proveedores extends BD {
    
    private $conex;
    private $id_proveedor;
    private $nombre;
    private $representante;
    private $rif1;
    private $rif2;
    private $telefono1; 
    private $telefono2;
    private $direccion;
    private $correo;
    private $observacion;
    private $activo=1;
    private $tableproveedor= 'tbl_proveedores';

    public function __construct() {
        $this->conex = null;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getRepresentante() {
        return $this->representante;
    }

    public function setRepresentante($representante) {
        $this->representante = $representante;
    }

    public function getRif1() {
        return $this->rif1;
    }

    public function setRif1($rif1) {
        $this->rif1 = $rif1;
    }

    public function getRif2() {
        return $this->rif2;
    }

    public function setRif2($rif2) {
        $this->rif2 = $rif2;
    }

    public function getTelefono1() {
        return $this->telefono1;
    }

    public function setTelefono1($telefono1) {
        $this->telefono1 = $telefono1;
    }

    public function getTelefono2() {
        return $this->telefono2;
    }

    public function setTelefono2($telefono2) {
        $this->telefono2 = $telefono2;
    }

    public function getDireccion() {
        return $this->direccion;
    }

    public function setDireccion($direccion) {
        $this->direccion = $direccion;
    }

    public function getCorreo() {
        return $this->correo;
    }

    public function setCorreo($correo) {
        $this->correo = $correo;
    }

    public function getObservacion() {
        return $this->observacion;
    }

    public function setObservacion($observacion) {
        $this->observacion = $observacion;
    }

    public function getIdProveedor() {
        return $this->id_proveedor;
    }

    public function setIdProveedor($id_proveedor) {
        $this->id_proveedor = $id_proveedor;
    }

    public function validarDatos($esModificacion = false) {
        $errores = [];

        // Validar nombre del proveedor
        $nombre = trim((string)$this->nombre);
        if ($nombre === '') {
            $errores['nombre_proveedor'] = 'El nombre del proveedor es obligatorio';
        } elseif (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 200) {
            $errores['nombre_proveedor'] = 'El nombre del proveedor debe tener entre 2 y 200 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
            $errores['nombre_proveedor'] = 'El nombre del proveedor solo puede contener letras, números, espacios y caracteres especiales comunes';
        }

        // Validar RIF del proveedor
        $rif1 = trim((string)$this->rif1);
        if ($rif1 === '') {
            $errores['rif_proveedor'] = 'El RIF del proveedor es obligatorio';
        } elseif (mb_strlen($rif1) < 5 || mb_strlen($rif1) > 20) {
            $errores['rif_proveedor'] = 'El RIF del proveedor debe tener entre 5 y 20 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $rif1)) {
            $errores['rif_proveedor'] = 'El RIF del proveedor solo puede contener letras, números y guiones';
        }

        // Validar nombre del representante
        $representante = trim((string)$this->representante);
        if ($representante === '') {
            $errores['nombre_representante'] = 'El nombre del representante es obligatorio';
        } elseif (mb_strlen($representante) < 2 || mb_strlen($representante) > 200) {
            $errores['nombre_representante'] = 'El nombre del representante debe tener entre 2 y 200 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $representante)) {
            $errores['nombre_representante'] = 'El nombre del representante solo puede contener letras, números, espacios y caracteres especiales comunes';
        }

        // Validar RIF del representante
        $rif2 = trim((string)$this->rif2);
        if ($rif2 === '') {
            $errores['rif_representante'] = 'El RIF del representante es obligatorio';
        } elseif (mb_strlen($rif2) < 5 || mb_strlen($rif2) > 20) {
            $errores['rif_representante'] = 'El RIF del representante debe tener entre 5 y 20 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $rif2)) {
            $errores['rif_representante'] = 'El RIF del representante solo puede contener letras, números y guiones';
        }

        // Validar correo electrónico (opcional pero si se proporciona debe ser válido)
        $correo = trim((string)$this->correo);
        if ($correo !== '') {
            if (mb_strlen($correo) > 255) {
                $errores['correo_proveedor'] = 'El correo electrónico no debe exceder los 255 caracteres';
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $errores['correo_proveedor'] = 'El formato del correo electrónico no es válido';
            }
        }

        // Validar dirección (opcional)
        $direccion = trim((string)$this->direccion);
        if ($direccion !== '' && mb_strlen($direccion) > 500) {
            $errores['direccion_proveedor'] = 'La dirección no debe exceder los 500 caracteres';
        }

        // Validar teléfono 1 (opcional pero si se proporciona debe ser válido)
        $telefono1 = trim((string)$this->telefono1);
        if ($telefono1 !== '') {
            if (mb_strlen($telefono1) < 7 || mb_strlen($telefono1) > 20) {
                $errores['telefono_1'] = 'El teléfono 1 debe tener entre 7 y 20 caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono1)) {
                $errores['telefono_1'] = 'El teléfono 1 solo puede contener números, guiones, paréntesis y el signo +';
            }
        }

        // Validar teléfono 2 (opcional)
        $telefono2 = trim((string)$this->telefono2);
        if ($telefono2 !== '') {
            if (mb_strlen($telefono2) < 7 || mb_strlen($telefono2) > 20) {
                $errores['telefono_2'] = 'El teléfono 2 debe tener entre 7 y 20 caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono2)) {
                $errores['telefono_2'] = 'El teléfono 2 solo puede contener números, guiones, paréntesis y el signo +';
            }
        }

        // Validar observación (opcional)
        $observacion = trim((string)$this->observacion);
        if ($observacion !== '' && mb_strlen($observacion) > 1000) {
            $errores['observacion'] = 'La observación no debe exceder los 1000 caracteres';
        }

        // Validar ID del proveedor (solo para modificaciones)
        if ($esModificacion) {
            $id_proveedor = (int)$this->id_proveedor;
            if ($id_proveedor <= 0) {
                $errores['id_proveedor'] = 'El ID del proveedor no es válido';
            }
        }

        return $errores;
    }

    public function existeNombreProveedor($nombre, $excluir_id = null) {
        return $this->existeNomProveedor($nombre, $excluir_id); 
    }
    private function existeNomProveedor($nombre, $excluir_id) {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
        }
        try {
            $sql = "SELECT COUNT(*) FROM tbl_proveedores WHERE nombre_proveedor = ?";
            $params = [$nombre];
            if ($excluir_id !== null) {
                $sql .= " AND id_proveedor != ?";
                $params[] = $excluir_id;
            }
            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }

    public function existeRifProveedor($rif, $excluir_id = null) {
        return $this->existeRifProv($rif, $excluir_id);
    }
    private function existeRifProv($rif, $excluir_id) {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
        }
        try {
            $sql = "SELECT COUNT(*) FROM tbl_proveedores WHERE rif_proveedor = ?";
            $params = [$rif];
            if ($excluir_id !== null) {
                $sql .= " AND id_proveedor != ?";
                $params[] = $excluir_id;
            }
            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }

    public function existeRifRepresentante($rif, $excluir_id = null) {
        return $this->existeRifRep($rif, $excluir_id);
    }
    private function existeRifRep($rif, $excluir_id) {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
        }
        try {
            $sql = "SELECT COUNT(*) FROM tbl_proveedores WHERE rif_representante = ?";
            $params = [$rif];
            if ($excluir_id !== null) {
                $sql .= " AND id_proveedor != ?";
                $params[] = $excluir_id;
            }
            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }

    public function registrarProveedor() {
        return $this->r_proveedor();
    }
    private function r_proveedor() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "INSERT INTO tbl_proveedores (`nombre_proveedor`, `rif_proveedor`, `nombre_representante`, `rif_representante`, `correo_proveedor`, `direccion_proveedor`, `telefono_1`, `telefono_2`, `observacion`)
                    VALUES (:nombre, :rif1, :representante, :rif2, :correo, :direccion, :telefono1, :telefono2, :observacion)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':rif1', $this->rif1);
            $stmt->bindParam(':representante', $this->representante);
            $stmt->bindParam(':rif2', $this->rif2);
            $stmt->bindParam(':correo', $this->correo);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->bindParam(':telefono1', $this->telefono1);
            $stmt->bindParam(':telefono2', $this->telefono2);
            $stmt->bindParam(':observacion', $this->observacion);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function obtenerUltimoProveedor() {
        return $this->obtUltimoProveedor(); 
    }
    private function obtUltimoProveedor() {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
        }
        try {
            $sql = "SELECT * FROM tbl_proveedores ORDER BY id_proveedor DESC LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
            return $proveedor ? $proveedor : null;
        } catch (PDOException $e) {
            return null;
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }

    public function obtenerReporteSuministroProveedores() {
        return $this->obtReporteSuministroProveedores();
    }
    private function obtReporteSuministroProveedores() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT p.nombre_proveedor, SUM(dp.cantidad) AS cantidad
                    FROM tbl_proveedores p
                    JOIN tbl_recepcion_productos r ON p.id_proveedor = r.id_proveedor
                    JOIN tbl_detalle_recepcion_productos dp ON r.id_recepcion = dp.id_recepcion
                    GROUP BY p.id_proveedor, p.nombre_proveedor
                    ORDER BY cantidad DESC
                    LIMIT 10";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function obtenerProveedorPorId($id_proveedor) {
        return $this->obtProveedorPorId($id_proveedor);
    }
    private function obtProveedorPorId($id_proveedor) {
        $conexion = null;
        if ($this->conex === null) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
        }
        try {
            $query = "SELECT * FROM tbl_proveedores WHERE id_proveedor = ?";
            $stmt = $this->conex->prepare($query);
            $stmt->execute([$id_proveedor]);
            $proveedores = $stmt->fetch(PDO::FETCH_ASSOC);
            return $proveedores;
        } finally {
            if ($conexion) { $conexion->cerrar(); $this->conex = null; }
        }
    }

    public function modificarProveedor($id_proveedor) {
        return $this->m_proveedor($id_proveedor);
    }
    private function m_proveedor($id_proveedor) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_proveedores SET nombre_proveedor = :nombre, rif_proveedor = :rif1, nombre_representante = :representante, rif_representante = :rif2, correo_proveedor = :correo, direccion_proveedor = :direccion, telefono_1 = :telefono1, telefono_2 = :telefono2, observacion = :observacion WHERE id_proveedor = :id_proveedor";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_proveedor', $id_proveedor);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':rif1', $this->rif1);
            $stmt->bindParam(':representante', $this->representante);
            $stmt->bindParam(':rif2', $this->rif2);
            $stmt->bindParam(':correo', $this->correo);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->bindParam(':telefono1', $this->telefono1);
            $stmt->bindParam(':telefono2', $this->telefono2);
            $stmt->bindParam(':observacion', $this->observacion);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function eliminarProveedor($id_proveedor) {
        return $this->e_proveedor($id_proveedor);
    }
    private function e_proveedor($id_proveedor) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "DELETE FROM tbl_proveedores WHERE id_proveedor = :id_proveedor";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_proveedor', $id_proveedor);
            $result = $stmt->execute();
            return $result;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function getproveedores() {
        return $this->g_proveedores();
    }
    private function g_proveedores() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $queryproveedores = 'SELECT * FROM ' . $this->tableproveedor;
            $stmtproveedores = $this->conex->prepare($queryproveedores);
            $stmtproveedores->execute();
            $proveedores = $stmtproveedores->fetchAll(PDO::FETCH_ASSOC);
            return $proveedores;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function getRankingProveedores() {
        return $this->getRankingProv();
    }
    private function getRankingProv() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "
                SELECT p.nombre_proveedor, pr.nombre_producto, d.cantidad, d.costo, d.cantidad*d.costo AS total, r.fecha
                FROM tbl_recepcion_productos r
                INNER JOIN tbl_proveedores p ON r.id_proveedor = p.id_proveedor
                INNER JOIN tbl_detalle_recepcion_productos d ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_productos pr ON pr.id_producto = d.id_producto
                GROUP BY p.nombre_proveedor
                ORDER BY total DESC
            ";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function getComparacionPreciosProducto() {
        return $this->getComparacionPreciosProd();
    }
    private function getComparacionPreciosProd() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "
                SELECT 
                    pr.id_producto,
                    pr.nombre_producto,
                    p.nombre_proveedor,
                    SUM(d.cantidad) AS cantidad,
                    AVG(d.costo) AS precio_promedio,
                    COUNT(*) AS cantidad_registros,
                    MIN(r.fecha) AS fecha,
                    MONTH(MIN(r.fecha)) AS mes_num,
                    YEAR(MIN(r.fecha)) AS anio
                FROM 
                    tbl_detalle_recepcion_productos d
                INNER JOIN tbl_recepcion_productos r ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_proveedores p ON r.id_proveedor = p.id_proveedor
                INNER JOIN tbl_productos pr ON pr.id_producto = d.id_producto
                GROUP BY 
                    pr.id_producto,
                    pr.nombre_producto,
                    p.nombre_proveedor
                ORDER BY 
                    pr.id_producto,
                    precio_promedio DESC;
            ";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function getDependenciaProveedores() {
        return $this->getDependenciaProv();
    }
    private function getDependenciaProv() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "
                SELECT p.nombre_proveedor, SUM(d.cantidad * d.costo) AS monto_total_pagado, 
                ROUND( (SUM(d.cantidad * d.costo) * 100.0 / (SELECT SUM(d2.cantidad * d2.costo) 
                FROM tbl_detalle_recepcion_productos d2 
                INNER JOIN tbl_recepcion_productos r2 ON d2.id_recepcion = r2.id_recepcion) ), 2 )
                 AS dependencia_porcentaje 
                FROM tbl_recepcion_productos r
                INNER JOIN tbl_proveedores p ON r.id_proveedor = p.id_proveedor 
                INNER JOIN tbl_detalle_recepcion_productos d ON d.id_recepcion = r.id_recepcion 
                GROUP BY p.nombre_proveedor 
                ORDER BY dependencia_porcentaje DESC;
            ";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }


    public function cambiarEstatus($nuevoEstatus) {
        return $this->cam_Estatus($nuevoEstatus); 
    }
    private function cam_Estatus($nuevoEstatus) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_proveedores SET estado = :estatus WHERE id_proveedor = :id_proveedor";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':estatus', $nuevoEstatus);
            $stmt->bindParam(':id_proveedor', $this->id_proveedor);
            return $stmt->execute();
        } catch (PDOException $e) {
            // logging opcional
            return false;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }
}

?>
