<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/proveedor.php';

/*
 * Pruebas de INTEGRACIÓN del módulo de Proveedores.
 *
 * Secciones:
 * - Dobles: `StatementDoubleProveedorF` y `PDODoubleProveedorF` (simulan PDO/Statement y consultas usadas).
 * - Escenarios: registrar, existe por nombre, último, por ID, modificar, eliminar,
 *   listar, reportes (suministro, ranking, comparación precios, dependencia) y cambiar estatus.
 */

// =========================================
// Dobles de prueba (PDOStatement/Proveedor)
// =========================================
class StatementDoubleProveedorF
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
        // Validaciones mínimas
        if (stripos($this->sql, 'INSERT INTO tbl_proveedores') !== false) {
            $nombre = $params[':nombre'] ?? ($this->bound[':nombre'] ?? null);
            if ($nombre === null || $nombre === '') {
                throw new Exception('nombre requerido');
            }
        }
        if (stripos($this->sql, 'UPDATE tbl_proveedores') !== false) {
            if (stripos($this->sql, 'SET estado =') === false) {
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
class PDODoubleProveedorF extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // existeNombreProveedor
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_proveedores WHERE nombre_proveedor') !== false) {
            return new StatementDoubleProveedorF($sql, [ 'scalar' => 1 ]);
        }
        // INSERT proveedor
        if (stripos($sql, 'INSERT INTO tbl_proveedores') !== false) {
            return new StatementDoubleProveedorF($sql);
        }
        // Ultimo proveedor
        if (stripos($sql, 'SELECT * FROM tbl_proveedores ORDER BY id_proveedor DESC LIMIT 1') !== false) {
            return new StatementDoubleProveedorF($sql, [
                'row' => [
                    'id_proveedor' => 99,
                    'nombre_proveedor' => 'UltimoProv',
                ],
            ]);
        }
        // obtenerProveedorPorId
        if (stripos($sql, 'SELECT * FROM tbl_proveedores WHERE id_proveedor =') !== false) {
            return new StatementDoubleProveedorF($sql, [
                'row' => [
                    'id_proveedor' => 5,
                    'nombre_proveedor' => 'ProvX',
                    'correo_proveedor' => 'x@prov.test',
                ],
            ]);
        }
        // UPDATE proveedor
        if (stripos($sql, 'UPDATE tbl_proveedores SET') !== false) {
            return new StatementDoubleProveedorF($sql);
        }
        // DELETE proveedor
        if (stripos($sql, 'DELETE FROM tbl_proveedores WHERE id_proveedor') !== false) {
            return new StatementDoubleProveedorF($sql);
        }
        // Listado proveedores
        if (stripos($sql, 'SELECT * FROM tbl_proveedores') !== false) {
            return new StatementDoubleProveedorF($sql, [
                'rows' => [
                    ['id_proveedor' => 2, 'nombre_proveedor' => 'ProvA'],
                    ['id_proveedor' => 1, 'nombre_proveedor' => 'ProvB'],
                ],
            ]);
        }
        // Reporte comparacion precios producto (prepare)
        if (stripos($sql, 'AVG(d.costo) AS precio_promedio') !== false) {
            return new StatementDoubleProveedorF($sql, [
                'rows' => [
                    ['id_producto' => 1, 'nombre_producto' => 'P1', 'nombre_proveedor' => 'ProvA', 'precio_promedio' => 10.5, 'cantidad_registros' => 3],
                ],
            ]);
        }
        // Dependencia proveedores (prepare) - por si se ejecuta con prepare
        if (stripos($sql, 'dependencia_porcentaje') !== false && stripos($sql, 'GROUP BY p.nombre_proveedor') !== false) {
            return new StatementDoubleProveedorF($sql, [
                'rows' => [
                    ['nombre_proveedor' => 'ProvA', 'monto_total_pagado' => 1000, 'dependencia_porcentaje' => 60.0],
                    ['nombre_proveedor' => 'ProvB', 'monto_total_pagado' => 666, 'dependencia_porcentaje' => 40.0],
                ],
            ]);
        }
        return new StatementDoubleProveedorF($sql);
    }

    public function query($query, $fetchMode = null, ...$fetchModeArgs)
    {
        // Ranking de proveedores (query directo)
        if (stripos($query, 'FROM tbl_recepcion_productos r') !== false && stripos($query, 'ORDER BY total DESC') !== false) {
            return new StatementDoubleProveedorF($query, [
                'rows' => [
                    ['nombre_proveedor' => 'ProvA', 'nombre_producto' => 'Prod1', 'cantidad' => 5, 'costo' => 10, 'total' => 50, 'fecha' => '2025-10-01'],
                    ['nombre_proveedor' => 'ProvB', 'nombre_producto' => 'Prod2', 'cantidad' => 3, 'costo' => 20, 'total' => 60, 'fecha' => '2025-09-28'],
                ],
            ]);
        }
        // Dependencia (query directo)
        if (stripos($query, 'dependencia_porcentaje') !== false && stripos($query, 'GROUP BY p.nombre_proveedor') !== false) {
            return new StatementDoubleProveedorF($query, [
                'rows' => [
                    ['nombre_proveedor' => 'ProvA', 'monto_total_pagado' => 1000, 'dependencia_porcentaje' => 60.0],
                    ['nombre_proveedor' => 'ProvB', 'monto_total_pagado' => 666, 'dependencia_porcentaje' => 40.0],
                ],
            ]);
        }
        return new StatementDoubleProveedorF($query);
    }
}

