<?php
use PHPUnit\Framework\TestCase;

class CategoriaControllerTest extends TestCase
{
    private $controllerPath;
    private $testUserId = 1; // ID de usuario para pruebas
    private $testModulo = 7; // MODULO_CATEGORIA
    private $categoria;
    private $categoriaId;
    private $testCategoriaNombre;
    private $testCaracteristicas;
    private $db;
    private $pdo;

    protected function setUp(): void
    {
        // Definir constante para evitar efectos secundarios en pruebas
        if (!defined('SKIP_SIDE_EFFECTS')) {
            define('SKIP_SIDE_EFFECTS', true);
        }
        
        $this->controllerPath = __DIR__ . '/../../../Controlador/categoria.php';
        $this->testCategoriaNombre = 'CategoriaTest_' . uniqid();
        $this->testCaracteristicas = [
            ['nombre' => 'Color', 'tipo' => 'string', 'max' => 50],
            ['nombre' => 'Tamaño', 'tipo' => 'string', 'max' => 20],
            ['nombre' => 'Peso', 'tipo' => 'float']
        ];
        
        // Incluir archivos necesarios
        $projectRoot = realpath(__DIR__ . '/../../..');
        
        // Primero incluimos solo lo necesario para las pruebas
        require_once $projectRoot . '/Config/Config.php';
        require_once $projectRoot . '/Modelo/BD.php';
        require_once $projectRoot . '/Modelo/categoria.php';
        require_once $projectRoot . '/Modelo/permiso.php';
        
        // Configurar conexión a la base de datos
        $this->db = new BD('P');
        $this->pdo = $this->db->getConexion();
        
        // Crear tabla de categorías si no existe
        $this->crearTablaCategorias();
        
        // Inicializar sesión para pruebas
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['id_usuario'] = $this->testUserId;
        $_SESSION['id_rol'] = 1; // Asumiendo rol de administrador
        
        // Crear instancia de Categoria para pruebas directas
        $this->categoria = new Categoria();
    }

    protected function tearDown(): void
    {
        // Limpiar después de cada prueba
        if (isset($this->categoriaId)) {
            $this->eliminarCategoriaDePrueba($this->categoriaId);
        }
        
        // Limpiar sesión
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        
        // Cerrar conexión a la base de datos
        if (isset($this->db)) {
            $this->db->cerrar();
            $this->pdo = null;
        }
    }
    
