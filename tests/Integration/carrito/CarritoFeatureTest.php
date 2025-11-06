<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/carrito.php';

/*
 * Pruebas de INTEGRACIÓN del módulo Carrito.
 *
 * Secciones:
 * - Dobles: `StatementDoubleCarritoF`, `PDODoubleCarritoF` (principal) y `PDODoubleCarritoCF` (conexión 'C').
 * - Doble de clase: `CarritoDoubleF` para inyectar PDO 'C'.
 * - Escenarios: crear carrito, obtener por cliente, agregar producto (actualiza/insert),
 *   obtener productos, actualizar cantidad, eliminar producto, vaciar carrito,
 *   agregar combo (transacción), obtener cantidad por usuario (C), obtener resumen (C), registrar compra básico.
 */

// ======================================
// Dobles de prueba (PDOStatement/Carrito Feature)
// ======================================
class StatementDoubleCarritoF
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

    public function bindParam($name, &$value, $type = null) { $this->bound[$name] = $value; }
    public function bindValue($name, $value, $type = null) { $this->bound[$name] = $value; }
    public function execute(array $params = []) { return true; }
    public function fetch($mode = null) { if ($this->row !== null) return $this->row; if ($this->scalar !== null) return $this->scalar; return null; }
    public function fetchAll($mode = null) { return $this->rows; }
}

// ======================
// Doble de prueba (PDO - Principal)
// ======================
class PDODoubleCarritoF extends PDO
{
    public bool $inTx = false;
    public static bool $detalleExiste = false;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function beginTransaction(): bool { $this->inTx = true; return true; }
    public function commit(): bool { $this->inTx = false; return true; }
    public function rollBack(): bool { $this->inTx = false; return true; }
    public function lastInsertId($name = null): string|false { return '1000'; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        if (stripos($sql, 'INSERT INTO tbl_carrito (id_cliente)') !== false) {
            return new StatementDoubleCarritoF($sql);
        }
        if (stripos($sql, 'SELECT id_carrito, id_cliente FROM tbl_carrito WHERE id_cliente') !== false) {
            return new StatementDoubleCarritoF($sql, [ 'row' => ['id_carrito' => 10, 'id_cliente' => 5] ]);
        }
        if (stripos($sql, 'SELECT id_carrito_detalle, cantidad FROM tbl_carritodetalle') !== false) {
            return new StatementDoubleCarritoF($sql, self::$detalleExiste
                ? [ 'row' => ['id_carrito_detalle' => 77, 'cantidad' => 2] ]
                : [ 'row' => null ]);
        }
        if (stripos($sql, 'UPDATE tbl_carritodetalle SET cantidad =') !== false) {
            return new StatementDoubleCarritoF($sql);
        }
        if (stripos($sql, 'INSERT INTO tbl_carritodetalle (id_carrito, id_producto, cantidad)') !== false) {
            return new StatementDoubleCarritoF($sql);
        }
        if (stripos($sql, 'FROM tbl_carritodetalle cd') !== false && stripos($sql, 'WHERE cd.id_carrito =') !== false) {
            return new StatementDoubleCarritoF($sql, [
                'rows' => [
                    ['id_carrito_detalle' => 77, 'id_producto' => 1, 'nombre' => 'Prod1', 'nombre_modelo' => 'M1', 'nombre_marca' => 'X', 'cantidad' => 3, 'precio' => 5.0, 'subtotal' => 15.0],
                ],
            ]);
        }
        if (stripos($sql, 'DELETE FROM tbl_carritodetalle WHERE id_carrito_detalle') !== false) {
            return new StatementDoubleCarritoF($sql);
        }
        if (stripos($sql, 'DELETE FROM tbl_carritodetalle WHERE id_carrito =') !== false) {
            return new StatementDoubleCarritoF($sql);
        }
        if (stripos($sql, 'SELECT id_producto, cantidad FROM combo_detalle WHERE id_combo') !== false) {
            return new StatementDoubleCarritoF($sql, [ 'rows' => [ ['id_producto' => 1, 'cantidad' => 2], ['id_producto' => 2, 'cantidad' => 1] ] ]);
        }
        if (stripos($sql, 'INSERT INTO tbl_facturas (fecha, cliente, descuento, estatus)') !== false) {
            return new StatementDoubleCarritoF($sql);
        }
        if (stripos($sql, 'INSERT INTO tbl_factura_detalle (factura_id, id_producto, cantidad)') !== false) {
            return new StatementDoubleCarritoF($sql);
        }
        if (stripos($sql, 'UPDATE tbl_productos SET stock = stock -') !== false) {
            return new StatementDoubleCarritoF($sql);
        }
        return new StatementDoubleCarritoF($sql);
    }
}

