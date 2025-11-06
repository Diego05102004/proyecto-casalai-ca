<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/cliente.php';

/*
 * Pruebas de INTEGRACIÓN del módulo de Clientes.
 *
 * Secciones:
 * - Dobles: `StatementDoubleClienteF`, `PDODoubleClienteF` y `ClienteDoubleF` (simulan PDO/Statement y conexión para listarTodosClientes).
 * - Escenarios: registrar, existencia por cédula, último, por ID, modificar,
 *   eliminación lógica y física, listar y listarTodosClientes.
 */

// ======================================
// Dobles de prueba (PDOStatement/Cliente)
// ======================================
class StatementDoubleClienteF
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
        if (stripos($this->sql, 'INSERT INTO tbl_clientes') !== false) {
            $nombre = $params[':nombre'] ?? ($this->bound[':nombre'] ?? null);
            if ($nombre === null || $nombre === '') {
                throw new Exception('nombre requerido');
            }
        }
        if (stripos($this->sql, 'UPDATE tbl_clientes') !== false) {
            if (stripos($this->sql, 'SET activo = 0') === false) {
                $nombre = $params[':nombre'] ?? ($this->bound[':nombre'] ?? null);
                if ($nombre === null || $nombre === '') {
                    throw new Exception('nombre requerido');
                }
            }
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
class PDODoubleClienteF extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // existeNumeroCedula
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_clientes WHERE cedula') !== false) {
            return new StatementDoubleClienteF($sql, ['scalar' => 1]);
        }
        // INSERT
        if (stripos($sql, 'INSERT INTO tbl_clientes') !== false) {
            return new StatementDoubleClienteF($sql);
        }
        // Último cliente
        if (stripos($sql, 'SELECT * FROM tbl_clientes ORDER BY id_clientes DESC LIMIT 1') !== false) {
            return new StatementDoubleClienteF($sql, [
                'row' => [
                    'id_clientes' => 77,
                    'nombre' => 'UltimoCliente',
                    'cedula' => 'V-111',
                ],
            ]);
        }
        // obtenerclientesPorId
        if (stripos($sql, 'SELECT * FROM tbl_clientes WHERE id_clientes') !== false) {
            return new StatementDoubleClienteF($sql, [
                'row' => [
                    'id_clientes' => 5,
                    'nombre' => 'ClienteX',
                    'cedula' => 'V-123',
                ],
            ]);
        }
        // UPDATE modificar
        if (stripos($sql, 'UPDATE tbl_clientes SET nombre =') !== false) {
            return new StatementDoubleClienteF($sql);
        }
        // UPDATE eliminar_l
        if (stripos($sql, 'UPDATE tbl_clientes SET activo = 0 WHERE id_clientes') !== false) {
            return new StatementDoubleClienteF($sql);
        }
        // DELETE eliminar
        if (stripos($sql, 'DELETE FROM tbl_clientes WHERE id_clientes') !== false) {
            return new StatementDoubleClienteF($sql);
        }
        // getclientes listado
        if (stripos($sql, 'SELECT * FROM tbl_clientes') !== false) {
            return new StatementDoubleClienteF($sql, [
                'rows' => [
                    ['id_clientes' => 2, 'nombre' => 'Ana', 'cedula' => 'V-2'],
                    ['id_clientes' => 1, 'nombre' => 'Ben', 'cedula' => 'V-1'],
                ],
            ]);
        }
        // listarTodosClientes (activo=1)
        if (stripos($sql, 'SELECT id_clientes, nombre, cedula FROM tbl_clientes WHERE activo = 1') !== false) {
            return new StatementDoubleClienteF($sql, [
                'rows' => [
                    ['id_clientes' => 3, 'nombre' => 'Carlos', 'cedula' => 'V-3'],
                ],
            ]);
        }
        return new StatementDoubleClienteF($sql);
    }
}

// Doble de clase para listarTodosClientes()
class ClienteDoubleF extends cliente
{
    public function __construct() { /* evitar BD real */ }
    public function getConexion() { return new PDODoubleClienteF(); }
}

// ================================
// Casos de prueba (Integración)
// ================================
final class ClienteFeatureTest extends TestCase
{
    // Helper: crea `cliente` con PDO stub
    private function nuevoClienteConPDOStub(): cliente
    {
        $ref = new ReflectionClass(cliente::class);
        /** @var cliente $c */
        $c = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleClienteF();
        $prop = new ReflectionProperty(cliente::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($c, $pdo);
        return $c;
    }

    // CLI-FEAT-001: Registrar cliente
    public function testRegistrarClienteIntegracion(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $c->setnombre('Juan');
        $c->setcedula('V-100');
        $c->setdireccion('Dir');
        $c->settelefono('0414');
        $c->setcorreo('j@test.com');
        $this->assertTrue($c->ingresarclientes());
    }

    // CLI-FEAT-002: Existe número de cédula
    public function testExisteNumeroCedulaIntegracion(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $this->assertTrue($c->existeNumeroCedula('V-100'));
    }

    // CLI-FEAT-003: Obtener último cliente
    public function testObtenerUltimoClienteIntegracion(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $row = $c->obtenerUltimoCliente();
        $this->assertIsArray($row);
        $this->assertSame('UltimoCliente', $row['nombre']);
    }

    // CLI-FEAT-004: Obtener cliente por ID
    public function testObtenerClientePorIdIntegracion(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $row = $c->obtenerclientesPorId(5);
        $this->assertIsArray($row);
        $this->assertSame('ClienteX', $row['nombre']);
    }

    // CLI-FEAT-005: Modificar cliente
    public function testModificarClienteIntegracion(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $c->setnombre('Edit');
        $c->setcedula('V-9');
        $c->setdireccion('D');
        $c->settelefono('T');
        $c->setcorreo('e@test.com');
        $refActivo = new ReflectionProperty(cliente::class, 'activo');
        $refActivo->setAccessible(true);
        $refActivo->setValue($c, 1);
        $this->assertTrue($c->modificarclientes(5));
    }

    // CLI-FEAT-006: Eliminar lógico
    public function testEliminarLogicoIntegracion(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $this->assertTrue($c->eliminar_l(5));
    }

    // CLI-FEAT-007: Eliminar cliente
    public function testEliminarClienteIntegracion(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $this->assertTrue($c->eliminarclientes(5));
    }

    // CLI-FEAT-008: Listar clientes (g_clientes)
    public function testGetClientesListadoIntegracion(): void
    {
        $c = $this->nuevoClienteConPDOStub();
        $lista = $c->getclientes();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_clientes', $lista[0]);
        $this->assertArrayHasKey('nombre', $lista[0]);
    }

    // CLI-FEAT-009: listarTodosClientes() usando ClienteDoubleF
    public function testListarTodosClientesIntegracion(): void
    {
        $c = new ClienteDoubleF();
        $lista = $c->listarTodosClientes();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_clientes', $lista[0]);
    }
}
