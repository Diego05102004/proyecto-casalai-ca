<?php
// Requires organizados al inicio
require_once 'modelo/Recepcion.php';
require_once 'modelo/notificacion.php';
require_once __DIR__ . '/../modelo/permiso.php';
require_once 'modelo/bitacora.php';

define('MODULO_RECEPCION', 2); // Define el ID del módulo de cuentas bancarias

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

// Permisos: mantener variables compatibles con la vista y añadir consulta específica del módulo
$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('recepcion'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Instanciar Recepcion solo si hay POST (cuando se va a usar)
    $k = new Recepcion();
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    switch ($accion) {
        case 'listado':
            $respuesta = $k->listadoproductos();
            echo json_encode($respuesta);
        break;
        
        case 'productos_recepcion':
            $id_recepcion = $_POST['id_recepcion'];
            $recepcion = new Recepcion();
            $productos = $recepcion->obtenerProductosPorRecepcion($id_recepcion);
            echo json_encode($productos);
        break;

        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('recepcion'));
            echo json_encode($permisosActualizados);
        break;

        case 'registrar':
            header('Content-Type: application/json; charset=utf-8');

            // Setea los datos principales de la recepción
            $k->setidproveedor($_POST['proveedor']);
            $k->setcorrelativo($_POST['correlativo']);
            $k->settamanocompra($_POST['tamanocompra']);
            $k->setestado('habilitado');

            // Verifica si el correlativo ya existe
            if ($k->existeCorrelativo($_POST['correlativo'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El N° de Factura ya existe'
                ]);
                exit;
            }

            // Registra la recepción y los productos asociados
            $resultado = $k->registrarRecepcion(
                $_POST['producto'],
                $_POST['cantidad'],
                $_POST['costo']
            );

            // Obtiene la última recepción registrada
            $recepcionRegistrada = $k->obtenerUltimaRecepcion();

            if ($resultado && $recepcionRegistrada) {
                // Registra la acción en la bitácora
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_RECEPCION,
                        'INCLUIR',
                        'El usuario incluyó una nueva recepción: ' . $_POST['correlativo'],
                        'media'
                    );
                }

                $id_recepcion = $recepcionRegistrada['id_recepcion'];

                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        // Instanciar notificación solo cuando se usa
                        $bd_seguridad = new BD('S');
                        $pdo_seguridad = $bd_seguridad->getConexion();
                        $notificacionModel = new NotificacionModel($pdo_seguridad);
                        $notificacionModel->crear(
                            $_SESSION['id_usuario'],
                            'recepcion',
                            'Nueva recepción registrada',
                            "Se ha registrado una nueva recepción #".$_POST['correlativo']." con ".array_sum($_POST['cantidad'])." unidades por el usuario ".$_SESSION['name'],
                            'media',
                            MODULO_RECEPCION,
                            'ingresar',
                            $id_recepcion
                        );
                    }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Recepción registrada correctamente',
                    'recepcion' => $recepcionRegistrada
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar la recepción'
                ]);
            }
            break;

        case 'buscar':
            $correlativo = $_POST['correlativo'] ?? null;
            $k->setcorrelativo($correlativo);
            $respuesta = $k->buscar();
            if (!$respuesta) {
                echo json_encode([
                    "resultado" => "no_encontro",
                    "mensaje" => "No se encontró el correlativo: " . $correlativo
                ]);
            } else {
                echo json_encode($respuesta);
            }
            break;
        
        case 'anular':
            header('Content-Type: application/json; charset=utf-8');
            $correlativo = $_POST['correlativo'];
            $resultado = $k->anularRecepcion($correlativo);

            // Registrar en bitácora
            if ($resultado['status'] === 'success') {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_RECEPCION,
                        'ANULAR',
                        'El usuario anuló la recepción: ' . $correlativo,
                        'media'
                    );
                }

                // Obtener id_recepcion para referenciar en notificación
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $id_recepcion = $k->obtenerIdRecepcionPorCorrelativo($correlativo);
                    $bd_seguridad = new BD('S');
                    $pdo_seguridad = $bd_seguridad->getConexion();
                    $notificacionModel = new NotificacionModel($pdo_seguridad);
                    $notificacionModel->crear(
                        $_SESSION['id_usuario'],
                        'recepcion',
                        'Recepción anulada',
                        "Se ha anulado la recepción #".$correlativo." por parte del usuario ".($_SESSION['name'] ?? ''),
                        'media',
                        MODULO_RECEPCION,
                        'eliminar',
                        $id_recepcion
                    );
                }
            }
            echo json_encode($resultado);
        break;

        case 'reportes_recepcion':
            header('Content-Type: application/json; charset=utf-8');
            // Parámetros opcionales
            $fechaInicio = $_POST['fechaInicio'] ?? null;
            $fechaFin    = $_POST['fechaFin'] ?? null;
            $anio        = $_POST['anio'] ?? null;
            $proveedorId = $_POST['proveedorId'] ?? null;

            try {
                $resp = [
                    'proveedores' => $k->getRecepcionesPorProveedor($fechaInicio, $fechaFin),
                    'productos'   => $k->getProductosMasRecibidos($fechaInicio, $fechaFin, $proveedorId),
                    'mensual'     => $k->getRecepcionesMensuales($anio)
                ];
                echo json_encode(['status' => 'success', 'data' => $resp], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
        break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida '.$accion.'']);
    }
    exit;
}

function getrecepcion() {
    $recepcion = new Recepcion();
    return $recepcion->getrecepcion(); // Consulta resumen: fecha, correlativo, proveedor, tamaño, costo inversión
}
$r = new Recepcion();
$RecepcionesProveedor = $r->getRecepcionesPorProveedor();
$ProductorRecibidos = $r->getProductosMasRecibidos();
$RecepcionMensual = $r->getRecepcionesMensuales();

$proveedores = (new Recepcion())->obtenerproveedor();
$pagina = "recepcion";
if (is_file("vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        '2',
        'ACCESAR',
        'El usuario accedió al modulo de Recepcion',
        'media'
    );}
    $recepciones = getrecepcion();
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>