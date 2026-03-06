<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Catalogo;

// Definir constantes para IDs de módulo
define('MODULO_CATALOGO', "Catalogo");

$esAdmin = isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] == 'Administrador';

$data = [];
$dolarService = new DolarService();
$precioDolar = $dolarService->obtenerPrecioDolar();
$dolarService->guardarPrecioCache($precioDolar);

$productosModel = new Productos();
$catalogoModel = new Catalogo('P');
$bitacoraModel = new Bitacora();
// Manejar generación de reportes PDF
try {
    $dolarService = new DolarService();
    $precioDolar = $dolarService->obtenerPrecioDolar();
    $dolarService->guardarPrecioCache($precioDolar);
    
    // Asignar a $data
    $data['monitors'] = [
        'bcv' => [
            'price' => $precioDolar,
            'updated' => date('Y-m-d H:i:s')
        ]
    ];
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    $data['monitors'] = [
        'bcv' => [
            'price' => 35.50,
            'updated' => date('Y-m-d H:i:s') . ' (valor por defecto)'
        ]
    ];
    error_log('Error obteniendo precio dólar: ' . $e->getMessage());
}

// Manejar generación de reportes PDF
if (isset($_GET['reporte']) && $esAdmin) {
    switch ($_GET['reporte']) {
        case 'accesos_semanales':
            $datosAccesos = $bitacoraModel->obtenerEstadisticasAccesos();
            $pdf = new PDF('Reporte de Accesos', 'Estadísticas semanales de visitas al catálogo');
            $pdf->generarReporteAccesos($datosAccesos);
            exit;
            
        case 'usuarios_activos':
            $usuariosActivos = $bitacoraModel->obtenerUsuariosMasActivos(10);
            $pdf = new PDF('Reporte de Usuarios Activos', 'Top 10 usuarios con más accesos al catálogo');
            $pdf->generarReporteUsuarios($usuariosActivos);
            exit;
    }
}

function generarReporteAccesosSemanales($bitacoraModel) {
    $datos = $bitacoraModel->obtenerEstadisticasAccesoSemanal();
    $pdf = new PDF();
    $pdf->generarReporteAccesos($datos);
}

