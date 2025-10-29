<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Modelo/producto.php';

class ProductosCrudTest extends TestCase {
    private PDO $pdo;

    protected function setUp(): void {
        $this->pdo = test_pdo();
        if (!$this->tablaCategoriaDisponible()) {
            $this->markTestSkipped('No hay categorías disponibles en BD para probar Productos.');
        }
        if (!$this->modeloDisponible()) {
            $this->markTestSkipped('No hay modelos disponibles en BD para probar Productos.');
        }
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function primeraCategoria(): ?array {
        $stmt = $this->pdo->query('SELECT id_categoria, nombre_categoria FROM tbl_categoria LIMIT 1');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function tablaCategoriaDisponible(): bool {
        return (bool)$this->primeraCategoria();
    }

    private function modeloDisponible(): bool {
        $stmt = $this->pdo->query('SELECT id_modelo FROM tbl_modelos LIMIT 1');
        return (bool)$stmt->fetchColumn();
    }

    private function tablaCategoriaDesdeNombre(string $nombre): string {
        return 'cat_' . strtolower(str_replace(' ', '_', $nombre));
    }

    private function crearProductoBasico(): array {
        $cat = $this->primeraCategoria();
        $tablaCategoria = $this->tablaCategoriaDesdeNombre($cat['nombre_categoria']);
        $idModelo = (int)$this->pdo->query('SELECT id_modelo FROM tbl_modelos LIMIT 1')->fetchColumn();

        $p = new Productos();
        $p->setCodigo('TST-' . uniqid());
        $p->setNombreP('Producto PHPUnit ' . uniqid());
        $p->setDescripcionP('Desc de prueba');
        $p->setIdModelo($idModelo);
        $p->setStockActual(10);
        $p->setStockMin(1);
        $p->setStockMax(100);
        $p->setClausulaDeGarantia('12m');
        $p->setPrecio(9.99);

        return [$p, $tablaCategoria];
    }

    public function test_incluir_modificar_listar_eliminar_producto(): void {
        [$p, $tablaCategoria] = $this->crearProductoBasico();

        
        $idProducto = $p->ingresarProducto([
            'tabla_categoria' => $tablaCategoria,
            'carac' => []
        ]);
        $this->assertIsNumeric($idProducto, 'Se debe crear y devolver id de producto.');

        
        $row = $p->obtenerProductoPorId((int)$idProducto);
        $this->assertIsArray($row);
        $this->assertEquals($idProducto, $row['id_producto']);

        
        $p->setNombreP($row['nombre_producto'] . ' Editado');
        $p->setStockActual(((int)$row['stock']) + 5);
        $ok = $p->modificarProducto((int)$idProducto, [
            'Categoria' => $tablaCategoria,
            'carac' => []
        ]);
        $this->assertTrue($ok, 'Debe poder modificar el producto.');

       
        $lista = $p->obtenerProductoStock();
        $this->assertIsArray($lista);

       
        $res = $p->eliminarProducto((int)$idProducto);
        $this->assertIsArray($res);
        $this->assertTrue($res['success'] ?? false, $res['message'] ?? 'Eliminación fallida');
    }
}
