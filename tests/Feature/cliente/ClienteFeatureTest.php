<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/cliente.php';

/*
 * Pruebas de INTEGRACIÓN reales del módulo de Clientes (sin mocks).
 */

// ================================
// Casos de prueba (Integración REAL)
// ================================
final class ClienteFeatureTest extends TestCase
{
    private cliente $cliente;
    private ?int $idCreado = null;

    protected function setUp(): void
    {
        $this->cliente = new cliente();
    }

    protected function tearDown(): void
    {
        if ($this->idCreado) {
            $bd = new BD('P');
            $pdo = $bd->getConexion();
            try {
                // Primero eliminar lógico por si se exige en flujo, luego físico
                $stmt = $pdo->prepare("DELETE FROM tbl_clientes WHERE id_clientes = :id");
                $stmt->execute([':id' => $this->idCreado]);
            } finally {
                $bd->cerrar();
            }
            $this->idCreado = null;
        }
    }

    // CLI-FEAT-001: Registrar cliente
    public function testRegistrarClienteIntegracion(): void
    {
        $this->cliente->setnombre('Juan Test');
        $this->cliente->setcedula('V-' . rand(1000000,9999999));
        $this->cliente->setdireccion('Dir Test');
        $this->cliente->settelefono('0414000000');
        $this->cliente->setcorreo('juan.test@example.com');
        $this->assertTrue($this->cliente->ingresarclientes());

        // Guardar id creado
        $bd = new BD('P');
        $pdo = $bd->getConexion();
        try {
            $row = $pdo->query("SELECT id_clientes FROM tbl_clientes ORDER BY id_clientes DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $this->idCreado = (int)$row['id_clientes'];
        } finally { $bd->cerrar(); }
    }

    // CLI-FEAT-002: Existe número de cédula
    public function testExisteNumeroCedulaIntegracion(): void
    {
        // Asegurar un cliente base
        $this->testRegistrarClienteIntegracion();
        $bd = new BD('P');
        $pdo = $bd->getConexion();
        try {
            $row = $pdo->prepare("SELECT cedula FROM tbl_clientes WHERE id_clientes = :id");
            $row->execute([':id' => $this->idCreado]);
            $ced = $row->fetchColumn();
        } finally { $bd->cerrar(); }
        $this->assertTrue($this->cliente->existeNumeroCedula($ced));
    }

    // CLI-FEAT-003: Obtener último cliente
    public function testObtenerUltimoClienteIntegracion(): void
    {
        $this->testRegistrarClienteIntegracion();
        $row = $this->cliente->obtenerUltimoCliente();
        $this->assertIsArray($row);
        $this->assertArrayHasKey('id_clientes', $row);
    }

    // CLI-FEAT-004: Obtener cliente por ID
    public function testObtenerClientePorIdIntegracion(): void
    {
        $this->testRegistrarClienteIntegracion();
        $row = $this->cliente->obtenerclientesPorId($this->idCreado);
        $this->assertIsArray($row);
        $this->assertEquals($this->idCreado, (int)$row['id_clientes']);
    }

    // CLI-FEAT-005: Modificar cliente
    public function testModificarClienteIntegracion(): void
    {
        $this->testRegistrarClienteIntegracion();
        $this->cliente->setnombre('Editado');
        $this->cliente->setcedula('V-' . rand(1000,9999));
        $this->cliente->setdireccion('Dir Edit');
        $this->cliente->settelefono('0414001111');
        $this->cliente->setcorreo('edit@test.com');
        $this->assertTrue($this->cliente->modificarclientes($this->idCreado));
    }

    // CLI-FEAT-006: Eliminar lógico
    public function testEliminarLogicoIntegracion(): void
    {
        $this->testRegistrarClienteIntegracion();
        $this->assertTrue($this->cliente->eliminar_l($this->idCreado));
    }

    // CLI-FEAT-007: Eliminar cliente
    public function testEliminarClienteIntegracion(): void
    {
        $this->testRegistrarClienteIntegracion();
        $this->assertTrue($this->cliente->eliminarclientes($this->idCreado));
        // Limpiar manejado en tearDown
        $this->idCreado = null;
    }

    // CLI-FEAT-008: Listar clientes (g_clientes)
    public function testGetClientesListadoIntegracion(): void
    {
        $lista = $this->cliente->getclientes();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_clientes', $lista[0]);
        $this->assertArrayHasKey('nombre', $lista[0]);
    }

    // CLI-FEAT-009: listarTodosClientes() usando ClienteDoubleF
    public function testListarTodosClientesIntegracion(): void
    {
        $lista = $this->cliente->listarTodosClientes();
        $this->assertIsArray($lista);
        $this->assertNotEmpty($lista);
        $this->assertArrayHasKey('id_clientes', $lista[0]);
    }
}
