<?php
ob_start();
use Usuario\ProyectoCasalaiCa\Modelo\Clases\cliente;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Productos;

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

define('MODULO_CLIENTE', 9);

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('clientes'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    switch ($accion) {
        case 'registrar':
            // Validar datos de entrada
            $cliente = new cliente();
            $datosValidacion = [
                'nombre' => $_POST['nombre'] ?? '',
                'cedula' => $_POST['cedula'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'correo' => $_POST['correo'] ?? ''
            ];
            
            $errores = $cliente->validarRegistrarCliente($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos del cliente',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $cliente->setnombre($_POST['nombre']);
            $cliente->setcedula($_POST['cedula']);
            $cliente->settelefono($_POST['telefono']);
            $cliente->setdireccion($_POST['direccion']);
            $cliente->setcorreo($_POST['correo']);
            
            if ($cliente->existeNumeroCedula($_POST['cedula'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El número de Cedula ya existe'
                ]);
                exit;
            }

            if ($cliente->ingresarclientes()) {
                $clienteRegistrado = $cliente->obtenerUltimoCliente();

                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CLIENTE,
                        'INCLUIR',
                        'El usuario incluyó al cliente: ' . $_POST['nombre'],
                        'alta'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Cliente registrado correctamente',
                    'cliente' => $clienteRegistrado
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar el cliente'
                ]);
            }
            exit;
        case 'permisos_tiempo_real':
            header('Content-Type: application/json; charset=utf-8');
            $permisosActualizados = $permisos->getPermisosUsuarioModulo($id_rol, strtolower('clientes'));
            echo json_encode($permisosActualizados);
            exit;
        case 'obtener_clientes':
            // Validar datos de entrada
            $cliente = new cliente();
            $datosValidacion = [
                'id_cliente' => $_POST['id_clientes'] ?? null
            ];
            
            $errores = $cliente->validarConsultarCliente($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                exit;
            }

            $id = $_POST['id_clientes'];
            if ($id !== null) {
                $cliente = $cliente->obtenerclientesPorId($id);
                if ($cliente !== null) {
                    echo json_encode($cliente);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Cliente no encontrado']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ID de Cliente no proporcionado']);
            }
            exit;

        case 'modificar':
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            
            // Validar datos de entrada
            $cliente = new cliente();
            $datosValidacion = [
                'id_cliente' => $_POST['id_clientes'] ?? null,
                'nombre' => $_POST['nombre'] ?? '',
                'cedula' => $_POST['cedula'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'direccion' => $_POST['direccion'] ?? '',
                'correo' => $_POST['correo'] ?? ''
            ];
            
            $errores = $cliente->validarModificarCliente($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error en los datos del cliente',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $id = $_POST['id_clientes'];
            $cliente->setId($id);
            $cliente->setnombre($_POST['nombre']);
            $cliente->setcedula($_POST['cedula']);
            $cliente->settelefono($_POST['telefono']);
            $cliente->setdireccion($_POST['direccion']);
            $cliente->setcorreo($_POST['correo']);
            
            // Verificar que el cliente exista antes de modificar
            $clienteExistente = $cliente->obtenerclientesPorId($id);
            if (!$clienteExistente) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El cliente que intenta modificar no existe'
                ]);
                exit;
            }
            
            if ($cliente->existeNumeroCedula($_POST['cedula'], $id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El número de Cedula ya existe'
                ]);
                exit;
            }

            if ($cliente->modificarclientes($id)) {
                $clienteModificado = $cliente->obtenerclientesPorId($id);

                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CLIENTE,
                        'MODIFICAR',
                        'El usuario modificó el cliente ID: ' . $id,
                        'media'
                    );
                }

                echo json_encode([
                    'status' => 'success',
                    'cliente' => $clienteModificado
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al modificar el cliente']);
            }
            exit;

        case 'eliminar':
            // Validar datos de entrada
            $cliente = new cliente();
            $datosValidacion = [
                'id_cliente' => $_POST['id_clientes'] ?? null
            ];
            
            $errores = $cliente->validarEliminarCliente($datosValidacion);
            if (!empty($errores)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Datos inválidos',
                    'errors' => $errores
                ]);
                exit;
            }
            
            $id = $_POST['id_clientes'];
            
            // Verificar que el cliente exista antes de eliminar
            $clienteExistente = $cliente->obtenerclientesPorId($id);
            if (!$clienteExistente) {
                echo json_encode(['status' => 'error', 'message' => 'El cliente que intenta eliminar no existe']);
                exit;
            }
            
            if ($cliente->eliminarclientes($id)) {
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
                    $bitacoraModel = new Bitacora();
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CLIENTE,
                        'ELIMINAR',
                        'El usuario eliminó el cliente ID: ' . $id,
                        'media'
                    );
                }

                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al eliminar el Cliente']);
            }
            exit;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        exit;
    }
}
    $cliente = new cliente();
function getclientes() {
    $cliente = new cliente();
    return $cliente->getclientes();
}
$reporteComprasClientes = $cliente->obtenerReporteComprasClientes();
$totalComprasClientes = array_sum(array_column($reporteComprasClientes, 'cantidad'));
$pagina = "cliente";
if (is_file("Vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        '9',
        'ACCESAR',
        'El usuario accedió al módulo de Clientes',
        'media'
    );
}
    $clientes = getclientes();
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
