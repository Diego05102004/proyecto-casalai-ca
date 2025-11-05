<?php
namespace Tests\Unit\Notificacion;

use PHPUnit\Framework\TestCase;

// Cargar la clase NotificacionModel después de definir el namespace
require_once __DIR__ . '/../../../modelo/notificacion.php';

// Clase doble para BD que coincide con la implementación real
class BD {
    private $pdo = null;
    public static ?FakePDO $lastFakePdo = null;
    
    public function __construct($tipo = 'P') {
        // En pruebas, simplemente creamos un FakePDO
        self::$lastFakePdo = new FakePDO();
        $this->pdo = self::$lastFakePdo;
    }
    
    public function getConexion() {
        return $this->pdo;
    }
    
    public function cerrar() {
        // No hacer nada en la prueba
        $this->pdo = null;
    }
}

// Dobles simples para simular PDO y PDOStatement
class FakeStmt
{
    public string $sql;
    public array $bound = [];
    public array $executedWith = [];
    public function __construct(string $sql) { $this->sql = $sql; }
    public function bindParam($param, &$var, $type = null) { $this->bound[$param] = $var; return true; }
    public function execute($params = null) { $this->executedWith[] = $params; return true; }
    public function fetchAll($fetchStyle = null) { return []; }
}

class FakePDO
{
    public ?FakeStmt $lastStmt = null;
    public function prepare($sql) { $this->lastStmt = new FakeStmt($sql); return $this->lastStmt; }
    public function lastInsertId() { return 1; }
}

final class NotificacionModuleTest extends TestCase
{
    public function testCrearInsertaConParametrosYEjecuta(): void
    {
        // Usar el nombre de clase completo con la barra invertida para el espacio de nombres global
        $m = new \NotificacionModel();
        
        // Obtener la instancia de FakePDO que se creó en el constructor
        $fakePdo = \Tests\Unit\Notificacion\BD::$lastFakePdo;
        $this->assertNotNull($fakePdo, 'No se creó la instancia de FakePDO');
        
        // Llamar al método a probar
        $ok = $m->crear(1, 'tipoX', 'Titulo', 'Mensaje', 'media', 15, 'ACCION', 123);
        
        // Verificaciones
        $this->assertTrue($ok);
        $this->assertNotNull($fakePdo->lastStmt);
        $this->assertStringContainsString('INSERT INTO tbl_notificaciones', $fakePdo->lastStmt->sql);
        $this->assertSame('tipoX', $fakePdo->lastStmt->bound[':tipo'] ?? null);
        $this->assertSame('Titulo', $fakePdo->lastStmt->bound[':titulo'] ?? null);
        $this->assertSame('Mensaje', $fakePdo->lastStmt->bound[':mensaje'] ?? null);
        $this->assertSame(123, $fakePdo->lastStmt->bound[':id_referencia'] ?? null);
        $this->assertSame('media', $fakePdo->lastStmt->bound[':prioridad'] ?? null);
        $this->assertSame(15, $fakePdo->lastStmt->bound[':id_modulo'] ?? null);
        $this->assertSame('ACCION', $fakePdo->lastStmt->bound[':accion'] ?? null);
    }

    public function testMarcarComoLeidaEjecutaUpdate(): void
    {
        // Usar el nombre de clase completo con la barra invertida para el espacio de nombres global
        $m = new \NotificacionModel();
        
        // Obtener la instancia de FakePDO que se creó en el constructor
        $fakePdo = \Tests\Unit\Notificacion\BD::$lastFakePdo;
        $this->assertNotNull($fakePdo, 'No se creó la instancia de FakePDO');
        
        // Llamar al método a probar
        $ok = $m->marcarComoLeida(55, 1);
        
        // Verificaciones
        $this->assertTrue($ok);
        $this->assertNotNull($fakePdo->lastStmt);
        $this->assertStringContainsString('UPDATE tbl_notificaciones', $fakePdo->lastStmt->sql);
        $this->assertStringContainsString('leido = 1', $fakePdo->lastStmt->sql);
        
        // Verificar que se llamó a bindParam con los parámetros correctos
        $this->assertSame(55, $fakePdo->lastStmt->bound[':id_notificacion'] ?? null);
        $this->assertSame(1, $fakePdo->lastStmt->bound[':id_usuario'] ?? null);
    }

    public function testNotificarPagoLanzaErrorPorFirmaCrear(): void
    {
        $this->markTestSkipped('Este test ya no es necesario ya que la implementación ha cambiado');
    }

    public function testNotificarDespachoLanzaErrorPorFirmaCrear(): void
    {
        $this->markTestSkipped('Este test ya no es necesario ya que la implementación ha cambiado');
    }
}