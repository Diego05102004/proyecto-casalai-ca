<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/test_config.php';
require_once __DIR__ . '/../../../Modelo/categoria.php';

/**
 * Pruebas de integración para el módulo de Categorías
 */
class CategoriaTest extends TestCase
{
    private $categoria;
    private $db;
    private $pdo;
    private static $testDbInitialized = false;

    /**
     * Configuración inicial para todas las pruebas
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        // Ejecutar el script de configuración de la base de datos de prueba
        if (!self::$testDbInitialized) {
            $setupScript = __DIR__ . '/setup_test_db.php';
            if (file_exists($setupScript)) {
                include $setupScript;
            }
            self::$testDbInitialized = true;
        }
    }

    protected function setUp(): void
    {
        // Crear una nueva conexión a la base de datos para pruebas
        $this->db = new BD('P');
        $this->pdo = $this->db->getConexion();
        $this->categoria = new Categoria();
        
        // Asegurarse de que estamos usando la base de datos de prueba
        $this->pdo->exec('USE casalai_test');
        
        // Iniciar una transacción para cada prueba
        $this->pdo->beginTransaction();
        
        // Crear tablas necesarias si no existen
        $this->crearTablasNecesarias();
    }

    protected function tearDown(): void
    {
        // Revertir transacción para limpiar después de cada prueba
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        
        // Cerrar la conexión a la base de datos
        if ($this->db) {
            $this->db->cerrar();
        }
    }
    
    /**
     * Crea las tablas necesarias para las pruebas
     */
    private function crearTablasNecesarias()
    {
        try {
            // Tabla de productos (necesaria para pruebas de categorías con productos)
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `tbl_productos` (
                    `id_producto` int(11) NOT NULL AUTO_INCREMENT,
                    `nombre_producto` varchar(100) NOT NULL,
                    `id_categoria` int(11) DEFAULT NULL,
                    `estado` tinyint(1) DEFAULT 1,
                    PRIMARY KEY (`id_producto`),
                    KEY `id_categoria` (`id_categoria`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            
            // Tabla de categorías (por si no existe)
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `tbl_categoria` (
                    `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
                    `nombre_categoria` varchar(100) NOT NULL,
                    `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
                    `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id_categoria`),
                    UNIQUE KEY `nombre_categoria` (`nombre_categoria`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            
        } catch (PDOException $e) {
            $this->fail('Error al crear tablas de prueba: ' . $e->getMessage());
        }
    }

    /**
     * Datos de prueba para las características de las categorías
     */
    private function getCaracteristicasBasicas()
    {
        return [
            ['nombre' => 'Color', 'tipo' => 'string', 'max' => 50],
            ['nombre' => 'Tamanio', 'tipo' => 'string', 'max' => 20]
        ];
    }
    
    /**
     * Crea una categoría de prueba y devuelve su ID
     */
    private function crearCategoriaDePrueba($nombre = null)
    {
        if ($nombre === null) {
            $nombre = 'CategoriaTest_' . uniqid();
        }
        
        $caracteristicas = $this->getCaracteristicasBasicas();
        $this->categoria->setNombreCategoria($nombre);
        $resultado = $this->categoria->registrarCategoria($caracteristicas);
        
        if (!$resultado) {
            $this->fail('No se pudo crear la categoría de prueba: ' . $this->categoria->getMensajeError());
        }
        
        // Obtener el ID de la categoría recién creada
        $stmt = $this->pdo->query("SELECT LAST_INSERT_ID() as id");
        $id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
        
        if (!$id) {
            $this->fail('No se pudo obtener el ID de la categoría de prueba');
        }
        
        return $id;
    }

    /**
     * PRUEBAS PARA CREAR (CREATE)
     */

    /**
     * Test para verificar la creación exitosa de una categoría
     */
    public function testCrearCategoriaExitosa()
    {
        // Preparar datos de prueba con un nombre único
        $nombreCategoria = 'TestCategoria_' . uniqid();
        $caracteristicas = $this->getCaracteristicasBasicas();
        
        // Verificar que la categoría no existe previamente
        $stmt = $this->pdo->prepare("SELECT id_categoria FROM tbl_categoria WHERE nombre_categoria = ?");
        $stmt->execute([$nombreCategoria]);
        $this->assertFalse($stmt->fetch(), 'La categoría de prueba ya existe en la base de datos');
        
        // Configurar y crear la categoría
        $this->categoria->setNombreCategoria($nombreCategoria);
        $resultado = $this->categoria->registrarCategoria($caracteristicas);
        
        // Verificar que la creación fue exitosa
        $this->assertTrue($resultado, 'No se pudo crear la categoría: ' . $this->categoria->getMensajeError());
        
        // Verificar que la categoría existe en la base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM tbl_categoria WHERE nombre_categoria = ?");
        $stmt->execute([$nombreCategoria]);
        $categoriaCreada = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotFalse($categoriaCreada, 'La categoría no se encuentra en la base de datos');
        $this->assertEquals($nombreCategoria, $categoriaCreada['nombre_categoria']);
        
        // Verificar que se creó la tabla dinámica
        $nombreTabla = 'cat_' . strtolower(str_replace(' ', '_', $nombreCategoria));
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = ?
        ");
        $stmt->execute([$nombreTabla]);
        $tablaExiste = $stmt->fetchColumn() > 0;
        
        $this->assertTrue($tablaExiste, "No se creó la tabla dinámica '$nombreTabla' para la categoría");
    }
    
    /**
     * Test para verificar que no se puede crear una categoría con nombre vacío
     */
    public function testNoCrearCategoriaConNombreVacio()
    {
        // Configurar la categoría con nombre vacío
        $this->categoria->setNombreCategoria('');
        
        // Intentar registrar la categoría
        $resultado = $this->categoria->registrarCategoria($this->getCaracteristicasBasicas());
        
        // Verificar que el registro falló
        $this->assertFalse($resultado, 'Se permitió crear una categoría con nombre vacío');
        
        // Verificar que se devolvió un mensaje de error
        $mensajeError = $this->categoria->getMensajeError();
        $this->assertNotEmpty($mensajeError, 'No se devolvió ningún mensaje de error');
        $this->assertStringContainsString('nombre', strtolower($mensajeError), 
            'El mensaje de error no menciona el nombre de la categoría');
    }
    
    /**
     * Test para verificar que no se puede crear una categoría sin características
     */
    public function testNoCrearCategoriaSinCaracteristicas()
    {
        // Configurar la categoría con un nombre válido pero sin características
        $this->categoria->setNombreCategoria('CategoriaSinCaracteristicas_' . uniqid());
        
        // Intentar registrar la categoría sin características
        $resultado = $this->categoria->registrarCategoria([]);
        
        // Verificar que el registro falló
        $this->assertFalse($resultado, 'Se permitió crear una categoría sin características');
        
        // Verificar que se devolvió un mensaje de error
        $mensajeError = $this->categoria->getMensajeError();
        $this->assertNotEmpty($mensajeError, 'No se devolvió ningún mensaje de error');
        
        // Verificar que el mensaje de error es el esperado
        $this->assertStringContainsString('característica', strtolower($mensajeError), 
            'El mensaje de error no menciona las características de la categoría');
    }
    
    /**
     * Test para obtener una categoría por su ID
     */
    public function testObtenerCategoriaPorId()
    {
        // Crear una categoría de prueba
        $nombreCategoria = 'Ropa_' . uniqid();
        $idCategoria = $this->crearCategoriaDePrueba($nombreCategoria);
        
        // Obtener la categoría por su ID
        $categoriaObtenida = $this->categoria->obtenerCategoriaPorId($idCategoria);
        
        // Verificar que se obtuvo la categoría correcta
        $this->assertNotFalse($categoriaObtenida, 'No se pudo obtener la categoría por ID');
        $this->assertEquals($nombreCategoria, $categoriaObtenida['nombre_categoria']);
        $this->assertEquals($idCategoria, $categoriaObtenida['id_categoria']);
    }
    
    /**
     * Test para listar todas las categorías
     */
    public function testListarTodasLasCategorias()
    {
        // Crear algunas categorías de prueba
        $nombresCategorias = [
            'Electrodomésticos_' . uniqid(),
            'Ropa_' . uniqid(),
            'Tecnología_' . uniqid()
        ];
        
        foreach ($nombresCategorias as $nombre) {
            $this->crearCategoriaDePrueba($nombre);
        }
        
        // Obtener todas las categorías
        $categorias = $this->categoria->consultarCategorias();
        
        // Verificar que se devolvieron las categorías
        $this->assertIsArray($categorias, 'No se devolvió un array de categorías');
        $this->assertGreaterThanOrEqual(count($nombresCategorias), count($categorias), 
            'No se devolvieron todas las categorías esperadas');
        
        // Verificar que las categorías creadas están en la lista
        $nombresEncontrados = array_column($categorias, 'nombre_categoria');
        foreach ($nombresCategorias as $nombre) {
            $this->assertContains($nombre, $nombresEncontrados, 
                "La categoría $nombre no se encontró en la lista de categorías");
        }
    }
    
    /**
     * Test para verificar que no se pueden registrar categorías duplicadas
     */
    public function testNoRegistrarCategoriaDuplicada()
    {
        // Crear un nombre de categoría único para la prueba
        $nombreCategoria = 'CategoriaTest' . uniqid();
        
        // Primera categoría (debería crearse correctamente)
        $caracteristicas = [['nombre' => 'Color', 'tipo' => 'string']];
        $this->categoria->setNombreCategoria($nombreCategoria);
        $resultado1 = $this->categoria->registrarCategoria($caracteristicas);
        $this->assertTrue($resultado1, 'No se pudo crear la primera categoría: ' . ($this->categoria->getMensajeError() ?? 'Sin mensaje de error'));
        
        // Segunda categoría con el mismo nombre (debería fallar)
        $categoria2 = new Categoria(); // Nueva instancia para limpiar el estado
        $categoria2->setNombreCategoria($nombreCategoria);
        $resultado2 = $categoria2->registrarCategoria($caracteristicas);
        
        $this->assertFalse($resultado2, 'Se permitió crear una categoría duplicada');
        
        // Verificar que el mensaje de error indique que la categoría ya existe (sin importar mayúsculas/minúsculas)
        $errorMessage = strtolower($categoria2->getMensajeError() ?? '');
        $this->assertStringContainsString('ya existe', $errorMessage, 'El mensaje de error no indica que la categoría ya existe');
    }

    /**
     * PRUEBAS PARA ACTUALIZAR (UPDATE)
     */
    
    /**
     * Test para actualizar el nombre de una categoría
     */
    public function testActualizarNombreCategoria()
    {
        // Creamos una categoría de prueba
        $nombreOriginal = 'Muebles';
        $idCategoria = $this->crearCategoriaDePrueba($nombreOriginal);
        
        // Actualizamos el nombre de la categoría
        $nuevoNombre = 'Muebles de Oficina';
        $caracteristicas = $this->getCaracteristicasBasicas();
        $resultado = $this->categoria->modificarCategoria($idCategoria, $nuevoNombre, $caracteristicas);
        
        // Verificamos que la actualización fue exitosa
        $this->assertTrue($resultado, 'No se pudo actualizar la categoría');
        
        // Verificamos que el nombre se actualizó correctamente
        $categoriaActualizada = $this->categoria->obtenerCategoriaPorId($idCategoria);
        $this->assertEquals($nuevoNombre, $categoriaActualizada['nombre_categoria']);
    }
    
    /**
     * Test para actualizar las características de una categoría
     */
    public function testActualizarCaracteristicasCategoria()
    {
        // Creamos una categoría de prueba
        $idCategoria = $this->crearCategoriaDePrueba('Tecnología');
        
        // Definimos las nuevas características
        $nuevasCaracteristicas = [
            ['nombre' => 'Marca', 'tipo' => 'string', 'max' => 50],
            ['nombre' => 'Modelo', 'tipo' => 'string', 'max' => 50],
            ['nombre' => 'Año', 'tipo' => 'int']
        ];
        
        // Actualizamos las características
        $categoriaActual = $this->categoria->obtenerCategoriaPorId($idCategoria);
        $resultado = $this->categoria->modificarCategoria(
            $idCategoria, 
            $categoriaActual['nombre_categoria'], 
            $nuevasCaracteristicas
        );
        
        // Verificamos que la actualización fue exitosa
        $this->assertTrue($resultado, 'No se pudieron actualizar las características');
        
        // Verificamos que las características se actualizaron correctamente
        $categoriaActualizada = $this->categoria->obtenerCategoriaPorId($idCategoria);
        $this->assertCount(3, $categoriaActualizada['caracteristicas'], 'No se actualizaron las características');
    }
    /**
     * Crea una categoría de prueba y devuelve su ID
     * 
     * @param string $nombre Nombre de la categoría
     * @return int ID de la categoría crea
    /**
     * Test para verificar que no se pueden registrar categorías duplicadas
     */
    public function testNoRegistrarCategoriaDuplicada()
    {
        // Crear un nombre de categoría único para la prueba
        $nombreCategoria = 'CategoriaTest' . uniqid();
        
        // Primera categoría (debería crearse correctamente)
        $caracteristicas = [['nombre' => 'Color', 'tipo' => 'string']];
        $this->categoria->setNombreCategoria($nombreCategoria);
        $resultado1 = $this->categoria->registrarCategoria($caracteristicas);
        $this->assertTrue($resultado1, 'No se pudo crear la primera categoría: ' . ($this->categoria->getMensajeError() ?? 'Sin mensaje de error'));
        
        // Segunda categoría con el mismo nombre (debería fallar)
        $categoria2 = new Categoria(); // Nueva instancia para limpiar el estado
        $categoria2->setNombreCategoria($nombreCategoria);
        $resultado2 = $categoria2->registrarCategoria($caracteristicas);
        
        $this->assertFalse($resultado2, 'Se permitió crear una categoría duplicada');
        
        // Verificar que el mensaje de error indique que la categoría ya existe (sin importar mayúsculas/minúsculas)
        $errorMessage = strtolower($categoria2->getMensajeError() ?? '');
        $this->assertStringContainsString('ya existe', $errorMessage, 'El mensaje de error no indica que la categoría ya existe');
    }

    // El método testNoCrearCategoriaConNombreVacio() ya está definido anteriormente

    // El método testNoCrearCategoriaSinCaracteristicas() ya está definido anteriormente
}
