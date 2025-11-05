<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/carrito.php';
require_once __DIR__ . '/../../../Modelo/producto.php';

/**
 * Pruebas de INTEGRACIÓN del módulo Carrito.
 * 
 * Estas pruebas trabajan directamente con la base de datos real
 * para probar el flujo completo del carrito de compras.
 */
final class CarritoFeatureTest extends TestCase
{
    private $carrito;
    private $testCarritoId;
    private $testClienteId = 10; // ID de cliente (se valida en setUp y se crea si no existe)
    private $testProductoId;
    
    protected function setUp(): void 
    {
        // Crear instancia real de Carrito
        $this->carrito = new Carrito();
        
        // Asegurar que el cliente de prueba exista en la BD
        $this->asegurarCliente();

        // Limpiar datos de prueba previos
        $this->limpiarDatosPrueba();
        
        // Crear datos de prueba básicos
        $this->crearDatosPrueba();
    }

    private function asegurarCliente(): void
    {
        $bd = new BD('P');
        $pdo = $bd->getConexion();
        try {
            // Verificar si existe el cliente actual
            $stmt = $pdo->prepare("SELECT id_clientes FROM tbl_clientes WHERE id_clientes = :id LIMIT 1");
            $stmt->execute([':id' => $this->testClienteId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) { return; }

            // Si no existe, intentar obtener uno existente
            $row = $pdo->query("SELECT id_clientes FROM tbl_clientes ORDER BY id_clientes ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $this->testClienteId = (int)$row['id_clientes'];
                return;
            }

            // Crear un cliente de prueba
            $stmt = $pdo->prepare("INSERT INTO tbl_clientes (nombre, cedula, direccion, telefono, correo, activo) VALUES (:n, :c, :d, :t, :e, 1)");
            $stmt->execute([
                ':n' => 'Cliente Test',
                ':c' => (string)rand(10000000, 99999999),
                ':d' => 'Direccion Test',
                ':t' => '0412-0000000',
                ':e' => 'cliente@test.local',
            ]);
            $this->testClienteId = (int)$pdo->lastInsertId();
        } finally {
            $bd->cerrar();
        }
    }
    
    protected function tearDown(): void
    {
        // Limpiar datos después de cada prueba
        $this->limpiarDatosPrueba();
    }
    
    private function limpiarDatosPrueba(): void
    {
        if (!$this->testClienteId) return;
        
        $conexion = new BD('P');
        $pdo = $conexion->getConexion();
        
        try {
            // Limpiar tablas relacionadas con el carrito
            $pdo->exec("DELETE FROM tbl_carritodetalle WHERE id_carrito IN (SELECT id_carrito FROM tbl_carrito WHERE id_cliente = {$this->testClienteId})");
            $pdo->exec("DELETE FROM tbl_carrito WHERE id_cliente = {$this->testClienteId}");
            
            // Si hay un carrito de prueba específico, limpiarlo también
            if ($this->testCarritoId) {
                $pdo->exec("DELETE FROM tbl_carritodetalle WHERE id_carrito = {$this->testCarritoId}");
                $pdo->exec("DELETE FROM tbl_carrito WHERE id_carrito = {$this->testCarritoId}");
            }
            
            // Nota: No eliminamos el producto de prueba para evitar violaciones de FK
        } finally {
            $conexion->cerrar();
        }
    }
    
    private function crearDatosPrueba(): void
    {
        $conexion = new BD('P');
        $pdo = $conexion->getConexion();
        
        try {
            // Crear marca y modelo para cumplir con los INNER JOIN del modelo
            $pdo->exec("INSERT INTO tbl_marcas (nombre_marca) VALUES ('MarcaPrueba_" . uniqid() . "')");
            $idMarca = $pdo->lastInsertId();
            $pdo->exec("INSERT INTO tbl_modelos (nombre_modelo, id_marca) VALUES ('ModeloPrueba_" . uniqid() . "', {$idMarca})");
            $idModelo = $pdo->lastInsertId();

            // Insertar un producto de prueba con modelo
            $nombreProducto = 'Producto de Prueba ' . uniqid();
            $precio = 100.50;
            $stock = 10;
            $serial = 'SER' . rand(10000,99999);
            $garantia = 'Sin Garantía';
            
            $stmt = $pdo->prepare("INSERT INTO tbl_productos (serial, nombre_producto, precio, stock, estado, id_modelo, clausula_garantia) VALUES (:serial, :nombre, :precio, :stock, 'habilitado', :id_modelo, :garantia)");
            $stmt->execute([
                ':serial' => $serial,
                ':nombre' => $nombreProducto,
                ':precio' => $precio,
                ':stock' => $stock,
                ':id_modelo' => $idModelo,
                ':garantia' => $garantia
            ]);
            
            $this->testProductoId = $pdo->lastInsertId();
            
            // Insertar un carrito de prueba (la tabla no tiene columna estado, fecha_creacion tiene default)
            $stmt = $pdo->prepare("INSERT INTO tbl_carrito (id_cliente) VALUES (:cliente)");
            $stmt->execute([':cliente' => $this->testClienteId]);
            
            $this->testCarritoId = $pdo->lastInsertId();
            
        } finally {
            $conexion->cerrar();
        }
    }


    public function testCrearCarrito(): void
    {
        // Probar crear un nuevo carrito
        $resultado = $this->carrito->crearCarrito($this->testClienteId);
        $this->assertTrue($resultado);
        
        // Verificar que se creó el carrito
        $carrito = $this->carrito->obtenerCarritoPorCliente($this->testClienteId);
        $this->assertIsArray($carrito);
        $this->assertArrayHasKey('id_carrito', $carrito);
        $this->assertArrayHasKey('id_cliente', $carrito);
        $this->assertEquals($this->testClienteId, $carrito['id_cliente']);
    }
    
    public function testAgregarProductoAlCarrito(): void
    {
        // Agregar un producto al carrito
        $cantidad = 2;
        $resultado = $this->carrito->agregarProductoAlCarrito($this->testCarritoId, $this->testProductoId, $cantidad);
        $this->assertTrue($resultado);
        
        // Verificar que el producto se agregó al carrito
        $productos = $this->carrito->obtenerProductosDelCarrito($this->testCarritoId);
        $this->assertIsArray($productos);
        $this->assertNotEmpty($productos);
        $this->assertEquals($this->testProductoId, $productos[0]['id_producto']);
        $this->assertEquals($cantidad, $productos[0]['cantidad']);
    }
    
    public function testActualizarCantidadProducto(): void
    {
        // Primero agregamos un producto al carrito
        $this->testAgregarProductoAlCarrito();
        
        // Obtener los productos del carrito y ubicar el detalle del producto insertado
        $productos = $this->carrito->obtenerProductosDelCarrito($this->testCarritoId);
        $idDetalle = null;
        foreach ($productos as $p) {
            if ((int)$p['id_producto'] === (int)$this->testProductoId) { $idDetalle = $p['id_carrito_detalle']; break; }
        }
        $this->assertNotNull($idDetalle, 'No se encontró el detalle del producto agregado');
        
        // Actualizar la cantidad
        $nuevaCantidad = 5;
        $resultado = $this->carrito->actualizarCantidadProducto($idDetalle, $nuevaCantidad);
        $this->assertTrue($resultado);
        
        // Verificar que se actualizó la cantidad en la fila del producto
        $productos = $this->carrito->obtenerProductosDelCarrito($this->testCarritoId);
        $cantidadEncontrada = null;
        foreach ($productos as $p) {
            if ((int)$p['id_carrito_detalle'] === (int)$idDetalle) { $cantidadEncontrada = (int)$p['cantidad']; break; }
        }
        $this->assertSame($nuevaCantidad, $cantidadEncontrada);
    }
    
    public function testEliminarProductoDelCarrito(): void
    {
        // Primero agregamos un producto al carrito
        $this->testAgregarProductoAlCarrito();
        
        // Obtener los productos del carrito y ubicar el detalle del producto insertado
        $productos = $this->carrito->obtenerProductosDelCarrito($this->testCarritoId);
        $idDetalle = null;
        foreach ($productos as $p) {
            if ((int)$p['id_producto'] === (int)$this->testProductoId) { $idDetalle = $p['id_carrito_detalle']; break; }
        }
        $this->assertNotNull($idDetalle, 'No se encontró el detalle del producto agregado');
        
        // Eliminar el producto del carrito
        $resultado = $this->carrito->eliminarProductoDelCarrito($idDetalle);
        $this->assertTrue($resultado);
        
        // Verificar que el producto ya no está en el carrito
        $productos = $this->carrito->obtenerProductosDelCarrito($this->testCarritoId);
        $encontrado = false;
        foreach ($productos as $p) {
            if ((int)$p['id_carrito_detalle'] === (int)$idDetalle) { $encontrado = true; break; }
        }
        $this->assertFalse($encontrado, 'El detalle del producto no fue eliminado');
    }
    
    public function testEliminarTodoElCarrito(): void
    {
        // Primero agregamos un producto al carrito
        $this->testAgregarProductoAlCarrito();
        
        // Eliminar todo el carrito
        $resultado = $this->carrito->eliminarTodoElCarrito($this->testCarritoId);
        $this->assertTrue($resultado);
        
        // Verificar que el carrito está vacío
        $productos = $this->carrito->obtenerProductosDelCarrito($this->testCarritoId);
        $this->assertEmpty($productos);
    }
    
    public function testObtenerCantidadProductosCarrito(): void
    {
        // Primero agregamos un producto al carrito
        $this->testAgregarProductoAlCarrito();
        
        // Obtener la cantidad de productos en el carrito
        $cantidad = $this->carrito->obtenerCantidadProductosCarrito($this->testClienteId);
        $this->assertIsInt($cantidad);
        $this->assertGreaterThan(0, $cantidad);
    }
    
    public function testObtenerResumenCarrito(): void
    {
        // Primero agregamos un producto al carrito
        $this->testAgregarProductoAlCarrito();
        
        // Obtener resumen del carrito
        $resumen = $this->carrito->obtenerResumenCarrito($this->testClienteId);
        
        // Verificar la estructura del resumen
        $this->assertIsArray($resumen);
        $this->assertArrayHasKey('id_carrito', $resumen);
        $this->assertArrayHasKey('total_productos', $resumen);
        $this->assertArrayHasKey('total_precio', $resumen);
        $this->assertGreaterThan(0, $resumen['total_productos']);
        $this->assertGreaterThan(0, $resumen['total_precio']);
    }
    
    public function testRegistrarCompra(): void
    {
        // Primero agregamos un producto al carrito
        $this->testAgregarProductoAlCarrito();
        
        // Obtener los productos del carrito
        $productos = $this->carrito->obtenerProductosDelCarrito($this->testCarritoId);
        
        // Preparar los datos para el registro de compra (el modelo usa id_producto y cantidad)
        $productosCompra = [];
        foreach ($productos as $producto) {
            $productosCompra[] = [
                'id_producto' => $producto['id_producto'],
                'cantidad' => $producto['cantidad']
            ];
        }
        
        // Registrar la compra
        $resultado = $this->carrito->registrarCompra(
            $this->testCarritoId, 
            $this->testClienteId,
            $productosCompra
        );
        
        // Verificar que la compra se registró correctamente
        $this->assertTrue($resultado);
    }
}
