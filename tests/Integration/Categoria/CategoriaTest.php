<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../../Modelo/categoria.php';
require_once __DIR__ . '/../../../Config/Config.php';

class CategoriaIntegrationTest extends TestCase
{
    private $categoria;
    private $db;

    protected function setUp(): void
    {
        // Create a test database connection
        $this->db = new BD('P');
        $this->categoria = new Categoria();
        
        // Start transaction
        $pdo = $this->db->getConexion();
        $pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to clean up after each test
        $pdo = $this->db->getConexion();
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $this->db->cerrar();
    }

    /**
     * Test para registrar una nueva categoría
     */
    public function testRegistrarCategoria()
    {
        $caracteristicas = [
            ['nombre' => 'Color', 'tipo' => 'string', 'max' => 50],
            ['nombre' => 'Tamaño', 'tipo' => 'string', 'max' => 20],
            ['nombre' => 'Peso', 'tipo' => 'float']
        ];

        $this->categoria->setNombreCategoria('Electrónicos');
        $result = $this->categoria->registrarCategoria($caracteristicas);
        
        $this->assertTrue($result, 'No se pudo registrar la categoría');
        
        // Verificar que la tabla dinámica se creó
        $pdo = $this->db->getConexion();
        $tableName = 'cat_electronicos';
        $tableExists = $pdo->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
        $this->assertTrue($tableExists, 'No se creó la tabla dinámica');
    }

    /**
     * Test para verificar que no se pueden registrar categorías duplicadas
     */
    public function testNoRegistrarCategoriaDuplicada()
    {
        // Primera categoría
        $caracteristicas = [['nombre' => 'Color', 'tipo' => 'string']];
        $this->categoria->setNombreCategoria('Electrodomésticos');
        $result1 = $this->categoria->registrarCategoria($caracteristicas);
        $this->assertTrue($result1, 'No se pudo registrar la primera categoría');

        // Segunda categoría con el mismo nombre
        $this->categoria = new Categoria();
        $this->categoria->setNombreCategoria('Electrodomésticos');
        $result2 = $this->categoria->registrarCategoria($caracteristicas);
        
        // Debería fallar porque ya existe una categoría con ese nombre
        $this->assertFalse($result2, 'Se registró una categoría duplicada');
    }

    /**
     * Test para modificar una categoría existente
     */
    public function testModificarCategoria()
    {
        // Primero creamos una categoría
        $caracteristicas = [
            ['nombre' => 'Color', 'tipo' => 'string']
        ];
        $this->categoria->setNombreCategoria('Ropa');
        $this->categoria->registrarCategoria($caracteristicas);
        
        // Obtenemos el ID de la categoría recién creada
        $categoria = $this->categoria->obtenerUltimoCategoria();
        $idCategoria = $categoria['id_categoria'];
        
        // Modificamos la categoría
        $nuevasCaracteristicas = [
            ['nombre' => 'Talla', 'tipo' => 'string'],
            ['nombre' => 'Material', 'tipo' => 'string']
        ];
        
        $result = $this->categoria->modificarCategoria(
            $idCategoria, 
            'Ropa Actualizada', 
            $nuevasCaracteristicas
        );
        
        $this->assertTrue($result, 'No se pudo modificar la categoría');
        
        // Verificamos que el nombre se actualizó
        $categoriaActualizada = $this->categoria->obtenerCategoriaPorId($idCategoria);
        $this->assertEquals('Ropa Actualizada', $categoriaActualizada['nombre_categoria']);
        
        // Verificamos que las características se actualizaron
        $this->assertCount(2, $categoriaActualizada['caracteristicas']);
    }

    /**
     * Test para eliminar una categoría
     */
    public function testEliminarCategoria()
    {
        // Primero creamos una categoría
        $caracteristicas = [['nombre' => 'Color', 'tipo' => 'string']];
        $this->categoria->setNombreCategoria('ParaEliminar');
        $this->categoria->registrarCategoria($caracteristicas);
        
        // Obtenemos el ID de la categoría recién creada
        $categoria = $this->categoria->obtenerUltimoCategoria();
        $idCategoria = $categoria['id_categoria'];
        
        // Eliminamos la categoría
        $result = $this->categoria->eliminarCategoria($idCategoria);
        
        // Verificamos que se eliminó correctamente
        $this->assertEquals('success', $result['status']);
        
        // Verificamos que la categoría ya no existe
        $categoriaEliminada = $this->categoria->obtenerCategoriaPorId($idCategoria);
        $this->assertNull($categoriaEliminada);
    }

