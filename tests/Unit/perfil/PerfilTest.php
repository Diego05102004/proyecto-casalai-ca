<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../Config/database.php';
require_once __DIR__ . '/../../../Config/config.php';
require_once __DIR__ . '/../../../Modelo/usuario.php';

/*
 * Pruebas unitarias del módulo Perfil (usa el modelo Usuarios).
 *
 * Secciones:
 * - Dobles: `StatementDoublePerfil`, `PDODoublePerfilS` (seguridad) y `PDODoublePerfilP` (inventario) simulando consultas, inserts, updates y transacciones.
 * - Escenarios: ingresar usuario, modificar, existencia por username/cedula/correo, obtener último, por id,
 *   eliminar, cambiar estatus, reporte roles, actualizar perfil, listar usuarios por estatus.
 */

// ======================================
// Dobles de prueba (PDOStatement/Perfil)
// ======================================
class StatementDoublePerfil
{
    private string $sql;
    private array $rows;
    private ?array $row;
    private mixed $scalar;
    private array $bound = [];

    public function __construct(string $sql, array $data = [])
    {
        $this->sql = $sql;
        $this->rows = $data['rows'] ?? [];
        $this->row = $data['row'] ?? null;
        $this->scalar = $data['scalar'] ?? null;
    }

    public function bindParam($name, &$value, $type = null)
    {
        $this->bound[$name] = $value;
    }

    public function execute(array $params = [])
    {
        return true;
    }

    public function fetch($mode = null)
    {
        if ($this->row !== null) return $this->row;
        if ($this->scalar !== null) return $this->scalar;
        return null;
    }

    public function fetchAll($mode = null)
    {
        return $this->rows;
    }

    public function fetchColumn($columnNumber = 0)
    {
        return $this->scalar;
    }

    public function rowCount()
    {
        // Para SHOW COLUMNS LIKE 'estatus'
        if (stripos($this->sql, "SHOW COLUMNS FROM tbl_usuarios LIKE 'estatus'") !== false) {
            return 1; // simular que el campo existe
        }
        return count($this->rows);
    }
}

// ======================
// Doble de prueba (PDO - Seguridad)
// ======================
class PDODoublePerfilS extends PDO
{
    public bool $inTx = false;

    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }
    public function beginTransaction(): bool { $this->inTx = true; return true; }
    public function commit(): bool { $this->inTx = false; return true; }
    public function rollBack(): bool { $this->inTx = false; return true; }
    public function inTransaction(): bool { return $this->inTx; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // INSERT usuario
        if (stripos($sql, 'INSERT INTO tbl_usuarios (username, password, id_rol') !== false) {
            return new StatementDoublePerfil($sql);
        }
        // UPDATE usuario (modificarUsuario)
        if (stripos($sql, 'UPDATE tbl_usuarios SET') !== false && stripos($sql, 'WHERE id_usuario') !== false) {
            return new StatementDoublePerfil($sql);
        }
        // SELECT COUNT(*) usuario/cedula/correo
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_usuarios WHERE username') !== false) {
            return new StatementDoublePerfil($sql, ['scalar' => 1]);
        }
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_usuarios WHERE cedula') !== false) {
            return new StatementDoublePerfil($sql, ['scalar' => 0]);
        }
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_usuarios WHERE correo') !== false) {
            return new StatementDoublePerfil($sql, ['scalar' => 0]);
        }
        // obtenerUltimoUsuario join rol
        if (stripos($sql, 'FROM tbl_usuarios AS usuarios') !== false && stripos($sql, 'ORDER BY usuarios.id_usuario DESC LIMIT 1') !== false) {
            return new StatementDoublePerfil($sql, [
                'row' => [
                    'id_usuario' => 50,
                    'username' => 'last',
                    'id_rol' => 2,
                    'nombre_rol' => 'Supervisor',
                ],
            ]);
        }
        // obtenerUsuarioPorId join rol
        if (stripos($sql, 'FROM tbl_usuarios AS usuarios') !== false && stripos($sql, 'WHERE usuarios.id_usuario =') !== false) {
            return new StatementDoublePerfil($sql, [
                'row' => [
                    'id_usuario' => 5,
                    'username' => 'user5',
                    'nombre_rol' => 'Operador',
                ],
            ]);
        }
        // eliminarUsuario
        if (stripos($sql, 'DELETE FROM tbl_usuarios WHERE id_usuario') !== false) {
            return new StatementDoublePerfil($sql);
        }
        // cambiarEstatus
        if (stripos($sql, 'UPDATE tbl_usuarios SET estatus') !== false) {
            return new StatementDoublePerfil($sql);
        }
        // reporte roles
        if (stripos($sql, 'SELECT rol.nombre_rol, COUNT') !== false) {
            return new StatementDoublePerfil($sql, [
                'rows' => [
                    ['nombre_rol' => 'Admin', 'cantidad' => 2],
                    ['nombre_rol' => 'Operador', 'cantidad' => 1],
                ],
            ]);
        }
        // actualizarPerfil: UPDATE dinámico
        if (stripos($sql, 'UPDATE tbl_usuarios SET') !== false && stripos($sql, 'WHERE id_usuario = :id_usuario') !== false) {
            return new StatementDoublePerfil($sql);
        }
        // getusuarios por estatus
        if (stripos($sql, 'WHERE usuarios.estatus =') !== false && stripos($sql, 'ORDER BY usuarios.id_usuario DESC') !== false) {
            return new StatementDoublePerfil($sql, [
                'rows' => [
                    ['id_usuario' => 1, 'username' => 'a', 'nombre_rol' => 'Admin', 'estatus' => 'habilitado'],
                ],
            ]);
        }
        // SHOW COLUMNS estatus
        if (stripos($sql, "SHOW COLUMNS FROM tbl_usuarios LIKE 'estatus'") !== false) {
            return new StatementDoublePerfil($sql, ['rows' => [['Field' => 'estatus']]]);
        }

        return new StatementDoublePerfil($sql);
    }
}

