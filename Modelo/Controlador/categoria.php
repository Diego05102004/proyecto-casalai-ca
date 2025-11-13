<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Clases\Categoria;
use Usuario\ProyectoCasalaiCa\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Clases\Bitacora;

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
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('categorias'));
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
            // Iniciar buffer de salida para capturar posibles errores
            ob_start();
            
            // Registrar datos recibidos
            error_log('=== INICIO DE SOLICITUD DE MODIFICACIÓN ===');
            error_log('Datos POST recibidos: ' . print_r($_POST, true));
            
            if (!isset($_POST['id_categoria'], $_POST['nombre_categoria'])) {
                $error = 'Faltan parámetros requeridos';
                error_log('Error: ' . $error);
                
                // Capturar cualquier salida de depuración
                $debugOutput = ob_get_clean();
                if (!empty($debugOutput)) {
                    error_log('Salida de depuración: ' . $debugOutput);
                }
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $error,
                    'debug' => [
                        'post_data' => $_POST,
                        'debug_output' => $debugOutput
                    ]
                ]);
                exit;
            }
            
            $id_categoria  = $_POST['id_categoria'];
            $nuevo_nombre = trim($_POST['nombre_categoria']);
            $caracteristicas = [];
            
            // Decodificar características si están presentes
            if (isset($_POST['caracteristicas'])) {
                if (is_string($_POST['caracteristicas'])) {
                    $caracteristicas = json_decode($_POST['caracteristicas'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log('Error al decodificar características JSON: ' . json_last_error_msg());
                    }
                } else {
                    $caracteristicas = $_POST['caracteristicas'];
                }
            }
            
            error_log('Datos procesados - ID: ' . $id_categoria . ', Nombre: ' . $nuevo_nombre);
            error_log('Características recibidas: ' . print_r($caracteristicas, true));
            
            if (empty($nuevo_nombre)) {
                $error = 'El nombre de la categoría no puede estar vacío';
                error_log('Error: ' . $error);
                
                // Capturar cualquier salida de depuración
                $debugOutput = ob_get_clean();
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $error,
                    'debug' => [
                        'id_categoria' => $id_categoria,
                        'nombre_categoria' => $nuevo_nombre,
                        'caracteristicas' => $caracteristicas,
                        'debug_output' => $debugOutput
                    ]
                ]);
                exit;
            }
            
            $categoria = new Categoria();
            $categoria->setIdCategoria($id_categoria);
            $categoria->setNombreCategoria($nuevo_nombre);
            
            error_log('Iniciando modificación de categoría: ' . $nuevo_nombre);
            
            // Verificar si el nombre ya existe
            if ($categoria->existeNombreCategoria($nuevo_nombre, $id_categoria)) {
                $error = 'El nombre de la categoria ya existe';
                error_log('Error: ' . $error);
                
                // Capturar cualquier salida de depuración
                $debugOutput = ob_get_clean();
                
                echo json_encode([
                    'status' => 'error',
                    'message' => $error,
                    'debug' => [
                        'id_categoria' => $id_categoria,
                        'nombre_categoria' => $nuevo_nombre,
                        'debug_output' => $debugOutput
                    ]
                ]);
                exit;
            }

            // Intentar modificar la categoría
            $resultado = $categoria->modificarCategoria($id_categoria, $nuevo_nombre, $caracteristicas);
            
            if ($resultado === true) {
                error_log('Categoría modificada exitosamente, obteniendo datos actualizados...');
                $categoriaActualizada = $categoria->obtenerCategoriaPorId($id_categoria);
                
                if (!$categoriaActualizada) {
                    error_log('Error: No se pudo obtener la categoría actualizada');
                } else {
                    error_log('Datos de categoría actualizada: ' . print_r($categoriaActualizada, true));
                }

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
                        error_log('Bitácora registrada correctamente');
                    } catch (Exception $e) {
                        error_log('Error al registrar en bitácora: ' . $e->getMessage());
                    }
                }
                
                // Capturar cualquier salida de depuración
                $debugOutput = ob_get_clean();
                
                $respuesta = [
                    'status' => 'success',
                    'categoria' => $categoriaActualizada,
                    'debug' => [
                        'caracteristicas_enviadas' => $caracteristicas,
                        'debug_output' => $debugOutput
                    ]
                ];
                
                error_log('Respuesta de éxito: ' . print_r($respuesta, true));
                echo json_encode($respuesta);
            } else {
                $error = 'Error al modificar la categoría';
                if (is_string($resultado)) {
                    $error = $resultado; // Si modificarCategoria devuelve un mensaje de error
                }
                
                error_log('Error al modificar categoría: ' . $error);
                
                // Capturar cualquier salida de depuración
                $debugOutput = ob_get_clean();
                
                $respuestaError = [
                    'status' => 'error',
                    'message' => $error,
                    'debug' => [
                        'id_categoria' => $id_categoria,
                        'nombre_categoria' => $nuevo_nombre,
                        'caracteristicas_enviadas' => $caracteristicas,
                        'debug_output' => $debugOutput
                    ]
                ];
                
                error_log('Respuesta de error: ' . print_r($respuestaError, true));
                echo json_encode($respuestaError);
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
