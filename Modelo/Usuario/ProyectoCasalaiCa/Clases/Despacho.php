<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Despacho extends BD{
    private $conex;
    private $id;
    private $idcliente;
    private $tipocompra;
    private $desc;
    private $fecha;
    private $estado;
    private $correlativo;
    private $tablerecepcion = 'tbl_despachos';
    
    // Constantes para validaciones
    const MAX_DESCRIPCION = 500;
    const MAX_ESTADO = 50;
    const MAX_TIPO_COMPRA = 50;
    const ESTADOS_PERMITIDOS = ['Por Despachar', 'Despachado', 'Anulado'];
    const TIPOS_COMPRA_PERMITIDOS = ['Presencial', 'Online', 'Mixta'];
    const ESTADOS_CAMBIO = ['Por Despachar', 'Despachado'];

    public function __construct() {
        $this->conex = null;
    }

    public function getid() {
        return $this->id;
    }
    public function setid($id) {
        $this->id = $id;
    }

    public function getidcliente() {
        return $this->idcliente;
    }
    public function setidcliente($idcliente) {
        $this->idcliente = $idcliente;
    } 

    public function gettipocompra() {
        return $this->tipocompra;
    }
    public function settipocompra($tipocompra) {
        $this->tipocompra = $tipocompra;
    }

    public function getfecha() {
        return $this->fecha;
    }
    public function setfecha($fecha) {
        $this->fecha = $fecha;
    }

    public function getestado() {
        return $this->estado;
    }
    public function setestado($estado) {
        $this->estado = $estado;
    }

    public function getdesc() {
        return $this->desc;
    }
    public function setdesc($desc) {
        $this->desc = $desc;
    }
    
    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para consultar despachos
     */
    private function validarConsultar($datos) {
        $errores = [];
        
        // Validar ID del despacho (opcional)
        if (isset($datos['id_despacho'])) {
            if (!is_numeric($datos['id_despacho']) || $datos['id_despacho'] <= 0) {
                $errores['id_despacho'] = 'El ID del despacho debe ser un número positivo';
            }
        }
        
        // Validar ID del cliente (opcional)
        if (isset($datos['id_cliente'])) {
            if (!is_numeric($datos['id_cliente']) || $datos['id_cliente'] <= 0) {
                $errores['id_cliente'] = 'El ID del cliente debe ser un número positivo';
            }
        }
        
        // Validar límite de resultados (opcional)
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }
        
        // Validar fecha de inicio (opcional)
        if (isset($datos['fecha_inicio'])) {
            $fechaInicio = trim($datos['fecha_inicio']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaInicio);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_inicio'] = 'La fecha de inicio no es válida';
                }
            }
        }
        
        // Validar fecha de fin (opcional)
        if (isset($datos['fecha_fin'])) {
            $fechaFin = trim($datos['fecha_fin']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaFin);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_fin'] = 'La fecha de fin no es válida';
                }
            }
        }
        
        // Validar que la fecha de fin no sea anterior a la de inicio
        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin']) && !isset($errores['fecha_inicio']) && !isset($errores['fecha_fin'])) {
            $fechaInicio = new \DateTime($datos['fecha_inicio']);
            $fechaFin = new \DateTime($datos['fecha_fin']);
            if ($fechaFin < $fechaInicio) {
                $errores['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
            }
        }
        
        // Validar tipo de compra (opcional)
        if (isset($datos['tipocompra'])) {
            $tipoCompra = trim($datos['tipocompra']);
            if (!empty($tipoCompra) && !in_array($tipoCompra, self::TIPOS_COMPRA_PERMITIDOS)) {
                $errores['tipocompra'] = 'El tipo de compra debe ser uno de: ' . implode(', ', self::TIPOS_COMPRA_PERMITIDOS);
            } elseif (!empty($tipoCompra) && mb_strlen($tipoCompra) > self::MAX_TIPO_COMPRA) {
                $errores['tipocompra'] = 'El tipo de compra no debe exceder los ' . self::MAX_TIPO_COMPRA . ' caracteres';
            }
        }
        
        // Validar estado (opcional)
        if (isset($datos['estado'])) {
            $estado = trim($datos['estado']);
            if (!empty($estado) && !in_array($estado, self::ESTADOS_PERMITIDOS)) {
                $errores['estado'] = 'El estado debe ser uno de: ' . implode(', ', self::ESTADOS_PERMITIDOS);
            } elseif (!empty($estado) && mb_strlen($estado) > self::MAX_ESTADO) {
                $errores['estado'] = 'El estado no debe exceder los ' . self::MAX_ESTADO . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para detallar un despacho
     */
    private function validarDetallar($datos) {
        $errores = [];
        
        // Validar ID del despacho
        if (!isset($datos['id_despacho'])) {
            $errores['id_despacho'] = 'El ID del despacho es obligatorio';
        } elseif (!is_numeric($datos['id_despacho']) || $datos['id_despacho'] <= 0) {
            $errores['id_despacho'] = 'El ID del despacho debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para anular un despacho
     */
    private function validarAnular($datos) {
        $errores = [];
        
        // Validar ID del despacho
        if (!isset($datos['id_despacho'])) {
            $errores['id_despacho'] = 'El ID del despacho es obligatorio';
        } elseif (!is_numeric($datos['id_despacho']) || $datos['id_despacho'] <= 0) {
            $errores['id_despacho'] = 'El ID del despacho debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para cambiar estado de un despacho
     */
    private function validarCambiarEstadoDespacho($datos) {
        $errores = [];
        
        // Validar ID del despacho
        if (!isset($datos['id_despacho'])) {
            $errores['id_despacho'] = 'El ID del despacho es obligatorio';
        } elseif (!is_numeric($datos['id_despacho']) || $datos['id_despacho'] <= 0) {
            $errores['id_despacho'] = 'El ID del despacho debe ser un número positivo';
        }
        
        // Validar estado actual
        if (!isset($datos['estado_actual'])) {
            $errores['estado_actual'] = 'El estado actual del despacho es obligatorio';
        } elseif (!in_array($datos['estado_actual'], self::ESTADOS_CAMBIO)) {
            $errores['estado_actual'] = 'El estado actual debe ser uno de: ' . implode(', ', self::ESTADOS_CAMBIO);
        }
        
        // Validar nuevo estado (opcional, ya que se calcula automáticamente)
        if (isset($datos['nuevo_estado'])) {
            $nuevoEstado = trim($datos['nuevo_estado']);
            if (!in_array($nuevoEstado, self::ESTADOS_CAMBIO)) {
                $errores['nuevo_estado'] = 'El nuevo estado debe ser uno de: ' . implode(', ', self::ESTADOS_CAMBIO);
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para generar reporte
     */
    private function validarReporte($datos) {
        $errores = [];
        
        // Validar límite de resultados (opcional)
        if (isset($datos['limite'])) {
            $limite = (int)$datos['limite'];
            if ($limite <= 0 || $limite > 100) {
                $errores['limite'] = 'El límite debe ser un número positivo entre 1 y 100';
            }
        }
        
        // Validar año (opcional)
        if (isset($datos['anio'])) {
            $anio = (int)$datos['anio'];
            if ($anio < 2000 || $anio > 2100) {
                $errores['anio'] = 'El año debe estar entre 2000 y 2100';
            }
        }
        
        // Validar fecha de inicio (opcional)
        if (isset($datos['fecha_inicio'])) {
            $fechaInicio = trim($datos['fecha_inicio']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)) {
                $errores['fecha_inicio'] = 'La fecha de inicio debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaInicio);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_inicio'] = 'La fecha de inicio no es válida';
                }
            }
        }
        
        // Validar fecha de fin (opcional)
        if (isset($datos['fecha_fin'])) {
            $fechaFin = trim($datos['fecha_fin']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)) {
                $errores['fecha_fin'] = 'La fecha de fin debe tener formato YYYY-MM-DD';
            } else {
                $partes = explode('-', $fechaFin);
                if (!checkdate($partes[1], $partes[2], $partes[0])) {
                    $errores['fecha_fin'] = 'La fecha de fin no es válida';
                }
            }
        }
        
        // Validar que la fecha de fin no sea anterior a la de inicio
        if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin']) && !isset($errores['fecha_inicio']) && !isset($errores['fecha_fin'])) {
            $fechaInicio = new \DateTime($datos['fecha_inicio']);
            $fechaFin = new \DateTime($datos['fecha_fin']);
            if ($fechaFin < $fechaInicio) {
                $errores['fecha_fin'] = 'La fecha de fin no puede ser anterior a la fecha de inicio';
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para consultar (método público)
     */
    public function validarConsultarDespachos($datos) {
        return $this->validarConsultar($datos);
    }
    
    /**
     * Valida los datos para detallar (método público)
     */
    public function validarDetallarDespacho($datos) {
        return $this->validarDetallar($datos);
    }
    
    /**
     * Valida los datos para anular (método público)
     */
    public function validarAnularDespacho($datos) {
        return $this->validarAnular($datos);
    }
    
    /**
     * Valida los datos para cambiar estado (método público)
     */
    public function validarCambiarEstado($datos) {
        return $this->validarCambiarEstadoDespacho($datos);
    }
    
    /**
     * Valida los datos para reporte (método público)
     */
    public function validarReporteDespacho($datos) {
        return $this->validarReporte($datos);
    }
    
    /**
     * Verifica si un despacho existe por ID
     */
    private function verificarDespachoExistente($idDespacho) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT COUNT(*) FROM tbl_despachos WHERE id_despachos = :id_despacho";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(':id_despacho', $idDespacho, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarDespachoExistente: ' . $e->getMessage());
            return false;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }
    
    /**
     * Verifica si un cliente existe y está activo
     */
    private function verificarClienteExistente($idCliente) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT COUNT(*) FROM tbl_clientes WHERE id_clientes = :id_cliente AND activo = 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarClienteExistente: ' . $e->getMessage());
            return false;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }
    
    /**
     * Verifica si un despacho está activo
     */
    private function verificarDespachoActivo($idDespacho) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT COUNT(*) FROM tbl_despachos WHERE id_despachos = :id_despacho AND activo = 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(':id_despacho', $idDespacho, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Error en verificarDespachoActivo: ' . $e->getMessage());
            return false;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
        }
    }

    public function obtenercliente() {
        return $this->obt_cliente();
    }
    private function obt_cliente() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $this->conex->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $p = $this->conex->prepare("SELECT * FROM tbl_clientes");
            $p->execute();
            return $p->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => true, 'mensaje' => $e->getMessage()];
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    // Ejemplo con listadoproductos
    public function listadoproductos() {
        return $this->list_productos(); 
    }
    private function list_productos() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        $r = array();
        try {
            $resultado = $this->conex->query("
                SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial
                FROM tbl_productos AS p 
                INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
            ");
            
            $respuesta = '';
            $totalFilas = 0;
            foreach($resultado as $fila){
                $respuesta .= "<tr style='cursor:pointer' onclick='colocaproducto(this);'>";
                $respuesta .= "<td style='display:none'>{$fila['id_producto']}</td>";
                $respuesta .= "<td>{$fila['id_producto']}</td>";
                $respuesta .= "<td>{$fila['nombre_producto']}</td>";
                $respuesta .= "<td>{$fila['nombre_modelo']}</td>";
                $respuesta .= "<td>{$fila['nombre_marca']}</td>";
                $respuesta .= "<td>{$fila['serial']}</td>";
                $respuesta .= "</tr>";
                $totalFilas++;
            }

            $modalSize = 'modal-md';
            if ($totalFilas > 8) $modalSize = 'modal-lg';
            if ($totalFilas > 20) $modalSize = 'modal-xl';

            $r = [
                'resultado' => 'listado',
                'mensaje' => $respuesta,
                'modalSize' => $modalSize
            ];
        } catch (Exception $e) {
            $r = [
                'resultado' => 'error',
                'mensaje' => $e->getMessage(),
                'modalSize' => 'modal-md'
            ];
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
        return $r;
    }

    // Ejemplo con consultarproductos
    public function consultarproductos() {
        return $this->consul_productos(); 
    }
    private function consul_productos() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial
                    FROM tbl_productos AS p 
                    INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                    INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function getdespacho() {
        return $this->g_despacho(); 
    }

    private function g_despacho() {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $query = "
                SELECT 
                    r.id_despachos,
                    r.fecha_despacho AS fecha,
                    r.tipocompra,
                    r.estado,
                    c.nombre AS nombre_cliente,
                    c.cedula AS cedula_cliente,
                    SUM(d.cantidad) AS total_productos,
                    SUM(d.cantidad * p.precio) AS valor_total
                FROM tbl_despachos AS r
                INNER JOIN tbl_despacho_detalle AS d ON d.id_despacho = r.id_despachos
                INNER JOIN tbl_clientes AS c ON c.id_clientes = r.id_clientes
                INNER JOIN tbl_productos AS p ON p.id_producto = d.id_producto
                WHERE r.activo = '1'
                GROUP BY r.id_despachos, r.fecha_despacho, r.tipocompra, r.estado, c.nombre
                ORDER BY r.fecha_despacho DESC
            ";
            $stmt = $this->conex->prepare($query);
            $stmt->execute();
            $despachos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ahora obtenemos los productos por cada despacho
            foreach ($despachos as &$despacho) {
                $sqlProd = "
                    SELECT 
                        p.id_producto AS codigo,
                        p.nombre_producto AS producto,
                        m.nombre_modelo AS modelo,
                        mar.nombre_marca AS marca,
                        p.serial,
                        d.cantidad,
                        d.id_detalle AS id_detalle,
                        p.precio AS precio_unitario,
                        (d.cantidad * p.precio) AS subtotal
                    FROM tbl_despacho_detalle AS d
                    INNER JOIN tbl_productos AS p ON p.id_producto = d.id_producto
                    INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo
                    INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
                    WHERE d.id_despacho = ?
                ";
                $stmtProd = $this->conex->prepare($sqlProd);
                $stmtProd->execute([$despacho['id_despachos']]);
                $despacho['productos'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
            }

            return $despachos;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function obt_productos_despacho($id_despacho) {
        return $this->obt_productos_des($id_despacho); 
    }
    private function obt_productos_des($id_despacho) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "
                SELECT 
                    p.id_producto AS codigo,
                    p.nombre_producto AS producto,
                    m.nombre_modelo AS modelo,
                    mar.nombre_marca AS marca,
                    p.serial,
                    d.cantidad
                FROM tbl_despacho_detalle AS d
                INNER JOIN tbl_productos AS p ON d.id_producto = p.id_producto
                INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo
                INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
                WHERE d.id_despacho = :id_despacho
            ";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_despacho', $id_despacho, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

////////////////////////////////////////////////////////////

// 🚚 4. Despachos por estado (con rango de fechas)
public function getDespachosEstado($fechaInicio = null, $fechaFin = null) {
    $conexion = new BD('P');
    $this->conex = $conexion->getConexion();
    try {
        $sql = "
            SELECT 
                estado AS label, 
                COUNT(*) AS value
            FROM tbl_orden_despachos
            WHERE 1 = 1
        ";

        $params = [];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND fecha_despacho BETWEEN :fechaInicio AND :fechaFin";
            $params[':fechaInicio'] = $fechaInicio;
            $params[':fechaFin'] = $fechaFin;
        }

        $sql .= " GROUP BY estado ORDER BY value DESC";

        $stmt = $this->conex->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } finally {
        if (isset($conexion)) { $conexion->cerrar(); }
        $this->conex = null;
    }
}

////////////////////////////////////////////////////////////

// 📦 5. Volumen de productos despachados por mes (por año)
public function getProductosDespachadosPorMes($anio = null) {
    $conexion = new BD('P');
    $this->conex = $conexion->getConexion();
    try {
        $sql = "
            SELECT 
                MONTH(od.fecha_despacho) AS mes_num, 
                SUM(dd.cantidad) AS value
            FROM tbl_despacho_detalle dd
            INNER JOIN tbl_orden_despachos od ON dd.id_detalle = od.id_orden_despachos
            WHERE 1 = 1
        ";

        $params = [];

        if ($anio) {
            $sql .= " AND YEAR(od.fecha_despacho) = :anio";
            $params[':anio'] = $anio;
        }

        $sql .= "
            GROUP BY MONTH(od.fecha_despacho)
            ORDER BY mes_num
        ";

        $stmt = $this->conex->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Traducción de meses
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        foreach ($resultados as &$fila) {
            $fila['label'] = $meses[$fila['mes_num']] ?? 'Desconocido';
            unset($fila['mes_num']);
        }

        return $resultados;
    } finally {
        if (isset($conexion)) { $conexion->cerrar(); }
        $this->conex = null;
    }
}

    public function anularDespacho($idDespacho) {
        return $this->an_despacho($idDespacho); 
    }

    private function an_despacho($idDespacho) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_despachos SET activo = 0 WHERE id_despachos = :id";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id', $idDespacho, PDO::PARAM_INT);
            $result = $stmt->execute();

            return $result 
                ? ['status' => 'success'] 
                : ['status' => 'error', 'message' => 'No se pudo anular el despacho'];
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function cambiarEstadoDespacho($id, $nuevoEstado) {
        return $this->cam_estadoDespacho($id, $nuevoEstado); 
    }
    private function cam_estadoDespacho($id, $nuevoEstado) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "UPDATE tbl_despachos SET estado = :estado WHERE id_despachos = :id";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':estado', $nuevoEstado);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    // 🚚 5. Despachos por cliente
    public function getDespachosPorCliente($fechaInicio = null, $fechaFin = null) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "
                SELECT 
                    c.nombre AS cliente, 
                    COUNT(*) AS total
                FROM tbl_despachos d
                INNER JOIN tbl_clientes c ON d.id_clientes = c.id_clientes
                WHERE d.activo = '1'
            ";

            $params = [];

            if ($fechaInicio && $fechaFin) {
                $sql .= " AND d.fecha_despacho BETWEEN :fechaInicio AND :fechaFin";
                $params[':fechaInicio'] = $fechaInicio;
                $params[':fechaFin'] = $fechaFin;
            }

            $sql .= " GROUP BY c.id_clientes, c.nombre ORDER BY total DESC LIMIT 10";

            $stmt = $this->conex->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => true, 'mensaje' => $e->getMessage()];
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    // 🚚 6. Despachos por tipo de compra
    public function getDespachosPorTipoCompra($fechaInicio = null, $fechaFin = null) {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "
                SELECT 
                    tipocompra AS tipo_compra, 
                    COUNT(*) AS total
                FROM tbl_despachos
                WHERE activo = '1'
            ";

            $params = [];

            if ($fechaInicio && $fechaFin) {
                $sql .= " AND fecha_despacho BETWEEN :fechaInicio AND :fechaFin";
                $params[':fechaInicio'] = $fechaInicio;
                $params[':fechaFin'] = $fechaFin;
            }

            $sql .= " GROUP BY tipocompra ORDER BY total DESC";

            $stmt = $this->conex->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => true, 'mensaje' => $e->getMessage()];
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }
}
?>
