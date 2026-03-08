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
    const MAX_REGISTROS_PAGINA = 100;
    const CAMPOS_OBLIGATORIOS = ['nombre', 'cedula'];
    const FORMATOS_REPORTE = ['pdf', 'excel', 'csv'];
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
            parent::__construct('P'); 
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

    // ==================== VALIDACIONES DE BACKEND ====================

    /**
     * Sanitiza los datos de entrada
     */
    private function sanitizarDatos($datos) {
        if (!is_array($datos)) {
            return $datos;
        }
        
        $datos_sanitizados = [];
        
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                $valor = trim($valor);
                $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
                $valor = addslashes($valor);
                $datos_sanitizados[$clave] = $valor;
            } else {
                $datos_sanitizados[$clave] = $valor;
            }
        }
        
        return $datos_sanitizados;
    }
    
    /**
     * Valida el esquema de datos según la operación
     */
    private function validarEsquema($datos, $operacion = 'registrar') {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['esquema'] = 'Los datos deben ser un array';
            return $errores;
        }
        
        $campos_requeridos = self::CAMPOS_OBLIGATORIOS;
        
        if ($operacion === 'registrar') {
            foreach ($campos_requeridos as $campo) {
                if (!isset($datos[$campo]) || $datos[$campo] === '' || $datos[$campo] === null) {
                    $errores[$campo] = "El campo {$campo} es obligatorio";
                }
            }
        } elseif ($operacion === 'modificar') {
            if (!isset($datos['id_clientes']) || $datos['id_clientes'] === '' || $datos['id_clientes'] === null) {
                $errores['id_clientes'] = 'El ID del cliente es obligatorio para modificar';
            }
            
            $campos_modificar = array_intersect(array_keys($datos), $campos_requeridos);
            if (empty($campos_modificar)) {
                $errores['modificacion'] = 'Debe proporcionar al menos un campo para modificar';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida el formato de los datos
     */
    private function validarFormato($datos) {
        $errores = [];
        
        if (isset($datos['nombre'])) {
            $nombre = trim($datos['nombre']);
            if (mb_strlen($nombre) < self::MIN_NOMBRE_CLIENTE || mb_strlen($nombre) > self::MAX_NOMBRE_CLIENTE) {
                $errores['nombre'] = 'El nombre del cliente debe tener entre ' . self::MIN_NOMBRE_CLIENTE . ' y ' . self::MAX_NOMBRE_CLIENTE . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
                $errores['nombre'] = 'El nombre del cliente solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        if (isset($datos['cedula'])) {
            $cedula = trim($datos['cedula']);
            if (!preg_match('/^(?:\d{1,2}\.\d{3}\.\d{3})$/', $cedula)) {
                $errores['cedula'] = 'La cédula debe tener el formato 1.234.567 o 12.345.678';
            } elseif (mb_strlen($cedula) < self::MIN_CEDULA || mb_strlen($cedula) > self::MAX_CEDULA) {
                $errores['cedula'] = 'La cédula debe tener entre ' . self::MIN_CEDULA . ' y ' . self::MAX_CEDULA . ' caracteres';
            }
        }
        
        if (isset($datos['telefono']) && $datos['telefono'] !== '') {
            $telefono = trim($datos['telefono']);
            if (mb_strlen($telefono) < self::MIN_TELEFONO || mb_strlen($telefono) > self::MAX_TELEFONO) {
                $errores['telefono'] = 'El teléfono debe tener entre ' . self::MIN_TELEFONO . ' y ' . self::MAX_TELEFONO . ' caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono)) {
                $errores['telefono'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
            }
        }
        
        if (isset($datos['direccion']) && $datos['direccion'] !== '') {
            $direccion = trim($datos['direccion']);
            if (mb_strlen($direccion) > self::MAX_DIRECCION) {
                $errores['direccion'] = 'La dirección no debe exceder los ' . self::MAX_DIRECCION . ' caracteres';
            }
        }
        
        if (isset($datos['correo']) && $datos['correo'] !== '') {
            $correo = trim($datos['correo']);
            if (mb_strlen($correo) > self::MAX_CORREO) {
                $errores['correo'] = 'El correo no debe exceder los 255 caracteres';
            } elseif (!preg_match('/^[^@]+@[^@]+\.[^@]+$/', $correo)) {
                $errores['correo'] = 'El correo debe tener formato usuario@dominio.extensión';
            } else {
                list($usuario, $dominio_completo) = explode('@', $correo, 2);
                
                if (strlen($usuario) > 64) {
                    $errores['correo'] = 'El nombre de usuario no debe exceder los 64 caracteres';
                } elseif (strlen($dominio_completo) > 253) {
                    $errores['correo'] = 'El dominio no debe exceder los 253 caracteres';
                } elseif (preg_match('/[<>"\s]/', $usuario)) {
                    $errores['correo'] = 'El nombre de usuario contiene caracteres no permitidos';
                } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $errores['correo'] = 'El formato del correo electrónico no es válido';
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida ID de cliente
     */
    private function validarId($id) {
        $errores = [];
        
        if (!is_numeric($id) || $id <= 0) {
            $errores['id_clientes'] = 'El ID del cliente debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida filtros para consultas
     */
    private function validarFiltros($filtros) {
        $errores = [];
        
        // Validar página
        if (isset($filtros['pagina'])) {
            $pagina = (int)$filtros['pagina'];
            if ($pagina < 1) {
                $errores['pagina'] = 'La página debe ser un número mayor a 0';
            }
        }
        
        // Validar límite
        if (isset($filtros['limite'])) {
            $limite = (int)$filtros['limite'];
            if ($limite < 1 || $limite > self::MAX_REGISTROS_PAGINA) {
                $errores['limite'] = 'El límite debe estar entre 1 y ' . self::MAX_REGISTROS_PAGINA;
            }
        }
        
        // Validar orden
        if (isset($filtros['orden'])) {
            $ordenes_validos = ['id_clientes', 'nombre', 'cedula', 'telefono', 'correo'];
            if (!in_array($filtros['orden'], $ordenes_validos)) {
                $errores['orden'] = 'El campo de orden no es válido';
            }
        }
        
        // Validar dirección
        if (isset($filtros['direccion'])) {
            $direcciones_validas = ['ASC', 'DESC'];
            if (!in_array(strtoupper($filtros['direccion']), $direcciones_validas)) {
                $errores['direccion'] = 'La dirección de orden debe ser ASC o DESC';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida integridad referencial para eliminación
     */
    private function validarIntegridadReferencial($id, $pdo) {
        $errores = [];
        
        // Verificar si tiene compras asociadas
        $sql = "SELECT COUNT(*) as total FROM tbl_compras WHERE id_clientes = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $compras = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($compras > 0) {
            $errores['integridad'] = "No se puede eliminar el cliente porque tiene {$compras} compra(s) asociada(s)";
        }
        
        return $errores;
    }
    
    /**
     * Obtiene un cliente por su ID
     */
    private function obtenerClientePorId($id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id) {
            $sql = "SELECT * FROM tbl_clientes WHERE id_clientes = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para registrar un cliente
     */
    public function validarRegistrar($datos) {
        $datos = $this->sanitizarDatos($datos);
        
        $errores = $this->validarEsquema($datos, 'registrar');
        if (!empty($errores)) {
            return $errores;
        }
        
        $errores = $this->validarFormato($datos);
        if (!empty($errores)) {
            return $errores;
        }
        
        if ($this->existeNumeroCedula($datos['cedula'])) {
            $errores['cedula'] = 'La cédula del cliente ya está registrada';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para consultar clientes
     */
    public function validarConsultar($filtros = []) {
        $filtros_default = [
            'pagina' => 1,
            'limite' => 50,
            'orden' => 'nombre',
            'direccion' => 'ASC'
        ];
        
        $filtros = array_merge($filtros_default, $filtros);
        
        return $this->validarFiltros($filtros);
    }

    public function validarModificar($datos) {
        $datos = $this->sanitizarDatos($datos);
        
        $errores = $this->validarEsquema($datos, 'modificar');
        if (!empty($errores)) {
            return $errores;
        }
        
        $errores = $this->validarFormato($datos);
        if (!empty($errores)) {
            return $errores;
        }

        $cliente_existente = $this->obtenerClientePorId($datos['id_clientes']);
        if (!$cliente_existente) {
            $errores['existencia'] = 'El cliente que intenta modificar no existe';
            return $errores;
        }

        if (isset($datos['nombre']) && 
            $this->existeNombre($datos['nombre'], $datos['id_clientes'])) {
            $errores['nombre'] = 'El nombre del cliente ya existe';
        }
        
        return $errores;
    }
    
    public function validarEliminar($id) {
        $errores = $this->validarId($id);
        if (!empty($errores)) {
            return $errores;
        }
        
        $cliente = $this->obtenerClientePorId($id);
        if (!$cliente) {
            $errores['existencia'] = 'El cliente que intenta eliminar no existe';
            return $errores;
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($id) { 
            $errores = [];
            $errores_integridad = $this->validarIntegridadReferencial($id, $pdo); 
            $errores = array_merge($errores, $errores_integridad);
            return $errores;
        });
    }

    /**
     * Valida los datos para descargar reportes
     */
    public function validarDescarga($parametros) {
        $errores = $this->validarReporte($parametros);
        
        if (!isset($_SESSION['id_usuario']) || !$_SESSION['id_usuario']) {
            $errores['permisos'] = 'No tiene permisos para descargar reportes';
        }
        
        return $errores;
    }
    
    /**
     * Obtiene clientes con filtros aplicados
     */
    public function obtenerClientesConFiltros($filtros = []) {
        $errores = $this->validarConsultar($filtros);
        if (!empty($errores)) {
            return ['error' => $errores];
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($filtros) {
            $pagina = (int)($filtros['pagina'] ?? 1);
            $limite = (int)($filtros['limite'] ?? 50);
            $orden = $filtros['orden'] ?? 'nombre';
            $direccion = $filtros['direccion'] ?? 'ASC';
            $busqueda = $filtros['busqueda'] ?? '';
            
            $offset = ($pagina - 1) * $limite;
            
            $sql = "SELECT * FROM tbl_clientes WHERE activo = 1";
            $params = [];
            
            if (!empty($busqueda)) {
                $sql .= " AND (nombre LIKE :busqueda OR cedula LIKE :busqueda OR correo LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            $sql .= " ORDER BY {$orden} {$direccion}";
            $sql .= " LIMIT :limite OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sql_total = "SELECT COUNT(*) as total FROM tbl_clientes WHERE activo = 1";
            if (!empty($busqueda)) {
                $sql_total .= " AND (nombre LIKE :busqueda OR cedula LIKE :busqueda OR correo LIKE :busqueda)";
            }
            
            $stmt_total = $pdo->prepare($sql_total);
            foreach ($params as $key => $value) {
                $stmt_total->bindValue($key, $value);
            }
            $stmt_total->execute();
            $total = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'clientes' => $clientes,
                'total' => $total,
                'pagina' => $pagina,
                'limite' => $limite,
                'total_paginas' => ceil($total / $limite)
            ];
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