<?php
require_once 'config/config.php';

class Cuentabanco extends BD {

    private $id_cuenta;
    private $nombre_banco;
    private $numero_cuenta;
    private $rif_cuenta;
    private $telefono_cuenta;
    private $correo_cuenta;
    private $metodos_pago;
    private $estado;

    public function __construct() {
        $this->db = null;
    }

    public function getIdCuenta() { 
        return $this->id_cuenta; 
    }
    public function setIdCuenta($id_cuenta) { 
        $this->id_cuenta = $id_cuenta; 
    }

    public function getNombreBanco() { 
        return $this->nombre_banco; 
    }
    public function setNombreBanco($nombre_banco) { 
        $this->nombre_banco = $nombre_banco; 
    }

    public function getNumeroCuenta() { 
        return $this->numero_cuenta; 
    }
    public function setNumeroCuenta($numero_cuenta) { 
        $this->numero_cuenta = $numero_cuenta; 
    }

    public function getRifCuenta() { 
        return $this->rif_cuenta; 
    }
    public function setRifCuenta($rif_cuenta) { 
        $this->rif_cuenta = $rif_cuenta; 
    }

    public function getTelefonoCuenta() { 
        return $this->telefono_cuenta; 
    }
    public function setTelefonoCuenta($telefono_cuenta) { 
        $this->telefono_cuenta = $telefono_cuenta; 
    }

    public function getCorreoCuenta() { 
        return $this->correo_cuenta; 
    }
    public function setCorreoCuenta($correo_cuenta) { 
        $this->correo_cuenta = $correo_cuenta; 
    }

    public function getMetodosPago() {
        return $this->metodos_pago;
    }

    public function setMetodosPago($metodos_pago) {
        if (is_array($metodos_pago)) {
            $this->metodos_pago = implode(',', $metodos_pago);
        } else {
            $this->metodos_pago = $metodos_pago;
        }
    }

    public function getEstado() {
        return $this->estado;
    }
    public function setEstado($estado) {
        $this->estado = $estado;
    }

