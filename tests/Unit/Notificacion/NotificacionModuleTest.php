<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../modelo/notificacion.php';

// Dobles simples para simular PDO y PDOStatement
class FakeStmt
{
    public string $sql;
    public array $bound = [];
    public array $executedWith = [];
    public function __construct(string $sql) { $this->sql = $sql; }
    public function bindParam($param, &$var, $type = null) { $this->bound[$param] = $var; return true; }
    public function execute($params = null) { $this->executedWith[] = $params; return true; }
}
class FakePDO
{
    public ?FakeStmt $lastStmt = null;
    public function prepare($sql) { $this->lastStmt = new FakeStmt($sql); return $this->lastStmt; }
}

final class NotificacionModuleTest extends TestCase
{
    public function testCrearInsertaConParametrosYEjecuta(): void
    {
        $fake = new FakePDO();
        $m = new NotificacionModel($fake);
        $ok = $m->crear(1, 'tipoX', 'Titulo', 'Mensaje', 'media', 15, 'ACCION', 123);
        $this->assertTrue($ok);
        $this->assertNotNull($fake->lastStmt);
        $this->assertStringContainsString('INSERT INTO tbl_notificaciones', $fake->lastStmt->sql);
        $this->assertSame('tipoX', $fake->lastStmt->bound[':tipo'] ?? null);
        $this->assertSame('Titulo', $fake->lastStmt->bound[':titulo'] ?? null);
        $this->assertSame('Mensaje', $fake->lastStmt->bound[':mensaje'] ?? null);
        $this->assertSame(123, $fake->lastStmt->bound[':id_referencia'] ?? null);
        $this->assertSame('media', $fake->lastStmt->bound[':prioridad'] ?? null);
        $this->assertSame(15, $fake->lastStmt->bound[':id_modulo'] ?? null);
        $this->assertSame('ACCION', $fake->lastStmt->bound[':accion'] ?? null);
    }

    public function testMarcarComoLeidoEjecutaUpdate(): void
    {
        $fake = new FakePDO();
        $m = new NotificacionModel($fake);
        $ok = $m->marcarComoLeido(55);
        $this->assertTrue($ok);
        $this->assertNotNull($fake->lastStmt);
        $this->assertStringContainsString('UPDATE tbl_notificaciones SET leido = 1', $fake->lastStmt->sql);
        // Verifica que se llamó execute con arreglo que contenga el id
        $this->assertNotEmpty($fake->lastStmt->executedWith);
        $this->assertSame([55], $fake->lastStmt->executedWith[0] ?? []);
    }

    public function testNotificarPagoLanzaErrorPorFirmaCrear(): void
    {
        $this->expectException(\ArgumentCountError::class);
        $fake = new FakePDO();
        $m = new NotificacionModel($fake);
        // La implementación actual llama crear() con menos parámetros que los requeridos
        $m->notificarPago(1, 10, 'procesado');
    }

    public function testNotificarDespachoLanzaErrorPorFirmaCrear(): void
    {
        $this->expectException(\ArgumentCountError::class);
        $fake = new FakePDO();
        $m = new NotificacionModel($fake);
        $m->notificarDespacho(1, 77, 'enviado');
    }
}
