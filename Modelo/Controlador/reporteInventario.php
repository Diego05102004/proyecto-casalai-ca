<?php  
use Usuario\ProyectoCasalaiCa\Clases\Recepcion;
use Usuario\ProyectoCasalaiCa\Clases\Despacho;
use Usuario\ProyectoCasalaiCa\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Clases\Permiso;
use Usuario\ProyectoCasalaiCa\Clases\Bitacora;

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
if (is_file("Vista/" . $pagina . ".php")) {
    require_once("Vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}
?>