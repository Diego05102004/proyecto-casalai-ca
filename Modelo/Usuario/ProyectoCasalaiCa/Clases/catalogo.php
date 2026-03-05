<?php
namespace Usuario\ProyectoCasalaiCa\Modelo\Clases;
use Usuario\ProyectoCasalaiCa\Config\BD;
use PDO;
use PDOException;

class Catalogo extends BD {
    private $tablaCombo = 'tbl_combo';
    private $cantidad;
    private $id_producto;
    
    // Constantes para validaciones
    const MAX_NOMBRE_COMBO = 100;
    const MAX_DESCRIPCION = 500;
    const MAX_CANTIDAD_PRODUCTO = 999;
    const MAX_PRECIO = 999999.99;
    const ESTADOS_PERMITIDOS = ['activo', 'inactivo', 'pendiente'];
    const ACCIONES_PERMITIDAS = ['consultar', 'agregar', 'crear', 'modificar', 'eliminar', 'cambiar_estado', 'filtrar', 'buscar'];

    public function setIdProducto($id_producto){
        $this->id_producto = $id_producto;
    }

    public function setCantidad($cantidad){
        $this->cantidad = $cantidad;
    }

    public function __construct($tipo = 'P') {
        parent::__construct($tipo);
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
        $conexion = new BD('P');
        
        try {
            $conexion->getConexion()->beginTransaction();
            
            $resultado = $operation($conexion->getConexion());
            
            $conexion->getConexion()->commit();
            
            return $resultado;
        } catch (Exception $e) {
            if (isset($conexion) && $conexion->getConexion()->inTransaction()) {
                $conexion->getConexion()->rollback();
            }
            throw new \RuntimeException("Error en operación de base de datos: " . $e->getMessage());
        } finally {
            if (isset($conexion)) {
                $conexion->cerrar();
            }
        }
    }

