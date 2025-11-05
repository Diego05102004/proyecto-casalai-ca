<?php
use PHPUnit\Framework\TestCase;

final class FacturaControllerTest extends TestCase
{
    private string $controllerPath;
    private $pdo;
    private $testFacturaId;
    private $testClienteId;
    private $testProductoId;

    protected function setUp(): void
    {
        $this->controllerPath = __DIR__ . '/../../../Controlador/factura.php';
        
        // Configurar base de datos de prueba
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
            // Limpiar datos de prueba
            if ($this->testFacturaId) {
                $this->pdo->exec("DELETE FROM tbl_factura_detalle WHERE factura_id = {$this->testFacturaId}");
                $this->pdo->exec("DELETE FROM tbl_facturas WHERE id_factura = {$this->testFacturaId}");
            }
            
            // Limpiar cliente de prueba
            if ($this->testClienteId) {
                $this->pdo->exec("DELETE FROM tbl_clientes WHERE id_cliente = {$this->testClienteId}");
            }
            
            // Limpiar producto de prueba
            if ($this->testProductoId) {
                $this->pdo->exec("DELETE FROM tbl_productos WHERE id_producto = {$this->testProductoId}");
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
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
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

    private function tableExists($tableName) {
        try {
            $result = $this->pdo->query("SHOW TABLES LIKE '$tableName'");
            return $result->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function setUpTestData(): void
    {
        try {
            // Crear cliente de prueba
            $this->pdo->exec("INSERT INTO tbl_clientes (nombre, direccion, telefono, email) 
                            VALUES ('Cliente Prueba', 'Dirección prueba', '12345678', 'test@example.com')");
            $this->testClienteId = $this->pdo->lastInsertId();

            // Crear producto de prueba
            $this->pdo->exec("INSERT INTO tbl_productos (nombre, descripcion, precio, stock, id_categoria) 
                            VALUES ('Producto Prueba', 'Descripción prueba', 10.50, 100, 1)");
            $this->testProductoId = $this->pdo->lastInsertId();
            
            // Crear factura de prueba
            $this->pdo->exec("INSERT INTO tbl_facturas (fecha, cliente, estatus) 
                            VALUES (NOW(), {$this->testClienteId}, 'Pendiente')");
            $this->testFacturaId = $this->pdo->lastInsertId();
            
            // Agregar detalle a la factura
            $this->pdo->exec("INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad, precio_unitario) 
                            VALUES ({$this->testFacturaId}, {$this->testProductoId}, 2, 10.50)");
            
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

        $script = <<<PHP
<?php
\error_reporting(E_ALL);
\ini_set('display_errors', '0');
\session_start();

\$_SESSION = [
    'id_usuario' => 1,
    'name' => 'Usuario de Prueba',
    'id_rol' => 1,
    'nombre_rol' => 'Administrador'
];

\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_POST = $postExport;
\$_FILES = $filesExport;

require '$controllerPath';
PHP;

        $tmpFile = tempnam(sys_get_temp_dir(), 'it_fact_');
        if ($tmpFile === false) {
            $this->fail('No se pudo crear script temporal para ejecutar el controlador.');
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
        if (preg_match('/\{.*\}\s*$/s', (string)$output, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return ['status' => 'unknown', 'raw' => (string)$output];
    }

    public function testListarProductos(): void
    {
        $resp = $this->runController([
            'accion' => 'listadoproductos'
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('data', $resp);
        $this->assertIsArray($resp['data']);
    }

    public function testRegistrarFacturaValida(): void
    {
        $resp = $this->runController([
            'accion' => 'registrar',
            'cliente' => $this->testClienteId,
            'productos' => [
                [
                    'id' => $this->testProductoId,
                    'cantidad' => 1,
                    'precio' => 10.50
                ]
            ]
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('status', $resp);
        $this->assertEquals('success', $resp['status']);
        
        // Verificar que se creó la factura en la base de datos
        $stmt = $this->pdo->query("SELECT * FROM tbl_facturas WHERE cliente = {$this->testClienteId} ORDER BY id_factura DESC LIMIT 1");
        $factura = $stmt->fetch();
        $this->assertNotFalse($factura, 'La factura no se creó en la base de datos');
    }

    public function testRegistrarFacturaSinProductos(): void
    {
        $resp = $this->runController([
            'accion' => 'registrar',
            'cliente' => $this->testClienteId,
            'productos' => []
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('status', $resp);
        $this->assertEquals('error', $resp['status']);
        $this->assertStringContainsString('productos', strtolower($resp['message'] ?? ''));
    }

    public function testRegistrarFacturaConProductoInexistente(): void
    {
        $resp = $this->runController([
            'accion' => 'registrar',
            'cliente' => $this->testClienteId,
            'productos' => [
                [
                    'id' => 999999, // ID de producto inexistente
                    'cantidad' => 1,
                    'precio' => 10.50
                ]
            ]
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('status', $resp);
        $this->assertEquals('error', $resp['status']);
    }

    public function testObtenerFacturaExistente(): void
    {
        // Primero creamos una factura de prueba
        $this->pdo->exec("INSERT INTO tbl_facturas (fecha, cliente, estatus) 
                         VALUES (NOW(), {$this->testClienteId}, 'Pendiente')");
        $facturaId = $this->pdo->lastInsertId();
        
        $resp = $this->runController([
            'accion' => 'obtener',
            'id' => $facturaId
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('status', $resp);
        $this->assertEquals('success', $resp['status']);
        $this->assertArrayHasKey('data', $resp);
        $this->assertEquals($facturaId, $resp['data']['id_factura'] ?? null);
    }

    public function testObtenerFacturaInexistente(): void
    {
        $resp = $this->runController([
            'accion' => 'obtener',
            'id' => 999999 // ID de factura inexistente
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('status', $resp);
        $this->assertEquals('error', $resp['status']);
    }

    public function testListarFacturas(): void
    {
        $resp = $this->runController([
            'accion' => 'listar'
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('data', $resp);
        $this->assertIsArray($resp['data']);
    }

    public function testAnularFactura(): void
    {
        // Primero creamos una factura de prueba
        $this->pdo->exec("INSERT INTO tbl_facturas (fecha, cliente, estatus) 
                         VALUES (NOW(), {$this->testClienteId}, 'Pendiente')");
        $facturaId = $this->pdo->lastInsertId();
        
        $resp = $this->runController([
            'accion' => 'anular',
            'id' => $facturaId
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('status', $resp);
        
        // Verificar que la factura se marcó como anulada
        $stmt = $this->pdo->query("SELECT estatus FROM tbl_facturas WHERE id_factura = $facturaId");
        $factura = $stmt->fetch();
        $this->assertEquals('Anulada', $factura['estatus'] ?? '');
    }
}
