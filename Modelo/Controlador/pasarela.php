<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\PasareladePago;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Cuentabanco;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\OrdenDespacho;
use Usuario\ProyectoCasalaiCa\Config\BD;

define('MODULO_PASARELA_PAGOS', 16); // Define el ID
$bitacoraModel = new Bitacora();
$id_rol = $_SESSION['id_rol'];


$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();

$pasarela = new PasareladePago();
$cuentaModel = new Cuentabanco();
$listadocuentas = $cuentaModel->consultarCuentabanco();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    switch ($accion) {
        case 'consultar_pagos':
            // Validar datos de entrada para consulta de pagos
            $datosValidacion = [
                'cedula' => $_POST['cedula'] ?? null,
                'id_factura' => $_POST['id_factura'] ?? null
            ];
            
            $errores = $pasarela->validarConsultarPagos($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos para consulta de pagos',
                    'errors' => $errores
                ]);
                exit;
            }
            
            // Aquí iría la lógica de consulta de pagos
            echo json_encode(['status' => 'success', 'message' => 'Validación exitosa']);
            exit;
            
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, 'Pasarela de pagos');
            echo json_encode($permisosActualizados);
            exit;
            
        case 'modificar':
            $id = $_POST['id_detalles'];
            $pasarela->setIdDetalles($id);
            $pasarela->setReferencia($_POST['referencia']);
            $pasarela->setFecha($_POST['fecha']);
            $pasarela->setTipo($_POST['tipo']);
            $pasarela->setFactura($_POST['id_factura']);
            $pasarela->setCuenta($_POST['cuenta']);
            $pasarela->setMonto($_POST['monto']);

            if ($pasarela->pasarelaTransaccion('Modificar')) {
                $pagoActualizado = $pasarela->obtenerPagoPorId($id);
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_PASARELA_PAGOS,
                    'MODIFICAR',
                    'El usuario modificó la referencia bancaria: ' . $pagoActualizado['referencia'],
                    'media'
                );
                echo json_encode(['status' => 'success', 'pago' => $pagoActualizado]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar el producto']);
            }
            break;
            
        case 'modificar_estado':
            $id = $_POST['id_detalles'];
            $nuevoEstatus = $_POST['estatus'];
            $factura = $_POST['id_factura'];
            $pasarela->setIdDetalles($id);
            $pasarela->setEstatus($nuevoEstatus);
            $pasarela->setFactura($factura);

            if ($pasarela->pasarelaTransaccion('Procesar')) {
                $ordenDespacho = new OrdenDespacho();
                $ordenDespacho->crearPorFactura($factura);
                $pagoActualizado = $pasarela->obtenerPagoPorId($id);
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_PASARELA_PAGOS,
                    'MODIFICAR',
                    'El usuario cambió el estatus del pago de la referencia bancaria: ' . $pagoActualizado['referencia'] . ' a ' . $nuevoEstatus,
                    'media'
                );
                echo json_encode(['status' => 'success', 'pago' => $pagoActualizado]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus']);
            }
            
            // Crear notificación
            $bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();
            $notificacionesModel = new NotificacionModel($pdo_seguridad);
            $notificacionesModel->crear(
                $_SESSION['id_usuario'],
                'pago',
                'Estatus de pago actualizado',
                "El estatus del pago con referencia " . $pagoActualizado['referencia'] . " ha sido cambiado a " . $nuevoEstatus . " por el usuario " . $_SESSION['name'],
                null,
                'media',
                MODULO_PASARELA_PAGOS,
                'modificar_estado'
            );
            break;
            
        case 'eliminar':
            $pasarela = new PasareladePago();
            $id = $_POST['id_detalles'];
            $pasarela->setIdDetalles($id);
            if ($pasarela->pasarelaTransaccion('Eliminar')) {
                $pagoEliminado = $pasarela->obtenerPagoPorId($id);
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_PASARELA_PAGOS,
                    'ELIMINAR',
                    'El usuario eliminó la referencia bancaria: ' . ($pagoEliminado ? $pagoEliminado['referencia'] : 'ID ' . $id),
                    'media'
                );
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el producto']);
            }
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
    exit;
}

// Consulta de datos según el rol
if ($_SESSION['nombre_rol'] != 'Cliente') {
    $datos = $pasarela->pasarelaTransaccion('ConsultarTodos');
} else {
    $pasarela->setCedula($_SESSION['cedula']);
    $datos = $pasarela->pasarelaTransaccion('Consultar');
}

$pagina = "pasarela";
if (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
            if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
    $_SESSION['id_usuario'],
    '12',
    'ACCESAR',
    'El usuario accedió al módulo de Pagos',
    'media'
);}
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>