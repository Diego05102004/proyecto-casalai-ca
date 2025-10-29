<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/marca.php';

/*
 * Pruebas unitarias del módulo de Marcas.
 *
 * Se emplean dobles de prueba para PDO a fin de no tocar la BD real.
 * Los escenarios cubren registrar, listar, actualizar, eliminar,
 * verificar existencia por nombre, obtener última marca y asociación de modelos.
 */

// ================================
// Dobles de prueba (PDOStatement)
// ================================
class StatementDoubleMarca
{
    private string $sql;
    private array $rows;
    private mixed $scalar;
    private ?array $row;
    private bool $throwOnExecute = false;
    private array $bound = [];

    public function __construct(string $sql, array $data = [])
    {
        $this->sql = $sql;
        $this->rows = $data['rows'] ?? [];
        $this->row = $data['row'] ?? null;
        $this->scalar = $data['scalar'] ?? null;
        $this->throwOnExecute = $data['throwOnExecute'] ?? false;
    }

    public function bindParam($name, &$value, $type = null)
    {
        // Guardar el valor enlazado para usarlo en execute()
        $this->bound[$name] = $value;
    }

    public function execute(array $params = [])
    {
        if ($this->throwOnExecute) {
            throw new Exception('Simulated execute failure');
        }
        // Validación básica al insertar/actualizar
        if (stripos($this->sql, 'INSERT INTO tbl_marcas') !== false || stripos($this->sql, 'UPDATE tbl_marcas') !== false) {
            // nombre_marca es requerido; tomar de execute(params) o de bindParam previo
            $nombre = $params[':nombre_marca'] ?? $params[0] ?? ($this->bound[':nombre_marca'] ?? null);
            if ($nombre === null || $nombre === '') {
                throw new Exception('nombre_marca requerido');
            }
        }
        return true;
    }

    public function fetch($mode = null)
    {
        return $this->row;
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
class PDODoubleMarca extends PDO
{
    private int $lastId = 1;

    public function __construct() {}

    public function setAttribute($attribute, $value)
    {
        return true;
    }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);

        // existeNomMarca: SELECT COUNT(*) FROM tbl_marcas WHERE nombre_marca = ? [AND id_marca != ?]
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_marcas WHERE nombre_marca') !== false) {
            // Simular existencia (COUNT(*)>0)
            return new StatementDoubleMarca($sql, [
                'scalar' => 1,
            ]);
        }

        // INSERT marca
        if (stripos($sql, 'INSERT INTO tbl_marcas') !== false) {
            $this->lastId++;
            return new StatementDoubleMarca($sql);
        }

        // SELECT * ORDER BY id_marca DESC LIMIT 1 (obtUltimaMarca)
        if (stripos($sql, 'SELECT * FROM tbl_marcas ORDER BY id_marca DESC LIMIT 1') !== false) {
            return new StatementDoubleMarca($sql, [
                'row' => [
                    'id_marca' => 10,
                    'nombre_marca' => 'UltimaMarca',
                ],
            ]);
        }

        // tieneModelosAsociados: SELECT COUNT(*) FROM tbl_modelos WHERE id_marca = :id_marca
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_modelos WHERE id_marca') !== false) {
            // Simular que hay modelos si id_marca = 99
            return new StatementDoubleMarca($sql, [
                'scalar' => 1,
            ]);
        }

        // obtenermarcasPorId
        if (stripos($sql, 'SELECT nombre_marca FROM tbl_marcas WHERE id_marca =') !== false) {
            return new StatementDoubleMarca($sql, [
                'row' => ['nombre_marca' => 'MarcaX'],
            ]);
        }

        // UPDATE marca
        if (stripos($sql, 'UPDATE tbl_marcas SET nombre_marca =') !== false) {
            return new StatementDoubleMarca($sql);
        }

        // DELETE marca
        if (stripos($sql, 'DELETE FROM tbl_marcas WHERE id_marca') !== false) {
            return new StatementDoubleMarca($sql);
        }

        // getmarcas listado
        if (stripos($sql, 'SELECT id_marca, nombre_marca FROM tbl_marcas') !== false) {
            return new StatementDoubleMarca($sql, [
                'rows' => [
                    ['id_marca' => 2, 'nombre_marca' => 'MarcaA'],
                    ['id_marca' => 1, 'nombre_marca' => 'MarcaB'],
                ],
            ]);
        }

        return new StatementDoubleMarca($sql);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class MarcaTest extends TestCase
{
    private function nuevaMarcaConPDOStub(): marca
    {
        $ref = new ReflectionClass(marca::class);
        /** @var marca $m */
        $m = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleMarca();
        $prop = new ReflectionProperty(marca::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($m, $pdo);
        return $m;
    }

    // MRK-UNIT-001: Registrar marca (happy path)
    public function testRegistrarMarcaHappyPath(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $m->setnombre_marca('NuevaMarca');
        $res = $m->registrarMarca();
        $this->assertTrue($res);
    }

    // MRK-UNIT-002: Existe nombre de marca (true)
    public function testExisteNombreMarcaTrue(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $exists = $m->existeNombreMarca('Duplicada');
        // Nuestro stub regresa >0 para COUNT(*) -> true
        $this->assertTrue($exists);
    }

    // MRK-UNIT-003: Obtener última marca
    public function testObtenerUltimaMarca(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $row = $m->obtenerUltimaMarca();
        $this->assertIsArray($row);
        $this->assertSame('UltimaMarca', $row['nombre_marca']);
    }

    // MRK-UNIT-004: Modificar marca
    public function testModificarMarca(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $m->setnombre_marca('MarcaEditada');
        $res = $m->modificarmarcas(5);
        $this->assertTrue($res);
    }

    // MRK-UNIT-005: Eliminar marca
    public function testEliminarMarca(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $res = $m->eliminarmarcas(5);
        $this->assertTrue($res);
    }

    // MRK-UNIT-006: Listar marcas
    public function testGetMarcasListado(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $lista = $m->getmarcas();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_marca', $lista[0]);
        $this->assertArrayHasKey('nombre_marca', $lista[0]);
    }

    // MRK-UNIT-007: obtener marca por id
    public function testObtenerMarcaPorId(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $row = $m->obtenermarcasPorId(3);
        $this->assertIsArray($row);
        $this->assertSame('MarcaX', $row['nombre_marca']);
    }

    // MRK-UNIT-008: tiene modelos asociados
    public function testTieneModelosAsociados(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $res = $m->tieneModelosAsociados(99);
        $this->assertTrue($res);
    }
}
