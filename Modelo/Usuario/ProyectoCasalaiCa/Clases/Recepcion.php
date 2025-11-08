<?php
require_once 'config/config.php';

class Recepcion extends BD{
    private $idproveedor;
    private $correlativo;
    private $tamanocompra;
    private $desc;
    private $fecha;
    private $costo;
    private $estado;
    private $conex;
    private $tablerecepcion = 'tbl_recepcion_productos';

    public function __construct() {
        $this->conex = null;
    }

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

    public function registrarRecepcion($idproducto, $cantidad, $costo) {
        return $this->r_recepcion($idproducto, $cantidad, $costo); 
    }

    private function r_recepcion($idproducto, $cantidad, $costo) {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
            $tiempo = date('Y-m-d');
            $this->conex->beginTransaction();
            $sql = "INSERT INTO tbl_recepcion_productos (id_proveedor, fecha, correlativo, tamanocompra, estado) 
                VALUES (:idproveedor, :fecha_recepcion, :correlativo, :tamanocompra, :estado)";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':idproveedor', $this->idproveedor, PDO::PARAM_INT);
            $stmt->bindParam(':fecha_recepcion', $tiempo, PDO::PARAM_STR);
            $stmt->bindParam(':correlativo', $this->correlativo, PDO::PARAM_STR);
            $stmt->bindParam(':tamanocompra', $this->tamanocompra, PDO::PARAM_STR);
            $stmt->bindParam(':estado', $this->estado, PDO::PARAM_STR);
            $stmt->execute();

            $idRecepcion = $this->conex->lastInsertId();
            $cap = count($idproducto);

            $productosArray = [];

            for ($i = 0; $i < $cap; $i++) {
                $sqlDetalle = "INSERT INTO tbl_detalle_recepcion_productos (id_recepcion, id_producto, cantidad, costo) 
                    VALUES (:idRecepcion, :idProducto, :cantidad, :costo)";
                $stmtDetalle = $this->conex->prepare($sqlDetalle);
                $stmtDetalle->bindParam(':idRecepcion', $idRecepcion, PDO::PARAM_INT);
                $stmtDetalle->bindParam(':idProducto', $idproducto[$i], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad', $cantidad[$i], PDO::PARAM_INT);
                $stmtDetalle->bindParam(':costo', $costo[$i], PDO::PARAM_INT);
                $stmtDetalle->execute();
                $idDetalle = $this->conex->lastInsertId();

                $sqlNombre = "SELECT id_producto FROM tbl_productos WHERE id_producto = ?";
                $stmtNombre = $this->conex->prepare($sqlNombre);
                $stmtNombre->execute([$idproducto[$i]]);
                $idProducto = $stmtNombre->fetchColumn();

                $sqlNombre = "SELECT nombre_producto FROM tbl_productos WHERE id_producto = ?";
                $stmtNombre = $this->conex->prepare($sqlNombre);
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
                $stmtEgreso = $this->conex->prepare($sqlEgreso);
                $stmtEgreso->execute([$monto_total, $descripcion, $tiempo]);
            }
            $this->conex->commit();

            // Consulta la recepción recién creada con todos los datos necesarios
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
            $stmtRecepcion = $this->conex->prepare($sqlRecepcion);
            $stmtRecepcion->bindParam(':idRecepcion', $idRecepcion, PDO::PARAM_INT);
            $stmtRecepcion->execute();
            $recepcion = $stmtRecepcion->fetch(PDO::FETCH_ASSOC);

            return [
                'id_recepcion' => $idRecepcion,
                'productos' => $productosArray,
                'recepcion' => $recepcion // <-- Aquí tienes todos los datos para la tabla
            ];

        } catch (Exception $e) {
            if ($this->conex && $this->conex->inTransaction()) {
                $this->conex->rollBack();
            }
            throw $e;
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function existeCorrelativo($r) {
        return $this->ex_correlativo($r);
    }
    private function ex_correlativo($correlativo) {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
            $sql = "SELECT COUNT(*) FROM tbl_recepcion_productos WHERE correlativo = :correlativo AND estado = 'habilitado'";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':correlativo', $correlativo, PDO::PARAM_STR);
            $stmt->execute();
            $existe = $stmt->fetchColumn();
            return $existe > 0;
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function obtenerUltimaRecepcion() {
        return $this->obtUltimaRecepcion(); 
    }
    private function obtUltimaRecepcion() {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

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
            
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $recepcion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $recepcion ? $recepcion : null;
            
        } catch (PDOException $e) {
            error_log("Error en obtUltimaRecepcion: " . $e->getMessage());
            return null;
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function getrecepcion(){
        return $this->g_recepcion();
    }

    private function g_recepcion(){
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
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

            $stmtrecepciones = $this->conex->prepare($queryrecepciones);
            $stmtrecepciones->execute();
            $recepciones = $stmtrecepciones->fetchAll(PDO::FETCH_ASSOC);

            return $recepciones;
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function obtenerProductosPorRecepcion($id_recepcion) {
        return $this->obt_productos_recepcion($id_recepcion); 
    }
    private function obt_productos_recepcion($id_recepcion) {
        // Abrir conexión al inicio de la función
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
                    d.cantidad,
                    d.costo
                FROM tbl_detalle_recepcion_productos AS d
                INNER JOIN tbl_productos AS p ON d.id_producto = p.id_producto
                INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo
                INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca
                WHERE d.id_recepcion = :id_recepcion
            ";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_recepcion', $id_recepcion, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function anularRecepcion($correlativo) {
        return $this->an_recepcion($correlativo); 
    }
    private function an_recepcion($correlativo) {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
            $sql = "UPDATE tbl_recepcion_productos SET estado = 'anulado' WHERE correlativo = :correlativo";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':correlativo', $correlativo, PDO::PARAM_STR);
            $result = $stmt->execute();
            return $result ? ['status' => 'success'] : ['status' => 'error', 'message' => 'No se pudo anular la recepción'];
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function obtenerIdRecepcionPorCorrelativo($correlativo) {
        return $this->obt_id_recepcion_por_correlativo($correlativo);
    }
    private function obt_id_recepcion_por_correlativo($correlativo) {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
            $sql = "SELECT id_recepcion FROM tbl_recepcion_productos WHERE correlativo = :correlativo LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':correlativo', $correlativo, PDO::PARAM_STR);
            $stmt->execute();
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : null;
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function obtenerproveedor() {
        return $this->obt_proveedor(); 
    }
    private function obt_proveedor() {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
            $this->conex->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT id_proveedor, nombre_proveedor FROM tbl_proveedores";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $r;
        } catch (Exception $e) {
            return [];
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function listadoproductos() {
        return $this->list_productos(); 
    }
    private function list_productos() {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        $this->conex->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $r = array();
        try {
            $sql = "SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial
                    FROM tbl_productos AS p 
                    INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                    INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca;";
            $resultado = $this->conex->query($sql);

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
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
        return $r;
    }

    public function consultarproductos() {
        return $this->consul_productos(); 
    }
    private function consul_productos() {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
            $sql = "SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, mar.nombre_marca, p.serial
                    FROM tbl_productos AS p 
                    INNER JOIN tbl_modelos AS m ON p.id_modelo = m.id_modelo 
                    INNER JOIN tbl_marcas AS mar ON m.id_marca = mar.id_marca;";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $registros;
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function buscar() {
        return $this->bus(); 
    }
    private function bus() {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        $r = array();
        try {
            $this->conex->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT * FROM tbl_recepcion_productos WHERE correlativo = :correlativo";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute(['correlativo' => $this->correlativo]);
            $fila = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($fila) {
                $r['resultado'] = 'encontró';
                $r['mensaje'] = 'El número de correlativo ya existe!';
            }
        } catch (Exception $e) {
            $r['resultado'] = 'error';
            $r['mensaje'] = $e->getMessage();
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
        return $r;
    }

    public function getRecepcionesPorProveedor($fechaInicio = null, $fechaFin = null) {
        return $this->getRecepPorProveedor($fechaInicio, $fechaFin);
    }
    private function getRecepPorProveedor($fechaInicio = null, $fechaFin = null) {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
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

            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

    public function getProductosMasRecibidos($fechaInicio = null, $fechaFin = null, $proveedor = null) {
        return $this->getProdMasRecibidos($fechaInicio, $fechaFin, $proveedor);
    }
    private function getProdMasRecibidos($fechaInicio = null, $fechaFin = null, $proveedor = null) {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
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

            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }


    public function getRecepcionesMensuales($anio = null) {
        return $this->getRecepMensuales($anio);
    }
    private function getRecepMensuales($anio = null) {
        // Abrir conexión al inicio de la función
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();

        try {
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

            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Traducir meses al español
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
        } finally {
            // Cerrar conexión al finalizar la función
            if (isset($conexion)) {
                $conexion->cerrar();
            }
            $this->conex = null;
        }
    }

}
?>