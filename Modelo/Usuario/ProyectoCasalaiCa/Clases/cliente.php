<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class cliente extends BD {
    private $tableclientes = 'tbl_clientes';
    private $nombre;
    private $direccion;
    private $telefono;
    private $cedula;
    private $correo;
    private $activo = 1;
    private $id;
    
    // Constantes para validaciones
    const MAX_NOMBRE_CLIENTE = 200;
    const MIN_NOMBRE_CLIENTE = 2;
    const MAX_DIRECCION = 500;
    const MAX_TELEFONO = 20;
    const MIN_TELEFONO = 7;
    const MAX_CORREO = 255;
    const MAX_CEDULA = 12;
    const MIN_CEDULA = 8;
    const ESTADOS_PERMITIDOS = [1, 0]; // 1 = activo, 0 = inactivo

    public function setnombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getnombre() {
        return $this->nombre;
    }

    public function setdireccion($direccion) {
        $this->direccion = $direccion;
    }

    public function getdireccion() {
        return $this->direccion;
    }

    public function settelefono($telefono) {
        $this->telefono = $telefono;
    }

    public function gettelefono() {
        return $this->telefono;
    }


    public function setcedula($cedula) {
        $this->cedula = $cedula;
    }

    public function getcedula() {
        return $this->cedula;
    }

    public function setcorreo($correo) {
        $this->correo = $correo;
    }

    public function getcorreo() {
        return $this->correo;
    }

    
    public function getId() {
        return $this->id;
    }
    public function setId($id) {
        $this->id = $id;
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

    // ==================== VALIDACIONES DE BACKEND ====================

    /**
     * Valida los datos para registrar un cliente
     */
    private function validarRegistrar($datos) {
        $errores = [];
        
        // Validar nombre del cliente
        if (!isset($datos['nombre'])) {
            $errores['nombre'] = 'El nombre del cliente es obligatorio';
        } else {
            $nombre = trim($datos['nombre']);
            if (empty($nombre)) {
                $errores['nombre'] = 'El nombre del cliente no puede estar vacío';
            } elseif (mb_strlen($nombre) < self::MIN_NOMBRE_CLIENTE || mb_strlen($nombre) > self::MAX_NOMBRE_CLIENTE) {
                $errores['nombre'] = 'El nombre del cliente debe tener entre ' . self::MIN_NOMBRE_CLIENTE . ' y ' . self::MAX_NOMBRE_CLIENTE . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
                $errores['nombre'] = 'El nombre del cliente solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        // Validar cédula (formato: 1.234.567 o 12.345.678)
        if (!isset($datos['cedula'])) {
            $errores['cedula'] = 'La cédula del cliente es obligatoria';
        } else {
            $cedula = trim($datos['cedula']);
            if (empty($cedula)) {
                $errores['cedula'] = 'La cédula del cliente no puede estar vacía';
            } elseif (!preg_match('/^(?:\d{1,2}\.\d{3}\.\d{3})$/', $cedula)) {
                $errores['cedula'] = 'La cédula debe tener el formato 1.234.567 o 12.345.678';
            } elseif (mb_strlen($cedula) < self::MIN_CEDULA || mb_strlen($cedula) > self::MAX_CEDULA) {
                $errores['cedula'] = 'La cédula debe tener entre ' . self::MIN_CEDULA . ' y ' . self::MAX_CEDULA . ' caracteres';
            }
        }
        
        // Validar teléfono (opcional)
        if (isset($datos['telefono'])) {
            $telefono = trim($datos['telefono']);
            if (!empty($telefono)) {
                if (mb_strlen($telefono) < self::MIN_TELEFONO || mb_strlen($telefono) > self::MAX_TELEFONO) {
                    $errores['telefono'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO . ' y ' . self::MAX_TELEFONO . ' caracteres';
                } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono)) {
                    $errores['telefono'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
                }
            }
        }
        
        // Validar dirección (opcional)
        if (isset($datos['direccion'])) {
            $direccion = trim($datos['direccion']);
            if (!empty($direccion) && mb_strlen($direccion) > self::MAX_DIRECCION) {
                $errores['direccion'] = 'La dirección no debe exceder los ' . self::MAX_DIRECCION . ' caracteres';
            }
        }
        
        // Validar correo electrónico (opcional)
        if (isset($datos['correo'])) {
            $correo = trim($datos['correo']);
            if (!empty($correo)) {
                if (mb_strlen($correo) > self::MAX_CORREO) {
                    $errores['correo'] = 'El correo electrónico no debe exceder los ' . self::MAX_CORREO . ' caracteres';
                } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $errores['correo'] = 'El formato del correo electrónico no es válido';
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para consultar un cliente
     */
    private function validarConsultar($datos) {
        $errores = [];
        
        // Validar ID del cliente
        if (!isset($datos['id_cliente'])) {
            $errores['id_cliente'] = 'El ID del cliente es obligatorio';
        } elseif (!is_numeric($datos['id_cliente']) || $datos['id_cliente'] <= 0) {
            $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para modificar un cliente
     */
    private function validarModificar($datos) {
        $errores = [];
        
        // Validar ID del cliente
        if (!isset($datos['id_cliente'])) {
            $errores['id_cliente'] = 'El ID del cliente es obligatorio';
        } elseif (!is_numeric($datos['id_cliente']) || $datos['id_cliente'] <= 0) {
            $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
        }
        
        // Validar nombre del cliente
        if (!isset($datos['nombre'])) {
            $errores['nombre'] = 'El nombre del cliente es obligatorio';
        } else {
            $nombre = trim($datos['nombre']);
            if (empty($nombre)) {
                $errores['nombre'] = 'El nombre del cliente no puede estar vacío';
            } elseif (mb_strlen($nombre) < self::MIN_NOMBRE_CLIENTE || mb_strlen($nombre) > self::MAX_NOMBRE_CLIENTE) {
                $errores['nombre'] = 'El nombre del cliente debe tener entre ' . self::MIN_NOMBRE_CLIENTE . ' y ' . self::MAX_NOMBRE_CLIENTE . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
                $errores['nombre'] = 'El nombre del cliente solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        // Validar cédula
        if (!isset($datos['cedula'])) {
            $errores['cedula'] = 'La cédula del cliente es obligatoria';
        } else {
            $cedula = trim($datos['cedula']);
            if (empty($cedula)) {
                $errores['cedula'] = 'La cédula del cliente no puede estar vacía';
            } elseif (!preg_match('/^(?:\d{1,2}\.\d{3}\.\d{3})$/', $cedula)) {
                $errores['cedula'] = 'La cédula debe tener el formato 1.234.567 o 12.345.678';
            } elseif (mb_strlen($cedula) < self::MIN_CEDULA || mb_strlen($cedula) > self::MAX_CEDULA) {
                $errores['cedula'] = 'La cédula debe tener entre ' . self::MIN_CEDULA . ' y ' . self::MAX_CEDULA . ' caracteres';
            }
        }
        
        // Validar teléfono (opcional)
        if (isset($datos['telefono'])) {
            $telefono = trim($datos['telefono']);
            if (!empty($telefono)) {
                if (mb_strlen($telefono) < self::MIN_TELEFONO || mb_strlen($telefono) > self::MAX_TELEFONO) {
                    $errores['telefono'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO . ' y ' . self::MAX_TELEFONO . ' caracteres';
                } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono)) {
                    $errores['telefono'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
                }
            }
        }
        
        // Validar dirección (opcional)
        if (isset($datos['direccion'])) {
            $direccion = trim($datos['direccion']);
            if (!empty($direccion) && mb_strlen($direccion) > self::MAX_DIRECCION) {
                $errores['direccion'] = 'La dirección no debe exceder los ' . self::MAX_DIRECCION . ' caracteres';
            }
        }
        
        // Validar correo electrónico (opcional)
        if (isset($datos['correo'])) {
            $correo = trim($datos['correo']);
            if (!empty($correo)) {
                if (mb_strlen($correo) > self::MAX_CORREO) {
                    $errores['correo'] = 'El correo electrónico no debe exceder los ' . self::MAX_CORREO . ' caracteres';
                } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $errores['correo'] = 'El formato del correo electrónico no es válido';
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para eliminar un cliente
     */
    private function validarEliminar($datos) {
        $errores = [];
        
        // Validar ID del cliente
        if (!isset($datos['id_cliente'])) {
            $errores['id_cliente'] = 'El ID del cliente es obligatorio';
        } elseif (!is_numeric($datos['id_cliente']) || $datos['id_cliente'] <= 0) {
            $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para generar reporte
     */
    private function validarDatosReporte($datos) {
        $errores = [];
        
        // Validar límite de resultados (opcional)
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================

    /**
     * Valida los datos para registrar (método público)
     */
    public function validarRegistrarCliente($datos) {
        return $this->validarRegistrar($datos);
    }
    
    /**
     * Valida los datos para consultar (método público)
     */
    public function validarConsultarCliente($datos) {
        return $this->validarConsultar($datos);
    }
    
    /**
     * Valida los datos para modificar (método público)
     */
    public function validarModificarCliente($datos) {
        return $this->validarModificar($datos);
    }
    
    /**
     * Valida los datos para eliminar (método público)
     */
    public function validarEliminarCliente($datos) {
        return $this->validarEliminar($datos);
    }
    
    /**
     * Valida los datos para reporte (método público)
     */
    public function validarReporte($datos) {
        return $this->validarDatosReporte($datos);
    }
    
    /**
     * Verifica si un cliente existe por ID
     */
    private function verificarClienteExistente($idCliente) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_clientes WHERE id_clientes = :id_cliente AND activo = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        });
    }
    
    /**
     * Verifica si una cédula ya existe (excluyendo un ID específico)
     */
    private function verificarCedulaExistente($cedula, $excluirId = null) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_clientes WHERE cedula = :cedula";
            $params = [':cedula' => $cedula];
            if ($excluirId !== null) {
                $sql .= " AND id_clientes != :id_cliente";
                $params[':id_cliente'] = $excluirId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function ingresarclientes() {
        return $this->r_cliente();
    }
    private function r_cliente() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_clientes (`nombre`, `cedula`, `direccion`, `telefono`, `correo`, `activo`)
                    VALUES (:nombre, :cedula, :direccion, :telefono, :correo, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':correo', $this->correo);
            return $stmt->execute();
        });
    }

    // En modelo/cliente.php
    public function listarTodosClientes() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $stmt = $pdo->prepare("SELECT id_clientes, nombre, cedula FROM tbl_clientes WHERE activo = 1 ORDER BY nombre");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    private function existeNumeroCedula($cedula, $excluir_id = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($cedula, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_clientes WHERE cedula = ?";
            $params = [$cedula];
            if ($excluir_id !== null) {
                $sql .= " AND id_clientes != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function obtenerUltimoCliente() {
        return $this->obtUltimaCliente(); 
    }
    private function obtUltimaCliente() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_clientes ORDER BY id_clientes DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cliente ? $cliente : null;
        });
    }

    function obtenerReporteComprasClientes() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT c.nombre, COUNT(d.id_producto) AS cantidad
            FROM tbl_clientes c
            JOIN tbl_despachos ds ON c.id_clientes = ds.id_clientes
            JOIN tbl_despacho_detalle d ON ds.id_despachos = d.id_despacho
            GROUP BY c.id_clientes, c.nombre
            ORDER BY cantidad DESC
            LIMIT 10;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }
    public function obtenerclientesPorId($id) {
        return $this->obtClientePorId($id);
    }
    private function obtClientePorId($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id){
            $query = "SELECT * FROM tbl_clientes WHERE id_clientes = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id]);
            $clientes = $stmt->fetch(PDO::FETCH_ASSOC);
            return $clientes;
        });
    }

    public function modificarclientes($id) {
        return $this->m_cliente($id);
    }
    private function m_cliente($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id) {
            $sql = "UPDATE tbl_clientes SET nombre = :nombre, cedula = :cedula, direccion = :direccion, telefono = :telefono, correo = :correo, activo = :activo WHERE id_clientes = :id_clientes";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_clientes', $id);
            $stmt->bindParam(':nombre', $this->nombre);
            $stmt->bindParam(':direccion', $this->direccion);
            $stmt->bindParam(':telefono', $this->telefono);
            $stmt->bindParam(':cedula', $this->cedula);
            $stmt->bindParam(':correo', $this->correo);
            $stmt->bindParam(':activo', $this->activo);
            return $stmt->execute();
        });
    }

    function eliminar_l($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id){
            $sql = "UPDATE tbl_clientes SET activo = 0 WHERE id_clientes = :id_clientes";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_clientes', $id);
            return $stmt->execute();
        });
    }

    public function eliminarclientes($id) {
        return $this->e_cliente($id);
    }
    private function e_cliente($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id){
            $sql = "DELETE FROM tbl_clientes WHERE id_clientes = :id_clientes";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_clientes', $id);
            $result = $stmt->execute();
            return $result;
        });
    }

    public function getclientes() {
        return $this->g_clientes();
    }
    private function g_clientes() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $queryclientes = 'SELECT * FROM ' . $this->tableclientes;
            $stmtclientes = $pdo->prepare($queryclientes);
            $stmtclientes->execute();
            $clientes = $stmtclientes->fetchAll(PDO::FETCH_ASSOC);
            return $clientes;
        });
    }
}