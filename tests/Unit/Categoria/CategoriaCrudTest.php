<?php
use PHPUnit\Framework\TestCase;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria;

final class CategoriaCrudTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
        try {
            $this->pdo->query('SELECT 1 FROM tbl_categoria LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Tabla tbl_categoria no disponible: ' . $e->getMessage());
            return;
        }
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function crearCategoriaBasica(): array
    {
        $c = new Categoria();
        $nombre = 'CatPHP ' . substr(uniqid(), 0, 8);
        $c->setNombreCategoria($nombre);

        $caracteristicas = [
            ['nombre' => 'campo_int', 'tipo' => 'int'],
            ['nombre' => 'campo_float', 'tipo' => 'float'],
            ['nombre' => 'campo_texto', 'tipo' => 'string', 'max' => 50],
        ];

        $ok = $c->registrarCategoria($caracteristicas);
        $this->assertTrue($ok, 'Debe poder registrar la categoría.');

        $ultima = $c->obtenerUltimoCategoria();
        $this->assertIsArray($ultima);
        $this->assertStringStartsWith('CatPHP ', $ultima['nombre_categoria']);

        $idCategoria = (int)$ultima['id_categoria'];
        $nombreGuardado = $ultima['nombre_categoria'];

        return [$c, $idCategoria, $nombreGuardado, $caracteristicas];
    }

    public function testIncluirCategoria(): void
    {
        [$c, $idCategoria, $nombre] = $this->crearCategoriaBasica();

        $row = $c->obtenerCategoriaPorId($idCategoria);
        $this->assertIsArray($row);
        $this->assertEquals($idCategoria, (int)$row['id_categoria']);
        $this->assertEquals($nombre, $row['nombre_categoria']);
    }

    public function testListarCategoria(): void
    {
        [$c, $idCategoria] = $this->crearCategoriaBasica();

        $lista = $c->consultarCategorias();
        $this->assertIsArray($lista);
        $ids = array_column($lista, 'id_categoria');
        $this->assertContains($idCategoria, $ids);
    }

    public function testModificarCategoria(): void
    {
        [$c, $idCategoria, $nombre] = $this->crearCategoriaBasica();

        $nuevoNombre = 'CatMod ' . substr(uniqid(), 0, 5);
        $nuevasCaracteristicas = [
            ['nombre' => 'campo_int', 'tipo' => 'int'],
            ['nombre' => 'campo_texto', 'tipo' => 'string', 'max' => 100],
            ['nombre' => 'campo_extra', 'tipo' => 'string', 'max' => 50],
        ];

        $ok = $c->modificarCategoria($idCategoria, $nuevoNombre, $nuevasCaracteristicas);
        $this->assertTrue($ok, 'Debe poder modificar la categoría.');

        $row = $c->obtenerCategoriaPorId($idCategoria);
        $this->assertIsArray($row);
        $this->assertEquals($nuevoNombre, $row['nombre_categoria']);
    }

    public function testEliminarCategoria(): void
    {
        [$c, $idCategoria] = $this->crearCategoriaBasica();

        $res = $c->eliminarCategoria($idCategoria);
        $this->assertIsArray($res);
        $this->assertSame('success', $res['status'] ?? null);

        $row = $c->obtenerCategoriaPorId($idCategoria);
        $this->assertNull($row);
    }
}
