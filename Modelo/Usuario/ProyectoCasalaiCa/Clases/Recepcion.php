<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use Usuario\ProyectoCasalaiCa\Config\Encryption;
use PDO;
use PDOException;
class Recepcion extends BD{
    private $idproveedor;
    private $correlativo;
    private $desc;
    private $fecha;
    private $costo;
    private $estado;
    private $tablerecepcion = 'tbl_recepcion_productos';
    private $encryption;
    
    // Campos cifrados de proveedores (para descifrar en reportes)
    const CAMPOS_CIFRADOS_PROVEEDORES = ['nombre_proveedor', 'nombre_representante', 'telefono_1', 'telefono_2', 'direccion_proveedor', 'correo_proveedor'];
    
    // Constantes de validación
    const MAX_REGISTROS_PAGINA = 100;
    const MAX_RANGO_FECHAS_DIAS = 365;
    const CAMPOS_OBLIGATORIOS = ['idproveedor', 'correlativo'];
    
    const MAX_ID_PROVEEDOR = 999999999;
    const MIN_ID_PROVEEDOR = 1;
    const MAX_CORRELATIVO = 50;
    const MIN_CORRELATIVO = 3;
    const MAX_DESCRIPCION = 500;
    const MIN_DESCRIPCION = 0;
    const MAX_COSTO = 999999.99;
    const MIN_COSTO = 0.01;
    const MAX_ID_RECEPCION = 999999999;
    const MIN_ID_RECEPCION = 1;
    const MAX_ID_PRODUCTO = 999999999;
    const MIN_ID_PRODUCTO = 1;
    const MAX_CANTIDAD = 99999;
    const MIN_CANTIDAD = 1;
    const ESTADOS_VALIDOS = ['habilitado', 'anulado', 'deshabilitado'];
    const ESTADOS_VALIDOS_CAMBIO = ['habilitado', 'anulado'];
    const MAX_ANIO = 2099;
    const MIN_ANIO = 2000;
    const MAX_MES = 12;
    const MIN_MES = 1;
    
    const FORMATOS_REPORTE = ['pdf', 'excel', 'csv'];

    public function getidproveedor() {
        return $this->idproveedor;
    }
    public function setidproveedor($idproveedor) {
        $this->idproveedor = $idproveedor;
    }

    public function getcorrelativo() {
        return $this->correlativo;
    }
    public function setcorrelativo($correlativo) {
        $this->correlativo = $correlativo;
    }

    public function getdesc() {
        return $this->desc;
    }
    public function setdesc($desc) {
        $this->desc = $desc;
    }
    
    public function getfecha() {
        return $this->fecha;
    }
    public function setfecha($fecha) {
        $this->fecha = $fecha;
    }

    public function setcosto($costo) {
        $this->costo = $costo;
    }
    public function getcosto() {
        return $this->costo;
    }

    public function getestado() {
        return $this->estado;
    }
    public function setestado($estado) {
        $this->estado = $estado;
    }

    public function __construct($tipo = 'P') {
        $this->encryption = new Encryption();
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

    // Helper validation methods
    private function sanitizarDatos($datos) {
        if (!is_array($datos)) {
            return $datos;
        }
        
        $datos_sanitizados = [];
        
        // Sanitizar campos de texto
        $campos_texto = ['correlativo', 'desc', 'estado'];
        foreach ($campos_texto as $campo) {
            if (isset($datos[$campo])) {
                $datos_sanitizados[$campo] = trim((string)$datos[$campo]);
            }
        }
        
        // Sanitizar campos numéricos
        $campos_numericos = ['idproveedor', 'costo'];
        foreach ($campos_numericos as $campo) {
            if (isset($datos[$campo])) {
                $datos_sanitizados[$campo] = is_numeric($datos[$campo]) ? $datos[$campo] : 0;
            }
        }
        
        // Sanitizar arrays de productos
        if (isset($datos['idproducto']) && is_array($datos['idproducto'])) {
            $datos_sanitizados['idproducto'] = array_map('intval', $datos['idproducto']);
        }
        if (isset($datos['cantidad']) && is_array($datos['cantidad'])) {
            $datos_sanitizados['cantidad'] = array_map('intval', $datos['cantidad']);
        }
        if (isset($datos['costo']) && is_array($datos['costo'])) {
            $datos_sanitizados['costo'] = array_map('floatval', $datos['costo']);
        }
        
        // Mantener otros campos no especificados
        foreach ($datos as $clave => $valor) {
            if (!isset($datos_sanitizados[$clave])) {
                $datos_sanitizados[$clave] = $valor;
            }
        }
        
        return $datos_sanitizados;
    }
    
    private function validarEsquema($datos, $operacion = 'registrar') {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['datos'] = 'Los datos deben ser un arreglo';
            return $errores;
        }
        
        // Validar campos obligatorios según la operación
        if ($operacion === 'registrar') {
            foreach (self::CAMPOS_OBLIGATORIOS as $campo) {
                if (!isset($datos[$campo]) || $datos[$campo] === '' || $datos[$campo] === null) {
                    $errores[$campo] = 'El campo ' . $campo . ' es obligatorio';
                }
            }
        }
        
        return $errores;
    }
    
