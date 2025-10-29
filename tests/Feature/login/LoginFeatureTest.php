<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/Login.php';

/*
 * Pruebas de INTEGRACIÓN del módulo Login.
 *
 * Secciones:
 * - Dobles: `StatementDoubleLoginF`, `PDODoubleLoginF` (co) y `PDODoubleLoginPF` (cop) simulando consultas, inserts, updates y transacciones.
 * - Escenarios: login ok/fallo, solicitar recuperación, validar token, actualizar contraseña,
 *   registrar usuario+cliente (ok y username duplicado).
 */

// ======================================
// Dobles de prueba (PDOStatement/Login - Feature)
// ======================================
class StatementDoubleLoginF
{
    private string $sql;
    private array $rows;
    private ?array $row;
    private mixed $scalar;
    private array $bound = [];

    public function __construct(string $sql, array $data = [])
    {
        $this->sql = $sql;
        $this->rows = $data['rows'] ?? [];
        $this->row = $data['row'] ?? null;
        $this->scalar = $data['scalar'] ?? null;
    }

    public function bindParam($name, &$value, $type = null)
    {
        $this->bound[$name] = $value;
    }

    public function execute(array $params = [])
    {
        return true;
    }

    public function fetch($mode = null)
    {
        if ($this->row !== null) return $this->row;
        if ($this->scalar !== null) return $this->scalar;
        return null;
    }

    public function fetchAll($mode = null)
    {
        return $this->rows;
    }

    public function fetchColumn($columnNumber = 0)
    {
        return $this->scalar;
    }
}

// ======================
// Doble de prueba (PDO - co)
// ======================
class PDODoubleLoginF extends PDO
{
    public bool $inTx = false;
    public static bool $usernameExists = false; // controla SELECT COUNT(*)

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function beginTransaction(): bool { $this->inTx = true; return true; }
    public function commit(): bool { $this->inTx = false; return true; }
    public function rollBack(): bool { $this->inTx = false; return true; }
    public function inTransaction(): bool { return $this->inTx; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // SELECT COUNT(*) FROM tbl_usuarios WHERE username = ? (poner primero)
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_usuarios WHERE username') !== false) {
            return new StatementDoubleLoginF($sql, [ 'scalar' => (int)self::$usernameExists ]);
        }
        // SELECT join usuario/rol por username
        if (stripos($sql, 'FROM') !== false && stripos($sql, 'tbl_usuarios') !== false && stripos($sql, 'WHERE username =') !== false) {
            $hash = password_hash('secret123', PASSWORD_DEFAULT);
            return new StatementDoubleLoginF($sql, [
                'row' => [
                    'id_usuario' => 10,
                    'id_rol' => 2,
                    'nombre_rol' => 'Supervisor',
                    'username' => 'admin',
                    'password' => $hash,
                    'cedula' => 'V-10',
                ],
            ]);
        }
        // SELECT id_usuario FROM tbl_usuarios WHERE correo = ?
        if (stripos($sql, 'SELECT id_usuario FROM tbl_usuarios WHERE correo') !== false) {
            return new StatementDoubleLoginF($sql, [ 'row' => ['id_usuario' => 10] ]);
        }
        // DELETE FROM tbl_recuperar WHERE id_usuario = ?
        if (stripos($sql, 'DELETE FROM tbl_recuperar WHERE id_usuario') !== false) {
            return new StatementDoubleLoginF($sql);
        }
        // INSERT INTO tbl_recuperar (...)
        if (stripos($sql, 'INSERT INTO tbl_recuperar') !== false) {
            return new StatementDoubleLoginF($sql);
        }
        // SELECT * FROM tbl_recuperar WHERE id_usuario = ? AND token = ? AND utilizado = 0 AND expiracion > NOW()
        if (stripos($sql, 'SELECT * FROM tbl_recuperar') !== false && stripos($sql, 'utilizado = 0') !== false) {
            return new StatementDoubleLoginF($sql, [
                'row' => [
                    'id_usuario' => 10,
                    'token' => 'tok',
                    'utilizado' => 0,
                ],
            ]);
        }
        // UPDATE tbl_usuarios SET password = ? WHERE id_usuario = ?
        if (stripos($sql, 'UPDATE tbl_usuarios SET password') !== false) {
            return new StatementDoubleLoginF($sql);
        }
        // UPDATE tbl_recuperar SET utilizado = 1 WHERE id_usuario = ?
        if (stripos($sql, 'UPDATE tbl_recuperar SET utilizado = 1 WHERE id_usuario') !== false) {
            return new StatementDoubleLoginF($sql);
        }
        // INSERT INTO tbl_usuarios (...)
        if (stripos($sql, 'INSERT INTO tbl_usuarios') !== false) {
            return new StatementDoubleLoginF($sql);
        }

