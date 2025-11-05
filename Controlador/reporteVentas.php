<?php  

require_once 'Modelo/notificacion.php';
require_once("Modelo/Recepcion.php");
require_once("Modelo/Despacho.php");
require_once 'Modelo/permiso.php';
require_once 'Modelo/bitacora.php';

$reporteDespacho = new Despacho();
$despachoEstado = $reporteDespacho->getDespachosEstado();
$despachoMes = $reporteDespacho->getProductosDespachadosPorMes();
$proveedores = $reporteDespacho->obtenercliente();
$productos = $reporteDespacho->consultarproductos();
function getdespacho() {
    $despacho = new Despacho();
    return $despacho->getdespacho();
}

// vista inicial
$despachos = getdespacho();

// Total de despachos
$totalDespachos = count($despachos);



$pagina = "reporteVentas";
if (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>