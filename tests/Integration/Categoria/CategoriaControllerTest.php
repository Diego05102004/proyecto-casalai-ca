<?php
use PHPUnit\Framework\TestCase;

// Evitar la inclusión directa del controlador para evitar conflictos
if (!defined('SKIP_SIDE_EFFECTS')) {
    define('SKIP_SIDE_EFFECTS', true);
}

require_once __DIR__ . '/../../../Modelo/categoria.php';
require_once __DIR__ . '/../../../Modelo/permiso.php';
require_once __DIR__ . '/../../../Modelo/bitacora.php';
require_once __DIR__ . '/../../../Config/Config.php';

/**
 * Pruebas de integración para el controlador de Categorías
 */
class CategoriaControllerTest extends TestCase
{
    private $categoria;
    private $db;
    private $pdo;

    protected function setUp(): void
    {
        // Iniciar sesión de prueba
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['id_usuario'] = 1;
        $_SESSION['id_rol'] = 1;

        // Crear conexión a la base de datos de pruebas
        $this->db = new BD('P');
        $this->pdo = $this->db->getConexion();
        $this->categoria = new Categoria();
        
        // Iniciar transacción
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Revertir transacción para limpiar después de cada prueba
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
        if ($this->db) {
            $this->db->cerrar();
        }
        
        // Limpiar variables de sesión
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Datos de prueba para las características de las categorías
     */
    private function getCaracteristicasBasicas()
    {
        return [
            ['nombre' => 'Color', 'tipo' => 'string', 'max' => 50],
            ['nombre' => 'Tamaño', 'tipo' => 'string', 'max' => 20]
        ];
    }

    /**
     * Crea una categoría de prueba y devuelve su ID
     */
    private function crearCategoriaDePrueba($nombre)
    {
        $caracteristicas = $this->getCaracteristicasBasicas();
        $this->categoria->setNombreCategoria($nombre);
        $resultado = $this->categoria->registrarCategoria($caracteristicas);
        
        if (!$resultado) {
            $this->fail('No se pudo crear la categoría de prueba');
        }
        
        $categoria = $this->categoria->obtenerUltimoCategoria();
        return $categoria['id_categoria'];
    }

    /**
     * Test para la acción de listar categorías
     */
    public function testListarCategorias()
    {
        // Crear una categoría de prueba
        $this->crearCategoriaDePrueba('Electrónica');
        
        // Simular petición GET
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['action' => 'listar'];
        
        // Capturar la salida
        ob_start();
        require __DIR__ . '/../../../Controlador/categoria.php';
        $output = json_decode(ob_get_clean(), true);
        
        // Verificar la respuesta
        $this->assertIsArray($output);
        $this->assertArrayHasKey('status', $output);
        $this->assertEquals('success', $output['status']);
        $this->assertArrayHasKey('data', $output);
        $this->assertIsArray($output['data']);
    }

    /**
     * Test para la acción de crear categoría
     */
    public function testCrearCategoria()
    {
        // Datos de prueba
        $datos = [
            'action' => 'crear',
            'nombre_categoria' => 'Nueva Categoría',
            'caracteristicas' => json_encode($this->getCaracteristicasBasicas())
        ];
        
        // Simular petición POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $datos;
        
        // Capturar la salida
        ob_start();
        require __DIR__ . '/../../../Controlador/categoria.php';
        $output = json_decode(ob_get_clean(), true);
        
        // Verificar la respuesta
        $this->assertIsArray($output);
        $this->assertArrayHasKey('status', $output);
        $this->assertEquals('success', $output['status']);
    }

    /**
     * Test para la acción de actualizar categoría
     */
    public function testActualizarCategoria()
    {
        // Crear una categoría de prueba
        $idCategoria = $this->crearCategoriaDePrueba('Categoría a actualizar');
        
        // Datos de prueba
        $datos = [
            'action' => 'actualizar',
            'id_categoria' => $idCategoria,
            'nombre_categoria' => 'Categoría Actualizada',
            'caracteristicas' => json_encode($this->getCaracteristicasBasicas())
        ];
        
        // Simular petición POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $datos;
        
        // Capturar la salida
        ob_start();
        require __DIR__ . '/../../../Controlador/categoria.php';
        $output = json_decode(ob_get_clean(), true);
        
        // Verificar la respuesta
        $this->assertIsArray($output);
        $this->assertArrayHasKey('status', $output);
        $this->assertEquals('success', $output['status']);
    }

    /**
     * Test para la acción de eliminar categoría
     */
    public function testEliminarCategoria()
    {
        // Crear una categoría de prueba
        $idCategoria = $this->crearCategoriaDePrueba('Categoría a eliminar');
        
        // Datos de prueba
        $datos = [
            'action' => 'eliminar',
            'id_categoria' => $idCategoria
        ];
        
        // Simular petición POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $datos;
        
        // Capturar la salida
        ob_start();
        require __DIR__ . '/../../../Controlador/categoria.php';
        $output = json_decode(ob_get_clean(), true);
        
        // Verificar la respuesta
        $this->assertIsArray($output);
        $this->assertArrayHasKey('status', $output);
        $this->assertEquals('success', $output['status']);
    }
}
