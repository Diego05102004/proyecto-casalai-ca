<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/OrdenDespacho.php';

/*
 * Pruebas de INTEGRACIÓN del módulo Orden de Despacho.
 *
 * Secciones:
 * - Dobles: `StatementDoubleODF` y `PDODoubleODF` (simulan PDO/Statement y resultados de consultas/updates).
 * - Escenarios: facturas disponibles, obtener por id, listar con productos, descargar por id,
 *   detalles de compra, cambiar estado, anular orden y cambiar estatus (usuarios).
 */

// ======================================
// Dobles de prueba (PDOStatement/ODF)
// ======================================
class StatementDoubleODF
{
    private string $sql;
    private array $rows;
    private ?array $row;
    private array $bound = [];

    public function __construct(string $sql, array $data = [])
    {
        $this->sql = $sql;
        $this->rows = $data['rows'] ?? [];
        $this->row = $data['row'] ?? null;
    }

    public function bindParam($name, &$value, $type = null)
    {
        $this->bound[$name] = $value;
    }

    public function execute(array $params = [])
    {
        // Validaciones mínimas para updates
        if (stripos($this->sql, 'UPDATE tbl_orden_despachos SET estado =') !== false) {
            $estado = $params[':estado'] ?? ($this->bound[':estado'] ?? null);
            $id = $params[':id'] ?? ($this->bound[':id'] ?? null);
            if ($estado === null || $id === null) {
                throw new Exception('estado/id requeridos');
            }
        }
        if (stripos($this->sql, 'UPDATE tbl_orden_despachos SET activo = 0') !== false) {
            $id = $params[':id'] ?? ($this->bound[':id'] ?? null);
            if ($id === null) {
                throw new Exception('id requerido');
            }
        }
        if (stripos($this->sql, 'UPDATE tbl_usuarios SET estatus =') !== false) {
            $estatus = $params[':estatus'] ?? ($this->bound[':estatus'] ?? null);
            $id = $params[':id'] ?? ($this->bound[':id'] ?? null);
            if ($estatus === null || $id === null) {
                throw new Exception('estatus/id requeridos');
            }
        }
        return true;
    }

    public function fetch($mode = null)
    {
        if ($this->row !== null) return $this->row;
        return null;
    }

    public function fetchAll($mode = null)
    {
        return $this->rows;
    }
}

// ======================
// Doble de prueba (PDO)
// ======================
class PDODoubleODF extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // Facturas disponibles (pagadas y no usadas)
        if (stripos($sql, 'FROM tbl_facturas f') !== false && stripos($sql, 'WHERE f.estatus =') !== false) {
            return new StatementDoubleODF($sql, [
                'rows' => [
                    ['id_factura' => 201, 'fecha' => '2025-10-20', 'nombre' => 'Cliente A'],
                    ['id_factura' => 202, 'fecha' => '2025-10-21', 'nombre' => 'Cliente B'],
                ],
            ]);
        }
        // Obtener orden por id
        if (stripos($sql, 'SELECT * FROM tbl_orden_despachos WHERE id_orden_despachos') !== false) {
            return new StatementDoubleODF($sql, [
                'row' => [
                    'id_orden_despachos' => 5,
                    'id_factura' => 300,
                    'cliente' => 'Juan',
                    'fecha_despacho' => '2025-10-22',
                    'estado' => 'Pendiente',
                    'activo' => 1,
                ],
            ]);
        }
        // Listado base getordendespacho()
        if (stripos($sql, 'FROM tbl_orden_despachos AS od') !== false && stripos($sql, 'ORDER BY od.fecha_despacho') !== false && stripos($sql, 'WHERE od.activo = 1') !== false) {
            return new StatementDoubleODF($sql, [
                'rows' => [
                    ['id_orden_despachos' => 9, 'id_factura' => 300, 'cedula' => 'V-1', 'cliente' => 'Carlos', 'fecha_despacho' => '2025-10-22', 'estado' => 'Pendiente', 'activo' => 1],
                ],
            ]);
        }
        // DescargarOrdenDespacho (mismo join pero con filtro de id)
        if (stripos($sql, 'FROM tbl_orden_despachos AS od') !== false && stripos($sql, 'WHERE od.id_orden_despachos =') !== false) {
            return new StatementDoubleODF($sql, [
                'rows' => [
                    ['id_orden_despachos' => 9, 'id_factura' => 300, 'cedula' => 'V-1', 'cliente' => 'Carlos', 'fecha_despacho' => '2025-10-22', 'estado' => 'Pendiente', 'activo' => 1],
                ],
            ]);
        }
        // Productos de la factura (ambas rutas)
        if (stripos($sql, 'FROM tbl_factura_detalle AS d') !== false && stripos($sql, 'WHERE d.factura_id =') !== false) {
            return new StatementDoubleODF($sql, [
                'rows' => [
                    ['imagen' => null, 'codigo' => 1, 'producto' => 'ProdX', 'modelo' => 'M1', 'marca' => 'Marca', 'serial' => 'S1', 'cantidad' => 2, 'id_detalle' => 11, 'precio_unitario' => 5.0, 'subtotal' => 10.0],
                ],
            ]);
        }
        // Detalles compra (tbl_despacho_detalle)
        if (stripos($sql, 'FROM tbl_despacho_detalle d') !== false) {
            return new StatementDoubleODF($sql, [
                'rows' => [
                    ['nombre_producto' => 'ProdY', 'cantidad' => 1, 'precio' => 3.0, 'subtotal' => 3.0],
                ],
            ]);
        }
        // Updates
        if (stripos($sql, 'UPDATE tbl_orden_despachos SET estado =') !== false) {
            return new StatementDoubleODF($sql);
        }
        if (stripos($sql, 'UPDATE tbl_orden_despachos SET activo = 0') !== false) {
            return new StatementDoubleODF($sql);
        }
        if (stripos($sql, 'UPDATE tbl_usuarios SET estatus =') !== false) {
            return new StatementDoubleODF($sql);
        }

        return new StatementDoubleODF($sql);
    }
}

