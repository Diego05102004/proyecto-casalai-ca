<?php
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Usuario;
use Usuario\ProyectoCasalaiCa\Config\BD;
define('MODULO_PERMISOS', "Permisos");

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

// Inicializaciones

$permisos = new Permisos();

// Obtener permisos actuales (por rol y módulo)
$permisosActuales = $permisos->getPermisosPorRolModulo();
$permisosUsuario = $permisos->getPermisosPorRolModulo();
// Obtener roles
$roles = $permisos->getRoles();

// Obtener módulos
$modulos_permiso = $permisos->getModulos();

// Acciones posibles
$acciones = ['ingresar','consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardarPermisos'])) {
    // Validar datos antes de guardar
    $permisosForm = $_POST['permisos'] ?? [];
    
    // Usar las nuevas validaciones centralizadas
    $errores = $permisos->validarModificarPermisos($permisosForm, $roles, $modulos_permiso, $acciones);
    
    if (!empty($errores)) {
        // Si hay errores, mostrar mensaje de error y redirigir
        $_SESSION['error_permisos'] = $errores;
        header("Location: ?pagina=permiso&error=1");
        exit;
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

    header("Location: ?pagina=permiso&ok=1");
    exit;
}

$pagina = "permiso";
if (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
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