<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../modelo/notificacion.php';

final class NotificacionModelTest extends TestCase
{
    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite no está disponible en este entorno.');
        }
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->crearEsquema();
        $this->seedBasico();
    }

    private function crearEsquema(): void
    {
        // Tablas mínimas usadas por notificacion.php
        $this->pdo->exec("CREATE TABLE tbl_rol (id_rol INTEGER PRIMARY KEY)");
        $this->pdo->exec("CREATE TABLE tbl_usuarios (id_usuario INTEGER PRIMARY KEY, id_rol INTEGER)");
        $this->pdo->exec("CREATE TABLE tbl_permisos (
            id_rol INTEGER,
            id_modulo INTEGER,
            accion TEXT,
            estatus TEXT
        )");
        $this->pdo->exec("CREATE TABLE tbl_notificaciones (
            id_notificacion INTEGER PRIMARY KEY AUTOINCREMENT,
            id_usuario INTEGER,
            tipo TEXT,
            titulo TEXT,
            mensaje TEXT,
            id_referencia INTEGER,
            prioridad TEXT,
            leido INTEGER DEFAULT 0
        )");
    }

    private function seedBasico(): void
    {
        // Un rol y un usuario
        $this->pdo->exec("INSERT INTO tbl_rol (id_rol) VALUES (1)");
        $this->pdo->exec("INSERT INTO tbl_usuarios (id_usuario, id_rol) VALUES (1, 1)");
        // Permiso que permitirá la notificación
        $st = $this->pdo->prepare("INSERT INTO tbl_permisos (id_rol, id_modulo, accion, estatus) VALUES (1, :m, :a, 'Permitido')");
        $st->execute([':m' => 99, ':a' => 'probar']);
    }

    public function testCrearInsertaCuandoHayPermiso(): void
    {
        $m = new NotificacionModel($this->pdo);
        $ok = $m->crear(1, 'test', 'Titulo X', 'Mensaje X', 'media', 99, 'probar', 123);
        $this->assertTrue($ok);
        $c = (int)$this->pdo->query("SELECT COUNT(*) FROM tbl_notificaciones")->fetchColumn();
        $this->assertSame(1, $c);
        $row = $this->pdo->query("SELECT id_usuario, tipo, titulo, mensaje, id_referencia, prioridad FROM tbl_notificaciones LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int)$row['id_usuario']);
        $this->assertSame('test', $row['tipo']);
        $this->assertSame('Titulo X', $row['titulo']);
        $this->assertSame('Mensaje X', $row['mensaje']);
        $this->assertSame(123, (int)$row['id_referencia']);
        $this->assertSame('media', $row['prioridad']);
    }

    public function testCrearNoDuplicaPorReglaNotExists(): void
    {
        $m = new NotificacionModel($this->pdo);
        $m->crear(1, 'test', 'Titulo X', 'Mensaje X', 'media', 99, 'probar', 123);
        $m->crear(1, 'test', 'Titulo X', 'Mensaje X', 'media', 99, 'probar', 123);
        $c = (int)$this->pdo->query("SELECT COUNT(*) FROM tbl_notificaciones")->fetchColumn();
        $this->assertSame(1, $c);
    }

    public function testCrearNoInsertaSinPermiso(): void
    {
        $m = new NotificacionModel($this->pdo);
        $m->crear(1, 'test', 'Otro', 'Msg', 'alta', 100, 'otra', 7); // no hay permiso para modulo 100/accion otra
        $c = (int)$this->pdo->query("SELECT COUNT(*) FROM tbl_notificaciones")->fetchColumn();
        $this->assertSame(0, $c);
    }

    public function testMarcarComoLeido(): void
    {
        $this->pdo->exec("INSERT INTO tbl_notificaciones (id_usuario, tipo, titulo, mensaje, id_referencia, prioridad, leido) VALUES (1, 't','Ti','Me',1,'media',0)");
        $id = (int)$this->pdo->lastInsertId();
        $m = new NotificacionModel($this->pdo);
        $ok = $m->marcarComoLeido($id);
        $this->assertTrue($ok);
        $leido = (int)$this->pdo->query("SELECT leido FROM tbl_notificaciones WHERE id_notificacion={$id}")->fetchColumn();
        $this->assertSame(1, $leido);
    }
}