    public function registrarCuentabanco() {
        return $this->r_cuentabanco(); 
    }
    private function r_cuentabanco() {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "INSERT INTO tbl_cuentas 
            (nombre_banco, numero_cuenta, rif_cuenta, telefono_cuenta, correo_cuenta, metodos)
            VALUES (:nombre_banco, :numero_cuenta, :rif_cuenta, :telefono_cuenta, :correo_cuenta, :metodos)";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':nombre_banco', $this->nombre_banco);
            $stmt->bindParam(':numero_cuenta', $this->numero_cuenta);
            $stmt->bindParam(':rif_cuenta', $this->rif_cuenta);
            $stmt->bindParam(':telefono_cuenta', $this->telefono_cuenta);
            $stmt->bindParam(':correo_cuenta', $this->correo_cuenta);
            $stmt->bindParam(':metodos', $this->metodos_pago);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    public function existeNumeroCuenta($numero_cuenta, $excluir_id = null) {
        return $this->existeNumCuenta($numero_cuenta, $excluir_id); 
    }
    private function existeNumCuenta($numero_cuenta, $excluir_id) {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "SELECT COUNT(*) FROM tbl_cuentas WHERE numero_cuenta = ?";
            $params = [$numero_cuenta];
            if ($excluir_id !== null) {
                $sql .= " AND id_cuenta != ?";
                $params[] = $excluir_id;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    public function obtenerUltimaCuenta() {
        return $this->obtUltimaCuenta(); 
    }
    private function obtUltimaCuenta() {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "SELECT * FROM tbl_cuentas ORDER BY id_cuenta DESC LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cuenta ? $cuenta : null;
        } catch (PDOException $e) {
            return null;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }
    
    public function obtenerCuentaPorId($id_cuenta) {
        return $this->cuentaporid($id_cuenta); 
    }
    private function cuentaporid($id_cuenta) {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $query = "SELECT * FROM tbl_cuentas WHERE id_cuenta = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id_cuenta]);
            $cuenta_obt = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cuenta_obt;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    public function consultarCuentabanco() {
        return $this->c_cuentabanco(); 
    }
    private function c_cuentabanco() {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "SELECT * FROM tbl_cuentas ORDER BY id_cuenta DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $cuentas_obt = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $cuentas_obt;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    public function cuentasReportes() {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "SELECT 
c.id_cuenta,
c.nombre_banco,
c.numero_cuenta,
c.metodos,
dp.fecha,
dp.tipo,
dp.monto,
dp.estatus,
f.cliente,
cl.nombre,
cl.cedula
FROM tbl_cuentas c 
INNER JOIN  tbl_detalles_pago dp ON dp.id_cuenta = c.id_cuenta
INNER JOIN  tbl_facturas f ON dp.id_factura = f.id_factura
INNER JOIN tbl_clientes cl ON cl.id_clientes = f.cliente";

            $stmt = $db->prepare($sql);
            $stmt->execute();
            $cuentas_obt = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $cuentas_obt;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    public function modificarCuentabanco($id_cuenta) {
        return $this->m_cuentabanco($id_cuenta); 
    }
    private function m_cuentabanco($id_cuenta) {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_cuentas SET nombre_banco = :nombre_banco, numero_cuenta = :numero_cuenta, 
            rif_cuenta = :rif_cuenta, telefono_cuenta = :telefono_cuenta, correo_cuenta = :correo_cuenta, 
            metodos = :metodos
            WHERE id_cuenta = :id_cuenta";

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id_cuenta', $id_cuenta);
            $stmt->bindParam(':nombre_banco', $this->nombre_banco);
            $stmt->bindParam(':numero_cuenta', $this->numero_cuenta);
            $stmt->bindParam(':rif_cuenta', $this->rif_cuenta);
            $stmt->bindParam(':telefono_cuenta', $this->telefono_cuenta);
            $stmt->bindParam(':correo_cuenta', $this->correo_cuenta);
            $stmt->bindParam(':metodos', $this->metodos_pago);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    public function eliminarCuentabanco($id_cuenta) {
    return $this->e_cuentabanco($id_cuenta);
}

private function e_cuentabanco($id_cuenta) {
    $pagosAsociados = $this->tienePagosAsociados($id_cuenta);
    if ($pagosAsociados['tiene_pagos']) {
        return [
            'status' => 'error', 
            'message' => 'No se puede eliminar la cuenta porque tiene pagos asociados.',
            'pagos' => $pagosAsociados['pagos'],
            'total_pagos' => $pagosAsociados['total']
        ];
    }
    $conexion = new BD('P');
    $db = $conexion->getConexion();
    try {
        $sql = "DELETE FROM tbl_cuentas WHERE id_cuenta = :id_cuenta";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
        $result = $stmt->execute();
        if ($result) {
            return ['status' => 'success'];
        } else {
            return [
                'status' => 'error', 
                'message' => 'Error al eliminar la cuenta bancaria',
                'pagos' => [],
                'total_pagos' => 0
            ];
        }
    } catch (PDOException $e) {
        return [
            'status' => 'error', 
            'message' => 'Error inesperado: ' . $e->getMessage(),
            'pagos' => [],
            'total_pagos' => 0
        ];
    } finally {
        if (isset($conexion)) { $conexion->cerrar(); }
    }
}

private function tienePagosAsociados($id_cuenta) {
    $conexion = new BD('P');
    $db = $conexion->getConexion();
    try {
        $sql = "SELECT COUNT(*) as total FROM tbl_pagos WHERE id_cuenta = :id_cuenta";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $resultado['total'];

        if ($count > 0) {
            $sqlPagos = "SELECT p.id_pago, p.monto, p.fecha_pago, 
                         c.nombre_cliente, c.apellido_cliente
                       FROM tbl_pagos p
                       INNER JOIN tbl_clientes c ON p.id_cliente = c.id_cliente
                       WHERE p.id_cuenta = :id_cuenta 
                       ORDER BY p.fecha_pago DESC 
                       LIMIT 5";
            $stmtPagos = $db->prepare($sqlPagos);
            $stmtPagos->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
            $stmtPagos->execute();
            $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'tiene_pagos' => true,
                'pagos' => $pagos,
                'total' => $count
            ];
        }

        return ['tiene_pagos' => false];
    } catch (PDOException $e) {
        return [
            'tiene_pagos' => true,
            'pagos' => [],
            'total' => 'Desconocido'
        ];
    } finally {
        if (isset($conexion)) { $conexion->cerrar(); }
    }
}

    public function obtenerEstadoCuenta($id_cuenta) {
       
    }
    public function verificarEstado() {
        return $this->v_estadoCuenta(); 
    }
    private function v_estadoCuenta() {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "SHOW COLUMNS FROM tbl_cuentas LIKE 'estado'";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $alterSql = "ALTER TABLE tbl_cuentas 
                ADD estado ENUM('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado'";
                $db->exec($alterSql);
            }
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    public function cambiarEstado($nuevoEstado) {
        return $this->estadoCuenta($nuevoEstado); 
    }
    private function estadoCuenta($nuevoEstado) {
        $conexion = new BD('P');
        $db = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_cuentas SET estado = :estado WHERE id_cuenta = :id_cuenta";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':estado', $nuevoEstado);
            $stmt->bindParam(':id_cuenta', $this->id_cuenta);
            $result = $stmt->execute();
            return $result;
        } catch (PDOException $e) {
            return false;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

}
?>