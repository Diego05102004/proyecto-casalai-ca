<?php
ob_start();
require_once __DIR__ . '/../modelo/categoria.php';
require_once __DIR__ . '/../modelo/permiso.php';
require_once __DIR__ . '/../modelo/bitacora.php';

define('MODULO_CATEGORIA', 7);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

// Inicializaciones
$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$id_rol = $_SESSION['id_rol'] ?? 0;

$permisosObj = new Permisos();

$permisosUsuario = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('Categorias'));
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    switch ($accion) {
        case 'registrar':
            $categoria = new Categoria();
            $categoria->setNombreCategoria($_POST['nombre_categoria']);
            $caracteristicas = isset($_POST['caracteristicas']) ? $_POST['caracteristicas'] : [];

            if ($categoria->existeNombreCategoria($_POST['nombre_categoria'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El nombre de la categoria ya existe'
                ]);
                exit;
            }

            if ($categoria->registrarCategoria($caracteristicas)) {
                $categoriaRegistrado = $categoria->obtenerUltimoCategoria();

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CATEGORIA,
                        'INCLUIR',
                        'El usuario incluyó la categoría: ' . $_POST['nombre_categoria'],
                        'media'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Categoria registrada correctamente',
                    'categoria' => $categoriaRegistrado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar la categoria'
                ]);
            }
            exit;

            case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('categorias'));
            echo json_encode($permisosActualizados);
            exit;

        case 'consultar_categorias':
            $categoria = new Categoria();
            $categorias_obt = $categoria->consultarCategorias();
            echo json_encode($categorias_obt);
            exit;

        case 'obtener_categoria':
            $id_categoria = $_POST['id_categoria'];
            if ($id_categoria !== null) {
                $categoria = new Categoria();
                $categoria_obt = $categoria->obtenerCategoriaPorId($id_categoria);
                if ($categoria_obt !== null) {
                    echo json_encode($categoria_obt);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Categoria no encontrada']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de la categoria no proporcionada']);
            }
            exit;

        case 'modificar':
            $id_categoria  = $_POST['id_categoria'];
            $nuevo_nombre = $_POST['nombre_categoria'];
            $caracteristicas = isset($_POST['caracteristicas']) ? $_POST['caracteristicas'] : [];
            $categoria = new Categoria();
            $categoria->setIdCategoria($id_categoria);
            $categoria->setNombreCategoria($nuevo_nombre);

            if ($categoria->existeNombreCategoria($nuevo_nombre, $id_categoria)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El nombre de la categoria ya existe'
                ]);
                exit;
            }

            if ($categoria->modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas)) {
                $categoriaActualizada = $categoria->obtenerCategoriaPorId($id_categoria);

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CATEGORIA,
                        'MODIFICAR',
                        'El usuario modificó la categoría ID: ' . $id_categoria,
                        'media'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'categoria' => $categoriaActualizada
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar la categoria']);
            }
            exit;

        case 'eliminar':
            $id_categoria = $_POST['id_categoria'];
            $categoria = new Categoria();
            $resultado = $categoria->eliminarCategoria($id_categoria);

            if ($resultado['status'] === 'error') {
                // Registrar en bitácora el intento fallido
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CATEGORIA,
                        'ELIMINAR_FALLIDO',
                        'Intento de eliminación fallido de categoría (ID: ' . $id_categoria . '): ' . $resultado['mensaje'],
                        'media'
                    );
                }
                
                echo json_encode([
                    'status' => 'error', 
                    'message' => $resultado['mensaje'],
                    'productos' => $resultado['productos'] ?? [],
                    'total_productos' => $resultado['total_productos'] ?? 0
                ]);
            } else {
                // Registrar eliminación exitosa
                if (!defined('SKIP_SIDE_EFFECTS')) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CATEGORIA,
                        'ELIMINAR',
                        'El usuario eliminó la categoría ID: ' . $id_categoria,
                        'media'
                    );
                }
                
                echo json_encode(['status' => 'success']);
            }
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            exit;
    }
}

function consultarCategorias() {
    $categoria = new Categoria();
    return $categoria->consultarCategorias();
}

$pagina = "categoria";
if (is_file("vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_CATEGORIA,
            'ACCESAR',
            'El usuario accedió al modulo de Categorias',
            'media'
        );
    }
    $categorias = consultarCategorias();
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
