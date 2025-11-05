<?php
use PHPUnit\Framework\TestCase;

final class ProductoControllerTest extends TestCase
{
    private $pdo;
    private string $controllerPath;
    private $testProductoId;
    private $testCategoriaId;
    private $testModeloId;
    private $testMarcaId;

    protected function setUp(): void
    {
        $this->controllerPath = __DIR__ . '/../../../Controlador/producto.php';
        $this->pdo = $this->createTestDatabaseConnection();
        $this->setUpTestData();
        
        // Iniciar sesión de prueba
        $_SESSION = [
            'id_usuario' => 1,
            'name' => 'Usuario de Prueba',
            'id_rol' => 1,
            'nombre_rol' => 'Administrador'
        ];
    }

    protected function tearDown(): void
    {
        try {
            if ($this->testProductoId) {
                $this->pdo->exec("DELETE FROM tbl_productos WHERE id_producto = {$this->testProductoId}");
            }
            if ($this->testCategoriaId) {
                $this->pdo->exec("DELETE FROM tbl_categorias WHERE id_categoria = {$this->testCategoriaId}");
            }
            if ($this->testModeloId) {
                $this->pdo->exec("DELETE FROM tbl_modelos WHERE id_modelo = {$this->testModeloId}");
            }
            if ($this->testMarcaId) {
                $this->pdo->exec("DELETE FROM tbl_marcas WHERE id_marca = {$this->testMarcaId}");
            }
        } catch (\Exception $e) {
            error_log('Error en tearDown: ' . $e->getMessage());
        }
    }

    private function createTestDatabaseConnection()
    {
        $host = 'localhost';
        $dbname = 'seguridadlai';
        $username = 'root';
        $password = '';
        
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new \PDOException(
                'Error de conexión a la base de datos: ' . $e->getMessage(),
                (int)$e->getCode()
            );
        }
    }

    private function setUpTestData(): void
    {
        try {
            // Crear categoría de prueba
            $this->pdo->exec("INSERT INTO tbl_categorias (nombre_categoria, descripcion) 
                            VALUES ('Categoría de Prueba', 'Descripción de prueba')");
            $this->testCategoriaId = $this->pdo->lastInsertId();

            // Crear marca de prueba
            $this->pdo->exec("INSERT INTO tbl_marcas (nombre_marca, descripcion) 
                            VALUES ('Marca de Prueba', 'Marca para pruebas')");
            $this->testMarcaId = $this->pdo->lastInsertId();

            // Crear modelo de prueba
            $this->pdo->exec("INSERT INTO tbl_modelos (nombre_modelo, id_marca, descripcion) 
                            VALUES ('Modelo de Prueba', {$this->testMarcaId}, 'Modelo para pruebas')");
            $this->testModeloId = $this->pdo->lastInsertId();
            
        } catch (\PDOException $e) {
            throw new \RuntimeException('Error al configurar datos de prueba: ' . $e->getMessage());
        }
    }

    private function runController(array $post, array $files = []): array
    {
        $projectRoot = realpath(__DIR__ . '/../../..');
        $controllerPath = $this->controllerPath;
        
        // Serializar los datos POST y FILES
        $postExport = var_export($post, true);
        $filesExport = var_export($files, true);

        $script = <<<'PHP'
<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');
session_start();

$_SESSION = [
    'id_usuario' => 1,
    'name' => 'Usuario de Prueba',
    'id_rol' => 1,
    'nombre_rol' => 'Administrador'
];

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = %s;
$_FILES = %s;

define('SKIP_SIDE_EFFECTS', true);

require %s;
PHP;

        $script = sprintf(
            $script,
            $postExport,
            $filesExport,
            var_export($controllerPath, true)
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        if ($tmpFile === false) {
            $this->fail('No se pudo crear archivo temporal');
        }
        
        $tmpPhp = $tmpFile . '.php';
        file_put_contents($tmpPhp, $script);

        $cmd = '"' . PHP_BINARY . '" ' . escapeshellarg($tmpPhp);
        $output = shell_exec($cmd);

        @unlink($tmpPhp);

        $decoded = json_decode((string)$output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        if (preg_match('/\{.*\}/s', (string)$output, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        $this->fail('No se pudo decodificar la respuesta: ' . substr((string)$output, 0, 200));
        return [];
    }

    public function testListarProductos(): void
    {
        $resp = $this->runController(['accion' => 'listar']);
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('data', $resp);
    }

    public function testRegistrarProductoValido(): void
    {
        $productoData = [
            'accion' => 'ingresar',
            'nombre_producto' => 'Producto de Prueba ' . uniqid(),
            'descripcion_producto' => 'Descripción del producto de prueba',
            'modelo' => $this->testModeloId,
            'Stock_Actual' => 10,
            'Stock_Maximo' => 100,
            'Stock_Minimo' => 5,
            'Clausula_garantia' => 'Garantía de 1 año',
            'Seriales' => 'SER-' . uniqid(),
            'Categoria' => $this->testCategoriaId,
            'Precio' => 99.99
        ];

        $resp = $this->runController($productoData);
        $this->assertEquals('success', $resp['status']);
        $this->assertArrayHasKey('id_producto', $resp);
        $this->testProductoId = $resp['id_producto'];
    }

    public function testValidarNombreProductoUnico(): void
    {
        $nombreUnico = 'Producto Único ' . uniqid();
        
        // Verificar que el nombre está disponible
        $resp = $this->runController([
            'accion' => 'validar_nombre',
            'nombre_producto' => $nombreUnico
        ]);
        
        $this->assertEquals('success', $resp['status']);
        $this->assertTrue($resp['disponible']);
    }

    public function testObtenerProducto(): void
    {
        // Primero creamos un producto para obtenerlo
        $productoData = [
            'accion' => 'ingresar',
            'nombre_producto' => 'Producto para Obtener ' . uniqid(),
            'descripcion_producto' => 'Producto para prueba de obtención',
            'modelo' => $this->testModeloId,
            'Stock_Actual' => 15,
            'Stock_Maximo' => 100,
            'Stock_Minimo' => 5,
            'Clausula_garantia' => 'Garantía de 1 año',
            'Seriales' => 'SER-GET-' . uniqid(),
            'Categoria' => $this->testCategoriaId,
            'Precio' => 79.99
        ];

        $resp = $this->runController($productoData);
        $productoId = $resp['id_producto'];

        // Ahora lo obtenemos
        $resp = $this->runController([
            'accion' => 'obtener_producto',
            'id_producto' => $productoId
        ]);
        
        $this->assertEquals('success', $resp['status']);
        $this->assertEquals($productoData['nombre_producto'], $resp['data']['nombre_producto']);
    }

    public function testAccionNoValida(): void
    {
        $resp = $this->runController(['accion' => 'accion_inexistente']);
        $this->assertEquals('error', $resp['status']);
        $this->assertStringContainsString('no válida', $resp['message'] ?? '');
    }
}
