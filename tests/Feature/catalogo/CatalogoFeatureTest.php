<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/catalogo.php';

/*
 * Pruebas de INTEGRACIÓN reales del módulo Catálogo (sin mocks).
 */

final class CatalogoFeatureTest extends TestCase
{
    private Catalogo $catalogo;
    private ?int $comboId = null;
    private ?int $productoId = null;

    protected function setUp(): void
    {
        $this->catalogo = new Catalogo();
        $this->crearDatosBasicos();
    }

    protected function tearDown(): void
    {
        $this->limpiarDatos();
    }

    private function crearDatosBasicos(): void
    {
        $bd = new BD('P');
        $pdo = $bd->getConexion();
        try {
            // Crear marca y modelo
            $pdo->exec("INSERT INTO tbl_marcas (nombre_marca) VALUES ('MarcaCat_" . uniqid() . "')");
            $idMarca = (int)$pdo->lastInsertId();
            $pdo->exec("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('ModeloCat_" . uniqid() . "', {$idMarca})");
            $idModelo = (int)$pdo->lastInsertId();

            // Crear categoría
            $pdo->exec("INSERT INTO tbl_categoria (nombre_categoria) VALUES ('CategoriaCat_" . uniqid() . "')");
            $idCategoria = (int)$pdo->lastInsertId();

            // Crear producto habilitado (con modelo y categoría para cumplir INNER JOIN)
            $stmt = $pdo->prepare("INSERT INTO tbl_productos (serial, nombre_producto, precio, stock, estado, id_modelo, id_categoria, clausula_garantia)
                                   VALUES (:serial, :nombre, :precio, :stock, 'habilitado', :id_modelo, :id_categoria, :garantia)");
            $stmt->execute([
                ':serial' => 'SCAT' . rand(10000,99999),
                ':nombre' => 'Prod Cat ' . uniqid(),
                ':precio' => 10.5,
                ':stock' => 20,
                ':id_modelo' => $idModelo,
                ':id_categoria' => $idCategoria,
                ':garantia' => 'Sin Garantía',
            ]);
            $this->productoId = (int)$pdo->lastInsertId();

            // Crear un combo base
            $this->comboId = (int)$this->catalogo->crearNuevoCombo();
        } finally {
            $bd->cerrar();
        }
    }

    private function limpiarDatos(): void
    {
        $bd = new BD('P');
        $pdo = $bd->getConexion();
        try {
            if ($this->comboId) {
                $pdo->exec("DELETE FROM tbl_combo_detalle WHERE id_combo = {$this->comboId}");
                $pdo->exec("DELETE FROM tbl_combo WHERE id_combo = {$this->comboId}");
            }
            // No eliminamos el producto para evitar violaciones de FK de posibles otras tablas
        } finally {
            $bd->cerrar();
        }
    }

    public function testInsertarComboIntegracion(): void
    {
        $this->catalogo->setIdProducto($this->productoId);
        $this->catalogo->setCantidad(2);
        $this->assertTrue($this->catalogo->insertarCombo());
    }

    public function testObtenerProductosIntegracion(): void
    {
        $rows = $this->catalogo->obtenerProductos();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('nombre_producto', $rows[0]);
    }

    public function testObtenerCombosIntegracion(): void
    {
        // Asegurar un detalle para que aparezca en el listado
        $ok = $this->catalogo->insertarProductoEnCombo($this->comboId, $this->productoId, 3);
        $this->assertTrue($ok);

        $rows = $this->catalogo->obtenerCombos();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('precio_total', $rows[0]);
    }

    public function testEliminarComboIntegracion(): void
    {
        $nuevoCombo = (int)$this->catalogo->crearNuevoCombo();
        $this->assertTrue($this->catalogo->eliminarCombo($nuevoCombo));
    }

    public function testObtenerUltimoIdComboIntegracion(): void
    {
        $ultimo = $this->catalogo->obtenerUltimoIdCombo();
        $this->assertNotNull($ultimo);
        $this->assertGreaterThan(0, (int)$ultimo);
    }

    public function testCrearNuevoComboIntegracion(): void
    {
        $id = $this->catalogo->crearNuevoCombo();
        $this->assertNotFalse($id);
        $this->assertGreaterThan(0, (int)$id);
    }

    public function testInsertarProductoEnComboIntegracion(): void
    {
        $this->assertTrue($this->catalogo->insertarProductoEnCombo($this->comboId, $this->productoId, 3));
    }
}
