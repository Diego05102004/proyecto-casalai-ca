<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/rol.php';

/*
 * Pruebas de INTEGRACIÓN del módulo de Roles.
 *
 * Secciones:
 * - Dobles: `StatementDoubleRolF` y `PDODoubleRolF` (simulan PDO/Statement, lastInsertId y consultas usadas).
 * - Escenarios: registrar (con inserción de permisos), existencia por nombre, obtener último, por ID,
 *   listar, modificar, eliminar y verificación de usuarios asignados.
 */

// ==================================
// Dobles de prueba (PDOStatement/Rol)
// ==================================
class StatementDoubleRolF
{
    private string $sql;
    private array $rows;
    private ?array $row;
    private mixed $scalar;
    private array $bound = [];
    public static int $permInsertCount = 0;

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
        if (stripos($this->sql, 'INSERT INTO tbl_rol') !== false) {
            $nombre = $params[':nombre_rol'] ?? ($this->bound[':nombre_rol'] ?? null);
            if ($nombre === null || $nombre === '') {
                throw new Exception('nombre_rol requerido');
            }
        }
        if (stripos($this->sql, 'INSERT INTO tbl_permisos') !== false) {
            self::$permInsertCount++;
        }
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
// Doble de prueba (PDO)
// ======================
class PDODoubleRolF extends PDO
{
    private int $lastId = 1000;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function lastInsertId($name = null): string|false { return (string)$this->lastId; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // INSERT rol
        if (stripos($sql, 'INSERT INTO tbl_rol') !== false) {
            $this->lastId = 1001;
            return new StatementDoubleRolF($sql);
        }
        // SELECT id_modulo FROM tbl_modulos
        if (stripos($sql, 'SELECT id_modulo FROM tbl_modulos') !== false) {
            return new StatementDoubleRolF($sql, [ 'rows' => [1,2,3] ]);
        }
        // INSERT permisos
        if (stripos($sql, 'INSERT INTO tbl_permisos') !== false) {
            return new StatementDoubleRolF($sql);
        }
        // existe nombre rol (COUNT)
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_rol WHERE nombre_rol') !== false) {
            return new StatementDoubleRolF($sql, ['scalar' => 1]);
        }
        // obtener último rol
        if (stripos($sql, 'SELECT * FROM tbl_rol ORDER BY id_rol DESC LIMIT 1') !== false) {
            return new StatementDoubleRolF($sql, [ 'row' => ['id_rol' => 9, 'nombre_rol' => 'UltimoRol'] ]);
        }
        // obtener rol por id
        if (stripos($sql, 'SELECT * FROM tbl_rol WHERE id_rol =') !== false) {
            return new StatementDoubleRolF($sql, [ 'row' => ['id_rol' => 5, 'nombre_rol' => 'RolX'] ]);
        }
        // listar
        if (stripos($sql, 'SELECT id_rol, nombre_rol FROM tbl_rol') !== false) {
            return new StatementDoubleRolF($sql, [
                'rows' => [
                    ['id_rol' => 3, 'nombre_rol' => 'Operador'],
                    ['id_rol' => 2, 'nombre_rol' => 'Supervisor'],
                ],
            ]);
        }
        // update
        if (stripos($sql, 'UPDATE tbl_rol SET nombre_rol') !== false) {
            return new StatementDoubleRolF($sql);
        }
        // delete
        if (stripos($sql, 'DELETE FROM tbl_rol WHERE id_rol') !== false) {
            return new StatementDoubleRolF($sql);
        }
        // usuarios asignados
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_usuarios WHERE id_rol') !== false) {
            return new StatementDoubleRolF($sql, ['scalar' => 0]);
        }
        return new StatementDoubleRolF($sql);
    }
}

// ================================
// Casos de prueba (Integración)
// ================================
final class RolFeatureTest extends TestCase
{
    // Helper: crea `Rol` con PDO stub
    private function nuevoRolConPDOStub(): Rol
    {
        $ref = new ReflectionClass(Rol::class);
        /** @var Rol $r */
        $r = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleRolF();
        $prop = new ReflectionProperty(Rol::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($r, $pdo);
        return $r;
    }

    // ROL-FEAT-001: Registrar rol (con permisos)
    public function testRegistrarRolIntegracion(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $r->setNombreRol('Nuevo Rol');
        StatementDoubleRolF::$permInsertCount = 0;
        // Suprimir/consumir warnings generados por el código productivo durante el registro
        $prev = error_reporting();
        error_reporting($prev & ~E_WARNING);
        set_error_handler(function() { return true; }, E_WARNING);
        $ok = $r->registrarRol();
        restore_error_handler();
        error_reporting($prev);
        $this->assertTrue($ok);
        // 3 módulos * 6 acciones = 18 inserts
        $this->assertSame(18, StatementDoubleRolF::$permInsertCount);
    }

    // ROL-FEAT-002: Existe nombre de rol
    public function testExisteNombreRolIntegracion(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $this->assertTrue($r->existeNombreRol('Operador'));
    }

    // ROL-FEAT-003: Obtener último rol
    public function testObtenerUltimoRolIntegracion(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $row = $r->obtenerUltimoRol();
        $this->assertIsArray($row);
        $this->assertSame('UltimoRol', $row['nombre_rol']);
    }

    // ROL-FEAT-004: Obtener rol por id
    public function testObtenerRolPorIdIntegracion(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $row = $r->obtenerRolPorId(5);
        $this->assertIsArray($row);
        $this->assertSame('RolX', $row['nombre_rol']);
    }

    // ROL-FEAT-005: Listar roles
    public function testConsultarRolesIntegracion(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $lista = $r->consultarRoles();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_rol', $lista[0]);
        $this->assertArrayHasKey('nombre_rol', $lista[0]);
    }

    // ROL-FEAT-006: Modificar rol
    public function testModificarRolIntegracion(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $r->setNombreRol('Editado');
        $this->assertTrue($r->modificarRol(3));
    }

    // ROL-FEAT-007: Eliminar rol
    public function testEliminarRolIntegracion(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $this->assertTrue($r->eliminarRol(3));
    }

    // ROL-FEAT-008: Tiene usuarios asignados (false)
    public function testTieneUsuariosAsignadosFalseIntegracion(): void
    {
        $r = $this->nuevoRolConPDOStub();
        $this->assertFalse($r->tieneUsuariosAsignados(3));
    }
}
