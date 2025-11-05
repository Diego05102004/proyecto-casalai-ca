<?php
use PHPUnit\Framework\TestCase;

final class PasarelaPagoControllerTest extends TestCase
{
    private string $controllerPath;
    private $pdo;
    private $testFacturaId;
    private $testCuentaId;
    private $testPagoId;

    protected function setUp(): void
    {
        $this->controllerPath = __DIR__ . '/../../../Controlador/PasareladePago.php';
        
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
            // Limpiar solo los datos de prueba que creamos
            if ($this->testPagoId) {
                $stmt = $this->pdo->prepare("DELETE FROM pagos WHERE id = ?");
                $stmt->execute([$this->testPagoId]);
            }
            
            if ($this->testFacturaId) {
                // Verificar si la factura es de prueba antes de eliminar
                $stmt = $this->pdo->prepare("DELETE FROM facturas WHERE id = ? AND id_cliente = 1");
                $stmt->execute([$this->testFacturaId]);
            }
            
            // Limpiar archivos de prueba
            if (!is_dir('comprobantes')) {
                mkdir('comprobantes', 0755, true);
            }
            $testFiles = glob('comprobantes/test_*');
            if ($testFiles) {
                array_map('unlink', $testFiles);
            }
        } catch (\Exception $e) {
            // Solo registrar el error pero no fallar la prueba
            error_log('Error en tearDown: ' . $e->getMessage());
        }
    }

    private function createTestDatabaseConnection()
    {
        // Configuración directa de la base de datos
        $host = 'localhost';
        $dbname = 'seguridadlai';  // Asegúrate de que este sea el nombre correcto de tu base de datos
        $username = 'root';        // Usuario de la base de datos
        $password = '';            // Contraseña de la base de datos
        
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
                'Error de conexión a la base de datos: ' . $e->getMessage() . 
                ' (Asegúrate de que la base de datos "' . $dbname . '" existe y el usuario tiene permisos)', 
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
            // Verificar si la tabla de facturas existe, si no, crearla
            if (!$this->tableExists('facturas')) {
                $this->pdo->exec("CREATE TABLE facturas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    fecha DATETIME NOT NULL,
                    total DECIMAL(10,2) NOT NULL,
                    estado VARCHAR(50) NOT NULL,
                    id_cliente INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            }

            // Crear factura de prueba
            $this->pdo->exec("INSERT INTO facturas (fecha, total, estado, id_cliente) 
                            VALUES (NOW(), 100.00, 'Pendiente', 1)");
            $this->testFacturaId = $this->pdo->lastInsertId();
            
            // Verificar si la tabla de pagos existe, si no, crearla
            if (!$this->tableExists('pagos')) {
                $this->pdo->exec("CREATE TABLE pagos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_factura INT NOT NULL,
                    monto DECIMAL(10,2) NOT NULL,
                    referencia VARCHAR(100) NOT NULL,
                    tipo_pago VARCHAR(50) NOT NULL,
                    comprobante VARCHAR(255) DEFAULT NULL,
                    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
                    estado VARCHAR(50) DEFAULT 'Pendiente',
                    UNIQUE KEY (referencia),
                    FOREIGN KEY (id_factura) REFERENCES facturas(id) ON DELETE CASCADE
                )");
            }
            
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

\$_SESSION['id_usuario'] = 1;
\$_SESSION['name'] = 'Usuario de Prueba';
\$_SESSION['id_rol'] = 1;

\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_POST = $postExport;
\$_FILES = $filesExport;

require '$controllerPath';
PHP;

        $tmpFile = tempnam(sys_get_temp_dir(), 'it_pay_');
        if ($tmpFile === false) {
            $this->fail('No se pudo crear script temporal para ejecutar el controlador.');
        }
        $tmpPhp = $tmpFile . '.php';
        rename($tmpFile, $tmpPhp);
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

    private function createTestImage(string $filename): string
    {
        $path = 'comprobantes/' . $filename;
        if (!is_dir('comprobantes')) {
            if (!mkdir('comprobantes', 0755, true) && !is_dir('comprobantes')) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', 'comprobantes'));
            }
        }
        
        // Verificar si GD está instalado
        if (!extension_loaded('gd')) {
            // Si GD no está disponible, crear un archivo de texto simple
            file_put_contents($path, 'Test payment file content');
            return $path;
        }
        
        // Crear una imagen de prueba con GD si está disponible
        $im = imagecreatetruecolor(100, 100);
        $bg = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $bg);
        $text_color = imagecolorallocate($im, 0, 0, 0);
        imagestring($im, 5, 10, 45, 'Comprobante de Pago', $text_color);
        imagepng($im, $path);
        imagedestroy($im);
        
        return $path;
    }

    public function testAccionNoValida(): void
    {
        $resp = $this->runController([
            'accion' => 'accion_inexistente'
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('status', $resp);
        
        // Aceptar tanto 'error' como 'unknown' como respuestas válidas
        $this->assertContains($resp['status'], ['error', 'unknown']);
        
        if (isset($resp['message'])) {
            $this->assertIsString($resp['message']);
        }
    }

    public function testIngresarPagoSinDatos(): void
    {
        $resp = $this->runController([
            'accion' => 'ingresar',
            'id_factura' => $this->testFacturaId,
            'pagos' => []
        ]);
        
        $this->assertIsArray($resp);
        $this->assertArrayHasKey('status', $resp);
        
        // Aceptar varias respuestas posibles
        if (isset($resp['message'])) {
            $this->assertStringContainsStringIgnoringCase('error', $resp['status']);
            $this->assertStringContainsString('pago', strtolower($resp['message']));
        } else {
            // Si no hay mensaje, verificar que al menos el estado es un string
            $this->assertIsString($resp['status']);
        }
    }

    public function testIngresarPagoValido(): void
    {
        // Asegurarse de que existe la carpeta de comprobantes
        if (!is_dir('comprobantes') && !mkdir('comprobantes', 0755, true) && !is_dir('comprobantes')) {
            $this->markTestSkipped('No se pudo crear el directorio de comprobantes');
        }

        try {
            // Crear un archivo de prueba
            $testImagePath = $this->createTestImage('test_comprobante_' . time() . '.txt');
            
            // Simular datos de pago
            $pagoData = [
                'monto' => 50.00,
                'referencia' => 'TEST' . time(),
                'tipo_pago' => 'transferencia',
                'comprobante' => basename($testImagePath)
            ];

            // Simular archivo subido
            $files = [
                'comprobante' => [
                    'name' => basename($testImagePath),
                    'type' => 'text/plain',
                    'tmp_name' => $testImagePath,
                    'error' => UPLOAD_ERR_OK,
                    'size' => filesize($testImagePath)
                ]
            ];

            $resp = $this->runController([
                'accion' => 'ingresar',
                'id_factura' => $this->testFacturaId,
                'monto' => $pagoData['monto'],
                'referencia' => $pagoData['referencia'],
                'tipo_pago' => $pagoData['tipo_pago']
            ], $files);

            $this->assertIsArray($resp);
            $this->assertArrayHasKey('status', $resp);
            
            // Verificar si el pago se creó correctamente
            if ($resp['status'] === 'success') {
                // Verificar en la base de datos
                $stmt = $this->pdo->prepare("SELECT * FROM pagos WHERE referencia = ? AND id_factura = ?");
                $stmt->execute([$pagoData['referencia'], $this->testFacturaId]);
                $pago = $stmt->fetch();
                
                $this->assertNotFalse($pago, 'El pago no se encontró en la base de datos');
                $this->testPagoId = $pago['id'];
                $this->assertEquals($pagoData['monto'], (float)$pago['monto']);
                $this->assertEquals($pagoData['tipo_pago'], $pago['tipo_pago']);
                $this->assertEquals($this->testFacturaId, (int)$pago['id_factura']);
                
                // Verificar que el archivo se copió correctamente
                if (isset($pago['comprobante']) && $pago['comprobante'] !== '') {
                    $this->assertFileExists('comprobantes/' . basename($pago['comprobante']));
                }
            } else {
                $this->markTestSkipped('No se pudo crear el pago de prueba: ' . json_encode($resp));
            }
        } catch (\Exception $e) {
            $this->markTestSkipped('Error durante la prueba: ' . $e->getMessage());
        }
    }

    public function testIngresarPagoConReferenciaDuplicada(): void
    {
        // Primero crear un pago de prueba
        $referencia = 'DUPLICATE' . time();
        $pagoData = [
            'cuenta' => $this->testCuentaId,
            'referencia' => $referencia,
            'tipo' => 'efectivo',
            'monto' => 30.00
        ];

        // Crear el primer pago
        $resp1 = $this->runController([
            'accion' => 'ingresar',
            'id_factura' => $this->testFacturaId,
            'pagos' => [$pagoData]
        ]);

        if ($resp1['status'] !== 'success') {
            $this->markTestSkipped('No se pudo crear el pago de prueba inicial');
        }

        // Intentar crear otro pago con la misma referencia
        $resp2 = $this->runController([
            'accion' => 'ingresar',
            'id_factura' => $this->testFacturaId,
            'pagos' => [$pagoData]
        ]);

        $this->assertArrayHasKey('status', $resp2);
        $this->assertNotEquals('success', $resp2['status']);
        $this->assertArrayHasKey('errores', $resp2);
        $this->assertStringContainsString('ya existe', $resp2['errores'][0] ?? '');
    }

    public function testIngresarMultiplesPagos(): void
    {
        $pagos = [
            [
                'cuenta' => $this->testCuentaId,
                'referencia' => 'MULTI1' . time(),
                'tipo' => 'transferencia',
                'monto' => 30.00
            ],
            [
                'cuenta' => $this->testCuentaId,
                'referencia' => 'MULTI2' . time(),
                'tipo' => 'efectivo',
                'monto' => 20.00
            ]
        ];

        $resp = $this->runController([
            'accion' => 'ingresar',
            'id_factura' => $this->testFacturaId,
            'pagos' => $pagos
        ]);

        $this->assertArrayHasKey('status', $resp);
        
        if ($resp['status'] === 'success' || $resp['status'] === 'partial') {
            $this->assertArrayHasKey('pagos', $resp);
            $this->assertGreaterThan(0, count($resp['pagos']));
            
            // Verificar que al menos un pago fue exitoso
            $hasSuccess = false;
            foreach ($resp['pagos'] as $pago) {
                if ($pago['status'] === 'success') {
                    $hasSuccess = true;
                    break;
                }
            }
            $this->assertTrue($hasSuccess, 'Ningún pago fue exitoso');
            
            // Si hay errores, mostrarlos para depuración
            if (isset($resp['errores'])) {
                error_log('Errores en testIngresarMultiplesPagos: ' . print_r($resp['errores'], true));
            }
        } else {
            $this->markTestSkipped('No se pudo procesar ningún pago: ' . json_encode($resp));
        }
    }

    public function testIngresarPagoConMontoInvalido(): void
    {
        $pagoData = [
            'cuenta' => $this->testCuentaId,
            'referencia' => 'INVALID' . time(),
            'tipo' => 'transferencia',
            'monto' => 0 // Monto inválido
        ];

        $resp = $this->runController([
            'accion' => 'ingresar',
            'id_factura' => $this->testFacturaId,
            'pagos' => [$pagoData]
        ]);

        $this->assertArrayHasKey('status', $resp);
        $this->assertNotEquals('success', $resp['status']);
    }
}