    private function validarFormato($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['datos'] = 'Los datos deben ser un arreglo';
            return $errores;
        }
        
        // Validar ID del proveedor
        if (isset($datos['idproveedor'])) {
            $idproveedor = (int)$datos['idproveedor'];
            if ($idproveedor < self::MIN_ID_PROVEEDOR || $idproveedor > self::MAX_ID_PROVEEDOR) {
                $errores['idproveedor'] = 'El ID del proveedor debe ser un número entre ' . self::MIN_ID_PROVEEDOR . ' y ' . self::MAX_ID_PROVEEDOR;
            }
        }
        
        // Validar correlativo
        if (isset($datos['correlativo'])) {
            $correlativo = trim((string)$datos['correlativo']);
            if ($correlativo !== '' && mb_strlen($correlativo) < self::MIN_CORRELATIVO) {
                $errores['correlativo'] = 'El N° de Factura debe tener al menos ' . self::MIN_CORRELATIVO . ' caracteres';
            } elseif (mb_strlen($correlativo) > self::MAX_CORRELATIVO) {
                $errores['correlativo'] = 'El N° de Factura no debe exceder los ' . self::MAX_CORRELATIVO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $correlativo)) {
                $errores['correlativo'] = 'El N° de Factura solo puede contener letras, números y guiones';
            }
        }
        
        // Validar descripción
        if (isset($datos['desc'])) {
            $desc = trim((string)$datos['desc']);
            if ($desc !== '' && mb_strlen($desc) > self::MAX_DESCRIPCION) {
                $errores['desc'] = 'La descripción no debe exceder los ' . self::MAX_DESCRIPCION . ' caracteres';
            }
        }
        
        // Validar fecha
        if (isset($datos['fecha'])) {
            $fecha = trim((string)$datos['fecha']);
            if ($fecha !== '' && !$this->validarFormatoFecha($fecha)) {
                $errores['fecha'] = 'La fecha debe tener el formato AAAA-MM-DD';
            }
        }
        
        // Validar costo
        if (isset($datos['costo'])) {
            $costo = (float)$datos['costo'];
            if ($costo < self::MIN_COSTO || $costo > self::MAX_COSTO) {
                $errores['costo'] = 'El costo debe estar entre ' . self::MIN_COSTO . ' y ' . self::MAX_COSTO;
            }
        }
        
        // Validar estado
        if (isset($datos['estado'])) {
            $estado = trim((string)$datos['estado']);
            if (!in_array($estado, self::ESTADOS_VALIDOS)) {
                $errores['estado'] = 'El estado no es válido. Estados permitidos: ' . implode(', ', self::ESTADOS_VALIDOS);
            }
        }
        
        return $errores;
    }
    
    private function validarFiltros($filtros) {
        $errores = [];
        
        if (!is_array($filtros)) {
            $errores['filtros'] = 'Los filtros deben ser un arreglo';
            return $errores;
        }
        
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
        
        // Validar ID de recepción
        if (isset($filtros['id_recepcion'])) {
            $id_recepcion = (int)$filtros['id_recepcion'];
            if ($id_recepcion < self::MIN_ID_RECEPCION || $id_recepcion > self::MAX_ID_RECEPCION) {
                $errores['id_recepcion'] = 'El ID de la recepción debe ser un número entre ' . self::MIN_ID_RECEPCION . ' y ' . self::MAX_ID_RECEPCION;
            }
        }
        
        // Validar correlativo
        if (isset($filtros['correlativo'])) {
            $correlativo = trim((string)$filtros['correlativo']);
            if ($correlativo !== '' && mb_strlen($correlativo) > self::MAX_CORRELATIVO) {
                $errores['correlativo'] = 'El correlativo no debe exceder los ' . self::MAX_CORRELATIVO . ' caracteres';
            }
        }
        
        // Validar fechas
        if (isset($filtros['fecha_inicio'])) {
            $fecha_inicio = trim((string)$filtros['fecha_inicio']);
            if ($fecha_inicio !== '' && !$this->validarFormatoFecha($fecha_inicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener el formato AAAA-MM-DD';
            }
        }
        
        if (isset($filtros['fecha_fin'])) {
            $fecha_fin = trim((string)$filtros['fecha_fin']);
            if ($fecha_fin !== '' && !$this->validarFormatoFecha($fecha_fin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener el formato AAAA-MM-DD';
            }
        }
        
        // Validar rango de fechas
        if (isset($filtros['fecha_inicio']) && isset($filtros['fecha_fin'])) {
            $fecha_inicio = strtotime($filtros['fecha_inicio']);
            $fecha_fin = strtotime($filtros['fecha_fin']);
            if ($fecha_inicio && $fecha_fin && $fecha_inicio > $fecha_fin) {
                $errores['rango_fechas'] = 'La fecha de inicio no puede ser mayor a la fecha de fin';
            }
            
            if ($fecha_inicio && $fecha_fin) {
                $dias_diferencia = ($fecha_fin - $fecha_inicio) / (60 * 60 * 24);
                if ($dias_diferencia > self::MAX_RANGO_FECHAS_DIAS) {
                    $errores['rango_fechas'] = 'El rango de fechas no puede exceder los ' . self::MAX_RANGO_FECHAS_DIAS . ' días';
                }
            }
        }
        
        // Validar ID de proveedor
        if (isset($filtros['id_proveedor'])) {
            $id_proveedor = (int)$filtros['id_proveedor'];
            if ($id_proveedor < self::MIN_ID_PROVEEDOR || $id_proveedor > self::MAX_ID_PROVEEDOR) {
                $errores['id_proveedor'] = 'El ID del proveedor debe ser un número entre ' . self::MIN_ID_PROVEEDOR . ' y ' . self::MAX_ID_PROVEEDOR;
            }
        }
        
        return $errores;
    }
    
    private function validarId($id_recepcion) {
        $errores = [];
        
        if ($id_recepcion === null || $id_recepcion === '') {
            $errores['id_recepcion'] = 'El ID de la recepción es obligatorio';
        } else {
            $id_recepcion = (int)$id_recepcion;
            if ($id_recepcion < self::MIN_ID_RECEPCION || $id_recepcion > self::MAX_ID_RECEPCION) {
                $errores['id_recepcion'] = 'El ID de la recepción debe ser un número entre ' . self::MIN_ID_RECEPCION . ' y ' . self::MAX_ID_RECEPCION;
            }
        }
        
        return $errores;
    }
    
    private function validarCorrelativo($correlativo) {
        $errores = [];
        
        if ($correlativo === null || $correlativo === '') {
            $errores['correlativo'] = 'El correlativo de la recepción es obligatorio';
        } else {
            $correlativo = trim((string)$correlativo);
            if (mb_strlen($correlativo) < self::MIN_CORRELATIVO || mb_strlen($correlativo) > self::MAX_CORRELATIVO) {
                $errores['correlativo'] = 'El correlativo debe tener entre ' . self::MIN_CORRELATIVO . ' y ' . self::MAX_CORRELATIVO . ' caracteres';
            }
            
            // Validar formato alfanumérico
            if (!preg_match('/^[A-Z0-9\-]+$/', $correlativo)) {
                $errores['correlativo'] = 'El correlativo solo puede contener letras mayúsculas, números y guiones';
            }
        }
        
        return $errores;
    }
    
    private function validarIntegridadReferencial($id_recepcion, $pdo) {
        $errores = [];
        
        // Verificar si la recepción existe
        $sql = "SELECT COUNT(*) as total FROM tbl_recepcion_productos WHERE id_recepcion = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_recepcion]);
        $total = $stmt->fetchColumn();
        
        if ($total == 0) {
            $errores['id_recepcion'] = 'La recepción no existe';
        }
        
        // Verificar si la recepción ya está anulada (no se puede anular dos veces)
        $sql = "SELECT estado FROM tbl_recepcion_productos WHERE id_recepcion = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_recepcion]);
        $estado = $stmt->fetchColumn();
        
        if ($estado === 'anulado') {
            $errores['estado'] = 'La recepción ya está anulada';
        }
        
        return $errores;
    }
    
    private function validarReporte($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['datos'] = 'Los datos deben ser un arreglo';
            return $errores;
        }
        
        // Validar tipo de reporte
        if (isset($datos['tipo_reporte'])) {
            if (!in_array($datos['tipo_reporte'], self::FORMATOS_REPORTE)) {
                $errores['tipo_reporte'] = 'El tipo de reporte no es válido. Tipos permitidos: ' . implode(', ', self::FORMATOS_REPORTE);
            }
        }
        
        // Validar fechas si vienen
        if (isset($datos['fechaInicio'])) {
            $fechaInicio = trim((string)$datos['fechaInicio']);
            if ($fechaInicio !== '' && !$this->validarFormatoFecha($fechaInicio)) {
                $errores['fechaInicio'] = 'La fecha de inicio debe tener el formato AAAA-MM-DD';
            }
        }
        
        if (isset($datos['fechaFin'])) {
            $fechaFin = trim((string)$datos['fechaFin']);
            if ($fechaFin !== '' && !$this->validarFormatoFecha($fechaFin)) {
                $errores['fechaFin'] = 'La fecha de fin debe tener el formato AAAA-MM-DD';
            }
        }
        
        // Validar año si viene
        if (isset($datos['anio'])) {
            $anio = (int)$datos['anio'];
            if ($anio < self::MIN_ANIO || $anio > self::MAX_ANIO) {
                $errores['anio'] = 'El año debe estar entre ' . self::MIN_ANIO . ' y ' . self::MAX_ANIO;
            }
        }
        
        // Validar ID de proveedor si viene
        if (isset($datos['proveedorId'])) {
            $proveedorId = (int)$datos['proveedorId'];
            if ($proveedorId < self::MIN_ID_PROVEEDOR || $proveedorId > self::MAX_ID_PROVEEDOR) {
                $errores['proveedorId'] = 'El ID del proveedor debe ser un número entre ' . self::MIN_ID_PROVEEDOR . ' y ' . self::MAX_ID_PROVEEDOR;
            }
        }
        
        return $errores;
    }
    
    // Main validation methods
    
    private function validarFormatoFecha($fecha) {
        // Validar formato AAAA-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        
        $partes = explode('-', $fecha);
        $anio = (int)$partes[0];
        $mes = (int)$partes[1];
        $dia = (int)$partes[2];
        
        // Validar rango de año
        if ($anio < self::MIN_ANIO || $anio > self::MAX_ANIO) {
            return false;
        }
        
        // Validar rango de mes
        if ($mes < self::MIN_MES || $mes > self::MAX_MES) {
            return false;
        }
        
        // Validar día según mes (validación básica)
        $diasPorMes = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        if (($anio % 4 == 0 && $anio % 100 != 0) || ($anio % 400 == 0)) {
            $diasPorMes[1] = 29; // Año bisiesto
        }
        
        if ($dia < 1 || $dia > $diasPorMes[$mes - 1]) {
            return false;
        }
        
        return true;
    }
    
    private function validarDetalleProductos($productos) {
        $errores = [];
        
        if (!is_array($productos)) {
            $errores['productos'] = 'Los datos de productos deben ser un arreglo';
            return $errores;
        }
        
        // Validar que los arrays no estén vacíos
        if (empty($productos['idproducto']) || !is_array($productos['idproducto'])) {
            $errores['productos'] = 'Debe agregar al menos un producto';
            return $errores;
        }
        
        // Validar que todos los arrays tengan la misma cantidad de elementos
        if (count($productos['idproducto']) !== count($productos['cantidad']) || 
            count($productos['idproducto']) !== count($productos['costo'])) {
            $errores['productos'] = 'La información de productos está incompleta';
            return $errores;
        }
        
        // Validar cada producto
        for ($i = 0; $i < count($productos['idproducto']); $i++) {
            $index = $i + 1; // Para mostrar en mensajes de error
            
            // Validar ID de producto
            $idProd = (int)$productos['idproducto'][$i];
            if ($idProd < self::MIN_ID_PRODUCTO || $idProd > self::MAX_ID_PRODUCTO) {
                $errores["producto_{$i}"] = "El producto {$index} no es válido";
            }
            
            // Validar cantidad
            $cant = (int)$productos['cantidad'][$i];
            if ($cant < self::MIN_CANTIDAD || $cant > self::MAX_CANTIDAD) {
                $errores["cantidad_{$i}"] = "La cantidad del producto {$index} debe estar entre " . self::MIN_CANTIDAD . " y " . self::MAX_CANTIDAD;
            }
            
            // Validar costo
            $cost = (float)$productos['costo'][$i];
            if ($cost < self::MIN_COSTO || $cost > self::MAX_COSTO) {
                $errores["costo_{$i}"] = "El costo del producto {$index} debe estar entre " . self::MIN_COSTO . " y " . self::MAX_COSTO;
            }
        }
        
        return $errores;
    }
    
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
        
        return $errores;
    }
    
    public function validarConsultar($filtros = []) {
        return $this->validarFiltros($filtros);
    }
    
    public function validarDetallar($id_recepcion) {
        $errores = $this->validarId($id_recepcion);
        if (!empty($errores)) {
            return $errores;
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_recepcion) {
            return $this->validarIntegridadReferencial($id_recepcion, $pdo);
        });
    }
    
    public function validarAnular($correlativo) {
        $errores = $this->validarCorrelativo($correlativo);
        if (!empty($errores)) {
            return $errores;
        }
        
        return $this->ejecutarConConexionSegura(function($pdo) use ($correlativo) {
            // Obtener ID de recepción por correlativo para validación de integridad
            $sql = "SELECT id_recepcion FROM tbl_recepcion_productos WHERE correlativo = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$correlativo]);
            $id_recepcion = $stmt->fetchColumn();
            
            if (!$id_recepcion) {
                return ['correlativo' => 'La recepción no existe'];
            }
            
            return $this->validarIntegridadReferencial($id_recepcion, $pdo);
        });
    }
    
    public function validarGenerarReporte($datos) {
        return $this->validarReporte($datos);
    }
    
    public function obtenerRecepcionesConFiltros($filtros = []) {
        $errores = $this->validarConsultar($filtros);
        if (!empty($errores)) {
            return ['error' => $errores];
        }
        
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($filtros) {
            $sql = "
                SELECT 
                    r.id_recepcion,
                    r.fecha, 
                    r.correlativo, 
                    pr.nombre_proveedor, 
                    SUM(d.cantidad * d.costo) AS costo_inversion,
                    r.estado
                FROM tbl_recepcion_productos AS r
                INNER JOIN tbl_detalle_recepcion_productos AS d ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_proveedores AS pr ON pr.id_proveedor = r.id_proveedor
                WHERE 1=1";
            
            $params = [];
            
            // Aplicar filtros
            if (isset($filtros['id_recepcion'])) {
                $sql .= " AND r.id_recepcion = :id_recepcion";
                $params[':id_recepcion'] = $filtros['id_recepcion'];
            }
            
            if (isset($filtros['correlativo'])) {
                $sql .= " AND r.correlativo LIKE :correlativo";
                $params[':correlativo'] = '%' . $filtros['correlativo'] . '%';
            }
            
            if (isset($filtros['id_proveedor'])) {
                $sql .= " AND r.id_proveedor = :id_proveedor";
                $params[':id_proveedor'] = $filtros['id_proveedor'];
            }
            
            if (isset($filtros['fecha_inicio'])) {
                $sql .= " AND r.fecha >= :fecha_inicio";
                $params[':fecha_inicio'] = $filtros['fecha_inicio'];
            }
            
            if (isset($filtros['fecha_fin'])) {
                $sql .= " AND r.fecha <= :fecha_fin";
                $params[':fecha_fin'] = $filtros['fecha_fin'];
            }
            
            if (isset($filtros['estado'])) {
                $sql .= " AND r.estado = :estado";
                $params[':estado'] = $filtros['estado'];
            }
            
            $sql .= " GROUP BY r.id_recepcion, r.fecha, r.correlativo, pr.nombre_proveedor, r.estado";
            $sql .= " ORDER BY r.fecha DESC, r.correlativo DESC";
            
            // Aplicar paginación
            $pagina = isset($filtros['pagina']) ? (int)$filtros['pagina'] : 1;
            $limite = isset($filtros['limite']) ? (int)$filtros['limite'] : self::MAX_REGISTROS_PAGINA;
            $offset = ($pagina - 1) * $limite;
            
            $sql .= " LIMIT :offset, :limite";
            $params[':offset'] = $offset;
            $params[':limite'] = $limite;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $recepciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener total para paginación
            $sql_total = str_replace("GROUP BY r.id_recepcion, r.fecha, r.correlativo, pr.nombre_proveedor, r.estado ORDER BY r.fecha DESC, r.correlativo DESC LIMIT :offset, :limite", "", $sql);
            $sql_total = str_replace("SELECT r.id_recepcion, r.fecha, r.correlativo, pr.nombre_proveedor, SUM(d.cantidad * d.costo) AS costo_inversion, r.estado FROM", "SELECT COUNT(*) as total FROM", $sql_total);
            
            $stmt_total = $pdo->prepare($sql_total);
            $stmt_total->execute($params);
            $total = $stmt_total->fetchColumn();
            
            return [
                'data' => $recepciones,
                'total' => $total,
                'pagina' => $pagina,
                'limite' => $limite,
                'total_paginas' => ceil($total / $limite)
            ];
        });
        
        // Descifrar datos personales del proveedor
        if (isset($resultado['data']) && is_array($resultado['data'])) {
            $resultado['data'] = $this->encryption->decryptResults($resultado['data'], self::CAMPOS_CIFRADOS_PROVEEDORES);
        }
        
        return $resultado;
    }
    
    private function verificarRecepcionExistente($id_recepcion) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_recepcion_productos WHERE id_recepcion = ?");
                $stmt->execute([$id_recepcion]);
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                return false;
            } 
        });
    }
    
    private function verificarProveedorExistente($id_proveedor) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_proveedores WHERE id_proveedor = ?");
                $stmt->execute([$id_proveedor]);
                return $stmt->fetchColumn() > 0;
            } catch (PDOException $e) {
                return false;
            }
        });
    }

    public function registrarRecepcion($idproducto, $cantidad, $costo) {
        return $this->r_recepcion($idproducto, $cantidad, $costo); 
    }
    
    private function r_recepcion($idproducto, $cantidad, $costo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($idproducto, $cantidad, $costo){
            $tiempo = date('Y-m-d');
            $sql = "INSERT INTO tbl_recepcion_productos (id_proveedor, fecha, correlativo, estado) 
                VALUES (:idproveedor, :fecha_recepcion, :correlativo, :estado)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':idproveedor', $this->idproveedor, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_recepcion', $tiempo, PDO::PARAM_STR);
            $stmt->bindParam(':correlativo', $this->correlativo, PDO::PARAM_STR);
            $stmt->bindParam(':estado', $this->estado, PDO::PARAM_STR);
            $stmt->execute();

            $idRecepcion = $pdo->lastInsertId();
            $cap = count($idproducto);

            $productosArray = [];

            for ($i = 0; $i < $cap; $i++) {
                $sqlDetalle = "INSERT INTO tbl_detalle_recepcion_productos (id_recepcion, id_producto, cantidad, costo) 
                    VALUES (:idRecepcion, :idProducto, :cantidad, :costo)";
                $stmtDetalle = $pdo->prepare($sqlDetalle);
                $stmtDetalle->bindParam(':idRecepcion', $idRecepcion, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':idProducto', $idproducto[$i], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $cantidad[$i], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':costo', $costo[$i], PDO::PARAM_INT);
                $stmtDetalle->execute();
                $idDetalle = $pdo->lastInsertId();

                $sqlNombre = "SELECT nombre_producto FROM tbl_productos WHERE id_producto = ?";
                $stmtNombre = $pdo->prepare($sqlNombre);
                $stmtNombre->execute([$idproducto[$i]]);
                $nombreProducto = $stmtNombre->fetchColumn();

                $productosArray[] = [
                    'id_producto' => $idproducto[$i],
                    'cantidad' => $cantidad[$i],
                    'costo' => $costo[$i],
                    'iddetalles' => $idDetalle
                ];

                $monto_total = $costo[$i] * $cantidad[$i];
                $descripcion = "Compra: {$nombreProducto} (x{$cantidad[$i]})";

                $sqlEgreso = "INSERT INTO tbl_ingresos_egresos (tipo, monto, descripcion, fecha, estado, id_detalle_recepcion_productos)
                    VALUES ('egreso', ?, ?, ?, 1, LAST_INSERT_ID())";
                $stmtEgreso = $pdo->prepare($sqlEgreso);
                $stmtEgreso->execute([$monto_total, $descripcion, $tiempo]);
            }

            $sqlRecepcion = "
                SELECT 
                    r.id_recepcion,
                    r.fecha, 
                    r.correlativo, 
                    pr.nombre_proveedor, 
                    SUM(d.cantidad * d.costo) AS costo_inversion,
                    r.estado
                FROM tbl_recepcion_productos AS r
                INNER JOIN tbl_detalle_recepcion_productos AS d ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_proveedores AS pr ON pr.id_proveedor = r.id_proveedor
                WHERE r.id_recepcion = :idRecepcion
                GROUP BY r.id_recepcion, r.fecha, r.correlativo, pr.nombre_proveedor, r.estado
            ";
            $stmtRecepcion = $pdo->prepare($sqlRecepcion);
            $stmtRecepcion->bindParam(':idRecepcion', $idRecepcion, PDO::PARAM_INT);
            $stmtRecepcion->execute();
            $recepcion = $stmtRecepcion->fetch(PDO::FETCH_ASSOC);

            // Descifrar datos personales del proveedor
            if ($recepcion && is_array($recepcion)) {
                $recepcion = $this->encryption->decryptArray($recepcion, self::CAMPOS_CIFRADOS_PROVEEDORES);
            }

            return [
                'id_recepcion' => $idRecepcion,
                'productos' => $productosArray,
                'recepcion' => $recepcion
            ];
        });
    }

    private function existeCorrelativo($r) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($r) {
            $sql = "SELECT COUNT(*) FROM tbl_recepcion_productos WHERE correlativo = :correlativo AND estado = 'habilitado'";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':correlativo', $r['correlativo'], PDO::PARAM_STR);
            $stmt->execute();
            $existe = $stmt->fetchColumn();
            return $existe > 0;
        });
    }

    public function obtenerUltimaRecepcion() {
        return $this->obtUltimaRecepcion(); 
    }
    private function obtUltimaRecepcion() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT 
                    r.id_recepcion,
                    r.fecha, 
                    r.correlativo, 
                    pr.nombre_proveedor, 
                    SUM(d.cantidad * d.costo) AS costo_inversion,
                    r.estado
                FROM tbl_recepcion_productos AS r
                INNER JOIN tbl_detalle_recepcion_productos AS d ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_proveedores AS pr ON pr.id_proveedor = r.id_proveedor
                GROUP BY r.id_recepcion, r.fecha, r.correlativo, pr.nombre_proveedor, r.estado
                ORDER BY r.id_recepcion DESC 
                LIMIT 1";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $recepcion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $recepcion ? $recepcion : null;
                
            } catch (PDOException $e) {
                error_log("Error en obtUltimaRecepcion: " . $e->getMessage());
                return null;
            }
        });
        
        // Descifrar datos personales del proveedor
        if ($resultado && is_array($resultado)) {
            $resultado = $this->encryption->decryptArray($resultado, self::CAMPOS_CIFRADOS_PROVEEDORES);
        }
        
        return $resultado;
    }

    public function getrecepcion(){
        return $this->g_recepcion();
    }
    private function g_recepcion(){
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            $queryrecepciones = "
                SELECT 
                    r.id_recepcion,
                    r.fecha, 
                    r.correlativo, 
                    pr.nombre_proveedor, 
                    SUM(d.cantidad * d.costo) AS costo_inversion,
                    r.estado
                FROM tbl_recepcion_productos AS r
                INNER JOIN tbl_detalle_recepcion_productos AS d ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_proveedores AS pr ON pr.id_proveedor = r.id_proveedor
                WHERE r.estado = 'habilitado'
                GROUP BY r.id_recepcion, r.fecha, r.correlativo, pr.nombre_proveedor, r.estado
                ORDER BY r.fecha DESC, r.correlativo DESC
            ";
            $stmtrecepciones = $pdo->prepare($queryrecepciones);
            $stmtrecepciones->execute();
            $recepciones = $stmtrecepciones->fetchAll(PDO::FETCH_ASSOC);
            return $recepciones;
        });
        
        // Descifrar datos personales del proveedor
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS_PROVEEDORES);
        
        return $resultado;
    }

    public function obtenerProductosPorRecepcion($id_recepcion) {
        return $this->obt_productos_recepcion($id_recepcion); 
    }
    private function obt_productos_recepcion($id_recepcion) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_recepcion){
            $sql = "
                SELECT 
                    p.id_producto AS codigo,
                    p.nombre_producto AS producto,
                    m.nombre_modelo AS modelo,
                    mar.nombre_marca AS marca,
                    p.serial,
                    d.cantidad,
                    d.costo
                FROM tbl_detalle_recepcion_productos AS d
                INNER JOIN tbl_productos AS p ON d.id_producto = p.id_producto
                INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo
                INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
                WHERE d.id_recepcion = :id_recepcion
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_recepcion', $id_recepcion, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function anularRecepcion($correlativo) {
        return $this->an_recepcion($correlativo); 
    }
    private function an_recepcion($correlativo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($correlativo){
            $sql = "UPDATE tbl_recepcion_productos SET estado = 'anulado' WHERE correlativo = :correlativo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':correlativo', $correlativo, PDO::PARAM_STR);
            $result = $stmt->execute();
            return $result ? ['status' => 'success'] : ['status' => 'error', 'message' => 'No se pudo anular la recepción'];
        });
    }

    public function obtenerIdRecepcionPorCorrelativo($correlativo) {
        return $this->obt_id_recepcion_por_correlativo($correlativo);
    }
    private function obt_id_recepcion_por_correlativo($correlativo) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($correlativo) {
            $sql = "SELECT id_recepcion FROM tbl_recepcion_productos WHERE correlativo = :correlativo LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':correlativo', $correlativo, PDO::PARAM_STR);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : null;
        });
    }

    public function obtenerproveedor() {
        return $this->obt_proveedor(); 
    }
    private function obt_proveedor() {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sql = "SELECT id_proveedor, nombre_proveedor FROM tbl_proveedores";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return $r;
            } catch (Exception $e) {
                return [];
            }
        });
        
        // Descifrar datos personales del proveedor
        $resultado = $this->encryption->decryptResults($resultado, self::CAMPOS_CIFRADOS_PROVEEDORES);
        
        return $resultado;
    }

    public function listadoproductos() {
        return $this->list_productos(); 
    }
    private function list_productos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $r = array();
            try {
                $sql = "SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial
                        FROM tbl_productos AS p 
                        INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                        INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca;";
                $resultado = $pdo->query($sql);

                if($resultado){
                    $respuesta = '';
                    foreach($resultado as $r){
                        $respuesta = $respuesta."<tr style='cursor:pointer' onclick='colocaproducto(this);'>";
                            $respuesta = $respuesta."<td style='display:none'>";
                                $respuesta = $respuesta.$r['id_producto'];
                            $respuesta = $respuesta."</td>";
                            $respuesta = $respuesta."<td>";
                                $respuesta = $respuesta.$r['id_producto'];
                            $respuesta = $respuesta."</td>";
                            $respuesta = $respuesta."<td>";
                                $respuesta = $respuesta.$r['nombre_producto'];
                            $respuesta = $respuesta."</td>";
                            $respuesta = $respuesta."<td>";
                                $respuesta = $respuesta.$r['nombre_modelo'];
                            $respuesta = $respuesta."</td>";
                            $respuesta = $respuesta."<td>";
                                $respuesta = $respuesta.$r['nombre_marca'];
                            $respuesta = $respuesta."</td>";
                            $respuesta = $respuesta."<td>";
                                $respuesta = $respuesta.$r['serial'];
                            $respuesta = $respuesta."</td>";
                        $respuesta = $respuesta."</tr>";
                    }
                }
                $r['resultado'] = 'listado';
                $r['mensaje'] = $respuesta;
            } catch (Exception $e) {
                $r['resultado'] = 'error';
                $r['mensaje'] = $e->getMessage();
            }
            return $r;
        });
    }

    public function consultarproductos() {
        return $this->consul_productos(); 
    }
    private function consul_productos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial
                    FROM tbl_productos AS p 
                    INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                    INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $registros;
        });
    }

    public function buscar() {
        return $this->bus(); 
    }
    private function bus() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $r = array();
            try {
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $sql = "SELECT * FROM tbl_recepcion_productos WHERE correlativo = :correlativo";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['correlativo' => $this->correlativo]);
                $fila = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($fila) {
                    $r['resultado'] = 'encontró';
                    $r['mensaje'] = 'El número de correlativo ya existe!';
                }
            } catch (Exception $e) {
                $r['resultado'] = 'error';
                $r['mensaje'] = $e->getMessage();
            }
            return $r;
        });
    }

    public function getRecepcionesPorProveedor($fechaInicio = null, $fechaFin = null) {
        return $this->getRecepPorProveedor($fechaInicio, $fechaFin);
    }
    private function getRecepPorProveedor($fechaInicio = null, $fechaFin = null) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($fechaInicio, $fechaFin){
            $sql = "
                SELECT 
                    p.nombre_proveedor AS label,
                    r.fecha AS fecha,
                    r.id_recepcion,
                    1 AS value
                FROM tbl_recepcion_productos r
                INNER JOIN tbl_proveedores p ON r.id_proveedor = p.id_proveedor
                WHERE 1=1
            ";

            $params = [];

            if ($fechaInicio && $fechaFin) {
                $sql .= " AND r.fecha BETWEEN :fechaInicio AND :fechaFin";
                $params[':fechaInicio'] = $fechaInicio;
                $params[':fechaFin'] = $fechaFin;
            }

            $sql .= "
                ORDER BY r.fecha DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
        
        // Descifrar datos personales del proveedor
        // Incluimos el alias 'label' que corresponde a nombre_proveedor
        $camposADescifrar = array_merge(self::CAMPOS_CIFRADOS_PROVEEDORES, ['label']);
        if (is_array($resultado) && !empty($resultado)) {
            $resultado = $this->encryption->decryptResults($resultado, $camposADescifrar);
        }
        
        return $resultado;
    }

    public function getProductosMasRecibidos($fechaInicio = null, $fechaFin = null, $proveedor = null) {
        return $this->getProdMasRecibidos($fechaInicio, $fechaFin, $proveedor);
    }
    private function getProdMasRecibidos($fechaInicio = null, $fechaFin = null, $proveedor = null) {
        $resultado = $this->ejecutarConConexionSegura(function($pdo) use ($fechaInicio, $fechaFin, $proveedor){
            $sql = "
                SELECT 
                    pr.nombre_producto AS label,
                    r.fecha AS fecha,
                    dr.cantidad AS value,
                    p.nombre_proveedor AS proveedor
                FROM tbl_detalle_recepcion_productos dr
                INNER JOIN tbl_productos pr ON dr.id_producto = pr.id_producto
                INNER JOIN tbl_recepcion_productos r ON dr.id_recepcion = r.id_recepcion
                INNER JOIN tbl_proveedores p ON r.id_proveedor = p.id_proveedor
                WHERE 1 = 1
            ";

            $params = [];

            if ($fechaInicio && $fechaFin) {
                $sql .= " AND r.fecha BETWEEN :fechaInicio AND :fechaFin";
                $params[':fechaInicio'] = $fechaInicio;
                $params[':fechaFin'] = $fechaFin;
            }

            if ($proveedor) {
                $sql .= " AND p.id_proveedor = :proveedor";
                $params[':proveedor'] = $proveedor;
            }

            $sql .= "
                ORDER BY r.fecha DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
        
        // Descifrar datos personales del proveedor
        // Incluimos el alias 'proveedor' que corresponde a nombre_proveedor
        $camposADescifrar = array_merge(self::CAMPOS_CIFRADOS_PROVEEDORES, ['proveedor']);
        if (is_array($resultado) && !empty($resultado)) {
            $resultado = $this->encryption->decryptResults($resultado, $camposADescifrar);
        }
        
        return $resultado;
    }

    public function getRecepcionesMensuales($anio = null) {
        return $this->getRecepMensuales($anio);
    }
    private function getRecepMensuales($anio = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($anio) {
            $sql = "
                SELECT 
                    MONTH(r.fecha) AS mes_num,
                    YEAR(r.fecha) AS anio,
                    COUNT(*) AS value
                FROM tbl_recepcion_productos r
                WHERE 1 = 1
            ";

            $params = [];

            if ($anio) {
                $sql .= " AND YEAR(r.fecha) = :anio";
                $params[':anio'] = $anio;
            }

            $sql .= "
                GROUP BY YEAR(r.fecha), MONTH(r.fecha)
                ORDER BY anio, mes_num
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];

            foreach ($resultados as &$fila) {
                $fila['label'] = $meses[$fila['mes_num']] ?? 'Desconocido';
                // conservar mes_num y anio para filtrado en frontend
            }

            return $resultados;
        });
    }
}
?>