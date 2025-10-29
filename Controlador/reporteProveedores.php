<?php
ob_start();

require_once 'modelo/proveedor.php';
require_once 'modelo/producto.php';
require_once 'modelo/permiso.php';
require_once 'modelo/bitacora.php';

$id_rol = $_SESSION['id_rol']; // Asegúrate de tener este dato en sesión

// Definir constantes para IDs de módulo y acciones
define('MODULO_PROVEEDORES', 8); // Cambiar según tu estructura de módulos

$permisosObj = new Permisos();
$reporteProveedor = new Proveedores();
$reporteRankingProveedores = $reporteProveedor->getRankingProveedores();
$reporteComparacion = $reporteProveedor->getComparacionPreciosProducto();
$reporteDependencia = $reporteProveedor->getDependenciaProveedores();
$proveedorModel = new Proveedores();
$reporteSuministroProveedores = $proveedorModel->obtenerReporteSuministroProveedores();
$totalSuministrado = array_sum(array_column($reporteSuministroProveedores, 'cantidad'));


$pagina = "reporteProveedores";
if (is_file("vista/" . $pagina . ".php")) {
    require_once("vista/" . $pagina . ".php");
} else {
    echo "Página en construcción";
}

ob_end_flush();
?>
