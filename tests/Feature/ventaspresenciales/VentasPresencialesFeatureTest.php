<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/comprafisica.php';

/*
 * Pruebas de INTEGRACIÓN del módulo de Ventas Presenciales.
 *
 * Secciones:
 * - Dobles de prueba: `StatementDoubleVP` y `PDODoubleVP` (simulan PDO/Statement y transacciones).
 * - Escenarios: registrar y consultar compra, múltiples pagos, inválida con rollback y campos vacíos.
 *
 * Notas:
 * - Se inyecta el stub de PDO en `Compra` vía reflexión para ejercer el flujo completo sin BD real.
 */

// ======================================
// Dobles de prueba (PDOStatement/VP)
// ======================================
class StatementDoubleVP
{
    private $sql;
    private $rows;
    private $row;
    private $throwOnExecute = false;
    private $bound = [];

    public function __construct(string $sql, array $data = [])
    {
        $this->sql = $sql;
        $this->rows = $data['rows'] ?? [];
        $this->row = $data['row'] ?? null;
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
        if (stripos($this->sql, 'INSERT INTO tbl_despachos') !== false) {
            $idCliente = $params[':id_cliente'] ?? $params[0] ?? ($this->bound[':id_cliente'] ?? null);
            if ($idCliente === null || $idCliente === '') {
                throw new Exception('Cliente requerido');
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
}

// ======================
// Doble de prueba (PDO)
// ======================
class PDODoubleVP extends PDO
{
    public static $throwOnDetalleInsert = false;
    private $lastId = 1;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function rollBack(): bool { return true; }
    public function inTransaction(): bool { return true; }
    public function lastInsertId($name = null): string|false { return (string)$this->lastId; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        if (stripos($sql, 'INSERT INTO tbl_despachos') !== false) {
            $this->lastId = 10;
            return new StatementDoubleVP($sql);
        }
        if (stripos($sql, 'INSERT INTO tbl_despacho_detalle') !== false) {
            return new StatementDoubleVP($sql, [
                'throwOnExecute' => self::$throwOnDetalleInsert,
            ]);
        }
        if (stripos($sql, 'FROM tbl_productos p') !== false && stripos($sql, 'INNER JOIN tbl_modelos') !== false) {
            return new StatementDoubleVP($sql, [
                'row' => [
                    'id_producto' => 1,
                    'nombre_producto' => 'Producto X',
                    'nombre_modelo' => 'M1',
                    'nombre_marca' => 'Marca',
                    'serial' => 'ABC123',
                    'precio' => 10.0,
                ],
            ]);
        }
        if (stripos($sql, 'INSERT INTO tbl_facturas') !== false) {
            $this->lastId = 100;
            return new StatementDoubleVP($sql);
        }
        if (stripos($sql, 'INSERT INTO tbl_factura_detalle') !== false) {
            return new StatementDoubleVP($sql);
        }
        if (stripos($sql, 'FROM tbl_clientes') !== false) {
            return new StatementDoubleVP($sql, [
                'row' => [
                    'id_clientes' => 5,
                    'nombre' => 'Cliente Demo',
                    'cedula' => 'V-12345678',
                    'telefono' => '0414-0000000',
                    'correo' => 'demo@correo.test',
                ],
            ]);
        }
        if (stripos($sql, 'INSERT INTO tbl_detalles_pago') !== false) {
            return new StatementDoubleVP($sql);
        }
        if (stripos($sql, 'FROM tbl_facturas f') !== false && stripos($sql, 'GROUP BY f.id_factura') !== false) {
            return new StatementDoubleVP($sql, [
                'rows' => [[
                    'id_factura' => 123,
                    'fecha_factura' => date('Y-m-d'),
                    'descuento' => 0,
                    'id_clientes' => 5,
                    'cedula' => 'V-12345678',
                    'nombre_cliente' => 'Cliente Demo',
                    'direccion' => 'Av. Siempre Viva',
                    'telefono' => '0414-0000000',
                    'correo' => 'demo@correo.test',
                    'id_despachos' => 77,
                    'productos' => '{"id_producto":"1","codigo":"1","nombre":"Producto X","descripcion":"Desc","modelo":"M1","marca":"Marca","serial":"ABC123","precio":"10","cantidad":"2"}',
                    'pagos' => '{"id_detalles":"1","cuenta":"2","referencia":"ABC","fecha":"2025-10-20","tipo":"Zelle","monto":"5.00","comprobante":"","estatus":"Aprobado","observaciones":""}',
                ]],
            ]);
        }
        return new StatementDoubleVP($sql);
    }
}

// ================================
// Casos de prueba (Integración)
// ================================
final class VentasPresencialesFeatureTest extends TestCase
{
    // Helper: crea instancia de `Compra` con PDO stub
    private function nuevaCompra(): Compra
    {
        $ref = new ReflectionClass(Compra::class);
        $c = $ref->newInstanceWithoutConstructor();
        $pdo = new PDODoubleVP();
        $prop = new ReflectionProperty(Compra::class, 'conex');
        $prop->setAccessible(true);
        $prop->setValue($c, $pdo);
        return $c;
    }

    // VP-FEAT-001: Registrar y consultar compra
    // Objetivo: registrar una compra y luego consultar lista con `g_Compras()`
    public function testRegistrarYConsultarCompra(): void
    {
        $c = $this->nuevaCompra();
        $datos = [
            'cliente' => 5,
            'productos' => [
                ['id_producto' => 1, 'cantidad' => '2'],
            ],
            'pagos' => [
                ['tipo' => 'Efectivo', 'cuenta' => 0, 'referencia' => '', 'monto' => 20.0],
            ],
        ];
        $res = $c->registrarCompraFisica($datos);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('id_factura', $res);
        $this->assertGreaterThan(0, (int)$res['id_factura']);

        $metodo = new ReflectionMethod(Compra::class, 'g_Compras');
        $metodo->setAccessible(true);
        $list = $metodo->invoke($c);
        $this->assertIsArray($list);
        $this->assertNotEmpty($list);
        $this->assertArrayHasKey('id_factura', $list[0]);
    }

    // VP-FEAT-002: Compra con múltiples pagos
    // Objetivo: validar que múltiples métodos de pago se reflejen en la respuesta
    public function testCompraMultiplesPagosIntegracion(): void
    {
        $c = $this->nuevaCompra();
        $datos = [
            'cliente' => 5,
            'productos' => [
                ['id_producto' => 1, 'cantidad' => '3'],
            ],
            'pagos' => [
                ['tipo' => 'Efectivo', 'cuenta' => 0, 'referencia' => '', 'monto' => 10.0],
                ['tipo' => 'Zelle', 'cuenta' => 2, 'referencia' => 'ABC', 'monto' => 5.0],
                ['tipo' => 'Pago Movil', 'cuenta' => 3, 'referencia' => 'PM123', 'monto' => 15.0],
            ],
        ];
        $res = $c->registrarCompraFisica($datos);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('pagos', $res);
        $this->assertCount(3, $res['pagos']);
    }

    // VP-FEAT-003: Compra inválida (rollback)
    // Objetivo: forzar error en detalle de despacho y esperar `status=error`
    public function testCompraInvalidaRollbackIntegracion(): void
    {
        $c = $this->nuevaCompra();
        PDODoubleVP::$throwOnDetalleInsert = true;
        try {
            $res = $c->registrarCompraFisica([
                'cliente' => 5,
                'productos' => [['id_producto' => 1, 'cantidad' => '1']],
                'pagos' => [],
            ]);
        } finally {
            PDODoubleVP::$throwOnDetalleInsert = false;
        }
        $this->assertIsArray($res);
        $this->assertArrayHasKey('status', $res);
        $this->assertSame('error', $res['status']);
    }

    // VP-FEAT-004: Campos vacíos (cliente requerido)
    // Objetivo: validar manejo de cliente vacío en flujo de registro
    public function testCompraCamposVaciosIntegracion(): void
    {
        $c = $this->nuevaCompra();
        $res = $c->registrarCompraFisica([
            'cliente' => '',
            'productos' => [['id_producto' => 1, 'cantidad' => '1']],
            'pagos' => [],
        ]);
        $this->assertIsArray($res);
        $this->assertArrayHasKey('status', $res);
        $this->assertSame('error', $res['status']);
    }
}