        return new StatementDoubleLoginF($sql);
    }
}

// ======================
// Doble de prueba (PDO - cop)
// ======================
class PDODoubleLoginPF extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // INSERT INTO tbl_clientes (...)
        if (stripos($sql, 'INSERT INTO tbl_clientes') !== false) {
            return new StatementDoubleLoginF($sql);
        }
        return new StatementDoubleLoginF($sql);
    }
}

// ================================
// Casos de prueba (Integración)
// ================================
final class LoginFeatureTest extends TestCase
{
    // Helper: crea `Login` con dos conexiones stub (co y cop)
    private function nuevoLoginConPDOStub(): Login
    {
        $ref = new ReflectionClass(Login::class);
        /** @var Login $l */
        $l = $ref->newInstanceWithoutConstructor();
        $co = new PDODoubleLoginF();
        $cop = new PDODoubleLoginPF();
        $propCo = new ReflectionProperty(Login::class, 'co');
        $propCo->setAccessible(true);
        $propCo->setValue($l, $co);
        $propCop = new ReflectionProperty(Login::class, 'cop');
        $propCop->setAccessible(true);
        $propCop->setValue($l, $cop);
        return $l;
    }

    // LGN-FEAT-001: Login correcto
    public function testExisteLoginOkIntegracion(): void
    {
        $l = $this->nuevoLoginConPDOStub();
        $l->set_username('admin');
        $l->set_password('secret123');
        $r = $l->existe();
        $this->assertSame('existe', $r['resultado']);
        $this->assertSame('Supervisor', $r['nombre_rol']);
    }

    // LGN-FEAT-002: Login falla por password
    public function testExisteLoginFailPasswordIntegracion(): void
    {
        $l = $this->nuevoLoginConPDOStub();
        $l->set_username('admin');
        $l->set_password('wrong');
        $r = $l->existe();
        $this->assertSame('noexiste', $r['resultado']);
    }

    // LGN-FEAT-003: Solicitar recuperación OK
    public function testSolicitarRecuperacionOkIntegracion(): void
    {
        $l = $this->nuevoLoginConPDOStub();
        $res = $l->solicitarRecuperacion('a@b.com');
        $this->assertSame('success', $res['status']);
        $this->assertArrayHasKey('token', $res);
        $this->assertSame(10, $res['id_usuario']);
    }

    // LGN-FEAT-004: Validar token OK
    public function testValidarTokenOkIntegracion(): void
    {
        $l = $this->nuevoLoginConPDOStub();
        $row = $l->validarToken(10, 'tok');
        $this->assertIsArray($row);
        $this->assertSame(10, $row['id_usuario']);
    }

    // LGN-FEAT-005: Actualizar password OK
    public function testActualizarPasswordOkIntegracion(): void
    {
        $l = $this->nuevoLoginConPDOStub();
        $ok = $l->actualizarPassword(10, 'newpass');
        $this->assertTrue($ok);
    }

    // LGN-FEAT-006: Registrar usuario+cliente OK
    public function testRegistrarUsuarioYClienteOkIntegracion(): void
    {
        PDODoubleLoginF::$usernameExists = false;
        $l = $this->nuevoLoginConPDOStub();
        $res = $l->registrarUsuarioYCliente([
            'nombre_usuario' => 'nuevo',
            'clave' => 'clave',
            'cedula' => 'V-12',
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'correo' => 'ana@test.com',
            'telefono' => '0414',
            'direccion' => 'Dir 1',
        ]);
        $this->assertSame('success', $res['status']);
    }

    // LGN-FEAT-007: Registrar usuario duplicado - error
    public function testRegistrarUsuarioYClienteUsernameDuplicadoIntegracion(): void
    {
        PDODoubleLoginF::$usernameExists = true;
        $l = $this->nuevoLoginConPDOStub();
        $res = $l->registrarUsuarioYCliente([
            'nombre_usuario' => 'existente',
            'clave' => 'clave',
            'cedula' => 'V-12',
            'nombre' => 'Ana',
            'apellido' => 'Gomez',
            'correo' => 'ana@test.com',
            'telefono' => '0414',
            'direccion' => 'Dir 1',
        ]);
        $this->assertSame('error', $res['status']);
        $this->assertNotEmpty($res['mensaje']);
    }
}
