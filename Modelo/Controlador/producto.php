<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

define('MODULO_PRODUCTOS', "Productos");

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
            // DEPURACIÓN: Registrar datos recibidos
            error_log("=== DEPURACIÓN: INICIANDO REGISTRO DE PRODUCTO ===");
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            $Producto = new Productos();

            $nombre_producto = $_POST['nombre_producto'] ?? '';
            $descripcion_producto = $_POST['descripcion_producto'] ?? '';
            $modelo = $_POST['modelo'] ?? null;
            $Stock_Actual = $_POST['Stock_Actual'] ?? 0;
            $Stock_Maximo = $_POST['Stock_Maximo'] ?? 0;
            $Stock_Minimo = $_POST['Stock_Minimo'] ?? 0;
            $Clausula_garantia = $_POST['Clausula_garantia'] ?? '';
            $Seriales = $_POST['Seriales'] ?? '';
            $Categoria = $_POST['Categoria'] ?? '';
            $Precio = $_POST['Precio'] ?? 0;
            $Producto->setNombreP($nombre_producto);
            $Producto->setDescripcionP($descripcion_producto);
            $Producto->setIdModelo($modelo);
            $Producto->setStockActual($Stock_Actual);
            $Producto->setStockMax($Stock_Maximo);
            $Producto->setStockMin($Stock_Minimo);
            $Producto->setClausulaDeGarantia($Clausula_garantia);
            $Producto->setCodigo($Seriales);
            $Producto->setCategoria($Categoria);
            $Producto->setPrecio($Precio);
            
            error_log("Datos extraídos:");
            error_log("  nombre_producto: " . $nombre_producto);
            error_log("  descripcion_producto: " . $descripcion_producto);
            error_log("  modelo: " . $modelo);
            error_log("  Categoria: " . $Categoria);
            error_log("  Precio: " . $Precio);

            // Preparar datos para validación
            $datos_validacion = [
                'nombre_producto' => $nombre_producto,
                'descripcion_producto' => $descripcion_producto,
                'id_modelo' => $modelo,
                'stock_actual' => $Stock_Actual,
                'stock_maximo' => $Stock_Maximo,
                'stock_minimo' => $Stock_Minimo,
                'clausula_garantia' => $Clausula_garantia,
                'serial' => $Seriales,
                'categoria' => $Categoria,
                'precio' => $Precio
            ];

            // Validar datos del producto usando las nuevas validaciones centralizadas
            error_log("Validando datos del producto...");
            $errores = $Producto->validarRegistrarProducto($datos_validacion);
            if (!empty($errores)) {
                error_log("❌ Errores de validación encontrados: " . print_r($errores, true));
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos del producto',
                    'errors' => $errores
                ]);
                exit;
            }
            error_log("✅ Validación de datos exitosa");

            // Validar que el modelo exista antes de registrar
            if ($_POST['modelo'] ?? null) {
                $modeloValido = false;
                // Aquí deberías tener una función para verificar si el modelo existe
                // Por ahora, asumimos que si hay un ID, es válido
                $idModelo = (int)$_POST['modelo'];
                if ($idModelo > 0) {
                    $modeloValido = true;
                }
                
                if (!$modeloValido) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'El modelo seleccionado no es válido'
                    ]);
                    exit;
                }
            }

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
                            // Validar imagen usando las nuevas validaciones
                            $imagenData = [
                                'name' => $_FILES['imagen']['name'],
                                'tmp_name' => $_FILES['imagen']['tmp_name'],
                                'error' => $_FILES['imagen']['error'],
                                'size' => $_FILES['imagen']['size']
                            ];
                            $errores_imagen = $Producto->validarImagen($imagenData);
                            
                            if (!empty($errores_imagen)) {
                                $respuesta['imagen_error'] = $errores_imagen;
                            } else {
                                $directorio = "../assets/img/productos/";
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
            
            // Preparar datos para validación
            $datos_validacion = [
                'id_producto' => $id,
                'nombre_producto' => $_POST['nombre_producto'] ?? '',
                'descripcion_producto' => $_POST['descripcion_producto'] ?? '',
                'id_modelo' => $_POST['modelo'] ?? null,
                'stock_actual' => $_POST['Stock_Actual'] ?? 0,
                'stock_maximo' => $_POST['Stock_Maximo'] ?? 0,
                'stock_minimo' => $_POST['Stock_Minimo'] ?? 0,
                'clausula_garantia' => $_POST['Clausula_garantia'] ?? '',
                'serial' => $_POST['Seriales'] ?? '',
                'precio' => $_POST['Precio'] ?? 0
            ];

            // Validar datos del producto usando las nuevas validaciones centralizadas
            $errores = $Producto->validarModificarProducto($datos_validacion);
            if (!empty($errores)) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos del producto',
                    'errors' => $errores
                ]);
                exit;
            }

            // Verificar que el producto exista antes de modificar
            $productoExistente = $Producto->obtenerProductoPorId($id);
            if (!$productoExistente) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El producto que intenta modificar no existe'
                ]);
                exit;
            }

            // Validar que el modelo exista antes de modificar
            if ($_POST['modelo'] ?? null) {
                $modeloValido = false;
                $idModelo = (int)$_POST['modelo'];
                if ($idModelo > 0) {
                    $modeloValido = true;
                }
                
                if (!$modeloValido) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'El modelo seleccionado no es válido'
                    ]);
                    exit;
                }
            }

            $productoViejo = $Producto->obtenerProductoPorId($id);

            // ESTABLECER DATOS EN EL OBJETO PRODUCTO (FALTABA)
            $Producto->setNombreP($_POST['nombre_producto'] ?? '');
            $Producto->setDescripcionP($_POST['descripcion_producto'] ?? '');
            $Producto->setIdModelo($_POST['modelo'] ?? null);
            $Producto->setStockActual($_POST['Stock_Actual'] ?? 0);
            $Producto->setStockMax($_POST['Stock_Maximo'] ?? 0);
            $Producto->setStockMin($_POST['Stock_Minimo'] ?? 0);
            $Producto->setClausulaDeGarantia($_POST['Clausula_garantia'] ?? '');
            $Producto->setCodigo($_POST['Seriales'] ?? '');
            $Producto->setPrecio($_POST['Precio'] ?? 0);

            try {
                if ($Producto->modificarProducto($id, $_POST)) {
                    // Procesar imagen si existe
                    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                        // Validar imagen usando las nuevas validaciones
                        $imagenData = [
                            'name' => $_FILES['imagen']['name'],
                            'tmp_name' => $_FILES['imagen']['tmp_name'],
                            'error' => $_FILES['imagen']['error'],
                            'size' => $_FILES['imagen']['size']
                        ];
                        $errores_imagen = $Producto->validarImagen($imagenData, false); // false = imagen opcional en edición
                        
                        if (!empty($errores_imagen)) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode([
                                'status' => 'error',
                                'message' => 'Error en la imagen',
                                'imagen_errors' => $errores_imagen
                            ], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                        
                        $directorio = "../assets/img/productos/";
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
            
            // Validar ID del producto
            $id_producto = (int)$id_producto;
            if ($id_producto <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El ID del producto no es válido'
                ]);
                exit;
            }
            
            $producto = new Productos();
            
            // Verificar que el producto exista antes de eliminar
            $productoExistente = $producto->obtenerProductoPorId($id_producto);
            if (!$productoExistente) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El producto que intenta eliminar no existe'
                ]);
                exit;
            }
            
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
            
            // Validar datos de entrada usando las nuevas validaciones centralizadas
            $Producto = new Productos();
            $datos_validacion = [
                'id_producto' => $id,
                'nuevo_estatus' => $nuevoEstatus
            ];
            $errores = $Producto->validarCambiarEstatus($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para cambiar estatus',
                    'errors' => $errores
                ], JSON_UNESCAPED_UNICODE);
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
    $Producto = new Productos();
    return $Producto->obtenerModelos();
}

function obtenerProductos() {
    $Producto = new Productos();
    return $Producto->obtenerProductos();
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
if (is_file("Vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                'Productos',
                'ACCESAR',
                'El usuario accedió al módulo de Productos',
                'media'
            );
        }
    }

    $modelos = obtenerModelos();
    $productos = obtenerProductos();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>