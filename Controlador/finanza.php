<?php
ob_start();
require_once 'modelo/finanza.php';
require_once __DIR__ . '/../modelo/permiso.php';
require_once 'modelo/bitacora.php';

$permisos = new Permisos();
$permisosUsuario = $permisos->getPermisosPorRolModulo();
define('MODULO_FINANZA', 16);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
    } else {
        $accion = '';
    }

    // Ya no se exponen acciones POST en este módulo
    echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
    exit;
}

// Consulta automática para la vista
function consultarFinanzas() {
    $finanza = new Finanza();
    return [
        'ingresos' => $finanza->consultarIngresos(),
        'egresos' => $finanza->consultarEgresos()
    ];
}

$finanzas = consultarFinanzas(); // <-- PRIMERO OBTÉN LOS DATOS

// Agrupar ingresos y egresos por mes
function agruparPorMes($registros) {
    $res = [];
    foreach ($registros as $r) {
        $mes = date('Y-m', strtotime($r['fecha']));
        if (!isset($res[$mes])) $res[$mes] = 0;
        $res[$mes] += $r['monto'];
    }
    return $res;
}

$ingresosPorMes = agruparPorMes($finanzas['ingresos']);
$egresosPorMes = agruparPorMes($finanzas['egresos']);



$meses = array_unique(array_merge(array_keys($ingresosPorMes), array_keys($egresosPorMes)));
sort($meses);

$totalIngresos = array_sum(array_column($finanzas['ingresos'], 'monto'));
$totalEgresos = array_sum(array_column($finanzas['egresos'], 'monto'));

$pagina = "finanza";
if (is_file("vista/" . $pagina . ".php")) {
    require_once("vista/" . $pagina . ".php");
        if (isset($_SESSION['id_usuario'])) {
        if (!defined('SKIP_SIDE_EFFECTS')) {
            $bitacoraModel = new Bitacora();
            $bitacoraModel->registrarBitacora(
                $_SESSION['id_usuario'],
                MODULO_FINANZA,
                'ACCESAR',
                'El usuario accedió al modulo de Finanzas',
                'media'
            );
        }
    }
} else {
    echo "Página en construcción";
}