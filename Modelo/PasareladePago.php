<?php
require_once 'config/config.php';
require_once 'factura.php';
require_once 'ordendespacho.php';

class PasareladePago extends Factura {
    private $id_detalles;
    private $cuenta;
    private $factura;
    private $tipo;
    private $observaciones;    
    private $referencia;    
    private $fecha;
    private $estatus;
    private $comprobante;
    private $monto;
    private $cedula;


public function __construct() {
    parent::__construct();
}
    // Setters y Getters

    // ID Detalles
public function getIdDetalles() {
    return $this->id_detalles;
}

public function setIdDetalles($id_detalles) {
    $this->id_detalles = $id_detalles;
}

// Cuenta
public function getCuenta() {
    return $this->cuenta;
}

public function setCuenta($cuenta) {
    $this->cuenta = $cuenta;
}

// Factura
public function getFactura() {
    return $this->factura;
}

public function setFactura($factura) {
    $this->factura = $factura;
}

// Tipo
public function getTipo() {
    return $this->tipo;
}

public function setTipo($tipo) {
    $this->tipo = $tipo;
}

// Observaciones
public function getObservaciones() {
    return $this->observaciones;
}

public function setObservaciones($observaciones) {
    $this->observaciones = $observaciones;
}

// Referencia
public function getReferencia() {
    return $this->referencia;
}

public function setReferencia($referencia) {
    $this->referencia = $referencia;
}

// Fecha
public function getFecha() {
    return $this->fecha;
}

public function setFecha($fecha) {
    $this->fecha = $fecha;
}

// Estatus
public function getEstatus() {
    return $this->estatus;
}

public function setEstatus($estatus) {
    $this->estatus = $estatus;
}
// Comprobante
public function getComprobante() {
    return $this->comprobante;
}
public function setComprobante($comprobante) {
    $this->comprobante = $comprobante;
}
// Monto
public function getMonto() {
    return $this->monto;
}
public function setMonto($monto) {
    $this->monto = $monto;
}
// Cedula
public function getCedula() {
    return $this->cedula;
}
public function setCedula($cedula) {
    $this->cedula = $cedula;
}


public function validarCodigoReferencia() {
    $sql = "SELECT COUNT(*) FROM tbl_detalles_pago WHERE referencia = :referencia";
    $stmt = $this->getConexion()->prepare($sql);
    $stmt->bindParam(':referencia', $this->referencia);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    // Retorna true si no existe un producto con el mismo código interno
    return $count == 0;
}
    public function pasarelaTransaccion($transaccion) {
        switch ($transaccion) {
            case 'Ingresar':
                return $this->pagoIngresar();                 
            case 'Consultar':
                return $this->pagoConsultar();
                case 'ConsultarTodos':
                return $this->pagoConsultarTodos();
            case 'Modificar':
                return $this->pagoModificar();
            case 'Procesar':
                return $this->pagoProcesar();
            default:
                throw new Exception("Transacción no válida.");
        }
    }

    
    private function pagoIngresar() {
        try {
            $stmt = $this->getConexion()->prepare("
                INSERT INTO `tbl_detalles_pago`
                (`id_factura`, `id_cuenta`, `observaciones`, `tipo`, `referencia`, `fecha`, `comprobante`, `monto`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->factura,
                $this->cuenta,
                $this->observaciones,
                $this->tipo,
                $this->referencia,
                $this->fecha,
                $this->comprobante,
                $this->monto
            ]);

            // Actualiza estatus de factura (si aplica)
            $updateStmt = $this->getConexion()->prepare("
                UPDATE `tbl_facturas` 
                SET `estatus` = 'En Proceso' 
                WHERE `id_factura` = ?
            ");
            $updateStmt->execute([$this->factura]);

            return true;
        } catch (PDOException $e) {
            // En lugar de imprimir el error, lo registramos en log y devolvemos false
            error_log("Error en pagoIngresar: " . $e->getMessage());
            return false;
        }
    }

private function pagoConsultar() {
    $sql = "SELECT 
                dp.id_detalles, 
                f.id_factura, 
                cl.nombre, 
                cl.cedula, 
                dp.id_cuenta, 
                c.nombre_banco AS nombre_cuenta, 
                dp.observaciones, 
                dp.tipo, 
                dp.referencia, 
                dp.fecha, 
                dp.comprobante,
                dp.monto, 
                dp.estatus 
            FROM tbl_detalles_pago dp 
            INNER JOIN tbl_cuentas c ON dp.id_cuenta = c.id_cuenta 
            INNER JOIN tbl_facturas f ON dp.id_factura = f.id_factura 
            INNER JOIN tbl_clientes cl ON f.cliente = cl.id_clientes 
            WHERE cl.cedula = :cedula";
    
    $stmt = $this->getConexion()->prepare($sql);
    $stmt->bindParam(':cedula', $this->cedula, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



    private function pagoConsultarTodos() {
        $sql = "SELECT dp.id_detalles, f.id_factura, cl.nombre, cl.cedula, dp.id_cuenta, c.nombre_banco AS nombre_cuenta, dp.observaciones, dp.tipo, dp.referencia, dp.fecha, dp.comprobante,dp.monto, dp.estatus FROM tbl_detalles_pago dp INNER JOIN tbl_cuentas c ON dp.id_cuenta = c.id_cuenta INNER JOIN tbl_facturas f ON dp.id_factura = f.id_factura INNER JOIN tbl_clientes cl ON f.cliente = cl.id_clientes;";
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    

    private function pagoModificar() {
        $sql = "UPDATE `tbl_detalles_pago` 
                SET `id_factura` = :id_factura,
                    `id_cuenta` = :id_cuenta,
                    `tipo` = :tipo,
                    `referencia` = :referencia,
                    `fecha` = :fecha

                WHERE id_detalles = :id_detalles";
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->bindParam(':id_detalles', $this->id_detalles);
        $stmt->bindParam(':id_factura', $this->factura);
        $stmt->bindParam(':id_cuenta', $this->cuenta);
        $stmt->bindParam(':tipo', $this->tipo);
        $stmt->bindParam(':referencia', $this->referencia);
        $stmt->bindParam(':fecha', $this->fecha);
        return $stmt->execute();
    }

private function pagoProcesar() {
    $sql = "UPDATE `tbl_detalles_pago` 
            SET `estatus` = :estatus
            WHERE id_detalles = :id_detalles";
    
    $stmt = $this->getConexion()->prepare($sql);
    $stmt->bindParam(':estatus', $this->estatus);
    $stmt->bindParam(':id_detalles', $this->id_detalles);
    
    $resultado = $stmt->execute();

    if ($resultado) {
        $this->facturaProcesar($this->factura, $this->estatus);
        if ($this->estatus === 'Pago Procesado') {
            try {
                $ordenDespacho = new OrdenDespacho();
                $ordenDespacho->crearPorFactura($this->factura);
            } catch (Exception $e) {
                error_log('Error creando orden de despacho: ' . $e->getMessage());
            }
        }
    }

    return $resultado;
}



        public function cambiarEstatus($nuevoEstatus) {
        try {
            $sql = "UPDATE tbl_detalles_pago SET estatus = :estatus WHERE id_detalles = :id";
            $stmt = $this->getConexion()->prepare($sql);
            $stmt->bindParam(':estatus', $nuevoEstatus);
            $stmt->bindParam(':id', $this->id_detalles);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
        public function obtenerPagoPorId($id) {
        $sql = "SELECT 
                    dp.id_detalles,
                    dp.id_factura,
                    dp.id_cuenta,
                    c.nombre_banco AS tbl_cuentas,
                    dp.observaciones,
                    dp.tipo,
                    dp.comprobante,
                    dp.referencia,
                    dp.fecha,
                    dp.estatus
                FROM tbl_detalles_pago dp
                INNER JOIN tbl_cuentas c ON dp.id_cuenta = c.id_cuenta
                WHERE dp.id_detalles = :id";
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

        private function pagoEliminar() {
        $sql = "DELETE FROM tbl_detalles_pago WHERE id_detalles = :id_detalles";
        $stmt = $this->getConexion()->prepare($sql);
        $stmt->bindParam(':id_detalles', $this->id_detalles, PDO::PARAM_INT);
        return $stmt->execute();
    }


}
?>
