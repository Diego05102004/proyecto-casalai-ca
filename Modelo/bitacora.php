<?php
require_once 'Config/Config.php';
class Bitacora extends BD {
    public function __construct() {
        parent::__construct();
    }

    // Registrar acción en la bitácora
public function registrarBitacora($id_usuario, $modulo, $accion, $descripcion,$prioridad, $datos_anteriores = null, $datos_nuevos = null)
{
    return $this->r_registrarBitacora($id_usuario, $modulo, $accion, $descripcion, $prioridad, $datos_anteriores, $datos_nuevos);
}
private function r_registrarBitacora($id_usuario, $modulo, $accion, $descripcion,$prioridad, $datos_anteriores = null, $datos_nuevos = null)
{
    if (defined('SKIP_SIDE_EFFECTS') && SKIP_SIDE_EFFECTS) {
        return true;
    }
    $conexion = new BD('S');
    $co = $conexion->getConexion();
    try {
        $sql = "
            INSERT INTO tbl_bitacora 
            (id_usuario, id_modulo, accion, descripcion, datos_viejos, datos_nuevos, fecha_hora, prioridad)
            VALUES 
            (:id_usuario, :modulo, :accion, :descripcion, :datos_anteriores, :datos_nuevos, NOW(), :prioridad)
        ";
        $stmt = $co->prepare($sql);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':modulo', $modulo, PDO::PARAM_STR);
        $stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
        $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
        $datos_anteriores_json = $datos_anteriores ? json_encode($datos_anteriores) : null;
        $datos_nuevos_json = $datos_nuevos ? json_encode($datos_nuevos) : null;
        $stmt->bindParam(':datos_anteriores', $datos_anteriores_json, PDO::PARAM_STR);
        $stmt->bindParam(':datos_nuevos', $datos_nuevos_json, PDO::PARAM_STR);
        $stmt->bindParam(':prioridad', $prioridad, PDO::PARAM_STR);
        return $stmt->execute();
    } finally {
        if (isset($conexion)) { 
            $conexion->cerrar(); 
        }
        $co = null;
    }
}


    // Obtener registros detallados de la bitácora (con usuario y módulo)
    public function obtenerRegistrosDetallados($limit = 100) {
        return $this->o_registrosDetallados($limit);
    }
    private function o_registrosDetallados($limit = 100) {
        $conexion = new BD('S');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT 
                    b.id_bitacora,
                    b.fecha_hora,
                    b.accion,
                    b.id_modulo,
                    b.descripcion,
                    b.datos_viejos,
                    b.datos_nuevos,
                    b.prioridad,
                    b.id_usuario,
                    u.username,
                    m.nombre_modulo AS modulo
                FROM tbl_bitacora b
                INNER JOIN tbl_usuarios u ON b.id_usuario = u.id_usuario
                INNER JOIN tbl_modulos m ON b.id_modulo = m.id_modulo
                ORDER BY b.fecha_hora DESC
                LIMIT :limite";
            $stmt = $co->prepare($sql);
            $stmt->bindValue(':limite', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar(); 
            }
            $co = null;
        }
    }

    // Estadísticas de accesos semanales al catálogo
    public function obtenerEstadisticasAccesos() {
        return $this->o_estadisticasAccesos();
    }
    private function o_estadisticasAccesos() {
        $conexion = new BD('S');
        $co = $conexion->getConexion();
        try {
            // Agrupa por semana (formato: YYYYWW)
            $sql = "SELECT 
                        YEAR(fecha_hora) * 100 + WEEK(fecha_hora, 1) AS semana,
                        COUNT(*) AS total_accesos,
                        COUNT(DISTINCT id_usuario) AS usuarios_unicos,
                        ROUND(COUNT(*) / 7, 1) AS promedio_diario
                    FROM tbl_bitacora
                    WHERE id_modulo = 1
                    GROUP BY semana
                    ORDER BY semana DESC
                    LIMIT 10";
            $stmt = $co->prepare($sql);
            $stmt->execute();
            $semanas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Totales generales
            $sqlTotal = "SELECT COUNT(*) AS total, COUNT(DISTINCT id_usuario) AS unicos FROM tbl_bitacora WHERE id_modulo = 1";
            $stmtTotal = $co->prepare($sqlTotal);
            $stmtTotal->execute();
            $totales = $stmtTotal->fetch(PDO::FETCH_ASSOC);

            // Promedio diario global
            $sqlDias = "SELECT DATEDIFF(MAX(fecha_hora), MIN(fecha_hora)) + 1 AS dias FROM tbl_bitacora WHERE id_modulo = 1";
            $stmtDias = $co->prepare($sqlDias);
            $stmtDias->execute();
            $dias = $stmtDias->fetchColumn();
            $promedio_diario = ($dias > 0 && $totales['total'] > 0) ? round($totales['total'] / $dias, 1) : 0;

            return [
                'total' => $totales['total'],
                'unicos' => $totales['unicos'],
                'promedio_diario' => $promedio_diario,
                'semanas' => $semanas
            ];
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar(); 
            }
            $co = null;
        }
    }

    // Top usuarios más activos en el catálogo
    public function obtenerUsuariosMasActivos($limite = 10) {
        return $this->o_usuariosMasActivos($limite);
    }
    private function o_usuariosMasActivos($limite = 10) {
        $conexion = new BD('S');
        $co = $conexion->getConexion();
        try {
            $sql = "SELECT 
                        u.id_usuario,
                        u.username,
                        u.nombres,
                        u.apellidos,
                        COUNT(b.id_bitacora) AS total_accesos,
                        MIN(b.fecha_hora) AS primer_acceso
                    FROM tbl_bitacora b
                    JOIN tbl_usuarios u ON b.id_usuario = u.id_usuario
                    WHERE b.id_modulo = 1
                    GROUP BY u.id_usuario
                    ORDER BY total_accesos DESC
                    LIMIT :limite";
            $stmt = $co->prepare($sql);
            $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalAccesos = array_sum(array_column($usuarios, 'total_accesos'));
            foreach ($usuarios as &$usuario) {
                $usuario['porcentaje'] = $totalAccesos > 0 ? round(($usuario['total_accesos'] / $totalAccesos) * 100, 2) : 0;
            }
            return $usuarios;
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar(); 
            }
            $co = null;
        }
    }

    // Puedes agregar más métodos según necesidades del sistema...
}
?>
