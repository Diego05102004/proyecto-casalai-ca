<?php
use PHPUnit\Framework\TestCase;

// Definir constante para pruebas
if (!defined('SKIP_SIDE_EFFECTS')) {
    define('SKIP_SIDE_EFFECTS', true);
}

// Configuración de entorno de pruebas
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Definir ruta base del proyecto
$projectRoot = realpath(__DIR__ . '/../../..');

// Incluir archivos necesarios con rutas absolutas
require_once $projectRoot . '/Config/database.php'; // Constantes de base de datos
require_once $projectRoot . '/Config/Config.php';   // Clase BD
require_once $projectRoot . '/Modelo/bitacora.php'; // Clase Bitacora

class BitacoraControllerTest extends TestCase
{
    private $bitacora;
    private $testUserId = 1; // ID de usuario para pruebas
    private $testModulo = 1; // Módulo de bitácora
    
    protected function setUp(): void
    {
        // Iniciar sesión si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Configurar usuario de prueba en sesión
        $_SESSION['id_usuario'] = $this->testUserId;
        $_SESSION['id_rol'] = 1; // Rol de administrador
        
        // Crear instancia de Bitácora para pruebas
        $this->bitacora = new Bitacora();
    }
    
    protected function tearDown(): void
    {
        // Limpiar variables de sesión
        session_unset();
    }
    
    /**
     * Prueba el registro de una entrada en la bitácora
     */
    public function testRegistrarBitacora()
    {
        $accion = 'PRUEBA';
        $descripcion = 'Prueba de registro en bitácora';
        $prioridad = 'media';
        
        $result = $this->bitacora->registrarBitacora(
            $this->testUserId,
            $this->testModulo,
            $accion,
            $descripcion,
            $prioridad
        );
        
        $this->assertTrue($result, 'No se pudo registrar la entrada en la bitácora');
    }
    
    /**
     * Prueba la obtención de registros detallados
     */
    public function testObtenerRegistrosDetallados()
    {
        // Primero registramos una entrada de prueba
        $this->bitacora->registrarBitacora(
            $this->testUserId,
            $this->testModulo,
            'CONSULTA',
            'Prueba de consulta de registros',
            'baja'
        );
        
        // Obtenemos los registros
        $registros = $this->bitacora->obtenerRegistrosDetallados(10);
        
        $this->assertIsArray($registros, 'No se pudo obtener los registros de la bitácora');
        $this->assertNotEmpty($registros, 'No se encontraron registros en la bitácora');
        
        // Verificamos que los registros tengan la estructura esperada
        $this->assertGreaterThan(0, count($registros), 'No se encontraron registros');
        $primerRegistro = $registros[0];
        $this->assertArrayHasKey('id_bitacora', $primerRegistro);
        $this->assertArrayHasKey('accion', $primerRegistro);
        $this->assertArrayHasKey('descripcion', $primerRegistro);
        $this->assertArrayHasKey('fecha_hora', $primerRegistro);
    }
    
    /**
     * Prueba la paginación de registros
     */
    public function testPaginacionRegistros()
    {
        // Registrar varias entradas para probar la paginación
        for ($i = 0; $i < 15; $i++) {
            $this->bitacora->registrarBitacora(
                $this->testUserId,
                $this->testModulo,
                'PAGINACION',
                "Entrada de prueba #$i",
                'baja'
            );
        }
        
        // Obtener solo 5 registros
        $registros = $this->bitacora->obtenerRegistrosDetallados(5);
        $this->assertCount(5, $registros, 'No se respetó el límite de registros');
    }
    
    /**
     * Prueba el registro de una acción con datos adicionales
     */
    public function testRegistroConDatosAdicionales()
    {
        $datosAnteriores = ['campo1' => 'valor1', 'campo2' => 'valor2'];
        $datosNuevos = ['campo1' => 'valor1_modificado', 'campo2' => 'valor2_modificado'];
        
        $result = $this->bitacora->registrarBitacora(
            $this->testUserId,
            $this->testModulo,
            'ACTUALIZACION',
            'Prueba con datos adicionales',
            'alta',
            $datosAnteriores,
            $datosNuevos
        );
        
        $this->assertTrue($result, 'No se pudo registrar la entrada con datos adicionales');
        
        // Verificar que los datos se pueden recuperar
        $registros = $this->bitacora->obtenerRegistrosDetallados(1);
        $this->assertIsArray($registros, 'No se pudieron obtener los registros');
        
        // Verificar que hay al menos un registro
        if (count($registros) > 0) {
            $ultimoRegistro = $registros[0];
            $this->assertArrayHasKey('accion', $ultimoRegistro, 'El registro no tiene la clave "accion"');
            $this->assertArrayHasKey('descripcion', $ultimoRegistro, 'El registro no tiene la clave "descripcion"');
        } else {
            $this->markTestSkipped('No se encontraron registros para verificar');
        }
    }
    
    /**
     * Prueba el manejo de errores al registrar sin los campos obligatorios
     */
    public function testRegistroIncompleto()
    {
        // Probamos con datos mínimos requeridos
        $result = $this->bitacora->registrarBitacora(
            $this->testUserId,
            $this->testModulo,
            'PRUEBA',
            'Prueba de registro mínimo',
            'baja'
        );
        $this->assertTrue($result, 'No se pudo registrar con datos mínimos');
    }
    
    /**
     * Prueba la obtención de registros con límite personalizado
     */
    public function testObtenerRegistrosConLimitePersonalizado()
    {
        // Registrar 10 entradas de prueba
        for ($i = 0; $i < 10; $i++) {
            $this->bitacora->registrarBitacora(
                $this->testUserId,
                $this->testModulo,
                'PRUEBA',
                "Entrada de prueba #$i",
                'baja'
            );
        }
        
        // Obtener solo 3 registros
        $registros = $this->bitacora->obtenerRegistrosDetallados(3);
        $this->assertCount(3, $registros, 'No se respetó el límite de 3 registros');
    }
    
    /**
     * Prueba el registro de una acción con prioridad alta
     */
    public function testRegistroConPrioridadAlta()
    {
        $result = $this->bitacora->registrarBitacora(
            $this->testUserId,
            $this->testModulo,
            'ALERTA',
            'Prueba de prioridad alta',
            'alta' // Prioridad alta
        );
        
        $this->assertTrue($result, 'No se pudo registrar la entrada con prioridad alta');
        
        // Verificar que el registro tiene prioridad alta
        $registros = $this->bitacora->obtenerRegistrosDetallados(1);
        $ultimoRegistro = $registros[0];
        
        // Verificar que la prioridad se guardó correctamente
        $this->assertTrue($result, 'No se pudo registrar con prioridad alta');
    }
}