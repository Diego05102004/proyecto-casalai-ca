<?php
ob_start();

// Requires
require_once 'modelo/cuenta.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';

// Constantes de módulo
define('MODULO_CUENTA_BANCARIA', 15);

// Inicializaciones de clases compartidas
$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();
$bitacoraModel = new Bitacora();
$id_rol = $_SESSION['id_rol'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('Cuentas bancarias'));
            echo json_encode($permisosActualizados);
            exit;

        case 'registrar':
            header('Content-Type: application/json; charset=utf-8');
            $cuentabanco = new Cuentabanco();
            $cuentabanco->setNombreBanco($_POST['nombre_banco']);
            $cuentabanco->setNumeroCuenta($_POST['numero_cuenta']);
            $cuentabanco->setRifCuenta($_POST['rif_cuenta']);
            $cuentabanco->setTelefonoCuenta($_POST['telefono_cuenta']);
            $cuentabanco->setCorreoCuenta($_POST['correo_cuenta']);
            $cuentabanco->setMetodosPago($_POST['metodos_pago'] ?? []);
            if ($_POST['numero_cuenta'] != ''){
            if ($cuentabanco->existeNumeroCuenta($_POST['numero_cuenta'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El número de cuenta ya existe'
                ]);
                exit;
            }
        }
            if ($cuentabanco->registrarCuentabanco()) {
                $cuentaRegistrada = $cuentabanco->obtenerUltimaCuenta();
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CUENTA_BANCARIA,
                        'INCLUIR',
                        'El usuario incluyó una nueva cuenta bancaria: ' . ($_POST['nombre_banco'] ?? ''),
                        'media'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Cuenta registrada correctamente',
                    'cuenta' => $cuentaRegistrada
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar la cuenta'
                ]);
            }
            exit;
        
        case 'obtener_cuenta':
            $id_cuenta = $_POST['id_cuenta'];

            if ($id_cuenta !== null) {
                $cuentabanco = new Cuentabanco();
                $cuenta_obt = $cuentabanco->obtenerCuentaPorId($id_cuenta);

                if ($cuenta_obt !== null) {
                    echo json_encode($cuenta_obt);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Cuenta no encontrado']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de cuenta no proporcionado']);
            }
            exit;
        
        case 'consultar_cuentas':
            $cuentabanco = new Cuentabanco();
            $cuentas_obt = $cuentabanco->consultarCuentabanco();

            echo json_encode($cuentas_obt);
            exit;

        case 'modificar':
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            $id_cuenta = $_POST['id_cuenta'];
            $cuentabanco = new Cuentabanco();
            $cuentabanco->setIdCuenta($id_cuenta);
            $cuentabanco->setNombreBanco($_POST['nombre_banco']);
            $cuentabanco->setNumeroCuenta($_POST['numero_cuenta']);
            $cuentabanco->setRifCuenta($_POST['rif_cuenta']);
            $cuentabanco->setTelefonoCuenta($_POST['telefono_cuenta']);
            $cuentabanco->setCorreoCuenta($_POST['correo_cuenta']);
            $cuentabanco->setMetodosPago($_POST['metodos_pago'] ?? []);
            if ($cuentabanco->existeNumeroCuenta($_POST['numero_cuenta'], $id_cuenta)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El número de cuenta ya existe'
                ]);
                exit;
            }

            if ($cuentabanco->modificarCuentabanco($id_cuenta)) {
                $cuentabancoActualizada = $cuentabanco->obtenerCuentaPorId($id_cuenta);
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CUENTA_BANCARIA,
                        'MODIFICAR',
                        'El usuario modificó la cuenta bancaria: ' . ($_POST['nombre_banco'] ?? '') . ' (ID: ' . $id_cuenta . ')',
                        'media'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'cuenta' => $cuentabancoActualizada
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar la cuenta']);
            }
            exit;

        case 'eliminar':
            $id_cuenta = $_POST['id_cuenta'];
            $cuentabanco = new Cuentabanco();

            $resultado = $cuentabanco->eliminarCuentabanco($id_cuenta);

            if (is_array($resultado) && $resultado['status'] === 'error') {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CUENTA_BANCARIA,
                        'ELIMINAR_FALLIDO',
                        'Intento de eliminación fallido de cuenta (ID: ' . $id_cuenta . '): ' . ($resultado['message'] ?? ''),
                        'media'
                    );
                }
                echo json_encode([
                    'status' => 'error', 
                    'message' => $resultado['message'],
                    'pagos' => $resultado['pagos'] ?? [],
                    'total_pagos' => $resultado['total_pagos'] ?? 0
                ]);
            } else if ($resultado['status'] === 'success') {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CUENTA_BANCARIA,
                        'ELIMINAR',
                        'El usuario eliminó la cuenta bancaria ID: ' . $id_cuenta,
                        'media'
                    );
                }
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Error al eliminar la cuenta',
                    'pagos' => [],
                    'total_pagos' => 0
                ]);
            }
            exit;

        case 'cambiar_estado':
            $id_cuenta = $_POST['id_cuenta'];
            $nuevoEstado = $_POST['estado'];
            
            if (!in_array($nuevoEstado, ['habilitado', 'inhabilitado'])) {
                echo json_encode(['status' => 'error', 'message' => 'Estado no válido']);
                exit;
            }
            
            $cuentabanco = new Cuentabanco();
            $cuentabanco->setIdCuenta($id_cuenta);
            
            if ($cuentabanco->cambiarEstado($nuevoEstado)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CUENTA_BANCARIA,
                        'CAMBIAR ESTADO',
                        'El usuario cambió el estado de la cuenta bancaria ID ' . $id_cuenta . ' a ' . $nuevoEstado,
                        'media'
                    );
                }

                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estado']);
            }
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        exit;
    }
}

function consultarCuentabanco() {
    $cuentabanco = new Cuentabanco();
    return $cuentabanco->consultarCuentabanco();
}

function cuentasReportes() {
    $cuentabanco = new Cuentabanco();
    return $cuentabanco->cuentasReportes();
}

$pagina = "cuenta";
if (is_file("vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        '15',
        'ACCESAR',
        'El usuario accedió al modulo de Cuentas Bancarias',
        'media'
    );}
    $cuentabancos = consultarCuentabanco();
    $cuentasReportes = cuentasReportes();
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>