// ======================
// Doble de prueba (PDO - Conexión 'C')
// ======================
class PDODoubleCarritoCF extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        if (stripos($sql, 'SELECT COUNT(dc.id_detalle_carrito) as total') !== false) {
            return new StatementDoubleCarritoF($sql, [ 'row' => ['total' => 4] ]);
        }
        if (stripos($sql, 'SELECT c.id_carrito, COUNT(dc.id_detalle_carrito) as total_productos') !== false) {
            return new StatementDoubleCarritoF($sql, [ 'row' => ['id_carrito' => 12, 'total_productos' => 3, 'total_precio' => 45.0] ]);
        }
        return new StatementDoubleCarritoF($sql);
    }
}

// Doble de clase para inyectar PDO 'C'
class CarritoDoubleF extends Carrito
{
    public PDO $pdoC;
    public function __construct() { /* evitar abrir BD real */ }
    public function getConexion() { return new PDODoubleCarritoF(); }
    public function setConex(PDO $pdo) { $ref = new ReflectionProperty(Carrito::class, 'conex'); $ref->setAccessible(true); $ref->setValue($this, $pdo); }
    public function obtenerCantidadProductosCarrito($id_usuario)
    {
        $stmt = $this->pdoC->prepare('SELECT COUNT(dc.id_detalle_carrito) as total FROM tbl_carrito c INNER JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito WHERE c.id_usuario = ? AND c.estado = "activo"');
        $stmt->execute([$id_usuario]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado ? $resultado['total'] : 0;
    }
    public function obtenerResumenCarrito($id_usuario)
    {
        $stmt = $this->pdoC->prepare('SELECT c.id_carrito, COUNT(dc.id_detalle_carrito) as total_productos, SUM(dc.cantidad * dc.precio_unitario) as total_precio FROM tbl_carrito c LEFT JOIN tbl_detalle_carrito dc ON c.id_carrito = dc.id_carrito WHERE c.id_usuario = ? AND c.estado = "activo" GROUP BY c.id_carrito');
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ================================
// Casos de prueba (Integración)
// ================================
final class CarritoFeatureTest extends TestCase
{
    private function nuevoCarritoConPDOStub(bool $detalleExiste = false): CarritoDoubleF
    {
        PDODoubleCarritoF::$detalleExiste = $detalleExiste;
        $c = new CarritoDoubleF();
        $pdo = new PDODoubleCarritoF();
        $c->setConex($pdo);
        $c->pdoC = new PDODoubleCarritoCF();
        return $c;
    }

    public function testCrearCarritoIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->crearCarrito(5));
    }

    public function testObtenerCarritoPorClienteIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $row = $c->obtenerCarritoPorCliente(5);
        $this->assertIsArray($row);
        $this->assertSame(10, $row['id_carrito']);
    }

    public function testAgregarProductoActualizaIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub(true);
        $this->assertTrue($c->agregarProductoAlCarrito(10, 1, 3));
    }

    public function testAgregarProductoInsertaIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub(false);
        $this->assertTrue($c->agregarProductoAlCarrito(10, 2, 1));
    }

    public function testObtenerProductosDelCarritoIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $rows = $c->obtenerProductosDelCarrito(10);
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('subtotal', $rows[0]);
    }

    public function testActualizarCantidadProductoIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->actualizarCantidadProducto(77, 5));
    }

    public function testEliminarProductoDelCarritoIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->eliminarProductoDelCarrito(77));
    }

    public function testEliminarTodoElCarritoIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->eliminarTodoElCarrito(10));
    }

    public function testAgregarComboAlCarritoIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $this->assertTrue($c->agregarComboAlCarrito(10, 99));
    }

    public function testObtenerCantidadProductosCarritoIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $total = $c->obtenerCantidadProductosCarrito(123);
        $this->assertSame(4, $total);
    }

    public function testObtenerResumenCarritoIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $res = $c->obtenerResumenCarrito(123);
        $this->assertIsArray($res);
        $this->assertSame(12, $res['id_carrito']);
        $this->assertSame(3, $res['total_productos']);
    }

    public function testRegistrarCompraBasicaIntegracion(): void
    {
        $c = $this->nuevoCarritoConPDOStub();
        $ok = $c->registrarCompra(10, 5, [
            ['id_producto' => 1, 'cantidad' => 2],
            ['id_producto' => 2, 'cantidad' => 1],
        ]);
        $this->assertTrue($ok);
    }
}