    public function insertarCombo(){
        return $this->i_insertarCombo();
    }
    private function i_insertarCombo(){
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO {$this->tablaCombo} (id_producto, cantidad)
                    VALUES (:id_producto, :cantidad)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_producto', $this->id_producto);
            $stmt->bindParam(':cantidad', $this->cantidad);
            return $stmt->execute();
        });
    }

    public function obtenerProductos() {
        return $this->o_obtenerProductos();
    }
    private function o_obtenerProductos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT p.id_producto, p.nombre_producto, m.nombre_modelo, c.nombre_caracteristicas AS categoria, p.stock, p.precio
                    FROM productos p
                    INNER JOIN modelo m ON p.id_modelo = m.id_modelo
                    INNER JOIN categoria c ON p.id_categoria = c.id_categoria
                    WHERE p.estado = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function obtenerCombos() {
        return $this->o_obtenerCombos();
    }
    private function o_obtenerCombos() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT c.id_combo, GROUP_CONCAT(p.nombre_producto SEPARATOR ', ') AS productos,
                    SUM(p.precio * c.cantidad) AS precio_total
                    FROM tbl_combo c
                    INNER JOIN productos p ON c.id_producto = p.id_producto
                    GROUP BY c.id_combo
                    ORDER BY c.id_combo DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }

    public function eliminarCombo($id_combo){
        return $this->d_eliminarCombo($id_combo);
    }
    private function d_eliminarCombo($id_combo){
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo) {
            $sql = "DELETE FROM {$this->tablaCombo} WHERE id_combo = :id_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            return $stmt->execute();
        });
    }

    public function obtenerUltimoIdCombo(){
        return $this->o_ultimoIdCombo();
    }
    private function o_ultimoIdCombo(){
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT MAX(id_combo) AS ultimo_id FROM {$this->tablaCombo}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['ultimo_id'];
        });
    }

    public function crearNuevoCombo() {
        return $this->c_crearNuevoCombo();
    }
    private function c_crearNuevoCombo() {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "INSERT INTO tbl_combo (fecha_creacion) VALUES (NOW())";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute()) {
                return $pdo->lastInsertId();
            }
            return false;
        });
    }

    public function insertarProductoEnCombo($id_combo, $id_producto, $cantidad) {
        return $this->i_insertarProductoEnCombo($id_combo, $id_producto, $cantidad);
    }
    private function i_insertarProductoEnCombo($id_combo, $id_producto, $cantidad) {
        return $this->ejecutarConConexionSegura(function($pdo) use ($id_combo, $id_producto, $cantidad) {
            $sql = "INSERT INTO {$this->tablaCombo} (id_combo, id_producto, cantidad) VALUES (:id_combo, :id_producto, :cantidad)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_combo', $id_combo);
            $stmt->bindParam(':id_producto', $id_producto);
            $stmt->bindParam(':cantidad', $cantidad);
            return $stmt->execute();
        });
    }

    // ==================== VALIDACIONES DE BACKEND ====================
    
    /**
     * Valida los datos para consultar productos o combos
     */
    private function validarConsultar($datos) {
        $errores = [];
        
        // Validar ID de marca (opcional)
        if (isset($datos['id_marca'])) {
            if (!is_numeric($datos['id_marca']) || $datos['id_marca'] <= 0) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número positivo';
            }
        }
        
        // Validar término de búsqueda (opcional)
        if (isset($datos['termino'])) {
            $termino = trim($datos['termino']);
            if (strlen($termino) > 100) {
                $errores['termino'] = 'El término de búsqueda no debe exceder los 100 caracteres';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para agregar al carrito
     */
    private function validarDatosAgregarCarrito($datos) {
        $errores = [];
        
        // Validar ID del producto
        if (!isset($datos['id_producto']) || !is_numeric($datos['id_producto']) || $datos['id_producto'] <= 0) {
            $errores['id_producto'] = 'El ID del producto debe ser un número positivo';
        }
        
        // Validar cantidad
        if (!isset($datos['cantidad'])) {
            $errores['cantidad'] = 'La cantidad es obligatoria';
        } else {
            $cantidad = (int)$datos['cantidad'];
            if ($cantidad <= 0) {
                $errores['cantidad'] = 'La cantidad debe ser un número positivo';
            } elseif ($cantidad > self::MAX_CANTIDAD_PRODUCTO) {
                $errores['cantidad'] = 'La cantidad no debe exceder los ' . self::MAX_CANTIDAD_PRODUCTO . ' productos';
            }
        }
        
        // Validar ID del combo (opcional)
        if (isset($datos['id_combo'])) {
            if (!is_numeric($datos['id_combo']) || $datos['id_combo'] <= 0) {
                $errores['id_combo'] = 'El ID del combo debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para detallar producto
     */
    private function validarDetallarProducto($datos) {
        $errores = [];
        
        // Validar ID del producto
        if (!isset($datos['id_producto']) || !is_numeric($datos['id_producto']) || $datos['id_producto'] <= 0) {
            $errores['id_producto'] = 'El ID del producto debe ser un número positivo';
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para filtrar por marca
     */
    private function validarFiltrarMarca($datos) {
        $errores = [];
        
        // Validar ID de marca (opcional)
        if (isset($datos['id_marca'])) {
            if (!is_numeric($datos['id_marca']) || $datos['id_marca'] <= 0) {
                $errores['id_marca'] = 'El ID de la marca debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    /**
     * Valida los datos para buscador
     */
    private function validarBuscador($datos) {
        $errores = [];
        
        // Validar término de búsqueda
        if (!isset($datos['termino'])) {
            $errores['termino'] = 'El término de búsqueda es obligatorio';
        } else {
            $termino = trim($datos['termino']);
            if (empty($termino)) {
                $errores['termino'] = 'El término de búsqueda no puede estar vacío';
            } elseif (strlen($termino) > 100) {
                $errores['termino'] = 'El término de búsqueda no debe exceder los 100 caracteres';
            }
        }
        
        // Validar tipo de búsqueda (opcional)
        if (isset($datos['tipo'])) {
            $tipos_permitidos = ['producto', 'combo', 'todos'];
            if (!in_array($datos['tipo'], $tipos_permitidos)) {
                $errores['tipo'] = 'El tipo de búsqueda debe ser uno de: ' . implode(', ', $tipos_permitidos);
            }
        }
        
        return $errores;
    }
    
    // ==================== MÉTODOS PÚBLICOS DE VALIDACIÓN ====================
    
    /**
     * Valida los datos para consultar (método público)
     */
    public function validarConsultarCatalogo($datos) {
        return $this->validarConsultar($datos);
    }
    
    /**
     * Valida los datos para agregar al carrito (método público)
     */
    public function validarAgregarCarrito($datos) {
        return $this->validarDatosAgregarCarrito($datos);
    }
    
    /**
     * Valida los datos para detallar producto (método público)
     */
    public function validarDetallar($datos) {
        return $this->validarDetallarProducto($datos);
    }
    
    /**
     * Valida los datos para filtrar por marca (método público)
     */
    public function validarFiltrar($datos) {
        return $this->validarFiltrarMarca($datos);
    }
    
    /**
     * Valida los datos para buscador (método público)
     */
    public function validarBuscar($datos) {
        return $this->validarBuscador($datos);
    }
    
    /**
     * Verifica si un producto existe y está activo
     */
    private function verificarProductoExistente($idProducto) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_producto, nombre_producto, stock, estado FROM productos WHERE id_producto = :id_producto";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id_producto', $idProducto, PDO::PARAM_INT);
            $stmt->execute();
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$producto) {
                return ['existe' => false, 'mensaje' => 'El producto no existe'];
            }
            
            if ($producto['stock'] <= 0) {
                return ['existe' => false, 'mensaje' => 'El producto no tiene stock disponible'];
            }
            
            if ($producto['estado'] != 1) {
                return ['existe' => false, 'mensaje' => 'El producto no está disponible'];
            }
            
            return ['existe' => true, 'producto' => $producto];
        });
    }
    
    /**
     * Verifica si un combo existe y está activo
     */
    private function verificarComboExistente($idCombo) {
        return $this->ejecutarConConexionSegura(function($pdo) {
            $sql = "SELECT id_combo, nombre_combo, activo FROM combos WHERE id_combo = :id_combo";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id_combo', $idCombo, PDO::PARAM_INT);
            $stmt->execute();
            $combo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$combo) {
                return ['existe' => false, 'mensaje' => 'El combo no existe'];
            }
            
            if ($combo['activo'] != 1) {
                return ['existe' => false, 'mensaje' => 'El combo no está activo'];
            }
            
            return ['existe' => true, 'combo' => $combo];
        });
    }
}
?>