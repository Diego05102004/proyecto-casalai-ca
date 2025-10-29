<?php  

require_once 'modelo/notificacion.php';
require_once("modelo/Recepcion.php");
require_once("modelo/Despacho.php");
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';

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


$r = new Recepcion();
$RecepcionesProveedor = $r->getRecepcionesPorProveedor();
$ProductorRecibidos = $r->getProductosMasRecibidos();
$RecepcionMensual = $r->getRecepcionesMensuales();


$pagina = "reporteInventario";
if (is_file("vista/" . $pagina . ".php")) {
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>