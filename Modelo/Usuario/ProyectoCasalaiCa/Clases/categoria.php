<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;
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
    
    public function existeNombreCategoria($nombre, $excluirId = null)
    {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        
        try {
            $sql = "SELECT COUNT(*) FROM tbl_categoria WHERE nombre_categoria = :nombre";
            $params = [':nombre' => $nombre];
            
            if ($excluirId !== null) {
                $sql .= " AND id_categoria != :id";
                $params[':id'] = $excluirId;
            }
            
            $stmt = $this->conex->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            
            return $count > 0;
        } catch (PDOException $e) {
            error_log('Error en existeNombreCategoria: ' . $e->getMessage());
            return false;
        } finally {
            if (isset($conexion)) {
                $conexion->cerrar();
                $this->conex = null;
            }
        }
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
        
        if (!$this->conex) {
            throw new PDOException("No se pudo establecer la conexión a la base de datos");
        }
        
        try {
            // 1️⃣ Obtener el nombre actual de la categoría usando la conexión actual
            $categoriaInfo = $this->o_categoriaPorId($id_categoria, $conexion);
            if (!$categoriaInfo) {
                throw new PDOException("Categoría no encontrada");
            }

            // Validar que el nombre de la categoría no esté vacío
            if (empty($nuevo_nombre)) {
                throw new PDOException("El nombre de la categoría no puede estar vacío");
            }

            $this->nombre_categoria = $categoriaInfo['nombre_categoria'];
            $tablaAntigua = $this->generarNombreTabla();

            // 2️⃣ Actualizar el nombre
            $this->nombre_categoria = $nuevo_nombre;
            $tablaNueva = $this->generarNombreTabla();

            // 3️⃣ Actualizar registro en tbl_categoria
            $sql = "UPDATE tbl_categoria SET nombre_categoria = :nombre WHERE id_categoria = :id";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindValue(':nombre', $nuevo_nombre);
            $stmt->bindValue(':id', $id_categoria, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new PDOException("Error al actualizar el nombre de la categoría");
            }

            // 4️⃣ Renombrar tabla si cambió el nombre
            if ($tablaAntigua !== $tablaNueva) {
                $this->conex->exec("RENAME TABLE `$tablaAntigua` TO `$tablaNueva`");
            }

            // 5️⃣ Obtener columnas actuales
            $stmt = $this->conex->query("SHOW COLUMNS FROM `$tablaNueva`");
            if ($stmt === false) {
                throw new PDOException("Error al obtener las columnas de la tabla: " . implode(" ", $this->conex->errorInfo()));
            }
            $colsActuales = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $baseCols = ['id', 'id_producto']; // columnas fijas
            $colsActualesFiltradas = array_diff($colsActuales, $baseCols);

            // 6️⃣ Preparar nombres nuevos de campos
            $nuevosCampos = [];
            if (is_array($caracteristicas)) {
                foreach ($caracteristicas as $carac) {
                    if (isset($carac['nombre']) && !empty(trim($carac['nombre']))) {
                        $nuevosCampos[] = strtolower(str_replace(' ', '_', trim($carac['nombre'])));
                    }
                }
            }

            // 7️⃣ Eliminar campos que ya no existen
            foreach ($colsActualesFiltradas as $colExistente) {
                if (!in_array($colExistente, $nuevosCampos)) {
                    $this->conex->exec("ALTER TABLE `$tablaNueva` DROP COLUMN `$colExistente`");
                }
            }

            // 8️⃣ Agregar nuevos campos o ajustar tipo si es diferente
            if (is_array($caracteristicas)) {
                foreach ($caracteristicas as $carac) {
                    if (!isset($carac['nombre']) || empty(trim($carac['nombre']))) {
                        continue;
                    }
                    
                    $campo = strtolower(str_replace(' ', '_', trim($carac['nombre'])));
                    $tipoSQL = '';
                    
                    switch (strtolower($carac['tipo'] ?? '')) {
                        case 'int':
                            $tipoSQL = 'INT';
                            break;
                        case 'float':
                            $tipoSQL = 'FLOAT';
                            break;
                        case 'string':
                        default:
                            $max = isset($carac['max']) ? (int)$carac['max'] : 255;
                            $tipoSQL = "VARCHAR(" . max(1, min(255, $max)) . ")";
                            break;
                    }

                    // Verificar si el campo ya existe
                    $existeCampo = in_array($campo, $colsActuales);
                    if (!$existeCampo) {
                        $this->conex->exec("ALTER TABLE `$tablaNueva` ADD `$campo` $tipoSQL");
                    } else {
                        // Si existe, verificar tipo actual
                        $stmt = $this->conex->query("SHOW COLUMNS FROM `$tablaNueva` LIKE '$campo'");
                        if ($stmt) {
                            $tipoActual = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($tipoActual && stripos($tipoActual['Type'], strtolower($tipoSQL)) === false) {
                                $this->conex->exec("ALTER TABLE `$tablaNueva` MODIFY `$campo` $tipoSQL");
                            }
                        }
                    }
                }
            }

            return true;
        } catch (PDOException $e) {
            throw new PDOException("Error al modificar la categoría: " . $e->getMessage());
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    private function m_categoria($id_categoria)
    {
        if (!$this->conex) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
        }
        
        if (!$this->conex) {
            error_log("No se pudo establecer la conexión a la base de datos");
            return false;
        }
        
        try {
            $sql = "UPDATE tbl_categoria SET nombre_categoria = :nombre_categoria WHERE id_categoria = :id_categoria";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Error al actualizar categoría: ' . $e->getMessage());
            return false;
        } finally {
            if (isset($conexion)) { $conexion->cerrar(); }
            $this->conex = null;
        }
    }

    public function eliminarCategoria($id_categoria)
    {
        $conexion = new BD('P');
        $this->conex = $conexion->getConexion();
        
        if (!$this->conex) {
            return ['status' => 'error', 'mensaje' => 'No se pudo establecer la conexión a la base de datos'];
        }
        
        try {
            // Verificar si la categoría existe
            $categoriaInfo = $this->o_categoriaPorId($id_categoria, $conexion);
            if (!$categoriaInfo) {
                return ['status' => 'error', 'mensaje' => 'Categoría no encontrada'];
            }

            // Verificar si hay productos asociados
            $this->nombre_categoria = $categoriaInfo['nombre_categoria'];
            $tabla = $this->generarNombreTabla();
            
            // Verificar si la tabla existe
            $tableExists = $this->conex->query("SHOW TABLES LIKE '$tabla'")->rowCount() > 0;
            
            if ($tableExists) {
                // Verificar si hay productos en la tabla de la categoría
                $stmt = $this->conex->query("SELECT COUNT(*) as total FROM `$tabla`");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $productCount = (int)$result['total'];
                
                // Si hay productos, retornar error con mensaje personalizado
                if ($productCount > 0) {
                    // Obtener información de los productos asociados (solo los primeros 10)
                    $stmt = $this->conex->query("
                        SELECT p.id_producto, p.nombre_producto, p.codigo_producto 
                        FROM `$tabla` c 
                        JOIN tbl_productos p ON c.id_producto = p.id_producto 
                        LIMIT 10
                    ");
                    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $mensaje = "No se puede eliminar la categoría porque tiene $productCount " . 
                              ($productCount === 1 ? 'producto asociado' : 'productos asociados') . 
                              ". Primero debes eliminar o desvincular los productos.";
                    
                    return [
                        'status' => 'error', 
                        'mensaje' => $mensaje,
                        'productos' => $productos,
                        'total_productos' => $productCount
                    ];
                }
            }

            // Eliminar la tabla dinámica si existe
            if ($tableExists) {
                $this->conex->exec("DROP TABLE IF EXISTS `$tabla`");
            }

            // Eliminar la categoría
            $sql = "DELETE FROM tbl_categoria WHERE id_categoria = :id_categoria";
            $stmt = $this->conex->prepare($sql);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            $stmt->execute();

            return ['status' => 'success'];
            
        } catch (PDOException $e) {
            error_log('Error en eliminarCategoria: ' . $e->getMessage());
            
            // Verificar si el error es por restricción de clave foránea
            if (strpos($e->getMessage(), 'a foreign key constraint fails') !== false) {
                // Obtener la información de la categoría para mostrar un mensaje más útil
                $categoriaInfo = $this->o_categoriaPorId($id_categoria, null);
                $tabla = $this->generarNombreTabla();
                
                // Intentar obtener el conteo de productos
                try {
                    $stmt = $this->conex->query("SELECT COUNT(*) as total FROM `$tabla`");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $productCount = (int)$result['total'];
                    
                    $mensaje = "No se puede eliminar la categoría porque tiene $productCount " . 
                              ($productCount === 1 ? 'producto asociado' : 'productos asociados') . 
                              ". Primero debes eliminar o desvincular los productos.";
                    
                    // Obtener la lista de productos (solo los primeros 10)
                    $stmt = $this->conex->query("
                        SELECT p.id_producto, p.nombre_producto, p.codigo_producto 
                        FROM `$tabla` c 
                        JOIN tbl_productos p ON c.id_producto = p.id_producto 
                        LIMIT 10
                    ");
                    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    return [
                        'status' => 'error',
                        'mensaje' => $mensaje,
                        'productos' => $productos,
                        'total_productos' => $productCount
                    ];
                } catch (Exception $innerE) {
                    error_log('Error al obtener productos asociados: ' . $innerE->getMessage());
                }
            }
            
            // Para cualquier otro tipo de error, devolver un mensaje genérico
            return [
                'status' => 'error', 
                'mensaje' => 'No se puede eliminar la categoría porque tiene productos asociados.',
                'productos' => [],
                'total_productos' => 0
            ];
        } finally {
            if (isset($conexion)) { 
                $conexion->cerrar(); 
            }
            $this->conex = null;
        }
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

    private function o_categoriaPorId($id_categoria, $conexion = null)
    {
        if (empty($id_categoria) || !is_numeric($id_categoria)) {
            return null;
        }
        
        $conexionLocal = null;
        if ($conexion === null) {
            $conexion = new BD('P');
            $this->conex = $conexion->getConexion();
            $conexionLocal = $conexion;
        }
        
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
            
            // Verificar si la tabla existe
            $tableExists = $this->conex->query("SHOW TABLES LIKE '$tabla'")->rowCount() > 0;
            
            if ($tableExists) {
                $cols = $this->conex->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cols as $col) {
                    if (!in_array($col['Field'], ['id', 'id_producto'])) {
                        $tipo = 'string';
                        if (strpos($col['Type'], 'int') !== false) {
                            $tipo = 'int';
                        } elseif (strpos($col['Type'], 'float') !== false) {
                            $tipo = 'float';
                        }
                        $max = 255;
                        if (preg_match('/varchar\((\d+)\)/i', $col['Type'], $m)) {
                            $max = $m[1];
                        }
                        
                        $caracteristicas[] = [
                            'nombre' => str_replace('_', ' ', ucfirst($col['Field'])),
                            'tipo' => $tipo,
                            'max' => $max
                        ];
                    }
                }
            }
            
            $categoria['caracteristicas'] = $caracteristicas;
            return $categoria;
        } finally {
            if ($conexionLocal !== null) { 
                $conexionLocal->cerrar();
                $this->conex = null;
            }
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
