<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../modelo/Recepcion.php';

final class RecepcionModuleTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        $this->limpiarTablas();
        $this->seedBasico();
    }

    private function limpiarTablas(): void
    {
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0'); } catch (Throwable $e) {}
        
        $tablas = [
            'tbl_ingresos_egresos',
            'tbl_detalle_recepcion_productos',
            'tbl_recepcion_productos',
            'tbl_productos',
            'tbl_modelos',
            'tbl_marcas',
            'tbl_proveedores',
        ];
        foreach ($tablas as $t) {
            try { $this->pdo->exec("TRUNCATE TABLE {$t}"); } catch (Throwable $e) {}
        }
        try { $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1'); } catch (Throwable $e) {}
    }

    private function seedBasico(): void
    {
        $this->pdo->prepare("INSERT INTO tbl_proveedores (nombre_proveedor) VALUES ('Proveedor Test')")->execute();
        
        $this->pdo->prepare("INSERT INTO tbl_marcas (nombre_marca) VALUES ('Marca T')")->execute();
        $id_marca = (int)$this->pdo->lastInsertId();
        $stmt = $this->pdo->prepare("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('Modelo T', :id_marca)");
        $stmt->execute([':id_marca' => $id_marca]);
        $id_modelo = (int)$this->pdo->lastInsertId();
        
        $stmt = $this->pdo->prepare("INSERT INTO tbl_productos (nombre_producto, id_modelo, serial) VALUES ('Prod T', :id_modelo, 'S-001')");
        $stmt->execute([':id_modelo' => $id_modelo]);
    }

    private function getPrimerProveedorId(): int
    {
        return (int)$this->pdo->query('SELECT id_proveedor FROM tbl_proveedores LIMIT 1')->fetchColumn();
    }

    private function getPrimerProductoId(): int
    {
        return (int)$this->pdo->query('SELECT id_producto FROM tbl_productos LIMIT 1')->fetchColumn();
    }

    public function testListadoProductosDevuelveHTML(): void
    {
        $r = new Recepcion();
        $resp = $r->listadoproductos();
        $this->assertIsArray($resp);
        $this->assertSame('listado', $resp['resultado'] ?? null);
        $this->assertIsString($resp['mensaje'] ?? '');
        $this->assertStringContainsString('<tr', $resp['mensaje']);
    }

    public function testRegistrarRecepcionYObtenerUltimaYExisteCorrelativo(): void
    {
        $r = new Recepcion();
        $r->setidproveedor($this->getPrimerProveedorId());
        $r->setcorrelativo('123456');
        $r->settamanocompra('Mediano');
        $r->setestado('habilitado');

        $idProd = $this->getPrimerProductoId();
        $res = $r->registrarRecepcion([$idProd], [2], [50.00]);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('id_recepcion', $res);
        $this->assertArrayHasKey('recepcion', $res);
        $this->assertSame('123456', $res['recepcion']['correlativo']);

        $ultima = $r->obtenerUltimaRecepcion();
        $this->assertIsArray($ultima);
        $this->assertSame('123456', $ultima['correlativo']);

        $this->assertTrue($r->existeCorrelativo('123456'));
        $this->assertFalse($r->existeCorrelativo('999999'));

        // Verificar que se creó asiento en ingresos/egresos
        $cnt = (int)$this->pdo->query("SELECT COUNT(*) FROM tbl_ingresos_egresos")->fetchColumn();
        $this->assertGreaterThanOrEqual(1, $cnt, 'Debe crear egreso asociado a la recepción');
    }

    public function testObtenerProductosPorRecepcion(): void
    {
        // Prepara recepción
        $r = new Recepcion();
        $r->setidproveedor($this->getPrimerProveedorId());
        $r->setcorrelativo('200001');
        $r->settamanocompra('Pequeño');
        $r->setestado('habilitado');
        $idProd = $this->getPrimerProductoId();
        $res = $r->registrarRecepcion([$idProd], [3], [10.00]);

        $items = $r->obtenerProductosPorRecepcion($res['id_recepcion']);
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);
        $this->assertArrayHasKey('codigo', $items[0]);
        $this->assertArrayHasKey('cantidad', $items[0]);
        $this->assertArrayHasKey('costo', $items[0]);
    }

    public function testGetRecepcionListaSoloHabilitados(): void
    {
        $r = new Recepcion();
        // Habilitada
        $r->setidproveedor($this->getPrimerProveedorId());
        $r->setcorrelativo('300001');
        $r->settamanocompra('Grande');
        $r->setestado('habilitado');
        $idProd = $this->getPrimerProductoId();
        $r->registrarRecepcion([$idProd], [1], [5.00]);

        // Otra y luego anularla
        $r2 = new Recepcion();
        $r2->setidproveedor($this->getPrimerProveedorId());
        $r2->setcorrelativo('300002');
        $r2->settamanocompra('Grande');
        $r2->setestado('habilitado');
        $r2->registrarRecepcion([$idProd], [1], [5.00]);
        $r2->anularRecepcion('300002');

        $lista = $r->getrecepcion();
        $this->assertIsArray($lista);
        // Debe listar solo la habilitada
        $correlativos = array_map(fn($x) => $x['correlativo'], $lista);
        $this->assertContains('300001', $correlativos);
        $this->assertNotContains('300002', $correlativos);
    }

    public function testAnularRecepcionCambiaEstado(): void
    {
        $r = new Recepcion();
        $r->setidproveedor($this->getPrimerProveedorId());
        $r->setcorrelativo('400001');
        $r->settamanocompra('Mediano');
        $r->setestado('habilitado');
        $idProd = $this->getPrimerProductoId();
        $r->registrarRecepcion([$idProd], [1], [1.00]);

        $res = $r->anularRecepcion('400001');
        $this->assertSame('success', $res['status'] ?? null);

        $estado = $this->pdo->query("SELECT estado FROM tbl_recepcion_productos WHERE correlativo='400001' LIMIT 1")->fetchColumn();
        $this->assertSame('anulado', $estado);
    }
}
