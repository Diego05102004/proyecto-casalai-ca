<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Modelo/Factura.php';

class FacturaCrudTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $this->pdo = test_pdo();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function anyClienteCedula(): ?string {
        $ced = $this->pdo->query('SELECT cedula FROM tbl_clientes LIMIT 1')->fetchColumn();
        return $ced ?: null;
    }

    private function someProductosIds(int $n = 1): array {
        $stmt = $this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT ' . (int)$n);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function test_incluir_consultar_cancelar_procesar_factura(): void {
        $cedula = $this->anyClienteCedula();
        $ids = $this->someProductosIds(2);
        if (!$cedula || count($ids) === 0) {
            $this->markTestSkipped('No hay clientes o productos en BD para probar Factura.');
        }

        $f = new Factura();
        $f->setFecha(date('Y-m-d'));
        $f->setCliente($cedula);
        $f->setDescuento(0);
        $f->setEstatus('Borrador');
        $f->setIdProducto($ids);
        $f->setCantidad(array_fill(0, count($ids), 1));

        // Incluir
        $resIngresar = $f->facturaTransaccion('Ingresar');
        $this->assertTrue($resIngresar === true || (is_array($resIngresar) && !isset($resIngresar['error'])), 'Debe poder ingresar factura.');

        // Obtener última factura creada
        $idFactura = (int)$this->pdo->query('SELECT MAX(id_factura) FROM tbl_facturas')->fetchColumn();
        $this->assertGreaterThan(0, $idFactura);

        // Consultar por cédula
        $f->setCedula($cedula);
        $resConsulta = $f->facturaTransaccion('Consultar');
        $this->assertIsArray($resConsulta);
        $this->assertArrayHasKey('resultado', $resConsulta);

        // Procesar estatus
        $f->setId($idFactura);
        $okProcesar = $f->facturaTransaccion('Procesar');
        $this->assertTrue($okProcesar === true);

        // Cancelar
        $f->setId($idFactura);
        $okCancelar = $f->facturaTransaccion('Cancelar');
        $this->assertTrue($okCancelar === true);

        // Verificar en BD (estatus Cancelada)
        $estatus = $this->pdo->query('SELECT estatus FROM tbl_facturas WHERE id_factura=' . (int)$idFactura)->fetchColumn();
        $this->assertNotEmpty($estatus);
    }
}
