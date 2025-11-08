<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'Modelo/bitacora.php';
require_once 'Modelo/permiso.php';

define('MODULO_BITACORA', 1);

// Permisos (si la vista los requiere)
$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();

// Redirigir a login si no hay sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

// Registrar acceso al módulo (formato Recepción) si no está en modo pruebas
if (!defined('SKIP_SIDE_EFFECTS')) {
    $bitacoraModel = new Bitacora();
    $bitacoraModel->registrarBitacora(
        $_SESSION['id_usuario'],
        MODULO_BITACORA,
        'ACCESAR',
        'El usuario accedió al módulo de bitácora',
        'baja'
    );
}

// Consultar registros
$bitacoraModel = new Bitacora();
try {
    $registros = $bitacoraModel->obtenerRegistrosDetallados(500);
} catch (Exception $e) {
    $registros = [];
}

// Render de vista
$pagina = "bitacora";
if (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
