<?php
use PHPUnit\Framework\TestCase;

final class CategoriaCrudTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = test_pdo();
    }

    public function testExisteAlgunaCategoria(): void
    {
        try {
            $stmt = $this->pdo->query('SELECT id_categoria, nombre_categoria FROM tbl_categoria LIMIT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('Tabla tbl_categoria no disponible: ' . $e->getMessage());
            return;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row) {
            $this->markTestSkipped('No hay categorías de ejemplo en la base de datos.');
            return;
        }
        $this->assertArrayHasKey('id_categoria', $row);
        $this->assertArrayHasKey('nombre_categoria', $row);
    }
}
