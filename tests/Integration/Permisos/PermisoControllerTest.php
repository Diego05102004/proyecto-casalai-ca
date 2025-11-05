<?php
use PHPUnit\Framework\TestCase;

final class PermisoControllerTest extends TestCase
{
    private string $controllerPath;
    private $pdo;
    private $testRolId;
    private $testModuloId;

    protected function setUp(): void
    {
        $this->controllerPath = __DIR__ . '/../../../Controlador/permiso.php';
        
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
            if ($this->testRolId) {
                $this->pdo->exec("DELETE FROM tbl_permisos WHERE id_rol = {$this->testRolId}");
            }
            
            if ($this->testModuloId) {
                $this->pdo->exec("DELETE FROM tbl_permisos WHERE id_modulo = {$this->testModuloId}");
                $this->pdo->exec("DELETE FROM tbl_modulos WHERE id_modulo = {$this->testModuloId}");
            }
            
            // Limpiar rol de prueba
            if ($this->testRolId) {
                $this->pdo->exec("DELETE FROM tbl_rol WHERE id_rol = {$this->testRolId}");
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

    private function setUpTestData(): void
    {
        try {
            // Crear rol de prueba
            $this->pdo->exec("INSERT INTO tbl_rol (nombre_rol, descripcion) 
                            VALUES ('Rol de Prueba', 'Rol para pruebas de integración')");
            $this->testRolId = $this->pdo->lastInsertId();

            // Crear módulo de prueba
            $this->pdo->exec("INSERT INTO tbl_modulos (nombre_modulo, descripcion) 
                            VALUES ('modulo_prueba', 'Módulo para pruebas de integración')");
            $this->testModuloId = $this->pdo->lastInsertId();
            
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
error_reporting(E_ALL);
ini_set('display_errors', '0');
session_start();

\$_SESSION = [
    'id_usuario' => 1,
    'name' => 'Usuario de Prueba',
    'id_rol' => 1,
    'nombre_rol' => 'Administrador'
];

\$_SERVER['REQUEST_METHOD'] = 'POST';
\$_POST = $postExport;
\$_FILES = $filesExport;

// Definir constante para evitar efectos secundarios en pruebas
define('SKIP_SIDE_EFFECTS', true);

require '$controllerPath';
PHP;

        $tmpFile = tempnam(sys_get_temp_dir(), 'it_perm_');
        if ($tmpFile === false) {
            $this->fail('No se pudo crear script temporal para ejecutar el controlador.');
        }
        $tmpPhp = $tmpFile . '.php';
        file_put_contents($tmpPhp, $script);

        $cmd = '"' . PHP_BINARY . '" ' . escapeshellarg($tmpPhp);
        $output = shell_exec($cmd);

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

    public function testObtenerRoles(): void
    {
        $permisos = new Permisos();
        $roles = $permisos->getRoles();
        
        $this->assertIsArray($roles);
        $this->assertNotEmpty($roles);
        $this->assertArrayHasKey('id_rol', $roles[0]);
        $this->assertArrayHasKey('nombre_rol', $roles[0]);
    }

    public function testObtenerModulos(): void
    {
        $permisos = new Permisos();
        $modulos = $permisos->getModulos();
        
        $this->assertIsArray($modulos);
        $this->assertNotEmpty($modulos);
        $this->assertArrayHasKey('id_modulo', $modulos[0]);
        $this->assertArrayHasKey('nombre_modulo', $modulos[0]);
    }

    public function testObtenerPermisosPorRolModulo(): void
    {
        $permisos = new Permisos();
        $permisosData = $permisos->getPermisosPorRolModulo();
        
        $this->assertIsArray($permisosData);
    }

    public function testObtenerPermisosUsuarioModulo(): void
    {
        $permisos = new Permisos();
        
        // Probar con rol de superusuario (debería tener todos los permisos)
        $permisosSuperUsuario = $permisos->getPermisosUsuarioModulo(6, 'cualquier_modulo');
        $this->assertTrue($permisosSuperUsuario['consultar']);
        $this->assertTrue($permisosSuperUsuario['incluir']);
        $this->assertTrue($permisosSuperUsuario['modificar']);
        $this->assertTrue($permisosSuperUsuario['eliminar']);
        $this->assertTrue($permisosSuperUsuario['generar reporte']);
        
        // Probar con módulo inexistente
        $permisosModuloInexistente = $permisos->getPermisosUsuarioModulo(1, 'modulo_inexistente');
        $this->assertFalse($permisosModuloInexistente['consultar']);
        $this->assertFalse($permisosModuloInexistente['incluir']);
        $this->assertFalse($permisosModuloInexistente['modificar']);
        $this->assertFalse($permisosModuloInexistente['eliminar']);
        $this->assertFalse($permisosModuloInexistente['generar reporte']);
    }

    public function testGuardarPermisos(): void
    {
        // Crear datos de prueba para guardar permisos
        $permisosData = [
            'permisos' => [
                $this->testRolId => [
                    $this->testModuloId => [
                        'consultar' => 'on',
                        'incluir' => 'on',
                        'modificar' => 'on',
                        'eliminar' => 'off',
                        'generar reporte' => 'on'
                    ]
                ]
            ],
            'guardarPermisos' => '1'
        ];

        // Ejecutar el controlador
        $output = $this->runController($permisosData);
        
        // Verificar que se redirigió correctamente
        $this->assertStringContainsString('Location: ?pagina=permiso&ok=1', implode('\n', headers_list()));
        
        // Verificar que los permisos se guardaron en la base de datos
        $stmt = $this->pdo->prepare(
            "SELECT accion, estatus FROM tbl_permisos 
             WHERE id_rol = ? AND id_modulo = ?"
        );
        $stmt->execute([$this->testRolId, $this->testModuloId]);
        $permisosGuardados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Verificar que se guardaron los permisos correctamente
        $this->assertCount(5, $permisosGuardados);
        
        $accionesPermitidas = ['consultar', 'incluir', 'modificar', 'generar reporte'];
        $accionesDenegadas = ['eliminar'];
        
        foreach ($permisosGuardados as $permiso) {
            if (in_array($permiso['accion'], $accionesPermitidas)) {
                $this->assertEquals('Permitido', $permiso['estatus']);
            } else {
                $this->assertEquals('Denegado', $permiso['estatus']);
            }
        }
    }

    public function testAccesoSinSesion(): void
    {
        // Simular que no hay sesión iniciada
        $_SESSION = [];
        
        // Intentar acceder al controlador
        $output = $this->runController([]);
        
        // Verificar que redirige al login
        $this->assertStringContainsString('Location: ?pagina=login', implode('\n', headers_list()));
    }

    public function testAccesoConRolSinPermisos(): void
    {
        // Simular sesión con un rol sin permisos
        $_SESSION = [
            'id_usuario' => 2,
            'name' => 'Usuario Sin Permisos',
            'id_rol' => 99, // ID de rol sin permisos
            'nombre_rol' => 'Invitado'
        ];
        
        // Intentar acceder al controlador
        $output = $this->runController([]);
        
        // Verificar que se deniega el acceso
        $this->assertStringContainsString('No tiene permisos para acceder a este módulo', $output['message'] ?? '');
    }
}
