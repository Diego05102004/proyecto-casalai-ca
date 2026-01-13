<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Evitar caché en páginas protegidas
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Carrito;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Factura;

define('MODULO_CARRITO', 11);

if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

$permisosModel = new Permisos();
$permisosUsuario = $permisosModel->getPermisosPorRolModulo();
$data = [];
$dolarService = new DolarService();
$precioDolar = $dolarService->obtenerPrecioDolar();
$dolarService->guardarPrecioCache($precioDolar);

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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json; charset=utf-8');
    $accion = $_POST['accion'] ?? '';
    
    switch ($accion) {

        case 'agregar_al_carrito':
            $id_producto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : 0;

            if ($id_producto <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ID de producto no válido'
                ]);
                break;
            }

            $carrito = new Carrito();
            $productoModel = new Productos();
            $id_cliente = $_SESSION['id_usuario']; // Obtener de la sesión
            
            try {
                $producto = $productoModel->obtenerProductoPorId($id_producto);
                if (!$producto) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'El producto no existe'
                    ]);
                    break;
                }

                $cantidad = 1; // Cantidad predeterminada
                
                if ((int)($producto['stock'] ?? 0) < $cantidad) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No hay stock suficiente para agregar el producto al carrito'
                    ]);
                    break;
                }

                $carritoCliente = $carrito->obtenerCarritoPorCliente($id_cliente);
                
                if (!$carritoCliente) {
                    $carrito->crearCarrito($id_cliente);
                    $carritoCliente = $carrito->obtenerCarritoPorCliente($id_cliente);
                }
                
                $id_carrito = $carritoCliente['id_carrito'];
                
                if ($carrito->agregarProductoAlCarrito($id_carrito, $id_producto, $cantidad)) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Producto agregado al carrito correctamente'
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Error al agregar el producto al carrito'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            
            break;

        case 'actualizar_cantidad':
            $id_carrito_detalle = isset($_POST['id_carrito_detalle']) ? (int)$_POST['id_carrito_detalle'] : 0;
            $cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;

            if ($id_carrito_detalle <= 0 || $cantidad <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Datos de cantidad o detalle del carrito inválidos']);
                break;
            }

            $carrito = new Carrito();
            $productoModel = new Productos();
            $id_cliente = $_SESSION['id_usuario'];

            try {
                if (method_exists($carrito, 'obtenerDetallePorId')) {
                    $detalle = $carrito->obtenerDetallePorId($id_carrito_detalle);
                } else {
                    $detalle = null;
                }

                if (!$detalle || (int)$detalle['id_cliente'] !== (int)$id_cliente) {
                    echo json_encode(['status' => 'error', 'message' => 'Detalle de carrito no encontrado']);
                    break;
                }

                $producto = $productoModel->obtenerProductoPorId($detalle['id_producto']);
                if (!$producto) {
                    echo json_encode(['status' => 'error', 'message' => 'Producto asociado al carrito no existe']);
                    break;
                }

                if ($cantidad > (int)($producto['stock'] ?? 0)) {
                    echo json_encode(['status' => 'error', 'message' => 'No hay stock suficiente para la cantidad solicitada']);
                    break;
                }

                if ($carrito->actualizarCantidadProducto($id_carrito_detalle, $cantidad)) {
                    echo json_encode(['status' => 'success', 'message' => 'Cantidad actualizada correctamente']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la cantidad']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'eliminar_del_carrito':
            $id_carrito_detalle = isset($_POST['id_carrito_detalle']) ? (int)$_POST['id_carrito_detalle'] : 0;
            if ($id_carrito_detalle <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID de detalle del carrito no proporcionado o inválido']);
                break;
            }
            $carrito = new Carrito();
            $id_cliente = $_SESSION['id_usuario'];
            try {
                if (method_exists($carrito, 'obtenerDetallePorId')) {
                    $detalle = $carrito->obtenerDetallePorId($id_carrito_detalle);
                } else {
                    $detalle = null;
                }

                if (!$detalle || (int)$detalle['id_cliente'] !== (int)$id_cliente) {
                    echo json_encode(['status' => 'error', 'message' => 'Detalle de carrito no encontrado']);
                    break;
                }

                if ($carrito->eliminarProductoDelCarrito($id_carrito_detalle)) {
                    echo json_encode(['status' => 'success', 'message' => 'Producto eliminado del carrito']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el producto del carrito']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'eliminar_todo_carrito':
            $id_cliente = $_SESSION['id_usuario']; // Obtener de la sesión

            $carrito = new Carrito();
            $carritoCliente = $carrito->obtenerCarritoPorCliente($id_cliente);

            if ($carritoCliente) {
                $id_carrito = $carritoCliente['id_carrito'];
                if ($carrito->eliminarTodoElCarrito($id_carrito)) {
                    echo json_encode(['status' => 'success', 'message' => 'Carrito vaciado correctamente']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error al vaciar el carrito']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró el carrito del cliente']);
            }
            break;

        case 'registrar_compra':
            if (!isset($_SESSION['cedula'])) {
                echo json_encode(['status' => 'error', 'message' => 'Sesión de cliente no válida']);
                break;
            }

            $productos = $_POST['productos'] ?? [];
            $cantidades = $_POST['cantidad'] ?? [];

            if (!is_array($productos) || !is_array($cantidades) || empty($productos) || count($productos) !== count($cantidades)) {
                echo json_encode(['status' => 'error', 'message' => 'Datos de productos o cantidades inválidos']);
                break;
            }

            $productosValidados = [];
            $cantidadesValidadas = [];
            $productoModel = new Productos();
            $errorMensaje = null;

            foreach ($productos as $index => $idProductoRaw) {
                $idProducto = (int)$idProductoRaw;
                $cantidad = isset($cantidades[$index]) ? (int)$cantidades[$index] : 0;

                if ($idProducto <= 0 || $cantidad <= 0) {
                    $errorMensaje = 'Todos los productos deben tener un ID y cantidad válidos';
                    break;
                }

                $producto = $productoModel->obtenerProductoPorId($idProducto);
                if (!$producto) {
                    $errorMensaje = 'Uno de los productos de la compra no existe';
                    break;
                }

                if ($cantidad > (int)($producto['stock'] ?? 0)) {
                    $errorMensaje = 'No hay stock suficiente para uno de los productos de la compra';
                    break;
                }

                $productosValidados[] = $idProducto;
                $cantidadesValidadas[] = $cantidad;
            }

            if ($errorMensaje !== null) {
                echo json_encode(['status' => 'error', 'message' => $errorMensaje]);
                break;
            }

            $factura = new Factura();
            // Aquí se debe obtener el ID del cliente de la sesión
            $factura->setCliente($_SESSION['cedula']); // Obtener de la sesión
            $factura->setFecha(date('Y-m-d H:i:s'));
            $factura->setDescuento(0); // Descuento inicial
            $factura->setEstatus('Borrador'); // Estado inicial de la compra
            $factura->setIdProducto($productosValidados);
            $factura->setCantidad($cantidadesValidadas);
            try {
                $resultado = $factura->facturaTransaccion("Ingresar");

                if (is_array($resultado) && isset($resultado['error'])) {

                    echo json_encode(['status' => 'error', 'message' => $resultado['error']]);
                } elseif ($resultado === true) {
                    // Todo fue exitoso
                    $carrito = new Carrito();   
                    $carritoCliente = $carrito->obtenerCarritoPorCliente($_SESSION['id_usuario']);
                    $id_carrito = $carritoCliente['id_carrito'];
                    $carrito->eliminarTodoElCarrito($id_carrito);
                    echo json_encode(['status' => 'success', 'message' => 'Registro de Pedido se registro correctamente (Falta Pagar el pedido)']);
                } else {
                    // Fallback genérico
                    echo json_encode(['status' => 'error', 'message' => 'Error desconocido al registrar la compra']);
                }
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Excepción: ' . $e->getMessage()]);
            }

            break;

        case 'filtrar_por_marca':
            $id_marca = isset($_POST['id_marca']) && is_numeric($_POST['id_marca']) ? (int)$_POST['id_marca'] : null;
            $producto = new Productos();
            $productos = $producto->obtenerProductosPorMarca($id_marca);

            if (!empty($productos)) {
                $html = '';
                foreach ($productos as $producto) {
                    $html .= '<tr>
                                <td>
                                    <button type="button" class="btn btn-modificar btn-agregar-carrito" 
                                            data-id-producto="'.htmlspecialchars($producto['id_producto']).'">
                                        Agregar al carrito
                                    </button>
                                </td>
                                <td>'.htmlspecialchars($producto['nombre_producto']).'</td>
                                <td>'.($producto['stock'] > 0 ? $producto['stock'] : '<p style="background: red; color:white; border-radius: 10px; opacity: 0.8;box-shadow: 2px 2px 5px red;">Agotado</p>').'</td>
                                <td>'.htmlspecialchars($producto['descripcion_producto']).'</td>
                                <td>'.htmlspecialchars($producto['marca']).'</td>
                                <td>'.htmlspecialchars($producto['serial']).'</td>
                            </tr>';
                }
                echo json_encode(['status' => 'success', 'html' => $html]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se encontraron productos para la marca seleccionada']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
    exit;
}

// Funciones para la vista
function obtenerProductos() {
    $producto = new Productos();
    return $producto->obtenerProductoStock();
}

function obtenerProductosDelCarrito() {
    $carrito = new Carrito();
    $id_cliente = $_SESSION['id_usuario']; // Obtener de la sesión
    $carritoCliente = $carrito->obtenerCarritoPorCliente($id_cliente);

    if ($carritoCliente) {
        $id_carrito = $carritoCliente['id_carrito'];
        return $carrito->obtenerProductosDelCarrito($id_carrito);
    }
    return [];
}

function obtenerMarcas() {
    $producto = new Productos();
    return $producto->obtenerMarcas();
}

// Cargar vista
$pagina = "carrito";
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_CARRITO,
            'ACCESAR',
            'El usuario accedió al módulo de Carrito',
            'baja'
        );
    }
    $productos = obtenerProductos();
    $carritos = obtenerProductosDelCarrito();
    $marcas = obtenerMarcas();
    require_once("Vista/" . $pagina . ".php");

} else {
    echo "Página en construcción";
}

ob_end_flush();