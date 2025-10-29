<?php
require_once 'config/config.php';

class Categoria extends BD
{
    private $id_categoria;
    private $nombre_categoria;
    private $conex;

    public function __construct()
    {
        $this->conex = null;
    }

    public function getIdCategoria()
    {
        return $this->id_categoria;
    }

    public function setIdCategoria($id_categoria)
    {
        $this->id_categoria = $id_categoria;
    }

    public function getNombreCategoria()
    {
        return $this->nombre_categoria;
    }

    public function setNombreCategoria($nombre_categoria)
    {
        $this->nombre_categoria = $nombre_categoria;
    }

    private function generarNombreTabla()
    {
        return 'cat_' . strtolower(str_replace(' ', '_', $this->nombre_categoria));
    }

    public function registrarCategoria($caracteristicas)
    {
        return $this->r_registrarCategoria($caracteristicas);
    }

    private function r_registrarCategoria($caracteristicas)
    {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $resultado = $this->r_Categoria();
            if ($resultado) {
                return $this->crearTablaCategoria($caracteristicas);
            }
            return false;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    private function r_Categoria()
    {
        $sql = "INSERT INTO tbl_categoria (nombre_categoria) VALUES (:nombre_categoria)";
        $stmt = $this->conex->prepare($sql);
        $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
        return $stmt->execute();
    }

    private function crearTablaCategoria($caracteristicas)
    {
        $nombreTabla = $this->generarNombreTabla();
        $sql = "CREATE TABLE IF NOT EXISTS `$nombreTabla` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_producto INT NOT NULL,
    ";

        foreach ($caracteristicas as $carac) {
            $campo = strtolower(str_replace(' ', '_', $carac['nombre']));
            switch ($carac['tipo']) {
                case 'int':
                    $sql .= "`$campo` INT,";
                    break;
                case 'float':
                    $sql .= "`$campo` FLOAT,";
                    break;
                case 'string':
                    $max = (int) ($carac['max'] ?? 255);
                    $sql .= "`$campo` VARCHAR($max),";
                    break;
            }
        }
        // Elimina la última coma y cierra el paréntesis
        $sql = rtrim($sql, ',') . ",
        FOREIGN KEY (id_producto) REFERENCES tbl_productos(id_producto) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        return $this->conex->exec($sql) !== false;
    }

    public function modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas)
    {
        return $this->m_modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas);
    }

    private function m_modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas)
    {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $tablaAntigua = $this->generarNombreTabla();
            $this->nombre_categoria = $nuevo_nombre;
            $tablaNueva = $this->generarNombreTabla();

            $this->m_categoria($id_categoria);

            if ($tablaAntigua !== $tablaNueva) {
                $this->conex->exec("RENAME TABLE `$tablaAntigua` TO `$tablaNueva`");
            }

            $cols = $this->conex->query("SHOW COLUMNS FROM `$tablaNueva`")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($cols as $col) {
                if (!in_array($col, ['id', 'id_producto'])) {
                    $this->conex->exec("ALTER TABLE `$tablaNueva` DROP COLUMN `$col`");
                }
            }

            foreach ($caracteristicas as $carac) {
                $campo = strtolower(str_replace(' ', '_', $carac['nombre']));
                switch ($carac['tipo']) {
                    case 'int':
                        $this->conex->exec("ALTER TABLE `$tablaNueva` ADD `$campo` INT");
                        break;
                    case 'float':
                        $this->conex->exec("ALTER TABLE `$tablaNueva` ADD `$campo` FLOAT");
                        break;
                    case 'string':
                        $max = (int) ($carac['max'] ?? 255);
                        $this->conex->exec("ALTER TABLE `$tablaNueva` ADD `$campo` VARCHAR($max)");
                        break;
                }
            }
            return true;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    private function m_categoria($id_categoria)
    {
        // CORRECCIÓN: Elimina ORDER BY, no es válido en UPDATE
        $sql = "UPDATE tbl_categoria SET nombre_categoria = :nombre_categoria WHERE id_categoria = :id_categoria";
        $stmt = $this->conex->prepare($sql);
        $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
        $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
        return $stmt->execute();
    }

    public function eliminarCategoria($id_categoria)
    {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            // Verifica si hay productos asociados a la categoría
            $sql = "SELECT COUNT(*) as total FROM tbl_productos WHERE id_categoria = :id_categoria";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            $stmt->execute();

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $resultado['total'];

            if ($count > 0) {
                // Obtener información de los productos asociados
                $sqlProductos = "SELECT nombre_producto FROM tbl_productos WHERE id_categoria = :id_categoria LIMIT 5";
                $stmtProductos = $this->conex->prepare($sqlProductos);
                $stmtProductos->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
                $stmtProductos->execute();
                $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

                return [
                    'status' => 'error',
                    'mensaje' => 'No se puede eliminar la categoría porque hay productos registrados en ella.',
                    'productos' => $productos,
                    'total_productos' => $count
                ];
            }

            // Si no hay productos, elimina la tabla dinámica y la categoría
            $categoriaInfo = $this->obtenerCategoriaPorId($id_categoria);
            if (!$categoriaInfo) {
                return ['status' => 'error', 'mensaje' => 'Categoría no encontrada'];
            }

            $this->nombre_categoria = $categoriaInfo['nombre_categoria'];
            $tabla = $this->generarNombreTabla();

            // Eliminar la tabla dinámica
            $this->conex->exec("DROP TABLE IF EXISTS `$tabla`");

            // Eliminar la categoría
            $sql = "DELETE FROM tbl_categoria WHERE id_categoria = :id_categoria";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return ['status' => 'success'];
            } else {
                return ['status' => 'error', 'mensaje' => 'Error al eliminar la categoría'];
            }
        } catch (PDOException $e) {
            return ['status' => 'error', 'mensaje' => 'Error en la base de datos: ' . $e->getMessage()];
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function existeNombreCategoria($nombre_categoria, $excluir_id = null)
    {
        return $this->e_existeNombreCategoria($nombre_categoria, $excluir_id);
    }

    private function e_existeNombreCategoria($nombre_categoria, $excluir_id = null)
    {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT COUNT(*) FROM tbl_categoria WHERE nombre_categoria = ?";
            $params = [$nombre_categoria];
            if ($excluir_id !== null) {
                $sql .= " AND id_categoria != ?";
                $params[] = $excluir_id;
            }
            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function obtenerUltimoCategoria()
    {
        return $this->o_ultimoCategoria();
    }

    private function o_ultimoCategoria()
    {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT * FROM tbl_categoria ORDER BY id_categoria DESC LIMIT 1";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function obtenerCategoriaPorId($id_categoria)
    {
        return $this->o_categoriaPorId($id_categoria);
    }

    private function o_categoriaPorId($id_categoria)
    {
        if (empty($id_categoria) || !is_numeric($id_categoria)) {
            return null;
        }
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT id_categoria, nombre_categoria FROM tbl_categoria WHERE id_categoria = :id_categoria ORDER BY id_categoria DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            $stmt->execute();
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$categoria) {
                return null;
            }

            // Obtener características de la tabla dinámica
            $this->nombre_categoria = $categoria['nombre_categoria'];
            $tabla = $this->generarNombreTabla();
            $caracteristicas = [];
            $cols = $this->conex->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                if (!in_array($col['Field'], ['id', 'id_producto'])) {
                    $tipo = 'string';
                    if (strpos($col['Type'], 'int') !== false)
                        $tipo = 'int';
                    elseif (strpos($col['Type'], 'float') !== false)
                        $tipo = 'float';
                    $max = 255;
                    if (preg_match('/varchar\((\d+)\)/i', $col['Type'], $m))
                        $max = $m[1];
                    $caracteristicas[] = [
                        'nombre' => str_replace('_', ' ', ucfirst($col['Field'])),
                        'tipo' => $tipo,
                        'max' => $max
                    ];
                }
            }
            $categoria['caracteristicas'] = $caracteristicas;
            return $categoria;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function consultarCategorias()
    {
        return $this->c_consultarCategorias();
    }

    private function c_consultarCategorias()
    {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        try {
            $sql = "SELECT id_categoria, nombre_categoria FROM tbl_categoria ORDER BY id_categoria DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }
}
?>