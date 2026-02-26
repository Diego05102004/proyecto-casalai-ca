<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Modelo\Clases\Permisos;

define('MODULO_BITACORA', 1);

// Permisos (si la vista los requiere)
$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();

// Redirigir a login si no hay sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ?pagina=login');
    exit;
}

// Validar y registrar acceso al módulo
if (!defined('SKIP_SIDE_EFFECTS')) {
    $bitacoraModel = new Bitacora();
    
    // Validar datos de registro
    $datosRegistro = [
        'id_usuario' => $_SESSION['id_usuario'],
        'modulo' => MODULO_BITACORA,
        'accion' => 'ACCESAR',
        'descripcion' => 'El usuario accedió al módulo de bitácora',
        'prioridad' => 'baja'
    ];
    
    $errores = $bitacoraModel->validarRegistrar($datosRegistro);
    if (empty($errores)) {
        $bitacoraModel->registrarBitacora(
            $datosRegistro['id_usuario'],
            $datosRegistro['modulo'],
            $datosRegistro['accion'],
            $datosRegistro['descripcion'],
            $datosRegistro['prioridad']
        );
    }
}

// Validar y consultar registros
$bitacoraModel = new Bitacora();
$registros = [];
$erroresConsulta = [];

// Obtener parámetros de consulta
$datosConsulta = [
    'limite' => $_GET['limite'] ?? 500,
    'fecha_inicio' => $_GET['fecha_inicio'] ?? null,
    'fecha_fin' => $_GET['fecha_fin'] ?? null,
    'id_usuario' => $_GET['id_usuario'] ?? null,
    'id_modulo' => $_GET['id_modulo'] ?? null,
    'accion' => $_GET['accion'] ?? null,
    'prioridad' => $_GET['prioridad'] ?? null
];

// Validar parámetros de consulta
$erroresConsulta = $bitacoraModel->validarConsultar($datosConsulta);

if (empty($erroresConsulta)) {
    try {
        // Usar el límite validado o el valor por defecto
        $limite = isset($datosConsulta['limite']) ? (int)$datosConsulta['limite'] : 500;
        $registros = $bitacoraModel->obtenerRegistrosDetallados($limite);
    } catch (Exception $e) {
        $registros = [];
        $erroresConsulta['sistema'] = 'Error al consultar los registros: ' . $e->getMessage();
    }
} else {
    // Si hay errores de validación, usar valores por defecto
    try {
        $registros = $bitacoraModel->obtenerRegistrosDetallados(500);
    } catch (Exception $e) {
        $registros = [];
        $erroresConsulta['sistema'] = 'Error al consultar los registros: ' . $e->getMessage();
    }
}

// Render de vista
$pagina = "bitacora";
if (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
