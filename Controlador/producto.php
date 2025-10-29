<?php
ob_start();

require_once __DIR__ . '/../modelo/producto.php';
require_once __DIR__ . '/../modelo/permiso.php';
require_once __DIR__ . '/../modelo/bitacora.php';

define('MODULO_PRODUCTOS', 6);

$id_rol = $_SESSION['id_rol'] ?? 0;

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('productos'));

// Manejo de solicitudes POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'] ?? '';

    switch ($accion) {

        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('productos'));
            echo json_encode($permisosActualizados, JSON_UNESCAPED_UNICODE);
            exit;

        case 'ingresar':
            $Producto = new Productos();

            $Producto->setNombreP($_POST['nombre_producto'] ?? '');
            $Producto->setDescripcionP($_POST['descripcion_producto'] ?? '');
            $Producto->setIdModelo($_POST['modelo'] ?? null);
            $Producto->setStockActual($_POST['Stock_Actual'] ?? 0);
            $Producto->setStockMax($_POST['Stock_Maximo'] ?? 0);
            $Producto->setStockMin($_POST['Stock_Minimo'] ?? 0);
            $Producto->setClausulaDeGarantia($_POST['Clausula_garantia'] ?? '');
            $Producto->setCodigo($_POST['Seriales'] ?? '');
            $Producto->setCategoria($_POST['Categoria'] ?? '');
            $Producto->setPrecio($_POST['Precio'] ?? 0);

            if (!$Producto->validarNombreProducto()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Este Producto ya existe'], JSON_UNESCAPED_UNICODE);
                exit;
            } elseif (!$Producto->validarCodigoProducto()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'Este Código Interno ya existe'], JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                try {
                    $resultado = $Producto->ingresarProducto($_POST);
                    if ($resultado) {
                        $id_producto = $resultado;
                        if (!defined('SKIP_SIDE_EFFECTS')) {
                            $bitacoraModel = new Bitacora();
                            $bitacoraModel->registrarBitacora(
                                $_SESSION['id_usuario'],
                                MODULO_PRODUCTOS,
                                'INCLUIR',
                                'El usuario incluyó un nuevo producto: ' . ($_POST['nombre_producto'] ?? ''),
                                'media'
                            );
                        }

                        $respuesta = [
                            'status' => 'success',
                            'id_producto' => $id_producto
                        ];

                        // Procesar imagen si fue enviada
                        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                            $directorio = "img/productos/";
                            if (!is_dir($directorio)) {
                                mkdir($directorio, 0755, true);
                            }
                            $nombre_original = $_FILES['imagen']['name'];
                            $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                            $nombre_nuevo = "producto_" . $id_producto . "." . $extension;
                            $ruta_destino = $directorio . $nombre_nuevo;

                            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                                // Intentar guardar nombre en BD si el método existe
                                if (method_exists($Producto, 'guardarImagenProducto')) {
                                    // Guardar la ruta relativa completa, no solo el nombre
                                    $Producto->guardarImagenProducto($id_producto, $ruta_destino);
                                }
                                $respuesta['imagen'] = $ruta_destino;
                                $respuesta['mensaje'] = "Producto registrado e imagen guardada correctamente.";
                            } else {
                                $respuesta['imagen'] = null;
                                $respuesta['mensaje'] = "Producto registrado, pero error al guardar la imagen.";
                            }
                        } else {
                            $respuesta['mensaje'] = "Producto registrado correctamente.";
                        }

                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
                        exit;
                    } else {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['status' => 'error', 'message' => 'Error al registrar producto'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                } catch (Exception $e) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
            break;

        case 'obtener_producto':
            $id = $_POST['id_producto'] ?? null;
            header('Content-Type: application/json; charset=utf-8');
            if ($id !== null) {
                $Producto = new Productos();
                $producto = $Producto->obtenerProductoPorId($id);
                if ($producto !== null) {
                    echo json_encode($producto, JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado'], JSON_UNESCAPED_UNICODE);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de producto no proporcionado'], JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;

        case 'modificar':
            $id = $_POST['id_producto'] ?? null;
            if ($id === null) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'ID de producto no proporcionado'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $Producto = new Productos();
            $Producto->setId($id);
            $Producto->setNombreP($_POST['nombre_producto'] ?? '');
            $Producto->setDescripcionP($_POST['descripcion_producto'] ?? '');
            $Producto->setIdModelo($_POST['modelo'] ?? null);
            $Producto->setStockActual($_POST['Stock_Actual'] ?? 0);
            $Producto->setStockMax($_POST['Stock_Maximo'] ?? 0);
            $Producto->setStockMin($_POST['Stock_Minimo'] ?? 0);
            $Producto->setClausulaDeGarantia($_POST['Clausula_garantia'] ?? '');
            $Producto->setCodigo($_POST['Seriales'] ?? '');
            $Producto->setPrecio($_POST['Precio'] ?? 0);

            $productoViejo = $Producto->obtenerProductoPorId($id);

            try {
                if ($Producto->modificarProducto($id, $_POST)) {
                    // Procesar imagen si existe
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                        $directorio = "img/productos/";
                        if (!is_dir($directorio)) {
                            mkdir($directorio, 0755, true);
                        }
                        // Eliminar imagen anterior
                        $extensiones = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
                        foreach ($extensiones as $ext) {
                            $ruta_antigua = $directorio . 'producto_' . $id . '.' . $ext;
                            if (file_exists($ruta_antigua)) {
                                @unlink($ruta_antigua);
                            }
                        }
                        // Guardar la nueva imagen
                        $nombre_original = $_FILES['imagen']['name'];
                        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                        $nombre_nuevo = "producto_" . $id . "." . $extension;
                        $ruta_destino = $directorio . $nombre_nuevo;
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                            if (method_exists($Producto, 'guardarImagenProducto')) {
                                // Guardar la ruta relativa completa, no solo el nombre
                                $Producto->guardarImagenProducto($id, $ruta_destino);
                            }
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['status' => 'success', 'mensaje' => 'Producto modificado e imagen actualizada'], JSON_UNESCAPED_UNICODE);
                            exit;
                        } else {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la imagen'], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    } else {
                        // Sin imagen, solo éxito en modificación
                        $productoActualizado = $Producto->obtenerProductoPorId($id);
                        if (!defined('SKIP_SIDE_EFFECTS')) {
                            $bitacoraModel = new Bitacora();
                            $bitacoraModel->registrarBitacora(
                                $_SESSION['id_usuario'],
                                MODULO_PRODUCTOS,
                                'MODIFICAR',
                                'El usuario modificó el producto: ' . ($_POST['nombre_producto'] ?? '') . ' | Antes: ' . json_encode($productoViejo) . ' | Después: ' . json_encode($productoActualizado),
                                'media'
                            );
                        }

                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['status' => 'success', 'mensaje' => 'Producto modificado correctamente'], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                } else {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'error', 'message' => 'Error al modificar el producto'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            } catch (Exception $e) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            break;

        case 'eliminar':
            $id_producto = $_POST['id_producto'] ?? null;
            header('Content-Type: application/json; charset=utf-8');
            if ($id_producto === null) {
                echo json_encode(['status' => 'error', 'message' => 'ID del Producto no proporcionado'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $producto = new Productos();
            $response = $producto->eliminarProducto($id_producto);
            if (is_array($response) && ($response['success'] ?? false)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_PRODUCTOS,
                        'ELIMINAR',
                        'El usuario eliminó el producto ID: ' . $id_producto,
                        'media'
                    );
                }

                echo json_encode(['status' => 'success', 'message' => $response['message']], JSON_UNESCAPED_UNICODE);
            } else {
                $msg = is_array($response) ? ($response['message'] ?? 'Error al eliminar producto') : 'Error al eliminar producto';
                echo json_encode(['status' => 'error', 'message' => $msg], JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;

        case 'cambiar_estatus':
            $id = $_POST['id_producto'] ?? null;
            $nuevoEstatus = $_POST['nuevo_estatus'] ?? null;
            header('Content-Type: application/json; charset=utf-8');
            if ($id === null || $nuevoEstatus === null) {
                echo json_encode(['status' => 'error', 'message' => 'Parámetros insuficientes'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if (!in_array($nuevoEstatus, ['habilitado', 'inhabilitado'])) {
                echo json_encode(['status' => 'error', 'message' => 'Estatus no válido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $producto = new Productos();
            $producto->setId($id);
            if ($producto->cambiarEstatus($nuevoEstatus)) {
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_PRODUCTOS,
                        'CAMBIAR ESTATUS',
                        'El usuario cambió el estatus del producto ID ' . $id . ' a ' . $nuevoEstatus,
                        'media'
                    );
                }
                echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al cambiar el estatus'], JSON_UNESCAPED_UNICODE);
            }
            exit;
            break;

        case 'reporte_parametrizado':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'reporte_parametrizado') {
                $tipoReporte = $_POST['tipoReporte'] ?? '';
                $categoria = $_POST['categoriaSeleccionada'] ?? '';
                $productoModel = new Productos();
                if ($tipoReporte === 'por_categoria') {
                    $datos = $productoModel->obtenerReporteCategorias();
                    $labels = array_column($datos, 'nombre_categoria');
                    $data = array_column($datos, 'cantidad');
                } elseif ($tipoReporte === 'por_categoria_especifica' && $categoria) {
                    $datos = $productoModel->obtenerProductosPorCategoria($categoria);
                    $labels = array_column($datos, 'nombre_producto');
                    $data = array_column($datos, 'stock');
                } elseif ($tipoReporte === 'precios') {
                    $datos = $productoModel->obtenerProductosConPrecios();
                    $labels = array_column($datos, 'nombre_producto');
                    $data = array_column($datos, 'precio');
                } else {
                    $labels = [];
                    $data = [];
                }
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['labels' => $labels, 'data' => $data], JSON_UNESCAPED_UNICODE);
                exit;
            }
            break;

        default:
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida'], JSON_UNESCAPED_UNICODE);
            exit;
            break;
    }
}

// Funciones auxiliares y carga de vista

function obtenerModelos() {
    $ModelosModel = new Productos();
    return $ModelosModel->obtenerModelos();
}

function obtenerProductos() {
    $producto = new Producto();
    return $producto->obtenerProductos();
}

$productoModel = new Productos();
$masVendidos   = $productoModel->getProductosMasVendidos();
$stockProductos = $productoModel->getStockProductos();
$rotacion      = $productoModel->getRotacionProductos();
$categorias = $productoModel->CategoriasReporte();
$categoriasDinamicas = $productoModel->obtenerCategoriasDinamicas();
$reporteCategorias = $productoModel->obtenerReporteCategorias();

if (!$reporteCategorias || !is_array($reporteCategorias)) {
    $reporteCategorias = [];
}

$totalCategorias = array_sum(array_column($reporteCategorias, 'cantidad'));
foreach ($reporteCategorias as &$cat) {
    $cat['porcentaje'] = $totalCategorias > 0 ? round(($cat['cantidad'] / $totalCategorias) * 100, 2) : 0;
}
unset($cat);

$mostrarFormulario = !empty($categoriasDinamicas);
$pagina = "producto";
if (is_file("vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                '6',
                'ACCESAR',
                'El usuario accedió al modulo de Productos',
                'media'
            );
        }
    }

    $modelos = obtenerModelos();
    $productos = obtenerProductos();
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>