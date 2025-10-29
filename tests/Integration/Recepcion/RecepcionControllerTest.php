<?php
use PHPUnit\Framework\TestCase;

final class RecepcionControllerTest extends TestCase
{
    private PDO $pdo;
    private string $controllerPath;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        $this->controllerPath = __DIR__ . '/../../../controlador/Recepcion.php';
        $this->limpiarTablas();
        $this->seedBasico();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['id_usuario'] = $_SESSION['id_usuario'] ?? 1;
        $_SESSION['id_rol'] = $_SESSION['id_rol'] ?? 1;
        $_SESSION['name'] = $_SESSION['name'] ?? 'Tester';
    }

    private function limpiarTablas(): void
    {
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}
        $tablas = [
            'tbl_ingresos_egresos',
            'tbl_detalle_recepcion_productos',
            'tbl_recepcion_productos',
            'tbl_productos',
            'tbl_modelos',
            'tbl_marcas',
            'tbl_proveedores',
        ];
        foreach ($tablas as $t) {
            try { $this->pdo->exec("TRUNCATE TABLE {$t}"); } catch (Throwable $e) {}
        }
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
    }

    private function seedBasico(): void
    {
        $this->pdo->prepare("INSERT INTO tbl_proveedores (nombre_proveedor) VALUES ('Proveedor IT')")->execute();
        
        $this->pdo->prepare("INSERT INTO tbl_marcas (nombre_marca) VALUES ('M-IT')")->execute();
        $id_marca = (int)$this->pdo->lastInsertId();
        $st = $this->pdo->prepare("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('MD-IT', :id_marca)");
        $st->execute([':id_marca' => $id_marca]);
        $id_modelo = (int)$this->pdo->lastInsertId();
        
        $st = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial) VALUES ('P-IT', :id_modelo, 'IT-001')");
        $st->execute([':id_modelo' => $id_modelo]);
    }

    private function getPrimerProveedorId(): int
    {
        return (int)$this->pdo->query('SELECT id_proveedor FROM tbl_proveedores LIMIT 1')->fetchColumn();
    }

    private function getPrimerProductoId(): int
    {
        return (int)$this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT 1')->fetchColumn();
    }

    private function runController(array $post): array
    {
        $projectRoot = dirname(__DIR__, 3);
        $postExport = var_export($post, true);
        $phpBinary = PHP_BINARY;
        $wrapper = tempnam(sys_get_temp_dir(), 'it_rece_') . '.php';

        $escapedRoot = addslashes($projectRoot);
        $script  = "<?php\n";
        $script .= "error_reporting(E_ALL);\n";
        $script .= "ini_set('display_errors','1');\n";
        $script .= "if (session_status() === PHP_SESSION_NONE) { session_start(); }\n";
        $script .= "if (!isset(\$_SESSION['id_usuario'])) { \$_SESSION['id_usuario'] = 1; }\n";
        $script .= "if (!isset(\$_SESSION['id_rol'])) { \$_SESSION['id_rol'] = 1; }\n";
        $script .= "if (!isset(\$_SESSION['name'])) { \$_SESSION['name'] = 'Tester'; }\n";
        $script .= "\$_SERVER['REQUEST_METHOD'] = 'POST';\n";
        $script .= "\$_POST = " . $postExport . ";\n";
        $script .= "$"."pagina = 'Recepcion';\n";
        $script .= "chdir('" . $escapedRoot . "');\n";
        $script .= "define('SKIP_SIDE_EFFECTS', true);\n";
        $script .= "ob_start();\n";
        $script .= "require 'controlador/Recepcion.php';\n";
        $script .= "$"."out = ob_get_clean();\n";
        $script .= "echo $"."out;\n";

        file_put_contents($wrapper, $script);
        
        $cmd = escapeshellarg($phpBinary) . ' ' . escapeshellarg($wrapper);
        $output = shell_exec($cmd);
        @unlink($wrapper);

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
        $this->fail('La salida del controlador no fue JSON parseable. Salida: ' . substr((string)$output, 0, 500));
    }

    public function testListadoProductosDesdeControlador(): void
    {
        $resp = $this->runController([
            'accion' => 'listado'
        ]);
        $this->assertSame('listado', $resp['resultado'] ?? null);
        $this->assertStringContainsString('<tr', $resp['mensaje'] ?? '');
    }

    public function testRegistrarRecepcionDesdeControlador(): void
    {
        $resp = $this->runController([
            'accion' => 'registrar',
            'proveedor' => (string)$this->getPrimerProveedorId(),
            'correlativo' => '555001',
            'tamanocompra' => 'Mediano',
            
            'producto' => [ (string)$this->getPrimerProductoId() ],
            'cantidad' => [ '2' ],
            'costo' => [ '25.50' ],
        ]);

        $this->assertSame('success', $resp['status'] ?? null, 'Registro debería ser success');
        $this->assertIsArray($resp['recepcion'] ?? null);
        $this->assertSame('555001', $resp['recepcion']['correlativo'] ?? null);
    }

    public function testProductosRecepcionDesdeControlador(): void
    {
        $this->runController([
            'accion' => 'registrar',
            'proveedor' => (string)$this->getPrimerProveedorId(),
            'correlativo' => '555002',
            'tamanocompra' => 'Pequeño',
            'producto' => [ (string)$this->getPrimerProductoId() ],
            'cantidad' => [ '3' ],
            'costo' => [ '10.00' ],
        ]);
        
        $id = (int)$this->pdo->query("SELECT id_recepcion FROM tbl_recepcion_productos WHERE correlativo='555002' LIMIT 1")->fetchColumn();

        $resp = $this->runController([
            'accion' => 'productos_recepcion',
            'id_recepcion' => (string)$id,
        ]);
        $this->assertIsArray($resp);
        $this->assertNotEmpty($resp);
        $this->assertArrayHasKey('codigo', $resp[0]);
    }

    public function testAnularRecepcionDesdeControlador(): void
    {
        $this->runController([
            'accion' => 'registrar',
            'proveedor' => (string)$this->getPrimerProveedorId(),
            'correlativo' => '555003',
            'tamanocompra' => 'Grande',
            'producto' => [ (string)$this->getPrimerProductoId() ],
            'cantidad' => [ '1' ],
            'costo' => [ '5.00' ],
        ]);

        $resp = $this->runController([
            'accion' => 'anular',
            'correlativo' => '555003'
        ]);
        $this->assertSame('success', $resp['status'] ?? null);

        $estado = $this->pdo->query("SELECT estado FROM tbl_recepcion_productos WHERE correlativo='555003' LIMIT 1")->fetchColumn();
        $this->assertSame('anulado', $estado);
    }
}