    /**
     * Test para verificar que no se puede eliminar una categoría con productos asociados
     */
    public function testNoEliminarCategoriaConProductos()
    {
        // Primero creamos una categoría
        $caracteristicas = [['nombre' => 'Color', 'tipo' => 'string']];
        $this->categoria->setNombreCategoria('ConProductos');
        $this->categoria->registrarCategoria($caracteristicas);
        
        // Obtenemos el ID de la categoría recién creada
        $categoria = $this->categoria->obtenerUltimoCategoria();
        $idCategoria = $categoria['id_categoria'];
        
        // Simulamos que hay productos asociados
        $pdo = $this->db->getConexion();
        $pdo->exec("INSERT INTO tbl_productos (nombre_producto, id_categoria) VALUES ('Producto de prueba', $idCategoria)");
        
        // Intentamos eliminar la categoría
        $result = $this->categoria->eliminarCategoria($idCategoria);
        
        // Verificamos que no se pudo eliminar
        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('No se puede eliminar la categoría', $result['mensaje']);
    }

    /**
     * Test para obtener una categoría por ID
     */
    public function testObtenerCategoriaPorId()
    {
        // Primero creamos una categoría
        $caracteristicas = [
            ['nombre' => 'Color', 'tipo' => 'string'],
            ['nombre' => 'Peso', 'tipo' => 'float']
        ];
        $this->categoria->setNombreCategoria('Hogar');
        $this->categoria->registrarCategoria($caracteristicas);
        
        // Obtenemos la categoría recién creada
        $categoria = $this->categoria->obtenerUltimoCategoria();
        $idCategoria = $categoria['id_categoria'];
        
        // Obtenemos la categoría por ID
        $categoriaObtenida = $this->categoria->obtenerCategoriaPorId($idCategoria);
        
        // Verificamos que los datos son correctos
        $this->assertEquals('Hogar', $categoriaObtenida['nombre_categoria']);
        $this->assertCount(2, $categoriaObtenida['caracteristicas']);
    }

    /**
     * Test para listar todas las categorías
     */
    public function testListarCategorias()
    {
        // Primero creamos algunas categorías de prueba
        $categoriasPrueba = ['Electrónica', 'Hogar', 'Oficina'];
        $caracteristicas = [['nombre' => 'Color', 'tipo' => 'string']];
        
        foreach ($categoriasPrueba as $nombre) {
            $this->categoria = new Categoria();
            $this->categoria->setNombreCategoria($nombre);
            $this->categoria->registrarCategoria($caracteristicas);
        }
        
        // Obtenemos la lista de categorías
        $categorias = $this->categoria->consultarCategorias();
        
        // Verificamos que se devolvieron las categorías
        $this->assertGreaterThanOrEqual(3, count($categorias));
        
        // Verificamos que las categorías están ordenadas por ID descendente
        $ids = array_column($categorias, 'id_categoria');
        $sortedIds = $ids;
        rsort($sortedIds);
        $this->assertEquals($sortedIds, $ids);
    }

    /**
     * Test para verificar que no se pueden crear categorías con nombres vacíos
     */
    public function testNoCrearCategoriaConNombreVacio()
    {
        $caracteristicas = [['nombre' => 'Color', 'tipo' => 'string']];
        $this->categoria->setNombreCategoria('');
        
        $this->expectException(PDOException::class);
        $this->categoria->registrarCategoria($caracteristicas);
    }

    /**
     * Test para verificar que no se pueden crear categorías sin características
     */
    public function testNoCrearCategoriaSinCaracteristicas()
    {
        $this->categoria->setNombreCategoria('Sin Caracteristicas');
        
        $this->expectException(PDOException::class);
        $this->categoria->registrarCategoria([]);
    }
}
