<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/marca.php';

/*
 * Pruebas de INTEGRACIÓN del módulo de Marcas.
 *
 * Secciones:
 * - Dobles de prueba: `StatementDoubleMarcaF` y `PDODoubleMarcaF` (simulan PDO/Statement)
 * - Escenarios: registrar, existe por nombre, obtener última, por id, modificar,
 *   eliminar, listar y verificar modelos asociados.
 */

// ======================================
// Dobles de prueba (PDOStatement/Marca)
// ======================================
class StatementDoubleMarcaF
{
    private string $sql;
    private array $rows;
    private ?array $row;
    private mixed $scalar;
    private array $bound = [];
    private bool $throwOnExecute = false;

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
        $this->bound[$name] = $value;
    }

    public function execute(array $params = [])
    {
        if ($this->throwOnExecute) {
            throw new Exception('Simulated failure');
        }
        if (stripos($this->sql, 'INSERT INTO tbl_marcas') !== false || stripos($this->sql, 'UPDATE tbl_marcas') !== false) {
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
class PDODoubleMarcaF extends PDO
{
    private int $lastId = 1;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function lastInsertId($name = null): string|false { return (string)$this->lastId; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);

        // existeNomMarca
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_marcas WHERE nombre_marca') !== false) {
            return new StatementDoubleMarcaF($sql, [ 'scalar' => 1 ]);
        }
        // INSERT marca
        if (stripos($sql, 'INSERT INTO tbl_marcas') !== false) {
            $this->lastId++;
            return new StatementDoubleMarcaF($sql);
        }
        // Última marca
        if (stripos($sql, 'SELECT * FROM tbl_marcas ORDER BY id_marca DESC LIMIT 1') !== false) {
            return new StatementDoubleMarcaF($sql, [ 'row' => ['id_marca' => 10, 'nombre_marca' => 'UltimaMarca'] ]);
        }
        // tiene modelos asociados
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_modelos WHERE id_marca') !== false) {
            return new StatementDoubleMarcaF($sql, [ 'scalar' => 0 ]);
        }
        // obtener por id
        if (stripos($sql, 'SELECT nombre_marca FROM tbl_marcas WHERE id_marca') !== false) {
            return new StatementDoubleMarcaF($sql, [ 'row' => ['nombre_marca' => 'MarcaX'] ]);
        }
        // UPDATE marca
        if (stripos($sql, 'UPDATE tbl_marcas SET nombre_marca') !== false) {
            return new StatementDoubleMarcaF($sql);
        }
        // DELETE marca
        if (stripos($sql, 'DELETE FROM tbl_marcas WHERE id_marca') !== false) {
            return new StatementDoubleMarcaF($sql);
        }
        // Listado marcas
        if (stripos($sql, 'SELECT id_marca, nombre_marca FROM tbl_marcas') !== false) {
            return new StatementDoubleMarcaF($sql, [
                'rows' => [
                    ['id_marca' => 2, 'nombre_marca' => 'MarcaA'],
                    ['id_marca' => 1, 'nombre_marca' => 'MarcaB'],
                ],
            ]);
        }

        return new StatementDoubleMarcaF($sql);
    }
}

// ================================
// Casos de prueba (Integración)
// ================================
final class MarcaFeatureTest extends TestCase
{
    // Helper: crea `marca` con PDO stub
    private function nuevaMarcaConPDOStub(): marca
    {
        $ref = new ReflectionClass(marca::class);
        /** @var marca $m */
        $m = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleMarcaF();
        $prop = new ReflectionProperty(marca::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($m, $pdo);
        return $m;
    }

    // MRK-FEAT-001: Registrar marca
    public function testRegistrarMarcaIntegracion(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $m->setnombre_marca('NuevaMarca');
        $this->assertTrue($m->registrarMarca());
    }

    // MRK-FEAT-002: Existe nombre (true)
    public function testExisteNombreMarcaIntegracion(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $this->assertTrue($m->existeNombreMarca('Duplicada'));
    }

    // MRK-FEAT-003: Obtener última marca
    public function testObtenerUltimaMarcaIntegracion(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $row = $m->obtenerUltimaMarca();
        $this->assertIsArray($row);
        $this->assertSame('UltimaMarca', $row['nombre_marca']);
    }

    // MRK-FEAT-004: Obtener por id
    public function testObtenerMarcaPorIdIntegracion(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $row = $m->obtenermarcasPorId(5);
        $this->assertIsArray($row);
        $this->assertSame('MarcaX', $row['nombre_marca']);
    }

    // MRK-FEAT-005: Modificar marca
    public function testModificarMarcaIntegracion(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $m->setnombre_marca('Editada');
        $this->assertTrue($m->modificarmarcas(5));
    }

    // MRK-FEAT-006: Eliminar marca
    public function testEliminarMarcaIntegracion(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $this->assertTrue($m->eliminarmarcas(5));
    }

    // MRK-FEAT-007: Listar marcas
    public function testGetMarcasListadoIntegracion(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $lista = $m->getmarcas();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_marca', $lista[0]);
        $this->assertArrayHasKey('nombre_marca', $lista[0]);
    }

    // MRK-FEAT-008: Verificar modelos asociados (false)
    public function testTieneModelosAsociadosIntegracion(): void
    {
        $m = $this->nuevaMarcaConPDOStub();
        $this->assertFalse($m->tieneModelosAsociados(5));
    }
}