// ================================
// Casos de prueba (Integración)
// ================================
final class OrdenDespachoFeatureTest extends TestCase
{
    // Helper: crea `OrdenDespacho` con PDO stub
    private function nuevaODConPDOStub(): OrdenDespacho
    {
        $ref = new ReflectionClass(OrdenDespacho::class);
        /** @var OrdenDespacho $o */
        $o = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleODF();
        $prop = new ReflectionProperty(OrdenDespacho::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($o, $pdo);
        return $o;
    }

    // ODF-FEAT-001: Facturas disponibles
    public function testFacturasDisponiblesIntegracion(): void
    {
        $o = $this->nuevaODConPDOStub();
        $rows = $o->obtenerFacturasDisponibles();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('id_factura', $rows[0]);
    }

    // ODF-FEAT-002: Obtener orden por ID
    public function testObtenerOrdenPorIdIntegracion(): void
    {
        $o = $this->nuevaODConPDOStub();
        $row = $o->obtenerOrdenPorId(5);
        $this->assertIsArray($row);
        $this->assertSame(5, $row['id_orden_despachos']);
    }

    // ODF-FEAT-003: Listar ordenes con productos
    public function testGetOrdenDespachoListadoIntegracion(): void
    {
        $o = $this->nuevaODConPDOStub();
        $list = $o->getordendespacho();
        $this->assertIsArray($list);
        $this->assertNotEmpty($list);
        $this->assertArrayHasKey('productos', $list[0]);
        $this->assertNotEmpty($list[0]['productos']);
    }

    // ODF-FEAT-004: Descargar orden por ID
    public function testDescargarOrdenDespachoIntegracion(): void
    {
        $o = $this->nuevaODConPDOStub();
        $rows = $o->DescargarOrdenDespacho(9);
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('productos', $rows[0]);
    }

    // ODF-FEAT-005: Detalles de compra por despacho
    public function testGetDetallesCompraIntegracion(): void
    {
        $o = $this->nuevaODConPDOStub();
        $det = $o->getDetallesCompra(1);
        $this->assertIsArray($det);
        $this->assertArrayHasKey('productos', $det);
        $this->assertNotEmpty($det['productos']);
    }

    // ODF-FEAT-006: Cambiar estado
    public function testCambiarEstadoOrdenIntegracion(): void
    {
        $o = $this->nuevaODConPDOStub();
        $this->assertTrue($o->cambiarEstadoOrden(9, 'Despachado'));
    }

    // ODF-FEAT-007: Anular orden (activo=0)
    public function testAnularOrdenDespachoIntegracion(): void
    {
        $o = $this->nuevaODConPDOStub();
        $res = $o->anularOrdenDespacho(9);
        $this->assertIsArray($res);
        $this->assertSame('success', $res['status']);
    }

    // ODF-FEAT-008: Cambiar estatus (usuarios)
    public function testCambiarEstatusUsuariosIntegracion(): void
    {
        $o = $this->nuevaODConPDOStub();
        $o->setId(123);
        $this->assertTrue($o->cambiarEstatus('Activo'));
    }
}
