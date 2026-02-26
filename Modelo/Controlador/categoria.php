<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Categoria;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;

define('MODULO_CATEGORIA', 7);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

$id_rol = $_SESSION['id_rol'] ?? 0;

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('Categorias'));
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    switch ($accion) {
        case 'registrar':
            // Validar datos de entrada
            $categoria = new Categoria();
            $datosValidacion = [
                'nombre_categoria' => $_POST['nombre_categoria'] ?? '',
                'caracteristicas' => isset($_POST['caracteristicas']) ? $_POST['caracteristicas'] : []
            ];
            
            $errores = $categoria->validarRegistrarCategoria($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos de la categoría',
                    'errors' => $errores
                ]);
                exit;
            }

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
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('categorias'));
            echo json_encode($permisosActualizados);
            exit;

        case 'consultar_categorias':
            $categoria = new Categoria();
            $categorias_obt = $categoria->consultarCategorias();
            echo json_encode($categorias_obt);
            exit;

        case 'obtener_categoria':
            // Validar datos de entrada
            $categoria = new Categoria();
            $datosValidacion = [
                'id_categoria' => $_POST['id_categoria'] ?? null
            ];
            
            $errores = $categoria->validarConsultarCategoria($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                exit;
            }

            $id_categoria = $_POST['id_categoria'];
            if ($id_categoria !== null) {
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
            // Validar datos de entrada
            $categoria = new Categoria();
            $datosValidacion = [
                'id_categoria' => $_POST['id_categoria'] ?? null,
                'nombre_categoria' => $_POST['nombre_categoria'] ?? '',
                'caracteristicas' => []
            ];
            
            // Decodificar características si están presentes
            if (isset($_POST['caracteristicas'])) {
                if (is_string($_POST['caracteristicas'])) {
                    $datosValidacion['caracteristicas'] = json_decode($_POST['caracteristicas'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('Error al decodificar características JSON: ' . json_last_error_msg());
                        $datosValidacion['caracteristicas'] = [];
                    }
                } else {
                    $datosValidacion['caracteristicas'] = $_POST['caracteristicas'];
                }
            }
            
            $errores = $categoria->validarModificarCategoria($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos de la categoría',
                    'errors' => $errores
                ]);
                exit;
            }
            
            if (!isset($_POST['id_categoria'], $_POST['nombre_categoria'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Faltan parámetros requeridos'
                ]);
                exit;
            }
            
            $id_categoria  = $_POST['id_categoria'];
            $nuevo_nombre = trim($_POST['nombre_categoria']);
            $caracteristicas = $datosValidacion['caracteristicas'];
            
            $categoria->setIdCategoria($id_categoria);
            $categoria->setNombreCategoria($nuevo_nombre);
            
            // Verificar si el nombre ya existe (excluyendo la categoría actual)
            if ($categoria->existeNombreCategoria($nuevo_nombre, $id_categoria)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El nombre de la categoria ya existe'
                ]);
                exit;
            }

            // Intentar modificar la categoría
            $resultado = $categoria->modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas);
            
            if ($resultado === true) {
                $categoriaActualizada = $categoria->obtenerCategoriaPorId($id_categoria);

                if (!defined('SKIP_SIDE_EFFECTS')) {
                    try {
                        $bitacoraModel = new Bitacora();
                        $bitacoraModel->registrarBitacora(
                            $_SESSION['id_usuario'],
                            MODULO_CATEGORIA,
                            'MODIFICAR',
                            'El usuario modificó la categoría ID: ' . $id_categoria,
                            'media'
                        );
                    } catch (Exception $e) {
                        error_log('Error al registrar en bitácora: ' . $e->getMessage());
                    }
                }
                
                echo json_encode([
                    'status' => 'success',
                    'categoria' => $categoriaActualizada
                ]);
            } else {
                $error = 'Error al modificar la categoría';
                if (is_string($resultado)) {
                    $error = $resultado;
                }
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $error
                ]);
            }
            exit;

        case 'eliminar':
            // Validar datos de entrada
            $categoria = new Categoria();
            $datosValidacion = [
                'id_categoria' => $_POST['id_categoria'] ?? null
            ];
            
            $errores = $categoria->validarEliminarCategoria($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $id_categoria = $_POST['id_categoria'];
            
            // Verificar que la categoría exista antes de eliminar
            $categoriaExistente = $categoria->obtenerCategoriaPorId($id_categoria);
            if (!$categoriaExistente) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'La categoría que intenta eliminar no existe'
                ]);
                exit;
            }

            // Intentar eliminar la categoría
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
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_CATEGORIA,
            'ACCESAR',
            'El usuario accedió al módulo de Categorias',
            'media'
        );
    }
    $categorias = consultarCategorias();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
