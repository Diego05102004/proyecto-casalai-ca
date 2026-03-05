<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
class Recepcion extends BD{
    private $idproveedor;
    private $correlativo;
    private $tamanocompra;
    private $desc;
    private $fecha;
    private $costo;
    private $estado;
    private $tablerecepcion = 'tbl_recepcion_productos';

    // Constantes de validación
    const MAX_ID_PROVEEDOR = 999999999;
    const MIN_ID_PROVEEDOR = 1;
    const MAX_CORRELATIVO = 50;
    const MIN_CORRELATIVO = 3;
    const MAX_TAMANOCOMPRA = 100;
    const MIN_TAMANOCOMPRA = 2;
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

    public function gettamanocompra() {
        return $this->tamanocompra;
    }
    public function settamanocompra($tamanocompra) {
        $this->tamanocompra = $tamanocompra;
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

    private function validarRecepcion($datos) {
        $errores = [];
        
        if (!is_array($datos)) {
            $errores['recepcion'] = 'Los datos de la recepción deben ser un arreglo';
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
            if ($correlativo === '') {
                $errores['correlativo'] = 'El N° de Factura es obligatorio';
            } elseif (mb_strlen($correlativo) < self::MIN_CORRELATIVO || mb_strlen($correlativo) > self::MAX_CORRELATIVO) {
                $errores['correlativo'] = 'El N° de Factura debe tener entre ' . self::MIN_CORRELATIVO . ' y ' . self::MAX_CORRELATIVO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $correlativo)) {
                $errores['correlativo'] = 'El N° de Factura solo puede contener letras, números y guiones';
            }
        }
        
        // Validar tamaño de compra
        if (isset($datos['tamanocompra'])) {
            $tamanocompra = trim((string)$datos['tamanocompra']);
            if ($tamanocompra === '') {
                $errores['tamanocompra'] = 'El tamaño de compra es obligatorio';
            } elseif (mb_strlen($tamanocompra) < self::MIN_TAMANOCOMPRA || mb_strlen($tamanocompra) > self::MAX_TAMANOCOMPRA) {
                $errores['tamanocompra'] = 'El tamaño de compra debe tener entre ' . self::MIN_TAMANOCOMPRA . ' y ' . self::MAX_TAMANOCOMPRA . ' caracteres';
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
    
    // Métodos públicos de validación
    public function validarConsultarRecepcion($datos) {
        $errores = [];
        
        // Para consultar, podemos validar por ID o correlativo
        if (isset($datos['id_recepcion'])) {
            $id_recepcion = (int)$datos['id_recepcion'];
            if ($id_recepcion < self::MIN_ID_RECEPCION || $id_recepcion > self::MAX_ID_RECEPCION) {
                $errores['id_recepcion'] = 'El ID de la recepción debe ser un número entre ' . self::MIN_ID_RECEPCION . ' y ' . self::MAX_ID_RECEPCION;
            }
        }
        
        if (isset($datos['correlativo'])) {
            $correlativo = trim((string)$datos['correlativo']);
            if ($correlativo !== '' && mb_strlen($correlativo) > self::MAX_CORRELATIVO) {
                $errores['correlativo'] = 'El correlativo no debe exceder los ' . self::MAX_CORRELATIVO . ' caracteres';
            }
        }
        
        return $errores;
    }
    
    public function validarDetallarRecepcion($datos) {
        $errores = [];
        
        // Para detallar, el ID es obligatorio
        if (!isset($datos['id_recepcion'])) {
            $errores['id_recepcion'] = 'El ID de la recepción es obligatorio';
        } else {
            $id_recepcion = (int)$datos['id_recepcion'];
            if ($id_recepcion < self::MIN_ID_RECEPCION || $id_recepcion > self::MAX_ID_RECEPCION) {
                $errores['id_recepcion'] = 'El ID de la recepción debe ser un número entre ' . self::MIN_ID_RECEPCION . ' y ' . self::MAX_ID_RECEPCION;
            }
        }
        
        return $errores;
    }
    
    public function validarRegistrarRecepcion($datos) {
        $errores = [];
        
        // Para registrar, requerimos campos obligatorios
        if (!isset($datos['idproveedor'])) {
            $errores['idproveedor'] = 'El proveedor es obligatorio';
        }
        
        if (!isset($datos['correlativo'])) {
            $errores['correlativo'] = 'El N° de Factura es obligatorio';
        }
        
        if (!isset($datos['tamanocompra'])) {
            $errores['tamanocompra'] = 'El tamaño de compra es obligatorio';
        }
        
        // Validar la recepción completa
        $errores_recepcion = $this->validarRecepcion($datos);
        if (!empty($errores_recepcion)) {
            $errores = array_merge($errores, $errores_recepcion);
        }
        
        // Validar productos si vienen
        if (isset($datos['productos'])) {
            $errores_productos = $this->validarDetalleProductos($datos['productos']);
            if (!empty($errores_productos)) {
                $errores = array_merge($errores, $errores_productos);
            }
        }
        
        return $errores;
    }
    
    public function validarAnularRecepcion($datos) {
        $errores = [];
        
        // Para anular, el correlativo es obligatorio
        if (!isset($datos['correlativo'])) {
            $errores['correlativo'] = 'El N° de Factura es obligatorio';
        } else {
            $correlativo = trim((string)$datos['correlativo']);
            if ($correlativo === '') {
                $errores['correlativo'] = 'El N° de Factura es obligatorio';
            } elseif (mb_strlen($correlativo) < self::MIN_CORRELATIVO || mb_strlen($correlativo) > self::MAX_CORRELATIVO) {
                $errores['correlativo'] = 'El N° de Factura debe tener entre ' . self::MIN_CORRELATIVO . ' y ' . self::MAX_CORRELATIVO . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9\-]+$/', $correlativo)) {
                $errores['correlativo'] = 'El N° de Factura solo puede contener letras, números y guiones';
            }
        }
        
        return $errores;
    }
    
    public function validarReporte($datos) {
        $errores = [];
        
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
    
    public function validarDescarga($datos) {
        $errores = [];
        
        // Para descarga, validar tipo de descarga
        if (isset($datos['tipo_descarga'])) {
            $tipos_validos = ['pdf', 'excel', 'csv'];
            if (!in_array($datos['tipo_descarga'], $tipos_validos)) {
                $errores['tipo_descarga'] = 'El tipo de descarga no es válido. Tipos permitidos: ' . implode(', ', $tipos_validos);
            }
        }
        
        // Validar parámetros adicionales según el tipo
        if (isset($datos['parametros']) && is_array($datos['parametros'])) {
            foreach ($datos['parametros'] as $parametro => $valor) {
                if (is_string($valor) && mb_strlen($valor) > 100) {
                    $errores[$parametro] = 'El parámetro ' . $parametro . ' es demasiado largo';
                }
            }
        }
        
        return $errores;
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
            try {
                $tiempo = date('Y-m-d');
                $pdo->beginTransaction();
                $sql = "INSERT INTO tbl_recepcion_productos (id_proveedor, fecha, correlativo, tamanocompra, estado) 
                    VALUES (:idproveedor, :fecha_recepcion, :correlativo, :tamanocompra, :estado)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':idproveedor', $this->idproveedor, PDO::PARAM_INT);
                $stmt->bindParam(':fecha_recepcion', $tiempo, PDO::PARAM_STR);
                $stmt->bindParam(':correlativo', $this->correlativo, PDO::PARAM_STR);
                $stmt->bindParam(':tamanocompra', $this->tamanocompra, PDO::PARAM_STR);
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

                    $sqlNombre = "SELECT id_producto FROM tbl_productos WHERE id_producto = ?";
                    $stmtNombre = $pdo->prepare($sqlNombre);
                    $stmtNombre->execute([$idproducto[$i]]);
                    $idProducto = $stmtNombre->fetchColumn();

                    $sqlNombre = "SELECT nombre_producto FROM tbl_productos WHERE id_producto = ?";
                    $stmtNombre = $pdo->prepare($sqlNombre);
                    $stmtNombre->execute([$idproducto[$i]]);
                    $nombreProducto = $stmtNombre->fetchColumn();

                    $productosArray[] = [
                        'id_producto' => $idProducto,
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
                $pdo->commit();

                $sqlRecepcion = "
                    SELECT 
                        r.id_recepcion,
                        r.fecha, 
                        r.correlativo, 
                        pr.nombre_proveedor, 
                        r.tamanocompra,
                        SUM(d.cantidad * d.costo) AS costo_inversion,
                        r.estado
                    FROM tbl_recepcion_productos AS r
                    INNER JOIN tbl_detalle_recepcion_productos AS d ON d.id_recepcion = r.id_recepcion
                    INNER JOIN tbl_proveedores AS pr ON pr.id_proveedor = r.id_proveedor
                    WHERE r.id_recepcion = :idRecepcion
                    GROUP BY r.id_recepcion, r.fecha, r.correlativo, pr.nombre_proveedor, r.tamanocompra, r.estado
                ";
                $stmtRecepcion = $pdo->prepare($sqlRecepcion);
                $stmtRecepcion->bindParam(':idRecepcion', $idRecepcion, PDO::PARAM_INT);
                $stmtRecepcion->execute();
                $recepcion = $stmtRecepcion->fetch(PDO::FETCH_ASSOC);

                return [
                    'id_recepcion' => $idRecepcion,
                    'productos' => $productosArray,
                    'recepcion' => $recepcion
                ];

            } catch (Exception $e) {
                if ($pdo && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
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
        return $this->ejecutarConConexionSegura(function($pdo) {
            try {
                $sql = "SELECT 
                    r.id_recepcion,
                    r.fecha, 
                    r.correlativo, 
                    pr.nombre_proveedor, 
                    r.tamanocompra,
                    SUM(d.cantidad * d.costo) AS costo_inversion,
                    r.estado
                FROM tbl_recepcion_productos AS r
                INNER JOIN tbl_detalle_recepcion_productos AS d ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_proveedores AS pr ON pr.id_proveedor = r.id_proveedor
                GROUP BY r.id_recepcion, r.fecha, r.correlativo, pr.nombre_proveedor, r.tamanocompra, r.estado
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
    }

    public function getrecepcion(){
        return $this->g_recepcion();
    }
    private function g_recepcion(){
        return $this->ejecutarConConexionSegura(function($pdo) {
            $queryrecepciones = "
                SELECT 
                    r.id_recepcion,
                    r.fecha, 
                    r.correlativo, 
                    pr.nombre_proveedor, 
                    r.tamanocompra,
                    SUM(d.cantidad * d.costo) AS costo_inversion,
                    r.estado
                FROM tbl_recepcion_productos AS r
                INNER JOIN tbl_detalle_recepcion_productos AS d ON d.id_recepcion = r.id_recepcion
                INNER JOIN tbl_proveedores AS pr ON pr.id_proveedor = r.id_proveedor
                WHERE r.estado = 'habilitado'
                GROUP BY r.id_recepcion, r.fecha, r.correlativo, pr.nombre_proveedor, r.tamanocompra
                ORDER BY r.fecha DESC, r.correlativo DESC, r.tamanocompra DESC
            ";
            $stmtrecepciones = $pdo->prepare($queryrecepciones);
            $stmtrecepciones->execute();
            $recepciones = $stmtrecepciones->fetchAll(PDO::FETCH_ASSOC);
            return $recepciones;
        });
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
        return $this->ejecutarConConexionSegura(function($pdo) {
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
        return $this->ejecutarConConexionSegura(function($pdo) use ($fechaInicio, $fechaFin){
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
    }

    public function getProductosMasRecibidos($fechaInicio = null, $fechaFin = null, $proveedor = null) {
        return $this->getProdMasRecibidos($fechaInicio, $fechaFin, $proveedor);
    }
    private function getProdMasRecibidos($fechaInicio = null, $fechaFin = null, $proveedor = null) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($fechaInicio, $fechaFin, $proveedor){
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