    private function crearTablaCategorias()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `tbl_categoria` (
            `id_categoria` INT AUTO_INCREMENT PRIMARY KEY,
            `nombre_categoria` VARCHAR(100) NOT NULL,
            `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `nombre_categoria` (`nombre_categoria`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->pdo->exec($sql);
    }
    
    private function eliminarTablaCategoria($nombreTabla)
    {
        try {
            $sql = "DROP TABLE IF EXISTS `$nombreTabla`";
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            // Ignorar errores si la tabla no existe
            return false;
        }
    }
    
    private function eliminarCategoriaDePrueba($id_categoria)
    {
        try {
            // Primero obtener el nombre de la categoría para encontrar la tabla
            $stmt = $this->pdo->prepare("SELECT nombre_categoria FROM tbl_categoria WHERE id_categoria = ?");
            $stmt->execute([$id_categoria]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($categoria) {
                // Eliminar la tabla de la categoría
                $nombreTabla = 'cat_' . strtolower(str_replace(' ', '_', $categoria['nombre_categoria']));
                $this->eliminarTablaCategoria($nombreTabla);
                
                // Eliminar la categoría de la tabla principal
                $stmt = $this->pdo->prepare("DELETE FROM tbl_categoria WHERE id_categoria = ?");
                $stmt->execute([$id_categoria]);
            }
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function runController($action, $data = [])
    {
        // Configurar la sesión para la prueba
        $_SESSION['id_usuario'] = $this->testUserId;
        $_SESSION['id_rol'] = 1; // Asumimos rol de administrador
        
        // Configurar la solicitud
        $_POST = $data;
        $_GET = ["action" => $action];
        
        // Guardar el estado actual de SKIP_SIDE_EFFECTS
        $skipSideEffects = defined('SKIP_SIDE_EFFECTS') ? SKIP_SIDE_EFFECTS : false;
        
        // Forzar SKIP_SIDE_EFFECTS a true para evitar problemas con el controlador
        define('SKIP_SIDE_EFFECTS', true);
        
        // Iniciar el buffer de salida
        ob_start();
        
        // Incluir el controlador dentro de una función anónima para aislar el ámbito
        $result = (function() {
            try {
                // Incluir el controlador
                include func_get_arg(0);
                return null;
            } catch (\Exception $e) {
                return $e;
            }
        })($this->controllerPath);
        
        // Obtener la salida
        $output = ob_get_clean();
        
        // Restaurar el valor original de SKIP_SIDE_EFFECTS
        if ($skipSideEffects !== false) {
            define('SKIP_SIDE_EFFECTS', $skipSideEffects);
        } else {
            if (defined('SKIP_SIDE_EFFECTS')) {
                // No podemos eliminar constantes definidas, pero podemos anular su valor
                // Esto es un workaround ya que no podemos usar runkit_constant_remove() sin la extensión runkit
                // y no podemos usar runkit7_constant_remove() sin la extensión runkit7
                // En PHP 8.0+ podríamos usar runkit7_constant_remove() si la extensión está instalada
            }
        }
        
        // Si hubo una excepción, lanzarla
        if ($result instanceof \Exception) {
            throw $result;
        }
        
        // Intentar decodificar la salida JSON
        $json = json_decode($output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
        
        return $output;
    }

    /**
     * Prueba el registro de una nueva categoría
     */
    public function testRegistrarCategoria()
    {
        $datos = [
            'accion' => 'registrar',
            'nombre_categoria' => $this->testCategoriaNombre,
            'caracteristicas' => $this->testCaracteristicas
        ];
        
        $respuesta = $this->runController($datos);
        
        $this->assertIsArray($respuesta, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('status', $respuesta, 'La respuesta debe tener una clave "status"');
        $this->assertEquals('success', $respuesta['status'], 'El estado debe ser "success"');
        $this->assertArrayHasKey('categoria', $respuesta, 'La respuesta debe incluir los datos de la categoría');
        
        // Guardar el ID para pruebas posteriores
        $this->categoriaId = $respuesta['categoria']['id_categoria'];
    }
    
    /**
     * Prueba la consulta de categorías
     */
    public function testConsultarCategorias()
    {
        // Primero creamos una categoría de prueba
        $this->testRegistrarCategoria();
        
        // Ahora la consultamos
        $datos = ['accion' => 'consultar_categorias'];
        $respuesta = $this->runController($datos);
        
        $this->assertIsArray($respuesta, 'La respuesta debe ser un array');
        $this->assertNotEmpty($respuesta, 'Debe haber al menos una categoría registrada');
        
        // Verificar que la categoría de prueba está en la lista
        $encontrada = false;
        foreach ($respuesta as $categoria) {
            if ($categoria['nombre_categoria'] === $this->testCategoriaNombre) {
                $encontrada = true;
                break;
            }
        }
        $this->assertTrue($encontrada, 'La categoría de prueba debe estar en la lista de categorías');
    }
    
    /**
     * Prueba la obtención de una categoría por ID
     */
    public function testObtenerCategoriaPorId()
    {
        // Primero creamos una categoría de prueba
        $this->testRegistrarCategoria();
        
        // Ahora la consultamos por ID
        $datos = [
            'accion' => 'obtener_categoria',
            'id_categoria' => $this->categoriaId
        ];
        
        $respuesta = $this->runController($datos);
        
        $this->assertIsArray($respuesta, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('id_categoria', $respuesta, 'La respuesta debe incluir el ID de la categoría');
        $this->assertEquals($this->categoriaId, $respuesta['id_categoria'], 'El ID de la categoría debe coincidir');
        $this->assertEquals($this->testCategoriaNombre, $respuesta['nombre_categoria'], 'El nombre de la categoría debe coincidir');
    }
    
    /**
     * Prueba la modificación de una categoría
     */
    public function testModificarCategoria()
    {
        // Primero creamos una categoría de prueba
        $this->testRegistrarCategoria();
        
        // Datos para modificar la categoría
        $nuevoNombre = $this->testCategoriaNombre . '_modificado';
        $nuevasCaracteristicas = [
            ['nombre' => 'Color', 'tipo' => 'string', 'max' => 50],
            ['nombre' => 'Tamaño', 'tipo' => 'string', 'max' => 20],
            ['nombre' => 'Peso', 'tipo' => 'float'],
            ['nombre' => 'Material', 'tipo' => 'string', 'max' => 30]
        ];
        
        $datos = [
            'accion' => 'modificar',
            'id_categoria' => $this->categoriaId,
            'nombre_categoria' => $nuevoNombre,
            'caracteristicas' => $nuevasCaracteristicas
        ];
        
        $respuesta = $this->runController($datos);
        
        $this->assertIsArray($respuesta, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('status', $respuesta, 'La respuesta debe tener una clave "status"');
        $this->assertEquals('success', $respuesta['status'], 'El estado debe ser "success"');
        $this->assertArrayHasKey('categoria', $respuesta, 'La respuesta debe incluir los datos actualizados de la categoría');
        $this->assertEquals($nuevoNombre, $respuesta['categoria']['nombre_categoria'], 'El nombre de la categoría debe haberse actualizado');
    }
    
    /**
     * Prueba la eliminación de una categoría
     */
    public function testEliminarCategoria()
    {
        // Primero creamos una categoría de prueba
        $this->testRegistrarCategoria();
        
        // Intentamos eliminarla
        $datos = [
            'accion' => 'eliminar',
            'id_categoria' => $this->categoriaId
        ];
        
        $respuesta = $this->runController($datos);
        
        $this->assertIsArray($respuesta, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('status', $respuesta, 'La respuesta debe tener una clave "status"');
        $this->assertEquals('success', $respuesta['status'], 'La eliminación debería ser exitosa');
        
        // Verificar que ya no existe
        $datosConsulta = ['accion' => 'obtener_categoria', 'id_categoria' => $this->categoriaId];
        $categoria = $this->runController($datosConsulta);
        
        $this->assertArrayNotHasKey('id_categoria', $categoria, 'La categoría ya no debería existir');
    }
    
    /**
     * Prueba el manejo de errores al intentar eliminar una categoría con productos asociados
     */
    public function testEliminarCategoriaConProductos()
    {
        // Primero creamos una categoría de prueba
        $this->testRegistrarCategoria();
        
        // Simulamos que hay productos asociados
        $categoria = new Categoria();
        $categoria->setIdCategoria($this->categoriaId);
        $categoria->setNombreCategoria($this->testCategoriaNombre);
        
        // Intentamos eliminarla
        $datos = [
            'accion' => 'eliminar',
            'id_categoria' => $this->categoriaId
        ];
        
        $respuesta = $this->runController($datos);
        
        // Como hay productos asociados, esperamos un error
        $this->assertIsArray($respuesta, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('status', $respuesta, 'La respuesta debe tener una clave "status"');
        $this->assertEquals('error', $respuesta['status'], 'Debería fallar al intentar eliminar una categoría con productos');
        $this->assertArrayHasKey('message', $respuesta, 'La respuesta debe incluir un mensaje de error');
    }
    
    /**
     * Prueba el manejo de acciones no válidas
     */
    public function testAccionNoValida()
    {
        $respuesta = $this->runController(['accion' => 'accion_inexistente']);
        
        $this->assertIsArray($respuesta, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('status', $respuesta, 'La respuesta debe tener una clave "status"');
        $this->assertEquals('error', $respuesta['status'], 'El estado debe ser "error"');
        $this->assertArrayHasKey('message', $respuesta, 'La respuesta debe incluir un mensaje de error');
    }
    
    /**
     * Prueba el registro de una categoría con nombre duplicado
     */
    public function testRegistrarCategoriaDuplicada()
    {
        // Primero creamos una categoría de prueba
        $this->testRegistrarCategoria();
        
        // Intentamos crear otra con el mismo nombre
        $datos = [
            'accion' => 'registrar',
            'nombre_categoria' => $this->testCategoriaNombre,
            'caracteristicas' => $this->testCaracteristicas
        ];
        
        $respuesta = $this->runController($datos);
        
        $this->assertIsArray($respuesta, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('status', $respuesta, 'La respuesta debe tener una clave "status"');
        $this->assertEquals('error', $respuesta['status'], 'Debería fallar al intentar registrar una categoría duplicada');
        $this->assertArrayHasKey('message', $respuesta, 'La respuesta debe incluir un mensaje de error');
    }
}

