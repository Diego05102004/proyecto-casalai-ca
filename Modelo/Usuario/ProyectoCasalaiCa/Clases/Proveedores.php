<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Proveedores extends BD {
    
    // Constantes de validación
    const MAX_REGISTROS_PAGINA = 100;
    const MAX_RANGO_FECHAS_DIAS = 365;
    const CAMPOS_OBLIGATORIOS = ['nombre_proveedor', 'rif_proveedor', 'nombre_representante', 'rif_representante'];
    
    // Estados válidos para el proveedor
    const ESTADOS_VALIDOS = ['habilitado', 'inhabilitado'];
    
    // Formatos de reporte permitidos
    const FORMATOS_REPORTE = ['pdf', 'excel', 'csv'];
    
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
    
    /**
     * Constructor - Inicializa la conexión a la base de datos usando herencia
     */
    public function __construct($tipo = 'P') {
        // Llama al constructor de la clase padre (BD) para inicializar la conexión
        parent::__construct($tipo);
    }
    
    /**
     * Obtiene la conexión a la base de datos de forma segura
     * @return PDO
     */
    public function getConexion() {
        return $this->pdo;
    }
    
    /**
     * Ejecuta una operación con conexión segura (abrir y cerrar automáticamente)
     * @param callable $operation Función que recibe la conexión y retorna un resultado
     * @return mixed Resultado de la operación
     */
    protected function ejecutarConConexionSegura($operation) {
        $conexion = new BD('P');
        
        try {
            // Iniciar transacción para seguridad
            $conexion->getConexion()->beginTransaction();
            
            // Ejecutar la operación pasándole la conexión
            $resultado = $operation($conexion->getConexion());
            
            // Confirmar transacción si todo fue exitoso
            $conexion->getConexion()->commit();
            
            return $resultado;
        } catch (Exception $e) {
            // Revertir cambios si hay error
            if (isset($conexion) && $conexion->getConexion()->inTransaction()) {
                $conexion->getConexion()->rollback();
            }
            throw new \RuntimeException("Error en operación de base de datos: " . $e->getMessage());
        } finally {
            // Siempre cerrar la conexión
            if (isset($conexion)) {
                $conexion->cerrar();
            }
        }
    }
    
    private function validarEsquema($datos, $operacion = 'registrar') {
        $errores = [];
        
        // Validar que $datos sea un array
        if (!is_array($datos)) {
            $errores['esquema'] = 'Los datos deben ser un array';
            return $errores;
        }
        
        // Validar campos obligatorios según la operación
        $campos_requeridos = self::CAMPOS_OBLIGATORIOS;
        
        if ($operacion === 'registrar') {
            // Para registrar, todos los campos obligatorios deben estar presentes
            foreach ($campos_requeridos as $campo) {
                if (!isset($datos[$campo]) || $datos[$campo] === '' || $datos[$campo] === null) {
                    $errores[$campo] = "El campo {$campo} es obligatorio";
                }
            }
        } elseif ($operacion === 'modificar') {
            // Para modificar, se requiere el ID y al menos un campo para actualizar
            if (!isset($datos['id_proveedor']) || $datos['id_proveedor'] === '' || $datos['id_proveedor'] === null) {
                $errores['id_proveedor'] = 'El ID del proveedor es obligatorio para modificar';
            }
            
            // Verificar que al menos haya un campo para modificar
            $campos_modificar = array_intersect(array_keys($datos), $campos_requeridos);
            if (empty($campos_modificar)) {
                $errores['modificacion'] = 'Debe proporcionar al menos un campo para modificar';
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar formato de los datos
     */
    private function validarFormato($datos) {
        $errores = [];
        
        // Validar nombre del proveedor
        if (isset($datos['nombre_proveedor'])) {
            $nombre = trim($datos['nombre_proveedor']);
            if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 200) {
                $errores['nombre_proveedor'] = 'El nombre del proveedor debe tener entre 2 y 200 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre)) {
                $errores['nombre_proveedor'] = 'El nombre solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        // Validar RIF del proveedor
        if (isset($datos['rif_proveedor'])) {
            $rif = trim($datos['rif_proveedor']);
            if (mb_strlen($rif) < 5 || mb_strlen($rif) > 20) {
                $errores['rif_proveedor'] = 'El RIF debe tener entre 5 y 20 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $rif)) {
                $errores['rif_proveedor'] = 'El RIF solo puede contener letras, números y guiones';
            }
        }
        
        // Validar nombre del representante
        if (isset($datos['nombre_representante'])) {
            $representante = trim($datos['nombre_representante']);
            if (mb_strlen($representante) < 2 || mb_strlen($representante) > 200) {
                $errores['nombre_representante'] = 'El nombre del representante debe tener entre 2 y 200 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $representante)) {
                $errores['nombre_representante'] = 'El nombre solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        // Validar RIF del representante
        if (isset($datos['rif_representante'])) {
            $rif_rep = trim($datos['rif_representante']);
            if (mb_strlen($rif_rep) < 5 || mb_strlen($rif_rep) > 20) {
                $errores['rif_representante'] = 'El RIF del representante debe tener entre 5 y 20 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $rif_rep)) {
                $errores['rif_representante'] = 'El RIF solo puede contener letras, números y guiones';
            }
        }
        
        // Validar correo electrónico
        if (isset($datos['correo_proveedor']) && $datos['correo_proveedor'] !== '') {
            $correo = trim($datos['correo_proveedor']);
            
            // Primero validar longitud total (RFC 5321: máximo 320 caracteres, pero usamos 255 para compatibilidad)
            if (mb_strlen($correo) > 255) {
                $errores['correo_proveedor'] = 'El correo no debe exceder los 255 caracteres';
            } 
            // Validar estructura básica
            elseif (!preg_match('/^[^@]+@[^@]+\.[^@]+$/', $correo)) {
                $errores['correo_proveedor'] = 'El correo debe tener formato usuario@dominio.extensión';
            }
            else {
                // Validar componentes individualmente
                list($usuario, $dominio_completo) = explode('@', $correo, 2);
                
                // Validar longitud del usuario (RFC 5321: máximo 64)
                if (strlen($usuario) > 64) {
                    $errores['correo_proveedor'] = 'El nombre de usuario no debe exceder los 64 caracteres';
                }
                // Validar longitud del dominio (RFC 1035: máximo 253)
                elseif (strlen($dominio_completo) > 253) {
                    $errores['correo_proveedor'] = 'El dominio no debe exceder los 253 caracteres';
                }
                // Validar caracteres inválidos en el usuario
                elseif (preg_match('/[<>"\s]/', $usuario)) {
                    $errores['correo_proveedor'] = 'El nombre de usuario contiene caracteres no permitidos';
                }
                // Validación final con filter_var (solo si pasa las validaciones básicas)
                elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $errores['correo_proveedor'] = 'El formato del correo electrónico no es válido';
                }
            }
        }
        
        // Validar teléfono 1
        if (isset($datos['telefono_1']) && $datos['telefono_1'] !== '') {
            $telefono1 = trim($datos['telefono_1']);
            if (mb_strlen($telefono1) < 7 || mb_strlen($telefono1) > 20) {
                $errores['telefono_1'] = 'El teléfono debe tener entre 7 y 20 caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono1)) {
                $errores['telefono_1'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
            }
        }
        
        // Validar teléfono 2
        if (isset($datos['telefono_2']) && $datos['telefono_2'] !== '') {
            $telefono2 = trim($datos['telefono_2']);
            if (mb_strlen($telefono2) < 7 || mb_strlen($telefono2) > 20) {
                $errores['telefono_2'] = 'El teléfono debe tener entre 7 y 20 caracteres';
            } elseif (!preg_match('/^[0-9\-\+\(\)\s]+$/', $telefono2)) {
                $errores['telefono_2'] = 'El teléfono solo puede contener números, guiones, paréntesis y el signo +';
            }
        }
        
        // Validar dirección
        if (isset($datos['direccion_proveedor']) && $datos['direccion_proveedor'] !== '') {
            $direccion = trim($datos['direccion_proveedor']);
            if (mb_strlen($direccion) > 500) {
                $errores['direccion_proveedor'] = 'La dirección no debe exceder los 500 caracteres';
            }
        }
        
        // Validar observación
        if (isset($datos['observacion']) && $datos['observacion'] !== '') {
            $observacion = trim($datos['observacion']);
            if (mb_strlen($observacion) > 1000) {
                $errores['observacion'] = 'La observación no debe exceder los 1000 caracteres';
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar ID del proveedor
     */
    private function validarId($id_proveedor) {
        $errores = [];
        
        if ($id_proveedor === null || $id_proveedor === '') {
            $errores['id_proveedor'] = 'El ID del proveedor es obligatorio';
        } elseif (!is_numeric($id_proveedor) || (int)$id_proveedor <= 0) {
            $errores['id_proveedor'] = 'El ID del proveedor debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Validar filtros de consulta
     */
    private function validarFiltros($filtros) {
        $errores = [];
        
        // Validar límites de paginación
        if (isset($filtros['limite'])) {
            $limite = (int)$filtros['limite'];
            if ($limite <= 0 || $limite > self::MAX_REGISTROS_PAGINA) {
                $errores['limite'] = "El límite debe estar entre 1 y " . self::MAX_REGISTROS_PAGINA . " registros";
            }
        }
        
        // Validar página
        if (isset($filtros['pagina'])) {
            $pagina = (int)$filtros['pagina'];
            if ($pagina < 1) {
                $errores['pagina'] = 'La página debe ser un número positivo';
            }
        }
        
        // Validar fechas
        if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
            $fecha_inicio = strtotime($filtros['fecha_inicio']);
            $fecha_fin = strtotime($filtros['fecha_fin']);
            
            if ($fecha_inicio && $fecha_fin) {
                if ($fecha_inicio > $fecha_fin) {
                    $errores['fechas'] = 'La fecha de inicio no puede ser mayor a la fecha fin';
                }
                
                $dias_diferencia = ($fecha_fin - $fecha_inicio) / (60 * 60 * 24);
                if ($dias_diferencia > self::MAX_RANGO_FECHAS_DIAS) {
                    $errores['rango_fechas'] = "El rango de fechas no puede exceder " . self::MAX_RANGO_FECHAS_DIAS . " días";
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar estado del proveedor
     */
    private function validarEstado($estado) {
        $errores = [];
        
        if (!in_array($estado, self::ESTADOS_VALIDOS)) {
            $errores['estado'] = 'El estado debe ser: ' . implode(' o ', self::ESTADOS_VALIDOS);
        }
        
        return $errores;
    }
    
    /**
     * Validar parámetros de reporte
     */
    private function validarReporte($parametros) {
        $errores = [];
        
        // Validar formato de salida
        if (isset($parametros['formato'])) {
            $formato = strtolower($parametros['formato']);
            if (!in_array($formato, self::FORMATOS_REPORTE)) {
                $errores['formato'] = 'El formato debe ser: ' . implode(', ', self::FORMATOS_REPORTE);
            }
        }
        
        // Validar rango de fechas para reportes
        if (isset($parametros['fecha_inicio']) && isset($parametros['fecha_fin'])) {
            $errores = array_merge($errores, $this->validarFiltros([
                'fecha_inicio' => $parametros['fecha_inicio'],
                'fecha_fin' => $parametros['fecha_fin']
            ]));
        }
        
        return $errores;
    }
    
    /**
     * Sanitizar datos de entrada
     */
    private function sanitizarDatos($datos) {
        if (!is_array($datos)) {
            return $datos;
        }
        
        $datos_sanitizados = [];
        
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                // Eliminar espacios en blanco al inicio y final
                $valor = trim($valor);
                
                // Convertir caracteres especiales a entidades HTML (previene XSS)
                $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
                
                // Escapar caracteres especiales para SQL (como capa adicional)
                $valor = addslashes($valor);
                
                $datos_sanitizados[$clave] = $valor;
            } else {
                $datos_sanitizados[$clave] = $valor;
            }
        }
        
        return $datos_sanitizados;
    }
    
    /**
     * Validar integridad referencial para eliminación
     */
    private function validarIntegridadReferencial($id_proveedor, $conexion) {
        $errores = [];
        
        return $this->ejecutarConConexionSegura(function($pdo) {
            // Verificar si el proveedor tiene recepciones asociadas
            $sql = "SELECT COUNT(*) as total FROM tbl_recepcion_productos WHERE id_proveedor = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$id_proveedor]);
            $recepciones = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($recepciones > 0) {
                $errores['integridad'] = "No se puede eliminar el proveedor porque tiene {$recepciones} recepción(es) asociada(s)";
            }
            
            return $errores;
        });
    }
    
    /**
     * Validar transición de estados
     */
    private function validarTransicionEstado($estado_actual, $estado_nuevo) {
        $errores = [];
        
        // Aquí se pueden definir reglas de transición específicas
        // Por ejemplo, si hay estados que no pueden pasar directamente a otros
        
        if ($estado_actual === $estado_nuevo) {
            $errores['transicion'] = 'El proveedor ya se encuentra en el estado ' . $estado_actual;
        }
        
        return $errores;
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

    /**
     * Validación centralizada para registrar proveedor
     */
    public function validarRegistrar($datos) {
        // Sanitizar datos primero
        $datos = $this->sanitizarDatos($datos);
        
        // Validar esquema
        $errores = $this->validarEsquema($datos, 'registrar');
        if (!empty($errores)) {
            return $errores;
        }
        
        // Validar formato
        $errores = $this->validarFormato($datos);
        if (!empty($errores)) {
            return $errores;
        }
        
        // Validar unicidad
        if ($this->existeNombreProveedor($datos['nombre_proveedor'])) {
            $errores['nombre_proveedor'] = 'El nombre del proveedor ya existe';
        }
        
        if ($this->existeRifProveedor($datos['rif_proveedor'])) {
            $errores['rif_proveedor'] = 'El RIF del proveedor ya está registrado';
        }
        
        if ($this->existeRifRepresentante($datos['rif_representante'])) {
            $errores['rif_representante'] = 'El RIF del representante ya está registrado';
        }
        
        return $errores;
    }
    
    /**
     * Validación centralizada para consultar proveedores
     */
    public function validarConsultar($filtros = []) {
        // Establecer valores por defecto
        $filtros_default = [
            'pagina' => 1,
            'limite' => 50,
            'orden' => 'nombre_proveedor',
            'direccion' => 'ASC'
        ];
        
        $filtros = array_merge($filtros_default, $filtros);
        
        // Validar filtros
        return $this->validarFiltros($filtros);
    }
    
    /**
     * Validación centralizada para detallar proveedor
     */
    public function validarDetallar($id_proveedor) {
        // Validar ID
        $errores = $this->validarId($id_proveedor);
        if (!empty($errores)) {
            return $errores;
        }
        
        // Verificar existencia
        $proveedor = $this->obtenerProveedorPorId($id_proveedor);
        if (!$proveedor) {
            $errores['existencia'] = 'El proveedor solicitado no existe';
        }
        
        return $errores;
    }
    
    /**
     * Validación centralizada para modificar proveedor
     */
    public function validarModificar($datos) {
        // Sanitizar datos primero
        $datos = $this->sanitizarDatos($datos);
        
        // Validar esquema
        $errores = $this->validarEsquema($datos, 'modificar');
        if (!empty($errores)) {
            return $errores;
        }
        
        // Validar formato
        $errores = $this->validarFormato($datos);
        if (!empty($errores)) {
            return $errores;
        }
        
        // Verificar existencia del proveedor
        $proveedor_existente = $this->obtenerProveedorPorId($datos['id_proveedor']);
        if (!$proveedor_existente) {
            $errores['existencia'] = 'El proveedor que intenta modificar no existe';
            return $errores;
        }
        
        // Validar unicidad (excluyendo el proveedor actual)
        if (isset($datos['nombre_proveedor']) && 
            $this->existeNombreProveedor($datos['nombre_proveedor'], $datos['id_proveedor'])) {
            $errores['nombre_proveedor'] = 'El nombre del proveedor ya existe';
        }
        
        if (isset($datos['rif_proveedor']) && 
            $this->existeRifProveedor($datos['rif_proveedor'], $datos['id_proveedor'])) {
            $errores['rif_proveedor'] = 'El RIF del proveedor ya está registrado';
        }
        
        if (isset($datos['rif_representante']) && 
            $this->existeRifRepresentante($datos['rif_representante'], $datos['id_proveedor'])) {
            $errores['rif_representante'] = 'El RIF del representante ya está registrado';
        }
        
        return $errores;
    }
    
    /**
     * Validación centralizada para eliminar proveedor
     */
    public function validarEliminar($id_proveedor) {
        // Validar ID
        $errores = $this->validarId($id_proveedor);
        if (!empty($errores)) {
            return $errores;
        }
        
        // Verificar existencia
        $proveedor = $this->obtenerProveedorPorId($id_proveedor);
        if (!$proveedor) {
            $errores['existencia'] = 'El proveedor que intenta eliminar no existe';
            return $errores;
        }
        
        // Validar integridad referencial
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_proveedor) {
            $errores_integridad = $this->validarIntegridadReferencial($id_proveedor, $pdo);
            $errores = array_merge($errores, $errores_integridad);
            return $errores;
        });
        
        return $errores;
    }
    
    /**
     * Validación centralizada para cambiar estatus
     */
    public function validarCambiarEstatus($id_proveedor, $nuevo_estatus) {
        $errores = [];
        
        // Validar ID
        $errores_id = $this->validarId($id_proveedor);
        if (!empty($errores_id)) {
            return array_merge($errores, $errores_id);
        }
        
        // Validar estado
        $errores_estado = $this->validarEstado($nuevo_estatus);
        if (!empty($errores_estado)) {
            return array_merge($errores, $errores_estado);
        }
        
        // Verificar existencia
        $proveedor = $this->obtenerProveedorPorId($id_proveedor);
        if (!$proveedor) {
            $errores['existencia'] = 'El proveedor no existe';
            return $errores;
        }
        
        // Validar transición de estado
        $estado_actual = $proveedor['estado'] ?? 'habilitado';
        $errores_transicion = $this->validarTransicionEstado($estado_actual, $nuevo_estatus);
        $errores = array_merge($errores, $errores_transicion);
        
        return $errores;
    }
    
    /**
     * Validación centralizada para generar reportes
     */
    public function validarGenerarReporte($parametros) {
        // Validar parámetros del reporte
        $errores = $this->validarReporte($parametros);
        
        // Validar permisos de exportación (aquí se podría agregar lógica de roles)
        if (!isset($_SESSION['id_usuario']) || !$_SESSION['id_usuario']) {
            $errores['permisos'] = 'No tiene permisos para generar reportes';
        }
        
        return $errores;
    }
    
    /**
     * Método mejorado para obtener proveedores con paginación y filtros
     */
    public function obtenerProveedoresConFiltros($filtros = []) {
        $errores = $this->validarConsultar($filtros);
        if (!empty($errores)) {
            return ['error' => $errores];
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($filtros) {
            $pagina = (int)($filtros['pagina'] ?? 1);
            $limite = (int)($filtros['limite'] ?? 50);
            $orden = $filtros['orden'] ?? 'nombre_proveedor';
            $direccion = $filtros['direccion'] ?? 'ASC';
            $busqueda = $filtros['busqueda'] ?? '';
            
            $offset = ($pagina - 1) * $limite;
            
            // Construir consulta base
            $sql = "SELECT * FROM tbl_proveedores WHERE 1=1";
            $params = [];
            
            // Agregar filtro de búsqueda
            if (!empty($busqueda)) {
                $sql .= " AND (nombre_proveedor LIKE :busqueda OR rif_proveedor LIKE :busqueda OR nombre_representante LIKE :busqueda)";
                $params[':busqueda'] = '%' . $busqueda . '%';
            }
            
            // Agregar ordenamiento
            $sql .= " ORDER BY {$orden} {$direccion}";
            
            // Agregar paginación
            $sql .= " LIMIT :limite OFFSET :offset";
            
            $stmt = $pdo->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener total para paginación
            $sql_total = "SELECT COUNT(*) as total FROM tbl_proveedores WHERE 1=1";
            if (!empty($busqueda)) {
                $sql_total .= " AND (nombre_proveedor LIKE :busqueda OR rif_proveedor LIKE :busqueda OR nombre_representante LIKE :busqueda)";
            }
            
            $stmt_total = $pdo->prepare($sql_total);
            foreach ($params as $key => $value) {
                $stmt_total->bindValue($key, $value);
            }
            $stmt_total->execute();
            $total = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'proveedores' => $proveedores,
                'total' => $total,
                'pagina' => $pagina,
                'limite' => $limite,
                'total_paginas' => ceil($total / $limite)
            ];
            
        }); 
    }

    public function existeNombreProveedor($nombre, $excluir_id = null) {
        return $this->existeNomProveedor($nombre, $excluir_id); 
    }
    private function existeNomProveedor($nombre, $excluir_id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nombre, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_proveedores WHERE nombre_proveedor = ?";
            $params = [$nombre];
            if ($excluir_id !== null) {
                $sql .= " AND id_proveedor != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function existeRifProveedor($rif, $excluir_id = null) {
        return $this->existeRifProv($rif, $excluir_id);
    }
    private function existeRifProv($rif, $excluir_id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($rif, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_proveedores WHERE rif_proveedor = ?";
            $params = [$rif];
            if ($excluir_id !== null) {
                $sql .= " AND id_proveedor != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function existeRifRepresentante($rif, $excluir_id = null) {
        return $this->existeRifRep($rif, $excluir_id);
    }
    private function existeRifRep($rif, $excluir_id) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($rif, $excluir_id) {
            $sql = "SELECT COUNT(*) FROM tbl_proveedores WHERE rif_representante = ?";
            $params = [$rif];
            if ($excluir_id !== null) {
                $sql .= " AND id_proveedor != ?";
                $params[] = $excluir_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        });
    }

    public function registrarProveedor() {
        return $this->r_proveedor();
    }
    private function r_proveedor() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_proveedores (`nombre_proveedor`, `rif_proveedor`, `nombre_representante`, `rif_representante`, `correo_proveedor`, `direccion_proveedor`, `telefono_1`, `telefono_2`, `observacion`)
                    VALUES (:nombre, :rif1, :representante, :rif2, :correo, :direccion, :telefono1, :telefono2, :observacion)";
            $stmt = $pdo->prepare($sql);
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
        });
    }

    public function obtenerUltimoProveedor() {
        return $this->obtUltimoProveedor(); 
    }
    private function obtUltimoProveedor() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_proveedores ORDER BY id_proveedor DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerReporteSuministroProveedores() {
        return $this->obtReporteSuministroProveedores();
    }

    private function obtReporteSuministroProveedores() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT p.nombre_proveedor, SUM(dp.cantidad) AS cantidad
                    FROM tbl_proveedores p
                    JOIN tbl_recepcion_productos r ON p.id_proveedor = r.id_proveedor
                    JOIN tbl_detalle_recepcion_productos dp ON r.id_recepcion = dp.id_recepcion
                    GROUP BY p.id_proveedor, p.nombre_proveedor
                    ORDER BY cantidad DESC
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerProveedorPorId($id_proveedor) {
        return $this->obtProveedorPorId($id_proveedor);
    }
    private function obtProveedorPorId($id_proveedor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_proveedor) {
            $query = "SELECT * FROM tbl_proveedores WHERE id_proveedor = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$id_proveedor]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function modificarProveedor($id_proveedor) {
        return $this->m_proveedor($id_proveedor);
    }
    private function m_proveedor($id_proveedor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_proveedor) {
            $sql = "UPDATE tbl_proveedores SET nombre_proveedor = :nombre, rif_proveedor = :rif1, nombre_representante = :representante, rif_representante = :rif2, correo_proveedor = :correo, direccion_proveedor = :direccion, telefono_1 = :telefono1, telefono_2 = :telefono2, observacion = :observacion WHERE id_proveedor = :id_proveedor";
            $stmt = $pdo->prepare($sql);
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
        });
    }

    public function eliminarProveedor($id_proveedor) {
        return $this->e_proveedor($id_proveedor);
    }
    private function e_proveedor($id_proveedor) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_proveedor) {
            $sql = "DELETE FROM tbl_proveedores WHERE id_proveedor = :id_proveedor";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_proveedor', $id_proveedor);
            return $stmt->execute();
        });
    }

    public function getproveedores() {
        return $this->g_proveedores();
    }
    private function g_proveedores() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $queryproveedores = 'SELECT * FROM ' . $this->tableproveedor;
            $stmtproveedores = $pdo->prepare($queryproveedores);
            $stmtproveedores->execute();
            return $stmtproveedores->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getRankingProveedores() {
        return $this->getRankingProv();
    }
    private function getRankingProv() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "
                SELECT p.nombre_proveedor, pr.nombre_producto, d.cantidad, d.costo, d.cantidad*d.costo AS total, r.fecha
                FROM tbl_recepcion_productos r
                INNER JOIN tbl_proveedores p ON r.id_proveedor = p.id_proveedor
                INNER JOIN tbl_detalle_recepcion_productos d ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_productos pr ON pr.id_producto = d.id_producto
                GROUP BY p.nombre_proveedor
                ORDER BY total DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getComparacionPreciosProducto() {
        return $this->getComparacionPreciosProd();
    }
    private function getComparacionPreciosProd() {
        return $this->ejecutarConConexionSegura(function($pdo) {
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
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function getDependenciaProveedores() {
        return $this->getDependenciaProv();
    }
    private function getDependenciaProv() {
        return $this->ejecutarConConexionSegura(function($pdo) {
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
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }


    public function cambiarEstatus($nuevoEstatus) {
        return $this->cam_Estatus($nuevoEstatus); 
    }
    private function cam_Estatus($nuevoEstatus) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nuevoEstatus) {
            $sql = "UPDATE tbl_proveedores SET estado = :estatus WHERE id_proveedor = :id_proveedor";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':estatus', $nuevoEstatus);
            $stmt->bindParam(':id_proveedor', $this->id_proveedor);
            return $stmt->execute();
        });
    }
}
?>