<?php
// Requires organizados al inicio
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Recepcion;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Config\BD;

define('MODULO_RECEPCION', "Recepcion"); // Define el ID del módulo de cuentas bancarias

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

            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $datos_validacion = [
                'idproveedor' => $_POST['proveedor'],
                'correlativo' => $_POST['correlativo'],
                'estado' => 'habilitado'
            ];

            $errores = $k->validarRegistrar($datos_validacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos de la recepción',
                    'errors' => $errores
                ]);
                exit;
            }

            // Procesar datos de verificación IA si existen
            $datos_ia = null;
            if (isset($_POST['ia_verificacion']) && !empty($_POST['ia_verificacion'])) {
                $datos_ia = json_decode($_POST['ia_verificacion'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $datos_ia = null;
                }
            }

            // Procesar imagen de factura si se subió
            $ruta_factura = null;
            if (isset($_FILES['ia_factura_imagen']) && $_FILES['ia_factura_imagen']['error'] === UPLOAD_ERR_OK) {
                $archivo_factura = $_FILES['ia_factura_imagen'];
                
                // Validar tipo de archivo
                $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'];
                if (!in_array($archivo_factura['type'], $tipos_permitidos)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Tipo de archivo no permitido. Use JPG, PNG o BMP.',
                        'errors' => ['ia_factura_imagen' => 'Tipo de archivo inválido']
                    ]);
                    exit;
                }
                
                // Validar tamaño (máximo 10MB)
                $tamano_maximo = 10 * 1024 * 1024; // 10MB
                if ($archivo_factura['size'] > $tamano_maximo) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'El archivo es demasiado grande. Máximo 10MB.',
                        'errors' => ['ia_factura_imagen' => 'Archivo demasiado grande']
                    ]);
                    exit;
                }
                
                // Generar nombre único y guardar archivo
                $extension = pathinfo($archivo_factura['name'], PATHINFO_EXTENSION);
                $nombre_archivo = 'factura_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
                $ruta_destino = 'assets/img/comprobantes/facturas/' . $nombre_archivo;
                
                // Crear directorio si no existe
                $directorio_destino = dirname($ruta_destino);
                if (!is_dir($directorio_destino)) {
                    mkdir($directorio_destino, 0755, true);
                }
                
                if (move_uploaded_file($archivo_factura['tmp_name'], $ruta_destino)) {
                    $ruta_factura = $ruta_destino;
                } else {
                    error_log("Error al guardar la factura: " . $archivo_factura['error']);
                }
            }

            $productos_data = [
                'idproducto' => $_POST['producto'],
                'cantidad' => $_POST['cantidad'],
                'costo' => $_POST['costo']
            ];

            $k->setidproveedor($_POST['proveedor']);
            $k->setcorrelativo($_POST['correlativo']);
            $k->setestado('habilitado');

            $resultado = $k->registrarRecepcion(
                $_POST['producto'],
                $_POST['cantidad'],
                $_POST['costo']
            );

            $recepcionRegistrada = $k->obtenerUltimaRecepcion();

            if ($resultado && $recepcionRegistrada) {
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
            $correlativo = $_POST['correlativo'] ?? '';
            
            $datos_validacion = ['correlativo' => $correlativo];
            $errores = $k->validarAnular($correlativo);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para anular la recepción',
                    'errors' => $errores
                ]);
                exit;
            }
            
            // Verificar que la recepción exista antes de anular
            $k->setcorrelativo($correlativo);
            $recepcionExistente = $k->buscar();
            if (!$recepcionExistente || $recepcionExistente['resultado'] !== 'encontró') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'La recepción no existe o ya fue anulada'
                ]);
                exit;
            }
            
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

            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $datos_validacion = [
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'anio' => $anio,
                'proveedorId' => $proveedorId
            ];
            $errores = $k->validarReporte($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para generar el reporte',
                    'errors' => $errores
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

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
if (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        'Recepcion',
        'ACCESAR',
        'El usuario accedió al módulo de Recepcion',
        'media'
    );}
    $recepciones = getrecepcion();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>