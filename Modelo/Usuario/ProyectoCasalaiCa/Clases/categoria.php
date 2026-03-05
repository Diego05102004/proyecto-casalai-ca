<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Categoria extends BD
{
    private $id_categoria;
    private $nombre_categoria;
    
    // Constantes para validaciones
    const MAX_NOMBRE_CATEGORIA = 100;
    const MIN_NOMBRE_CATEGORIA = 2;
    const MAX_NOMBRE_CARACTERISTICA = 100;
    const MIN_NOMBRE_CARACTERISTICA = 2;
    const MAX_VALOR_STRING = 1000;
    const MAX_VALOR_NUMERICO = 999999;
    const MAX_VALOR_DECIMAL = 999999.99;
    const MAX_LONGITUD_CAMPO = 255;
    const TIPOS_CARACTERISTICA_PERMITIDOS = ['int', 'float', 'string'];

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
     * Valida los datos para registrar una categoría
     */
    private function validarRegistrar($datos) {
        $errores = [];
        
        // Validar nombre de la categoría
        if (!isset($datos['nombre_categoria'])) {
            $errores['nombre_categoria'] = 'El nombre de la categoría es obligatorio';
        } else {
            $nombre_categoria = trim($datos['nombre_categoria']);
            if (empty($nombre_categoria)) {
                $errores['nombre_categoria'] = 'El nombre de la categoría no puede estar vacío';
            } elseif (mb_strlen($nombre_categoria) < self::MIN_NOMBRE_CATEGORIA || mb_strlen($nombre_categoria) > self::MAX_NOMBRE_CATEGORIA) {
                $errores['nombre_categoria'] = 'El nombre de la categoría debe tener entre ' . self::MIN_NOMBRE_CATEGORIA . ' y ' . self::MAX_NOMBRE_CATEGORIA . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_categoria)) {
                $errores['nombre_categoria'] = 'El nombre de la categoría solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        // Validar características si existen
        if (isset($datos['caracteristicas']) && is_array($datos['caracteristicas'])) {
            foreach ($datos['caracteristicas'] as $index => $caracteristica) {
                if (!is_array($caracteristica)) {
                    $errores["caracteristica_{$index}"] = 'La característica en la posición ' . $index . ' debe ser un array';
                    continue;
                }
                
                // Validar nombre de la característica
                $nombre_carac = trim($caracteristica['nombre'] ?? '');
                if (empty($nombre_carac)) {
                    $errores["caracteristica_{$index}_nombre"] = 'El nombre de la característica es obligatorio';
                } elseif (mb_strlen($nombre_carac) < self::MIN_NOMBRE_CARACTERISTICA || mb_strlen($nombre_carac) > self::MAX_NOMBRE_CARACTERISTICA) {
                    $errores["caracteristica_{$index}_nombre"] = 'El nombre de la característica debe tener entre ' . self::MIN_NOMBRE_CARACTERISTICA . ' y ' . self::MAX_NOMBRE_CARACTERISTICA . ' caracteres';
                } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_carac)) {
                    $errores["caracteristica_{$index}_nombre"] = 'El nombre de la característica solo puede contener letras, números, espacios y caracteres especiales comunes';
                }

                // Validar tipo
                $tipo = strtolower(trim($caracteristica['tipo'] ?? ''));
                if (!in_array($tipo, self::TIPOS_CARACTERISTICA_PERMITIDOS)) {
                    $errores["caracteristica_{$index}_tipo"] = 'El tipo de característica debe ser: ' . implode(', ', self::TIPOS_CARACTERISTICA_PERMITIDOS);
                }

                // Validar valor según tipo
                $valor = trim($caracteristica['valor'] ?? '');
                if (empty($valor)) {
                    $errores["caracteristica_{$index}_valor"] = 'El valor de la característica es obligatorio';
                } else {
                    switch ($tipo) {
                        case 'int':
                            if (!is_numeric($valor) || (int)$valor < 0 || (int)$valor > self::MAX_VALOR_NUMERICO) {
                                $errores["caracteristica_{$index}_valor"] = 'El valor entero debe estar entre 0 y ' . self::MAX_VALOR_NUMERICO;
                            }
                            break;
                        case 'float':
                            if (!is_numeric($valor) || (float)$valor < 0 || (float)$valor > self::MAX_VALOR_DECIMAL) {
                                $errores["caracteristica_{$index}_valor"] = 'El valor decimal debe estar entre 0 y ' . self::MAX_VALOR_DECIMAL;
                            }
                            break;
                        case 'string':
                            if (mb_strlen($valor) > self::MAX_VALOR_STRING) {
                                $errores["caracteristica_{$index}_valor"] = 'El valor de texto no debe exceder los ' . self::MAX_VALOR_STRING . ' caracteres';
                            }
                            break;
                    }
                }

                // Validar longitud máxima (string)
                if ($tipo === 'string' && isset($caracteristica['max'])) {
                    $max = (int)$caracteristica['max'];
                    if ($max <= 0 || $max > self::MAX_LONGITUD_CAMPO) {
                        $errores["caracteristica_{$index}_max"] = 'La longitud máxima debe estar entre 1 y ' . self::MAX_LONGITUD_CAMPO . ' caracteres';
                    }
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para consultar una categoría
     */
    private function validarConsultar($datos) {
        $errores = [];
        
        // Validar ID de la categoría
        if (!isset($datos['id_categoria'])) {
            $errores['id_categoria'] = 'El ID de la categoría es obligatorio';
        } elseif (!is_numeric($datos['id_categoria']) || $datos['id_categoria'] <= 0) {
            $errores['id_categoria'] = 'El ID de la categoría debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para modificar una categoría
     */
    private function validarModificar($datos) {
        $errores = [];
        
        // Validar ID de la categoría
        if (!isset($datos['id_categoria'])) {
            $errores['id_categoria'] = 'El ID de la categoría es obligatorio';
        } elseif (!is_numeric($datos['id_categoria']) || $datos['id_categoria'] <= 0) {
            $errores['id_categoria'] = 'El ID de la categoría debe ser un número positivo';
        }
        
        // Validar nombre de la categoría
        if (!isset($datos['nombre_categoria'])) {
            $errores['nombre_categoria'] = 'El nombre de la categoría es obligatorio';
        } else {
            $nombre_categoria = trim($datos['nombre_categoria']);
            if (empty($nombre_categoria)) {
                $errores['nombre_categoria'] = 'El nombre de la categoría no puede estar vacío';
            } elseif (mb_strlen($nombre_categoria) < self::MIN_NOMBRE_CATEGORIA || mb_strlen($nombre_categoria) > self::MAX_NOMBRE_CATEGORIA) {
                $errores['nombre_categoria'] = 'El nombre de la categoría debe tener entre ' . self::MIN_NOMBRE_CATEGORIA . ' y ' . self::MAX_NOMBRE_CATEGORIA . ' caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_categoria)) {
                $errores['nombre_categoria'] = 'El nombre de la categoría solo puede contener letras, números, espacios y caracteres especiales comunes';
            }
        }
        
        // Validar características si existen
        if (isset($datos['caracteristicas']) && is_array($datos['caracteristicas'])) {
            foreach ($datos['caracteristicas'] as $index => $caracteristica) {
                if (!is_array($caracteristica)) {
                    $errores["caracteristica_{$index}"] = 'La característica en la posición ' . $index . ' debe ser un array';
                    continue;
                }
                
                // Validar nombre de la característica
                $nombre_carac = trim($caracteristica['nombre'] ?? '');
                if (!empty($nombre_carac)) {
                    if (mb_strlen($nombre_carac) < self::MIN_NOMBRE_CARACTERISTICA || mb_strlen($nombre_carac) > self::MAX_NOMBRE_CARACTERISTICA) {
                        $errores["caracteristica_{$index}_nombre"] = 'El nombre de la característica debe tener entre ' . self::MIN_NOMBRE_CARACTERISTICA . ' y ' . self::MAX_NOMBRE_CARACTERISTICA . ' caracteres';
                    } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\.\&\']+$/', $nombre_carac)) {
                        $errores["caracteristica_{$index}_nombre"] = 'El nombre de la característica solo puede contener letras, números, espacios y caracteres especiales comunes';
                    }

                    // Validar tipo
                    $tipo = strtolower(trim($caracteristica['tipo'] ?? ''));
                    if ($tipo && !in_array($tipo, self::TIPOS_CARACTERISTICA_PERMITIDOS)) {
                        $errores["caracteristica_{$index}_tipo"] = 'El tipo de característica debe ser: ' . implode(', ', self::TIPOS_CARACTERISTICA_PERMITIDOS);
                    }

                    // Validar valor según tipo
                    $valor = trim($caracteristica['valor'] ?? '');
                    if (!empty($valor) && $tipo) {
                        switch ($tipo) {
                            case 'int':
                                if (!is_numeric($valor) || (int)$valor < 0 || (int)$valor > self::MAX_VALOR_NUMERICO) {
                                    $errores["caracteristica_{$index}_valor"] = 'El valor entero debe estar entre 0 y ' . self::MAX_VALOR_NUMERICO;
                                }
                                break;
                            case 'float':
                                if (!is_numeric($valor) || (float)$valor < 0 || (float)$valor > self::MAX_VALOR_DECIMAL) {
                                    $errores["caracteristica_{$index}_valor"] = 'El valor decimal debe estar entre 0 y ' . self::MAX_VALOR_DECIMAL;
                                }
                                break;
                            case 'string':
                                if (mb_strlen($valor) > self::MAX_VALOR_STRING) {
                                    $errores["caracteristica_{$index}_valor"] = 'El valor de texto no debe exceder los ' . self::MAX_VALOR_STRING . ' caracteres';
                                }
                                break;
                        }
                    }

                    // Validar longitud máxima (string)
                    if ($tipo === 'string' && isset($caracteristica['max'])) {
                        $max = (int)$caracteristica['max'];
                        if ($max <= 0 || $max > self::MAX_LONGITUD_CAMPO) {
                            $errores["caracteristica_{$index}_max"] = 'La longitud máxima debe estar entre 1 y ' . self::MAX_LONGITUD_CAMPO . ' caracteres';
                        }
                    }
                }
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para eliminar una categoría
     */
    private function validarEliminar($datos) {
        $errores = [];
        
        // Validar ID de la categoría
        if (!isset($datos['id_categoria'])) {
            $errores['id_categoria'] = 'El ID de la categoría es obligatorio';
        } elseif (!is_numeric($datos['id_categoria']) || $datos['id_categoria'] <= 0) {
            $errores['id_categoria'] = 'El ID de la categoría debe ser un número positivo';
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para registrar (método público)
     */
    public function validarRegistrarCategoria($datos) {
        return $this->validarRegistrar($datos);
    }
    
    /**
     * Valida los datos para consultar (método público)
     */
    public function validarConsultarCategoria($datos) {
        return $this->validarConsultar($datos);
    }
    
    /**
     * Valida los datos para modificar (método público)
     */
    public function validarModificarCategoria($datos) {
        return $this->validarModificar($datos);
    }
    
    /**
     * Valida los datos para eliminar (método público)
     */
    public function validarEliminarCategoria($datos) {
        return $this->validarEliminar($datos);
    }
    
    /**
     * Verifica si una categoría existe por ID
     */
    private function verificarCategoriaExistente($idCategoria) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT COUNT(*) FROM tbl_categoria WHERE id_categoria = :id_categoria";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id_categoria', $idCategoria, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        });
    }
    
    /**
     * Verifica si una categoría tiene productos asociados
     */
    private function verificarProductosAsociados($idCategoria) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $categoriaInfo = $this->o_categoriaPorId($idCategoria, $conexion);
            if (!$categoriaInfo) {
                return false;
            }
            
            $this->nombre_categoria = $categoriaInfo['nombre_categoria'];
            $tabla = $this->generarNombreTabla();
            
            // Verificar si la tabla existe
            $tableExists = $pdo->query("SHOW TABLES LIKE '$tabla'")->rowCount() > 0;
            
            if ($tableExists) {
                // Verificar si hay productos en la tabla de la categoría
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$tabla`");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return (int)$result['total'] > 0;
            }
            
            return false;
        });
    }
    
    private function existeNombreCategoria($nombre, $excluirId = null)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($nombre, $excluirId) {
            $sql = "SELECT COUNT(*) FROM tbl_categoria WHERE nombre_categoria = :nombre";
            $params = [':nombre' => $nombre];
            
            if ($excluirId !== null) {
                $sql .= " AND id_categoria != :id";
                $params[':id'] = $excluirId;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            
            return $count > 0;
        });
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
        return $this->ejecutarConConexionSegura(function($pdo) use ($caracteristicas) {
            $resultado = $this->r_Categoria();
            if ($resultado) {
                return $this->crearTablaCategoria($caracteristicas);
            }
            return false;
        });
    }

    private function r_Categoria()
    {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_categoria (nombre_categoria) VALUES (:nombre_categoria)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
            return $stmt->execute();
        });
    }

    private function crearTablaCategoria($caracteristicas)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($caracteristicas) {
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
            return $pdo->exec($sql) !== false;
        });
    }

    public function modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas)
    {
        return $this->m_modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas);
    }

    private function m_modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria, $nuevo_nombre, $caracteristicas) {
            // 1️⃣ Obtener el nombre actual de la categoría usando la conexión actual
            $categoriaInfo = $this->o_categoriaPorId($id_categoria, $pdo);
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
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':nombre', $nuevo_nombre);
            $stmt->bindValue(':id', $id_categoria, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new PDOException("Error al actualizar el nombre de la categoría");
            }

            // 4️⃣ Renombrar tabla si cambió el nombre
            if ($tablaAntigua !== $tablaNueva) {
                $pdo->exec("RENAME TABLE `$tablaAntigua` TO `$tablaNueva`");
            }

            // 5️⃣ Obtener columnas actuales
            $stmt = $pdo->query("SHOW COLUMNS FROM `$tablaNueva`");
            if ($stmt === false) {
                throw new PDOException("Error al obtener las columnas de la tabla: " . implode(" ", $pdo->errorInfo()));
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
                    $pdo->exec("ALTER TABLE `$tablaNueva` DROP COLUMN `$colExistente`");
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
                        $pdo->exec("ALTER TABLE `$tablaNueva` ADD `$campo` $tipoSQL");
                    } else {
                        // Si existe, verificar tipo actual
                        $stmt = $pdo->query("SHOW COLUMNS FROM `$tablaNueva` LIKE '$campo'");
                        if ($stmt) {
                            $tipoActual = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($tipoActual && stripos($tipoActual['Type'], strtolower($tipoSQL)) === false) {
                                $pdo->exec("ALTER TABLE `$tablaNueva` MODIFY `$campo` $tipoSQL");
                            }
                        }
                    }
                }
            }

            return true;
        });
    }

    private function m_categoria($id_categoria)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria) {
            $sql = "UPDATE tbl_categoria SET nombre_categoria = :nombre_categoria WHERE id_categoria = :id_categoria";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre_categoria', $this->nombre_categoria);
            $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            
            return $stmt->execute();
        });
    }

    public function eliminarCategoria($id_categoria){
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria) {
            try {
                $categoriaInfo = $this->o_categoriaPorId($id_categoria, $conexion);
                if (!$categoriaInfo) {
                    return ['status' => 'error', 'mensaje' => 'Categoría no encontrada'];
                }

                $this->nombre_categoria = $categoriaInfo['nombre_categoria'];
                $tabla = $this->generarNombreTabla();
                
                $tableExists = $pdo->query("SHOW TABLES LIKE '$tabla'")->rowCount() > 0;
                
                if ($tableExists) {
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$tabla`");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $productCount = (int)$result['total'];
                    
                    if ($productCount > 0) {
                        $stmt = $pdo->query("
                            SELECT p.id_producto, p.nombre_producto, p.codigo_producto 
                            FROM `$tabla` c 
                            JOIN tbl_productos p ON c.id_producto = p.id_producto 
                            LIMIT 10
                        ");
                        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        return [
                            'status' => 'error', 
                            'mensaje' => "No se puede eliminar porque tiene $productCount productos asociados.",
                            'productos' => $productos,
                            'total_productos' => $productCount
                        ];
                    }

                    $pdo->exec("DROP TABLE IF EXISTS `$tabla`");
                }

                $sql = "DELETE FROM tbl_categoria WHERE id_categoria = :id_categoria";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':id_categoria' => $id_categoria]);

                return ['status' => 'success'];

            } catch (\PDOException $e) {
                error_log('Error en eliminarCategoria: ' . $e->getMessage());
                
                if (strpos($e->getMessage(), '1451') !== false || strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                    return [
                        'status' => 'error',
                        'mensaje' => 'No se puede eliminar la categoría: existen dependencias activas en la base de datos.',
                        'debug' => $e->getCode()
                    ];
                }

                return [
                    'status' => 'error',
                    'mensaje' => 'Ocurrió un error inesperado al intentar eliminar la categoría.'
                ];
            }
        });
    }

    public function obtenerUltimoCategoria()
    {
        return $this->o_ultimoCategoria();
    }

    private function o_ultimoCategoria()
    {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT * FROM tbl_categoria ORDER BY id_categoria DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerCategoriaPorId($id_categoria)
    {
        return $this->o_categoriaPorId($id_categoria);
    }

    private function o_categoriaPorId($id_categoria, $conexion = null)
    {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_categoria, $conexion){
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
        });
    }

    public function consultarCategorias()
    {
        return $this->c_consultarCategorias();
    }

    private function c_consultarCategorias()
    {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_categoria, nombre_categoria FROM tbl_categoria ORDER BY id_categoria DESC";
            $stmt = $this->conex->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }
}
?>
