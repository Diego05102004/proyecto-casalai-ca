<?php
ob_start();

require_once 'modelo/cliente.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';
$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();
$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

define('MODULO_CLIENTE', 9);

$permisosObj = new Permisos();
$bitacoraModel = new Bitacora();

$permisosUsuario = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('clientes'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = isset($_POST['accion']) ? $_POST['accion'] : '';

    switch ($accion) {
        case 'registrar':
            $cliente = new cliente();
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
                    $bitacoraModel->registrarBitacora(
                        $_SESSION['id_usuario'],
                        MODULO_CLIENTE,
                        'INCLUIR',
                        'El usuario incluyó el cliente: ' . implode(', ', array_map(
                            function($value, $key) { return "$key: $value"; },
                            $clienteRegistrado,
                            array_keys($clienteRegistrado)
                        )),
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
            $permisosActualizados = $permisosObj->getPermisosUsuarioModulo($id_rol, strtolower('clientes'));
            echo json_encode($permisosActualizados);
            exit;
        case 'obtener_clientes':
            $id = $_POST['id_clientes'];
            if ($id !== null) {
                $cliente = new cliente();
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
            $id = $_POST['id_clientes'];
            $cliente = new cliente();
            $cliente->setId($id);
            $cliente->setnombre($_POST['nombre']);
            $cliente->setcedula($_POST['cedula']);
            $cliente->settelefono($_POST['telefono']);
            $cliente->setdireccion($_POST['direccion']);
            $cliente->setcorreo($_POST['correo']);
            
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
            $id = $_POST['id_clientes'];
            $clientesModel = new cliente();
            if ($clientesModel->eliminarclientes($id)) {
                if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
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
if (is_file("vista/" . $pagina . ".php")) {
    if (!defined('SKIP_SIDE_EFFECTS') && isset($_SESSION['id_usuario'])) {
         $bitacoraModel->registrarBitacora(
    $_SESSION['id_usuario'],
    '9',
    'ACCESAR',
    'El usuario accedió al al modulo de Clientes',
    'media'
);
}
    $clientes = getclientes();
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
