<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Cuentabanco extends BD {

    private $id_cuenta;
    private $nombre_banco;
    private $numero_cuenta;
    private $rif_cuenta;
    private $telefono_cuenta;
    private $correo_cuenta;
    private $metodos_pago;
    private $estado;
    
    const MAX_NOMBRE_BANCO = 100;
    const MIN_NOMBRE_BANCO = 3;
    const MAX_NUMERO_CUENTA = 30;
    const MIN_NUMERO_CUENTA = 10;
    const MAX_TELEFONO_CUENTA = 15;
    const MIN_TELEFONO_CUENTA = 7;
    const MAX_CORREO_CUENTA = 150;
    const MAX_RIF_CUENTA = 12;
    const MIN_RIF_CUENTA = 9;
    const ESTADOS_PERMITIDOS = ['habilitado', 'inhabilitado'];
    const TIPOS_PAGO_PERMITIDOS = ['efectivo', 'transferencia', 'zelle', 'pago_movil', 'tarjeta', 'cheque'];

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
    
    private function validarRegistrar($datos) {
        $errores = [];
        
        if (!isset($datos['nombre_banco'])) {
            $errores['nombre_banco'] = 'El nombre del banco es obligatorio';
        } else {
            $nombre_banco = trim($datos['nombre_banco']);
            if (empty($nombre_banco)) {
                $errores['nombre_banco'] = 'El nombre del banco no puede estar vacío';
            } elseif (mb_strlen($nombre_banco) < self::MIN_NOMBRE_BANCO || mb_strlen($nombre_banco) > self::MAX_NOMBRE_BANCO) {
                $errores['nombre_banco'] = 'El nombre del banco debe tener entre ' . self::MIN_NOMBRE_BANCO . ' y ' . self::MAX_NOMBRE_BANCO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_banco)) {
                $errores['nombre_banco'] = 'El nombre del banco solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        if (!isset($datos['numero_cuenta'])) {
            $errores['numero_cuenta'] = 'El número de cuenta es obligatorio';
        } else {
            $numero_cuenta = trim($datos['numero_cuenta']);
            if (empty($numero_cuenta)) {
                $errores['numero_cuenta'] = 'El número de cuenta no puede estar vacío';
            } else {
                $numero_limpio = preg_replace('/\D+/', '', $numero_cuenta);
                if (empty($numero_limpio)) {
                    $errores['numero_cuenta'] = 'El número de cuenta debe contener solo dígitos';
                } elseif (mb_strlen($numero_limpio) < self::MIN_NUMERO_CUENTA || mb_strlen($numero_limpio) > self::MAX_NUMERO_CUENTA) {
                    $errores['numero_cuenta'] = 'El número de cuenta debe tener entre ' . self::MIN_NUMERO_CUENTA . ' y ' . self::MAX_NUMERO_CUENTA . ' dígitos';
                }
            }
        }
        
        if (!isset($datos['rif_cuenta'])) {
            $errores['rif_cuenta'] = 'El RIF de la cuenta es obligatorio';
        } else {
            $rif_cuenta = trim($datos['rif_cuenta']);
            if (empty($rif_cuenta)) {
                $errores['rif_cuenta'] = 'El RIF de la cuenta no puede estar vacío';
            } else {
                $rif_limpio = str_replace(['-', ' '], '', strtoupper($rif_cuenta));
                if (!preg_match('/^[VEJGCP][0-9]{8,9}$/', $rif_limpio)) {
                    $errores['rif_cuenta'] = 'El formato del RIF no es válido. Debe ser como: V123456789, J123456789, G123456789, P123456789 o C123456789';
                } elseif (mb_strlen($rif_limpio) < self::MIN_RIF_CUENTA || mb_strlen($rif_limpio) > self::MAX_RIF_CUENTA) {
                    $errores['rif_cuenta'] = 'El RIF debe tener ' . self::MIN_RIF_CUENTA . ' caracteres (letra + 8-9 dígitos)';
                }
            }
        }
        
        if (!isset($datos['telefono_cuenta'])) {
            $errores['telefono_cuenta'] = 'El teléfono de la cuenta es obligatorio';
        } else {
            $telefono_cuenta = trim($datos['telefono_cuenta']);
            if (empty($telefono_cuenta)) {
                $errores['telefono_cuenta'] = 'El teléfono de la cuenta no puede estar vacío';
            } else {
                $telefono_limpio = preg_replace('/\D+/', '', $telefono_cuenta);
                if (empty($telefono_limpio)) {
                    $errores['telefono_cuenta'] = 'El teléfono debe contener solo dígitos';
                } elseif (mb_strlen($telefono_limpio) < self::MIN_TELEFONO_CUENTA || mb_strlen($telefono_limpio) > self::MAX_TELEFONO_CUENTA) {
                    $errores['telefono_cuenta'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO_CUENTA . ' y ' . self::MAX_TELEFONO_CUENTA . ' dígitos';
                }
            }
        }
        
        if (!isset($datos['correo_cuenta'])) {
            $errores['correo_cuenta'] = 'El correo de la cuenta es obligatorio';
        } else {
            $correo_cuenta = trim($datos['correo_cuenta']);
            if (empty($correo_cuenta)) {
                $errores['correo_cuenta'] = 'El correo de la cuenta no puede estar vacío';
            } elseif (mb_strlen($correo_cuenta) > self::MAX_CORREO_CUENTA) {
                $errores['correo_cuenta'] = 'El correo no debe exceder los ' . self::MAX_CORREO_CUENTA . ' caracteres';
            } elseif (!filter_var($correo_cuenta, FILTER_VALIDATE_EMAIL)) {
                $errores['correo_cuenta'] = 'El formato del correo electrónico no es válido';
            }
        }
        
        if (!isset($datos['metodos_pago'])) {
            $errores['metodos_pago'] = 'Debe seleccionar al menos un método de pago';
        } else {
            $metodos_pago = $datos['metodos_pago'];
            if (is_array($metodos_pago)) {
                $metodos_pago = array_filter($metodos_pago, function($metodo) {
                    return !empty($metodo);
                });
            }
            if (empty($metodos_pago)) {
                $errores['metodos_pago'] = 'Debe seleccionar al menos un método de pago válido';
            } else {
                foreach ($metodos_pago as $metodo) {
                    if (!in_array($metodo, self::TIPOS_PAGO_PERMITIDOS)) {
                        $errores['metodos_pago'] = 'Los métodos de pago permitidos son: ' . implode(', ', self::TIPOS_PAGO_PERMITIDOS);
                        break;
                    }
                }
            }
        }
        
        return $errores;
    }
    
    private function validarConsultar($datos) {
        $errores = [];
        
        if (isset($datos['id_cuenta'])) {
            if (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
                $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
            }
        }
        
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }
        
        return $errores;
    }
    
    private function validarDetallar($datos) {
        $errores = [];
        
        if (!isset($datos['id_cuenta'])) {
            $errores['id_cuenta'] = 'El ID de la cuenta es obligatorio';
        } elseif (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
            $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
        }
        
        return $errores;
    }
    
    private function validarModificar($datos) {
        $errores = [];
        
        // Validar ID de la cuenta
        if (!isset($datos['id_cuenta'])) {
            $errores['id_cuenta'] = 'El ID de la cuenta es obligatorio';
        } elseif (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
            $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
        }
        
        // Validar nombre del banco
        if (!isset($datos['nombre_banco'])) {
            $errores['nombre_banco'] = 'El nombre del banco es obligatorio';
        } else {
            $nombre_banco = trim($datos['nombre_banco']);
            if (empty($nombre_banco)) {
                $errores['nombre_banco'] = 'El nombre del banco no puede estar vacío';
            } elseif (mb_strlen($nombre_banco) < self::MIN_NOMBRE_BANCO || mb_strlen($nombre_banco) > self::MAX_NOMBRE_BANCO) {
                $errores['nombre_banco'] = 'El nombre del banco debe tener entre ' . self::MIN_NOMBRE_BANCO . ' y ' . self::MAX_NOMBRE_BANCO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_banco)) {
                $errores['nombre_banco'] = 'El nombre del banco solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        if (!isset($datos['numero_cuenta'])) {
            $errores['numero_cuenta'] = 'El número de cuenta es obligatorio';
        } else {
            $numero_cuenta = trim($datos['numero_cuenta']);
            if (empty($numero_cuenta)) {
                $errores['numero_cuenta'] = 'El número de cuenta no puede estar vacío';
            } else {
                $numero_limpio = preg_replace('/\D+/', '', $numero_cuenta);
                if (empty($numero_limpio)) {
                    $errores['numero_cuenta'] = 'El número de cuenta debe contener solo dígitos';
                } elseif (mb_strlen($numero_limpio) < self::MIN_NUMERO_CUENTA || mb_strlen($numero_limpio) > self::MAX_NUMERO_CUENTA) {
                    $errores['numero_cuenta'] = 'El número de cuenta debe tener entre ' . self::MIN_NUMERO_CUENTA . ' y ' . self::MAX_NUMERO_CUENTA . ' dígitos';
                }
            }
        }
        
        if (!isset($datos['rif_cuenta'])) {
            $errores['rif_cuenta'] = 'El RIF de la cuenta es obligatorio';
        } else {
            $rif_cuenta = trim($datos['rif_cuenta']);
            if (empty($rif_cuenta)) {
                $errores['rif_cuenta'] = 'El RIF de la cuenta no puede estar vacío';
            } else {
                $rif_limpio = str_replace(['-', ' '], '', strtoupper($rif_cuenta));
                if (!preg_match('/^[VEJGCP][0-9]{8,9}$/', $rif_limpio)) {
                    $errores['rif_cuenta'] = 'El formato del RIF no es válido. Debe ser como: V123456789, J123456789, G123456789, P123456789 o C123456789';
                } elseif (mb_strlen($rif_limpio) < self::MIN_RIF_CUENTA || mb_strlen($rif_limpio) > self::MAX_RIF_CUENTA) {
                    $errores['rif_cuenta'] = 'El RIF debe tener ' . self::MIN_RIF_CUENTA . ' caracteres (letra + 8-9 dígitos)';
                }
            }
        }
        
        if (!isset($datos['telefono_cuenta'])) {
            $errores['telefono_cuenta'] = 'El teléfono de la cuenta es obligatorio';
        } else {
            $telefono_cuenta = trim($datos['telefono_cuenta']);
            if (empty($telefono_cuenta)) {
                $errores['telefono_cuenta'] = 'El teléfono de la cuenta no puede estar vacío';
            } else {
                $telefono_limpio = preg_replace('/\D+/', '', $telefono_cuenta);
                if (empty($telefono_limpio)) {
                    $errores['telefono_cuenta'] = 'El teléfono debe contener solo dígitos';
                } elseif (mb_strlen($telefono_limpio) < self::MIN_TELEFONO_CUENTA || mb_strlen($telefono_limpio) > self::MAX_TELEFONO_CUENTA) {
                    $errores['telefono_cuenta'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO_CUENTA . ' y ' . self::MAX_TELEFONO_CUENTA . ' dígitos';
                }
            }
        }
        
        if (!isset($datos['correo_cuenta'])) {
            $errores['correo_cuenta'] = 'El correo de la cuenta es obligatorio';
        } else {
            $correo_cuenta = trim($datos['correo_cuenta']);
            if (empty($correo_cuenta)) {
                $errores['correo_cuenta'] = 'El correo de la cuenta no puede estar vacío';
            } elseif (mb_strlen($correo_cuenta) > self::MAX_CORREO_CUENTA) {
                $errores['correo_cuenta'] = 'El correo no debe exceder los ' . self::MAX_CORREO_CUENTA . ' caracteres';
            } elseif (!filter_var($correo_cuenta, FILTER_VALIDATE_EMAIL)) {
                $errores['correo_cuenta'] = 'El formato del correo electrónico no es válido';
            }
        }
        
        if (isset($datos['metodos_pago'])) {
            $metodos_pago = $datos['metodos_pago'];
            if (is_array($metodos_pago)) {
                $metodos_pago = array_filter($metodos_pago, function($metodo) {
                    return !empty($metodo);
                });
            }
            if (!empty($metodos_pago)) {
                foreach ($metodos_pago as $metodo) {
                    if (!in_array($metodo, self::TIPOS_PAGO_PERMITIDOS)) {
                        $errores['metodos_pago'] = 'Los métodos de pago permitidos son: ' . implode(', ', self::TIPOS_PAGO_PERMITIDOS);
                        break;
                    }
                }
            }
        }
        
        return $errores;
    }
    
    private function validarEliminar($datos) {
        $errores = [];
        
        if (!isset($datos['id_cuenta'])) {
            $errores['id_cuenta'] = 'El ID de la cuenta es obligatorio';
        } elseif (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
            $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
        }
        
        return $errores;
    }
    
    private function validarCambiarEstadoCuenta($datos) {
        $errores = [];
        
        if (!isset($datos['id_cuenta'])) {
            $errores['id_cuenta'] = 'El ID de la cuenta es obligatorio';
        } elseif (!is_numeric($datos['id_cuenta']) || $datos['id_cuenta'] <= 0) {
            $errores['id_cuenta'] = 'El ID de la cuenta debe ser un número positivo';
        }
        
        if (!isset($datos['estado'])) {
            $errores['estado'] = 'El estado es obligatorio';
        } elseif (!in_array($datos['estado'], self::ESTADOS_PERMITIDOS)) {
            $errores['estado'] = 'El estado debe ser uno de: ' . implode(', ', self::ESTADOS_PERMITIDOS);
        }
        
        return $errores;
    }
    
    private function validarReporte($datos) {
        $errores = [];
        
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }

        if (isset($datos['fecha_inicio'])) {
            $fecha_inicio = trim($datos['fecha_inicio']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_inicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fecha_inicio);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_inicio'] = 'La fecha de inicio no es válida';
                }
            }
        }
        
        if (isset($datos['fecha_fin'])) {
            $fecha_fin = trim($datos['fecha_fin']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_fin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fecha_fin);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_fin'] = 'La fecha de fin no es válida';
                }
            }
        }
        
        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin']) && !isset($errores['fecha_inicio']) && !isset($errores['fecha_fin'])) {
            $fecha_inicio = new \DateTime($datos['fecha_inicio']);
            $fecha_fin = new \DateTime($datos['fecha_fin']);
            if ($fecha_fin < $fecha_inicio) {
                $errores['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
            }
        }
        
        return $errores;
    }
    
    public function validarRegistrarCuenta($datos) {
        return $this->validarRegistrar($datos);
    }
    
    public function validarConsultarCuentas($datos) {
        return $this->validarConsultar($datos);
    }
    
    public function validarDetallarCuenta($datos) {
        return $this->validarDetallar($datos);
    }
    
    public function validarModificarCuenta($datos) {
        return $this->validarModificar($datos);
    }
    
    public function validarEliminarCuenta($datos) {
        return $this->validarEliminar($datos);
    }
    
    public function validarCambiarEstado($datos) {
        return $this->validarCambiarEstadoCuenta($datos);
    }
    
    public function validarReporteCuentas($datos) {
        return $this->validarReporte($datos);
    }

    public function registrarCuentabanco() {
        return $this->r_cuentabanco(); 
    }
    private function r_cuentabanco() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_cuentas 
            (nombre_banco, numero_cuenta, rif_cuenta, telefono_cuenta, correo_cuenta, metodos)
            VALUES (:nombre_banco, :numero_cuenta, :rif_cuenta, :telefono_cuenta, :correo_cuenta, :metodos)";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_banco', $this->nombre_banco);
            $stmt->bindParam(':numero_cuenta', $this->numero_cuenta);
            $stmt->bindParam(':rif_cuenta', $this->rif_cuenta);
            $stmt->bindParam(':telefono_cuenta', $this->telefono_cuenta);
            $stmt->bindParam(':correo_cuenta', $this->correo_cuenta);
            $stmt->bindParam(':metodos', $this->metodos_pago);
            return $stmt->execute();
        });
    }

    private function existeNumeroCuenta($numero_cuenta, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($numero_cuenta, $excluir_id){
            $sql = "SELECT COUNT(*) FROM tbl_cuentas WHERE numero_cuenta = ?";
            $params = [$numero_cuenta];
            if ($excluir_id !== null) {
                $sql .= " AND id_cuenta != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function obtenerUltimaCuenta() {
        return $this->obtUltimaCuenta(); 
    }
    private function obtUltimaCuenta() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_cuentas ORDER BY id_cuenta DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cuenta ? $cuenta : null;
        });
    }
    
    public function obtenerCuentaPorId($id_cuenta) {
        return $this->cuentaporid($id_cuenta); 
    }
    private function cuentaporid($id_cuenta) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cuenta){
            $query = "SELECT * FROM tbl_cuentas WHERE id_cuenta = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id_cuenta]);
            $cuenta_obt = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cuenta_obt;
        });
    }

    public function consultarCuentabanco() {
        return $this->c_cuentabanco(); 
    }
    private function c_cuentabanco() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_cuentas ORDER BY id_cuenta DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $cuentas_obt = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $cuentas_obt;
        });
    }

    public function cuentasReportes() {
        return $this->ejecutarConConexionSegura(function($pdo) {
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
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $cuentas_obt = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $cuentas_obt;
        });
    }

    public function modificarCuentabanco($id_cuenta) {
        return $this->m_cuentabanco($id_cuenta); 
    }
    private function m_cuentabanco($id_cuenta) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_cuenta){
            $sql = "UPDATE tbl_cuentas SET nombre_banco = :nombre_banco, numero_cuenta = :numero_cuenta, 
            rif_cuenta = :rif_cuenta, telefono_cuenta = :telefono_cuenta, correo_cuenta = :correo_cuenta, 
            metodos = :metodos
            WHERE id_cuenta = :id_cuenta";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_cuenta', $id_cuenta);
            $stmt->bindParam(':nombre_banco', $this->nombre_banco);
            $stmt->bindParam(':numero_cuenta', $this->numero_cuenta);
            $stmt->bindParam(':rif_cuenta', $this->rif_cuenta);
            $stmt->bindParam(':telefono_cuenta', $this->telefono_cuenta);
            $stmt->bindParam(':correo_cuenta', $this->correo_cuenta);
            $stmt->bindParam(':metodos', $this->metodos_pago);
            return $stmt->execute();
        });
    }

    public function eliminarCuentabanco($id_cuenta) {
        return $this->e_cuentabanco($id_cuenta);
    }

    private function e_cuentabanco($id_cuenta) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $pagosAsociados = $this->tienePagosAsociados($id_cuenta);
            if ($pagosAsociados['tiene_pagos']) {
                return [
                    'status' => 'error', 
                    'message' => 'No se puede eliminar la cuenta porque tiene pagos asociados.',
                    'pagos' => $pagosAsociados['pagos'],
                    'total_pagos' => $pagosAsociados['total']
                ];
            }
            try {
                $sql = "DELETE FROM tbl_cuentas WHERE id_cuenta = :id_cuenta";
                $stmt = $pdo->prepare($sql);
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
            }
        });
    }

    private function tienePagosAsociados($id_cuenta) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try{
                $sql = "SELECT COUNT(*) as total FROM tbl_detalles_pago WHERE id_cuenta = :id_cuenta";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
                $stmt->execute();
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = (int)$resultado['total'];

                if ($count > 0) {
                    // Si hay pagos, obtenemos los detalles de los últimos 5 pagos
                    $sqlPagos = "SELECT dp.id_detalle_pago as id_pago, dp.monto, dp.fecha, 
                                cl.nombre, cl.apellido, cl.cedula, f.numero_factura
                            FROM tbl_detalles_pago dp
                            INNER JOIN tbl_facturas f ON dp.id_factura = f.id_factura
                            INNER JOIN tbl_clientes cl ON f.cliente = cl.id_clientes
                            WHERE dp.id_cuenta = :id_cuenta 
                            ORDER BY dp.fecha DESC 
                            LIMIT 5";
                    $stmtPagos = $pdo->prepare($sqlPagos);
                    $stmtPagos->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
                    $stmtPagos->execute();
                    $pagos = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);
                    
                    return [
                        'tiene_pagos' => true,
                        'pagos' => $pagos,
                        'total' => $count
                    ];
                }

                // Si no hay pagos en tbl_detalles_pago, verificamos en tbl_pagos por compatibilidad
                $sql = "SELECT COUNT(*) as total FROM tbl_pagos WHERE id_cuenta = :id_cuenta";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':id_cuenta', $id_cuenta, PDO::PARAM_INT);
                $stmt->execute();
                $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = (int)$resultado['total'];

                if ($count > 0) {
                    $sqlPagos = "SELECT p.id_pago, p.monto, p.fecha_pago as fecha, 
                                c.nombre, c.apellido, c.cedula, '' as numero_factura
                            FROM tbl_pagos p
                            INNER JOIN tbl_clientes c ON p.id_cliente = c.id_cliente
                            WHERE p.id_cuenta = :id_cuenta 
                            ORDER BY p.fecha_pago DESC 
                            LIMIT 5";
                    $stmtPagos = $pdo->prepare($sqlPagos);
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
                error_log("Error al verificar pagos asociados: " . $e->getMessage());
                return ['tiene_pagos' => false];
            }
        });
    }

    public function verificarEstado() {
        return $this->v_estadoCuenta(); 
    }
    private function v_estadoCuenta() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SHOW COLUMNS FROM tbl_cuentas LIKE 'estado'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {
                $alterSql = "ALTER TABLE tbl_cuentas 
                ADD estado ENUM('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado'";
                $pdo->exec($alterSql);
            }
        });
    }

    public function cambiarEstado($nuevoEstado) {
        return $this->estadoCuenta($nuevoEstado); 
    }
    private function estadoCuenta($nuevoEstado) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "UPDATE tbl_cuentas SET estado = :estado WHERE id_cuenta = :id_cuenta";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':estado', $nuevoEstado);
                $stmt->bindParam(':id_cuenta', $this->id_cuenta);
                $result = $stmt->execute();
                return $result;
            } catch (PDOException $e) {
                return false;
            }
        });
    }
}
?>