// ================================
// Casos de prueba (Integración)
// ================================
final class ProveedorFeatureTest extends TestCase
{
    // Helper: crea `Proveedores` con PDO stub
    private function nuevoProveedorConPDOStub(): Proveedores
    {
        $ref = new ReflectionClass(Proveedores::class);
        /** @var Proveedores $p */
        $p = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleProveedorF();
        $prop = new ReflectionProperty(Proveedores::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($p, $pdo);
        return $p;
    }

    // PRV-FEAT-001: Registrar proveedor
    public function testRegistrarProveedorIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $p->setNombre('Proveedor Nuevo');
        $p->setRif1('J-123');
        $p->setRepresentante('Rep X');
        $p->setRif2('V-321');
        $p->setCorreo('prov@test.com');
        $p->setDireccion('Dirección');
        $p->setTelefono1('0414-0000000');
        $p->setTelefono2('0412-0000000');
        $p->setObservacion('Obs');
        $this->assertTrue($p->registrarProveedor());
    }

    // PRV-FEAT-002: Existe nombre proveedor
    public function testExisteNombreProveedorIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $this->assertTrue($p->existeNombreProveedor('Proveedor Existente'));
    }

    // PRV-FEAT-003: Obtener último proveedor
    public function testObtenerUltimoProveedorIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $row = $p->obtenerUltimoProveedor();
        $this->assertIsArray($row);
        $this->assertSame('UltimoProv', $row['nombre_proveedor']);
    }

    // PRV-FEAT-004: Obtener proveedor por ID
    public function testObtenerProveedorPorIdIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $row = $p->obtenerProveedorPorId(5);
        $this->assertIsArray($row);
        $this->assertSame('ProvX', $row['nombre_proveedor']);
    }

    // PRV-FEAT-005: Modificar proveedor
    public function testModificarProveedorIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $p->setNombre('Editado');
        $p->setRif1('J-999');
        $p->setRepresentante('Rep Y');
        $p->setRif2('V-111');
        $p->setCorreo('e@test.com');
        $p->setDireccion('Dir');
        $p->setTelefono1('0414');
        $p->setTelefono2('0424');
        $p->setObservacion('Obs');
        $this->assertTrue($p->modificarProveedor(5));
    }

    // PRV-FEAT-006: Eliminar proveedor
    public function testEliminarProveedorIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $this->assertTrue($p->eliminarProveedor(5));
    }

    // PRV-FEAT-007: Listar proveedores
    public function testGetProveedoresListadoIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $lista = $p->getproveedores();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_proveedor', $lista[0]);
        $this->assertArrayHasKey('nombre_proveedor', $lista[0]);
    }

    // PRV-FEAT-008: Reporte suministro proveedores (top 10)
    public function testObtenerReporteSuministroProveedoresIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $rows = $p->obtenerReporteSuministroProveedores();
        $this->assertIsArray($rows);
    }

    // PRV-FEAT-009: Ranking de proveedores
    public function testGetRankingProveedoresIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $rows = $p->getRankingProveedores();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('nombre_proveedor', $rows[0]);
        $this->assertArrayHasKey('total', $rows[0]);
    }

    // PRV-FEAT-010: Comparación de precios de producto
    public function testGetComparacionPreciosProductoIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $rows = $p->getComparacionPreciosProducto();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('precio_promedio', $rows[0]);
    }

    // PRV-FEAT-011: Dependencia de proveedores
    public function testGetDependenciaProveedoresIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $rows = $p->getDependenciaProveedores();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('dependencia_porcentaje', $rows[0]);
    }

    // PRV-FEAT-012: Cambiar estatus
    public function testCambiarEstatusIntegracion(): void
    {
        $p = $this->nuevoProveedorConPDOStub();
        $ref = new ReflectionProperty(Proveedores::class, 'id_proveedor');
        $ref->setAccessible(true);
        $ref->setValue($p, 7);
        $this->assertTrue($p->cambiarEstatus('A'));
    }
}