// Manejar solicitudes AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {

    try {
    $productos = $productosModel->obtenerProductosConMarca();
    $marcas = $productosModel->obtenerMarcas();
    $esAdmin = isset($_SESSION['nombre_rol']) && 
           ($_SESSION['nombre_rol'] == 'Administrador' || 
            $_SESSION['nombre_rol'] == 'SuperUsuario');
    $combos = $productosModel->obtenerCombosDisponibles($esAdmin);
    
    // Pasar el precio del dólar a la vista
    $data['monitors'] = [
        'bcv' => [
            'price' => $precioDolar,
            'updated' => date('Y-m-d H:i:s')
        ]
    ];
    
} catch (PDOException $e) {
    $productos = [];
    $marcas = [];
    $combos = [];
    $data['monitors'] = [
        'bcv' => [
            'price' => $precioDolar,
            'updated' => date('Y-m-d H:i:s')
        ]
    ];
}

    try {
        header('Content-Type: application/json; charset=utf-8');
        $accion = $_POST['accion'];

        if ($accion == 'obtener_datos_reportes') {
            try {
                // Obtener estadísticas de accesos
                $estadisticas = $bitacoraModel->obtenerEstadisticasAccesos();
                
                // Obtener usuarios más activos
                $usuariosActivos = $bitacoraModel->obtenerUsuariosMasActivos(10);
                
                echo json_encode([
                    'status' => 'success',
                    'estadisticas' => $estadisticas,
                    'usuarios' => $usuariosActivos
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }

        if ($accion == 'filtrar_por_marca') {
            $id_marca_raw = $_POST['id_marca'] ?? '';
            $id_marca = is_numeric($id_marca_raw) ? (int)$id_marca_raw : 0;
            
            // Validar datos usando las nuevas validaciones centralizadas
            $datos_validacion = ['id_marca' => $id_marca];
            $errores = $catalogoModel->validarFiltrar($datos_validacion);
            
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos para filtrar',
                    'errors' => $errores
                ]);
                exit;
            }
            
            if ($id_marca > 0) {
                $productos = $productosModel->obtenerProductosPorMarca($id_marca);
            } else {
                $productos = $productosModel->obtenerProductosConMarca();
            }

            // Registrar filtrado
            if (!defined('SKIP_SIDE_EFFECTS')) {
                $marcaFiltro = $id_marca ? " (Marca ID: $id_marca)" : "";
                $bitacoraModel->registrarBitacora(
                    $_SESSION['id_usuario'],
                    MODULO_CATALOGO,
                    'CONSULTAR',
                    "Filtrado de productos por marca" . $marcaFiltro,
                    'baja'
                );
            }

            if (!empty($productos)) {
                $html = '';
                foreach ($productos as $producto) {
                    $html .= '<tr class="product-row" data-id="' . htmlspecialchars($producto['id_producto']) . '">
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm btn-agregar-carrito" 
                                            data-id-producto="' . htmlspecialchars($producto['id_producto']) . '">
                                        <i class="bi bi-cart-plus"></i> <span class="btn-text">Agregar</span>
                                    </button>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">';
                    if (!empty($producto['imagen'])) {
                        $html .= '<img src="' . htmlspecialchars($producto['imagen']) . '" class="product-image"
                                    alt="' . htmlspecialchars($producto['nombre_producto']) . '"
                                    onerror="this.src=\'assets/img/placeholder-product.png\'">';
                    } else {
                        $html .= '<div class="product-image img-placeholder">
                                    <i class="bi bi-image"></i>
                                  </div>';
                    }
                    $html .= '<div>
                                <strong>' . htmlspecialchars($producto['nombre_producto']) . '</strong>
                                <div class="text-muted small">' . htmlspecialchars($producto['serial']) . '</div>
                              </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge ' . ($producto['stock'] > 0 ? 'bg-success' : 'bg-danger') . ' stock-badge">
                                ' . htmlspecialchars($producto['stock']) . '
                            </span>
                        </td>
                        <td>' . htmlspecialchars($producto['descripcion_producto']) . '</td>
                        <td>' . htmlspecialchars($producto['marca']) . '</td>
                        <td class="fw-bold">$' . number_format($producto['precio'], 2) . '</td>
                    </tr>';
                }
                echo json_encode(['status' => 'success', 'html' => $html]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No hay productos disponibles',
                    'html' => '<tr><td colspan="6" class="text-center py-4"><i class="bi bi-exclamation-circle"></i> No hay productos disponibles para esta selección</td></tr>'
                ]);
            }
            exit;
        }

        if ($accion == 'validar_stock') {
            try {
                header('Content-Type: application/json; charset=utf-8');
                $id_producto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
                $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
                if ($id_producto <= 0) {
                    throw new Exception('Producto no especificado o inválido');
                }
                if ($cantidad <= 0) {
                    throw new Exception('La cantidad debe ser mayor a cero');
                }
                $producto = $productosModel->obtenerProductoPorId($id_producto);
                if (!$producto) {
                    throw new Exception('Producto no encontrado');
                }

                $stock = (int)($producto['stock'] ?? 0);
                echo json_encode([
                    'status' => 'success',
                    'stock_disponible' => $stock,
                    'suficiente' => $stock >= $cantidad
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }

        if ($accion == 'agregar_al_carrito') {
            try {
                if (!isset($_SESSION['id_usuario'])) {
                    throw new Exception('Debe iniciar sesión para agregar productos al carrito');
                }

                $id_producto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;
                $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;

                // Validar datos usando las nuevas validaciones centralizadas
                $datos_validacion = [
                    'id_producto' => $id_producto,
                    'cantidad' => $cantidad,
                    'id_combo' => $id_combo
                ];
                $errores = $catalogoModel->validarAgregarCarrito($datos_validacion);
                
                if (!empty($errores)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error en los datos para agregar al carrito',
                        'errors' => $errores
                    ]);
                    exit;
                }

                $producto = $productosModel->obtenerProductoPorId($id_producto);
                if (!$producto) {
                    throw new Exception('Producto no encontrado');
                }

                $nombreProducto = $producto['nombre_producto'];

                $result = $productosModel->agregarProductoAlCarrito($_SESSION['id_usuario'], $id_producto, $cantidad);

                if ($result === true) {
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_CATALOGO,
                            'INCLUIR',
                            "El usuario agregó producto al carrito: $nombreProducto (Cantidad: $cantidad)",
                            'alta'
                        );
                    }

                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Producto agregado correctamente al carrito'
                    ]);
                } else {
                    $mensaje = is_string($result) ? $result : 'Error al agregar producto al carrito';
                    throw new Exception($mensaje);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }

        if ($accion == 'crear_combo') {
            try {
                $nombre = $_POST['nombre_combo'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $productos = $_POST['productos'] ?? [];

                $nombre = trim($nombre);

                if ($nombre === '') {
                    throw new Exception('El nombre del combo es requerido');
                }

                if (!is_array($productos) || empty($productos)) {
                    throw new Exception('Debe seleccionar al menos un producto para el combo');
                }

                $productosValidos = [];
                foreach ($productos as $producto) {
                    $id = isset($producto['id']) ? (int)$producto['id'] : 0;
                    $cantidad = isset($producto['cantidad']) ? (int)$producto['cantidad'] : 0;

                    if ($id <= 0 || $cantidad <= 0) {
                        throw new Exception('Los productos del combo deben tener un ID y una cantidad válidos');
                    }

                    $infoProducto = $productosModel->obtenerProductoPorId($id);
                    if (!$infoProducto) {
                        throw new Exception('Uno de los productos seleccionados para el combo no existe');
                    }

                    $productosValidos[] = [
                        'id' => $id,
                        'cantidad' => $cantidad
                    ];
                }

                if (empty($productosValidos)) {
                    throw new Exception('Debe seleccionar al menos un producto válido para el combo');
                }

                $id_combo = $productosModel->crearCombo($nombre, $descripcion, $productosValidos);

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CATALOGO,
                        'INCLUIR',
                        "El usuario creó un nuevo combo: $nombre (ID: $id_combo)",
                        'alta'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Combo creado exitosamente',
                    'id_combo' => $id_combo
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($accion == 'cambiar_estado_combo') {
            try {
                header('Content-Type: application/json; charset=utf-8');
                
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;
                
                if ($id_combo <= 0) {
                    throw new Exception('ID de combo no especificado o inválido');
                }
                
                $combo = $productosModel->obtenerComboPorId($id_combo);
                if (!$combo) {
                    throw new Exception('Combo no encontrado');
                }
                
                $resultado = $productosModel->cambiarEstadoCombo($id_combo);
                
                if ($resultado) {
                    $nuevoEstado = $productosModel->obtenerComboPorId($id_combo)['activo'];
                    $accionEstado = $nuevoEstado ? 'habilitó' : 'deshabilitó';
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_CATALOGO,
                            'CAMBIAR_ESTADO',
                            "El usuario $accionEstado el combo: {$combo['nombre_combo']} (ID: $id_combo)",
                            'media'
                        );
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Estado del combo actualizado correctamente',
                    'nuevo_estado' => $productosModel->obtenerComboPorId($id_combo)['activo']
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }
        
        if ($accion == 'actualizar_combo') {
            try {
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;
                $nombre = $_POST['nombre_combo'] ?? '';
                $descripcion = $_POST['descripcion'] ?? '';
                $productos = $_POST['productos'] ?? [];

                $nombre = trim($nombre);

                if ($id_combo <= 0) {
                    throw new Exception('ID de combo no especificado o inválido');
                }

                if ($nombre === '') {
                    throw new Exception('El nombre del combo es requerido');
                }

                if (!is_array($productos) || empty($productos)) {
                    throw new Exception('Debe seleccionar al menos un producto para el combo');
                }

                $comboAntes = $productosModel->obtenerComboPorId($id_combo);
                if (!$comboAntes) {
                    throw new Exception('El combo que intenta actualizar no existe');
                }

                $productosValidos = [];
                foreach ($productos as $producto) {
                    $id = isset($producto['id']) ? (int)$producto['id'] : 0;
                    $cantidad = isset($producto['cantidad']) ? (int)$producto['cantidad'] : 0;

                    if ($id <= 0 || $cantidad <= 0) {
                        throw new Exception('Los productos del combo deben tener un ID y una cantidad válidos');
                    }

                    $infoProducto = $productosModel->obtenerProductoPorId($id);
                    if (!$infoProducto) {
                        throw new Exception('Uno de los productos seleccionados para el combo no existe');
                    }

                    $productosValidos[] = [
                        'id' => $id,
                        'cantidad' => $cantidad
                    ];
                }

                if (empty($productosValidos)) {
                    throw new Exception('Debe seleccionar al menos un producto válido para el combo');
                }

                $result = $productosModel->actualizarCombo($id_combo, $nombre, $descripcion, $productosValidos);

                if ($result) {
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_CATALOGO,
                            'MODIFICAR',
                            "El usuario modificó el combo: {$comboAntes['nombre_combo']} (ID: $id_combo)",
                            'media'
                        );
                    }
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Combo actualizado exitosamente'
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($accion == 'eliminar_combo') {
            try {
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;

                if ($id_combo <= 0) {
                    throw new Exception('ID de combo no especificado o inválido');
                }

                $combo = $productosModel->obtenerComboPorId($id_combo);
                if (!$combo) {
                    throw new Exception('El combo que intenta eliminar no existe');
                }

                $result = $productosModel->eliminarCombo($id_combo);

                if ($result) {
                    if (!defined('SKIP_SIDE_EFFECTS')) {
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_CATALOGO,
                            'ELIMINAR',
                            "El usuario eliminó el combo: {$combo['nombre_combo']} (ID: $id_combo)",
                            'media'
                        );
                    }
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Combo eliminado exitosamente'
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($accion == 'obtener_detalles_combo') {
            try {
                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;

                if ($id_combo <= 0) {
                    throw new Exception('ID de combo no especificado o inválido');
                }

                $combo = $productosModel->obtenerComboPorId($id_combo);
                if (!$combo) {
                    throw new Exception('Combo no encontrado');
                }

                $detalles = $productosModel->obtenerDetallesCombo($id_combo);

                echo json_encode([
                    'status' => 'success',
                    'combo' => $combo,
                    'detalles' => $detalles
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
        }

        if ($accion == 'agregar_combo_al_carrito') {
            try {
                if (!isset($_SESSION['id_usuario'])) {
                    throw new Exception('Debe iniciar sesión para agregar combos');
                }

                $id_combo = isset($_POST['id_combo']) ? (int)$_POST['id_combo'] : 0;
                if ($id_combo <= 0) {
                    throw new Exception('No se especificó el combo o el identificador es inválido');
                }

                $combo = $productosModel->obtenerComboPorId($id_combo);
                if (!$combo || (isset($combo['activo']) && !$combo['activo'])) {
                    throw new Exception('El combo no está disponible');
                }

                $result = $productosModel->agregarComboAlCarrito($_SESSION['id_usuario'], $id_combo);
                if ($result !== true) {
                    $mensaje = is_string($result) ? $result : 'Error al agregar combo al carrito';
                    throw new Exception($mensaje);
                }

                $detalles = $productosModel->obtenerDetallesCombo($id_combo);

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CATALOGO,
                        'INCLUIR',
                        "El usuario agregó combo al carrito: {$combo['nombre_combo']} (ID: $id_combo, Productos: ".count($detalles).")",
                        'alta'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Combo agregado correctamente al carrito',
                    'productos_agregados' => count($detalles)
                ]);
            } catch (Exception $e) {
               echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Obtener datos para la vista
try {
    $productos = $productosModel->obtenerProductosConMarca();
    $marcas = $productosModel->obtenerMarcas();
    $esAdmin = isset($_SESSION['nombre_rol']) && 
           ($_SESSION['nombre_rol'] == 'Administrador' || 
            $_SESSION['nombre_rol'] == 'SuperUsuario');
    $combos = $productosModel->obtenerCombosDisponibles($esAdmin);
    
} catch (PDOException $e) {
    $productos = [];
    $marcas = [];
    $combos = [];
    // Mantener los datos del dólar incluso si hay error en la BD
}

// Asignar la página y cargar la vista
$pagina = "catalogo";
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_CATALOGO,
            'ACCESAR',
            'El usuario accedió al módulo de Catálogo',
            'media'
        );
    }
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>