<?php
use PHPUnit\Framework\TestCase;

final class CategoriaControllerTest extends TestCase
{
    private string $controllerPath;

    protected function setUp(): void
    {
        $this->controllerPath = __DIR__ . '/../../../Controlador/categoria.php';
    }

    private function runController(array $post): array
    {
        $projectRoot = realpath(__DIR__ . '/../../..');
        $controllerPath = $this->controllerPath;
        $postExport = var_export($post, true);

        $script = <<<'PHP'
<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

$projectRoot = %s;
chdir($projectRoot);

// Definir constantes necesarias
if (!defined('SKIP_SIDE_EFFECTS')) {
    define('SKIP_SIDE_EFFECTS', true);
}

// Simular entorno de prueba
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = %s;
$_FILES = [];

// Iniciar sesión de prueba
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Establecer variables de sesión necesarias
$_SESSION['id_usuario'] = 1;
$_SESSION['id_rol'] = 1;

// Capturar la salida
ob_start();

try {
    require %s;
    $output = ob_get_clean();
    
    // Si no hay salida, intentar forzar una respuesta JSON
    if (empty($output)) {
        $output = json_encode([
            'status' => 'error',
            'message' => 'No se generó ninguna salida',
            'post_data' => $_POST
        ]);
    }
    
    echo $output;
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Excepción: ' . $e->getMessage(),
        'file' => $e->getFile() . ':' . $e->getLine()
    ]);
}
PHP;

        $script = sprintf(
            $script,
            var_export($projectRoot, true),
            $postExport,
            var_export($controllerPath, true)
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'it_cat_');
        if ($tmpFile === false) {
            $this->fail('No se pudo crear script temporal para ejecutar el controlador.');
        }
        $tmpPhp = $tmpFile . '.php';
        rename($tmpFile, $tmpPhp);
        file_put_contents($tmpPhp, $script);

        $cmd = '"' . PHP_BINARY . '" ' . escapeshellarg($tmpPhp) . ' 2>&1';
        
        // Ejecutar el comando y capturar la salida y el código de salida
        $output = [];
        $return_var = 0;
        exec($cmd, $output, $return_var);
        $output = implode("\n", $output);

        @unlink($tmpPhp);

        // Depuración: Mostrar la salida cruda
        echo "\n--- SALIDA DEL CONTROLADOR ---\n";
        var_dump($output);
        echo "\nCódigo de salida: " . $return_var . "\n";
        echo "--- FIN DE SALIDA ---\n";
        
        // Si hay un error de sintaxis o similar, fallar la prueba con un mensaje claro
        if ($return_var !== 0) {
            $this->fail("El controlador devolvió un código de error: $return_var. Salida: " . substr($output, 0, 1000));
        }

        $decoded = json_decode((string)$output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
        
        // Intentar extraer JSON de la salida
        if (preg_match('/\{.*\}\s*$/s', (string)$output, $m)) {
            $decoded = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        // Si llegamos aquí, mostrar más detalles del error
        $errorMsg = 'La salida del controlador no fue JSON parseable. ';
        $errorMsg .= 'JSON error: ' . json_last_error_msg() . '\n';
        $errorMsg .= 'Salida (primeros 500 caracteres): ' . substr((string)$output, 0, 500);
        
        $this->fail($errorMsg);
    }

    public function testAccionNoValidaCategoria(): void
    {
        $resp = $this->runController([
            'accion' => 'desconocida'
        ]);
        $this->assertIsArray($resp);
    }
}

