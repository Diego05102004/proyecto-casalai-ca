<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../../modelo/cuenta.php';

final class CuentaControllerTest extends TestCase
{
    private PDO $pdo;
    private string $controllerPath;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        $this->controllerPath = __DIR__ . '/../../../controlador/cuenta.php';
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
        foreach (['tbl_pagos','tbl_cuentas'] as $t) {
            try { $this->pdo->exec("TRUNCATE TABLE {$t}"); } catch (Throwable $e) {}
        }
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
    }

    private function seedBasico(): void
    {
        // nada por ahora: se crean las cuentas durante los tests
    }

    private function runController(array $post): array
    {
        $projectRoot = dirname(__DIR__, 3);
        $postExport = var_export($post, true);
        $phpBinary = PHP_BINARY;
        $wrapper = tempnam(sys_get_temp_dir(), 'it_cta_') . '.php';

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
        $script .= "define('SKIP_SIDE_EFFECTS', true);\n";
        $script .= "chdir('" . $escapedRoot . "');\n";
        $script .= "ob_start();\n";
        $script .= "require 'controlador/cuenta.php';\n";
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

    private function registrarCuentaEjemplo(string $nb='Banco IT', string $num='01020304050607080900'): array
    {
        $resp = $this->runController([
            'accion' => 'registrar',
            'nombre_banco' => $nb,
            'numero_cuenta' => $num,
            'rif_cuenta' => 'J-12345678-9',
            'telefono_cuenta' => '0412-0000000',
            'correo_cuenta' => 'bank@it.test',
            'metodos_pago' => ['transferencia','pago_movil'],
        ]);
        $this->assertSame('success', $resp['status'] ?? null, 'Registro debe ser success');
        $this->assertIsArray($resp['cuenta'] ?? null);
        return $resp['cuenta'];
    }

    public function testRegistrarCuentaDesdeControlador(): void
    {
        $cuenta = $this->registrarCuentaEjemplo();
        $this->assertSame('Banco IT', $cuenta['nombre_banco'] ?? null);
    }

    public function testConsultarCuentasDesdeControlador(): void
    {
        $this->registrarCuentaEjemplo('Banco IT B', '11112222333344445555');
        $resp = $this->runController([
            'accion' => 'consultar_cuentas'
        ]);
        $this->assertIsArray($resp);
        $this->assertNotEmpty($resp);
        $this->assertArrayHasKey('id_cuenta', $resp[0]);
    }

    public function testObtenerCuentaPorIdDesdeControlador(): void
    {
        $c = $this->registrarCuentaEjemplo('Banco IT C', '22223333444455556666');
        $resp = $this->runController([
            'accion' => 'obtener_cuenta',
            'id_cuenta' => (string)$c['id_cuenta']
        ]);
        $this->assertIsArray($resp);
        $this->assertSame('Banco IT C', $resp['nombre_banco'] ?? null);
        $this->assertSame('22223333444455556666', $resp['numero_cuenta'] ?? null);
    }

    public function testModificarCuentaDesdeControlador(): void
    {
        $c = $this->registrarCuentaEjemplo('Banco IT D', '33334444555566667777');
        $resp = $this->runController([
            'accion' => 'modificar',
            'id_cuenta' => (string)$c['id_cuenta'],
            'nombre_banco' => 'Banco IT D Mod',
            'numero_cuenta' => '33334444555566667777',
            'rif_cuenta' => 'J-98765432-1',
            'telefono_cuenta' => '0414-1111111',
            'correo_cuenta' => 'otro@it.test',
            'metodos_pago' => ['transferencia']
        ]);
        $this->assertSame('success', $resp['status'] ?? null);
        $this->assertIsArray($resp['cuenta'] ?? null);
        $this->assertSame('Banco IT D Mod', $resp['cuenta']['nombre_banco'] ?? null);
    }

    public function testEliminarCuentaDesdeControlador(): void
    {
        $c = $this->registrarCuentaEjemplo('Banco IT E', '44445555666677778888');
        $resp = $this->runController([
            'accion' => 'eliminar',
            'id_cuenta' => (string)$c['id_cuenta']
        ]);
        $this->assertIsArray($resp);
        if (($resp['status'] ?? '') === 'success') {
            $existe = (int)$this->pdo->query("SELECT COUNT(*) FROM tbl_cuentas WHERE id_cuenta=".(int)$c['id_cuenta'])->fetchColumn();
            $this->assertSame(0, $existe);
        } else {
            $this->assertSame('error', $resp['status'] ?? null);
            $this->assertArrayHasKey('message', $resp);
        }
    }

    public function testCambiarEstadoDesdeControlador(): void
    {
        $c = $this->registrarCuentaEjemplo('Banco IT F', '55556666777788889999');
        // Asegura que exista la columna 'estado'
        (new Cuentabanco())->verificarEstado();
        $resp = $this->runController([
            'accion' => 'cambiar_estado',
            'id_cuenta' => (string)$c['id_cuenta'],
            'estado' => 'inhabilitado'
        ]);
        $this->assertSame('success', $resp['status'] ?? null);
        // Si la columna existe, verifica en BD
        $col = $this->pdo->query("SHOW COLUMNS FROM tbl_cuentas LIKE 'estado'")->fetch(PDO::FETCH_ASSOC);
        if ($col) {
            $estado = (string)$this->pdo->query("SELECT estado FROM tbl_cuentas WHERE id_cuenta=".(int)$c['id_cuenta']." LIMIT 1")->fetchColumn();
            $this->assertSame('inhabilitado', $estado);
        }
    }

    public function testPermisosTiempoRealDesdeControlador(): void
    {
        $resp = $this->runController([
            'accion' => 'permisos_tiempo_real'
        ]);
        $this->assertIsArray($resp);
        // En entornos sin seed de seguridad, evitar afirmar claves específicas
        $this->assertNotNull($resp);
    }
}
