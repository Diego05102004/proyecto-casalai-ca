<?php
require_once __DIR__ . '/../modelo/permiso.php';
require_once __DIR__ . '/../modelo/usuario.php';
require_once __DIR__ . '/../modelo/bitacora.php';
require_once __DIR__ . '/../modelo/notificacion.php';
$bd_seguridad = new BD('S');
            $pdo_seguridad = $bd_seguridad->getConexion();
            $notificacionesModel = new NotificacionModel($pdo_seguridad);
$permisos = new Permisos();
$bitacoraModel = new Bitacora();
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
    $bitacoraModel = new Bitacora();
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            '17',
            'MODIFICAR',
            'El usuario modificó los permisos de los roles del sistema',
            'media'
        );
    }
    $permisos->guardarPermisos($_POST['permisos'] ?? [], $roles, $modulos_permiso, $acciones);
    $notificacionesModel->crear(
        $_SESSION['id_usuario'],
        'seguridad',
        'Permisos actualizados',
        'Se han actualizado los permisos de los roles del sistema por el usuario ' . $_SESSION['name'],
        null,
        'media',
        '17',
        'modificar'
    );
    header("Location: ?pagina=permiso&ok=1");
    exit;
}

$pagina = "permiso";
if (is_file("vista/" . $pagina . ".php")) {
    require_once("vista/" . $pagina . ".php");
            if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
    $_SESSION['id_usuario'],
    '17',
    'ACCESAR',
    'El usuario accedió al al modulo de Permisos',
    'media'
);}
} else {
    echo "Página en construcción";
}