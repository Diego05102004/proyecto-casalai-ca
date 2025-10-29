<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'modelo/usuario.php';
require_once 'modelo/rol.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';
define('MODULO_USUARIO', 1);

$permisos = new Permisos();
$permisosUsuarioEntrar = $permisos->getPermisosPorRolModulo();

$id_rol = $_SESSION['id_rol'] ?? null;

$bitacoraModel = new Bitacora();

$usuarioModel = new Usuarios();
$rolModel = new Rol();

// --- Código relacionado al reporte de usuarios por rol ---
$reporteRoles = $usuarioModel->obtenerReporteRoles();
$selecionarRol = $rolModel->consultarRoles();
// Nuevos datasets crudos para agregación en el cliente (sin AJAX)
$usuariosHabilitados = $usuarioModel->getusuarios('habilitado');
$usuariosDeshabilitados = $usuarioModel->getusuarios('deshabilitado');
$usuariosTodos = array_merge($usuariosHabilitados ?? [], $usuariosDeshabilitados ?? []);

$totalRoles = array_sum(array_column($reporteRoles, 'cantidad'));
foreach ($reporteRoles as &$rol) {
    $rol['porcentaje'] = $totalRoles > 0 ? round(($rol['cantidad'] / $totalRoles) * 100, 2) : 0;
}
unset($rol);
// ---------------------------------------------------------

$pagina = "reporteUsuario";
if (is_file("vista/" . $pagina . ".php")) {
    if (isset($_SESSION['id_usuario'])) {
        $bitacoraModel->registrarBitacora(
            $_SESSION['id_usuario'],
            MODULO_USUARIO,
            'ACCESAR',
            'El usuario accedió al módulo de Reportes de Usuarios',
            'media'
        );
    }
    require_once("vista/" . $pagina . ".php");

} else {
    echo "Página en construcción";
}

ob_end_flush();
?>