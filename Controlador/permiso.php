<?php
require_once __DIR__ . '/../modelo/permiso.php';
require_once __DIR__ . '/../modelo/usuario.php';
require_once __DIR__ . '/../modelo/bitacora.php';
require_once __DIR__ . '/../modelo/notificacion.php';

define('MODULO_PERMISOS', 17);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

// Inicializaciones

$permisos = new Permisos();

// Obtener roles
$roles = $permisos->getRoles();

// Obtener módulos
$modulos_permiso = $permisos->getModulos();

// Acciones posibles
$acciones = ['ingresar','consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];

// Obtener permisos actuales (por rol y módulo)
$permisosActuales = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosPorRolModulo();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardarPermisos'])) {
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_PERMISOS,
            'MODIFICAR',
            'El usuario modificó los permisos de los roles del sistema',
            'media'
        );
    }
    $permisos->guardarPermisos($_POST['permisos'] ?? [], $roles, $modulos_permiso, $acciones);
    $bd_seguridad = new BD('S');
    $pdo_seguridad = $bd_seguridad->getConexion();
    $notificacionesModel = new NotificacionModel($pdo_seguridad);
    $notificacionesModel->crear(
        $_SESSION['id_usuario'],
        'seguridad',
        'Permisos actualizados',
        'Se han actualizado los permisos de los roles del sistema por el usuario ' . $_SESSION['name'],
        null,
        'media',
        MODULO_PERMISOS,
        'modificar'
    );
    header("Location: ?pagina=permiso&ok=1");
    exit;
}

$pagina = "permiso";
if (is_file("vista/" . $pagina . ".php")) {
    require_once("vista/" . $pagina . ".php");
    if (!defined('SKIP_SIDE_EFFECTS')) {
        $bitacoraModel = new Bitacora();
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_PERMISOS,
            'ACCESAR',
            'El usuario accedió al módulo de Permisos',
            'media'
        );
    }
} else {
    echo "Página en construcción";
}