// ======================
// Doble de prueba (PDO - Inventario)
// ======================
class PDODoublePerfilP extends PDO
{
    public function __construct() {}
    public function setAttribute($attribute, $value) { return true; }

    public function prepare($statement, array $options = [])
    {
        $sql = trim($statement);
        // INSERT cliente si no existe
        if (stripos($sql, 'INSERT INTO tbl_clientes (nombre, cedula, telefono, direccion, correo, activo)') !== false) {
            return new StatementDoublePerfil($sql);
        }
        // UPDATE cliente en modificarUsuario
        if (stripos($sql, 'UPDATE tbl_clientes SET') !== false) {
            return new StatementDoublePerfil($sql);
        }
        // SELECT COUNT(*) clienteExiste
        if (stripos($sql, 'SELECT COUNT(*) FROM tbl_clientes WHERE cedula') !== false) {
            return new StatementDoublePerfil($sql, ['scalar' => 0]);
        }
        return new StatementDoublePerfil($sql);
    }
}

// ================================
// Casos de prueba (Unit scenarios)
// ================================
final class PerfilTest extends TestCase
{
    // Helper: crea `Usuarios` con dos conexiones stub (conex Seguridad y con Inventario)
    private function nuevoPerfilConPDOStub(): Usuarios
    {
        $ref = new ReflectionClass(Usuarios::class);
        /** @var Usuarios $u */
        $u = $ref->newInstanceWithoutConstructor();
        $sec = new PDODoublePerfilS();
        $inv = new PDODoublePerfilP();
        $propSec = new ReflectionProperty(Usuarios::class, 'conex');
        $propSec->setAccessible(true);
        $propSec->setValue($u, $sec);
        $propInv = new ReflectionProperty(Usuarios::class, 'con');
        $propInv->setAccessible(true);
        $propInv->setValue($u, $inv);
        return $u;
    }

    // PRF-UNIT-001: Ingresar usuario (crea en seguridad y cliente si no existe)
    public function testIngresarUsuario(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $u->setUsername('nuevo');
        $u->setClave('clave');
        $u->setRango(2);
        $u->setCorreo('n@test.com');
        $u->setNombre('Ana');
        $u->setApellido('Lopez');
        $u->setTelefono('0414');
        $u->setCedula('V-100');
        $this->assertTrue($u->ingresarUsuario());
    }

    // PRF-UNIT-002: Modificar usuario (actualiza seguridad e inventario)
    public function testModificarUsuario(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $u->setUsername('edit');
        $u->setRango(3);
        $u->setNombre('Eva');
        $u->setApellido('Perez');
        $u->setCorreo('e@test.com');
        $u->setTelefono('0412');
        $u->setCedula('V-101');
        $this->assertTrue($u->modificarUsuario(5));
    }

    // PRF-UNIT-003: Existe usuario/cédula/correo
    public function testExistencias(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $this->assertTrue($u->existeUsuario('a'));
        $this->assertFalse($u->existeCedula('V-1'));
        $this->assertFalse($u->existeCorreo('a@b.com'));
    }

    // PRF-UNIT-004: Obtener último usuario
    public function testObtenerUltimoUsuario(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $row = $u->obtenerUltimoUsuario();
        $this->assertIsArray($row);
        $this->assertSame('last', $row['username']);
    }

    // PRF-UNIT-005: Obtener usuario por id
    public function testObtenerUsuarioPorId(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $row = $u->obtenerUsuarioPorId(5);
        $this->assertIsArray($row);
        $this->assertSame(5, $row['id_usuario']);
    }

    // PRF-UNIT-006: Eliminar usuario
    public function testEliminarUsuario(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $this->assertTrue($u->eliminarUsuario(7));
    }

    // PRF-UNIT-007: Cambiar estatus
    public function testCambiarEstatus(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $ref = new ReflectionProperty(Usuarios::class, 'id_usuario');
        $ref->setAccessible(true);
        $ref->setValue($u, 3);
        $this->assertTrue($u->cambiarEstatus('habilitado'));
    }

    // PRF-UNIT-008: Reporte de roles
    public function testObtenerReporteRoles(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $rows = $u->obtenerReporteRoles();
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('nombre_rol', $rows[0]);
    }

    // PRF-UNIT-009: Actualizar perfil con campos selectivos
    public function testActualizarPerfil(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $ok = $u->actualizarPerfil(2, [
            'nombres' => 'Nuevo Nombre',
            'password' => 'nuevaClave',
            'telefono' => '0000',
            'correo' => '', // vacío no actualiza
        ]);
        $this->assertTrue($ok);
    }

    // PRF-UNIT-010: Listar usuarios por estatus
    public function testGetUsuariosPorEstatus(): void
    {
        $u = $this->nuevoPerfilConPDOStub();
        $rows = $u->getusuarios('habilitado');
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertSame('habilitado', $rows[0]['estatus']);
    }
}
