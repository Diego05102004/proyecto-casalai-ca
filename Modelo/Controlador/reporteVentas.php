<?php  
use Usuario\ProyectoCasalaiCa\Clases\Despacho;
use Usuario\ProyectoCasalaiCa\Clases\Permisos;
use Usuario\ProyectoCasalaiCa\Clases\Bitacora;
use Usuario\ProyectoCasalaiCa\Clases\NotificacionModel;
use Usuario\ProyectoCasalaiCa\Clases\Recepcion;


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