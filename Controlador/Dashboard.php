<?php
require_once('modelo/permiso.php');

$id_rol = $_SESSION['id_rol'];
$nombre_rol = $_SESSION['nombre_rol'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 0;

$permisosObj = new Permisos();

$modulos = [
    'Usuario' => ['Gestionar Usuario', 'img/users-round.svg', '?pagina=usuario'],
    'Reporte de Usuarios' => ['Reporte de Usuarios', 'img/chart-column.svg', '?pagina=reporteUsuarios'],
    'Recepcion' => ['Gestionar Recepcion', 'img/package-open.svg', '?pagina=recepcion'],
    'Reporte de Inventario' => ['Reporte de Inventario', 'img/chart-column.svg', '?pagina=reporteInventario'],
    'Despacho' => ['Gestionar Despacho', 'img/package-check.svg', '?pagina=despacho'],
    'Marcas' => ['Gestionar Marcas', 'img/package-search.svg', '?pagina=marca'],
    'Modelos' => ['Gestionar Modelos', 'img/package-search.svg', '?pagina=modelo'],
    'Productos' => ['Gestionar Productos', 'img/package-search.svg', '?pagina=producto'],
    'Categorias' => ['Gestionar Categorias', 'img/package-search.svg', '?pagina=categoria'],
    'Reporte de Productos' => ['Reporte de Productos', 'img/chart-column.svg', '?pagina=reporteProductos'],
    'Compra Física' => ['Gestionar Compra Fisica', 'img/files.svg', '?pagina=comprafisica'],
    'Proveedores' => ['Gestionar Proveedores', 'img/truck.svg', '?pagina=proveedor'],
    'Reporte de Proveedores' => ['Reporte de Proveedores', 'img/chart-column.svg', '?pagina=reporteProveedores'],
    'Clientes' => ['Gestionar Clientes', 'img/users-round.svg', '?pagina=cliente'],
    'Catalogo' => ['Gestionar Catálogo', 'img/book-open.svg', '?pagina=catalogo'],
    'pasarela' => ['Gestionar Pagos', 'img/credit-card.svg', '?pagina=pasarela'],
    'Prefactura' => ['Gestionar Pedidos', 'img/receipt-text.svg', '?pagina=gestionarfactura'],
    'Ordenes de despacho' => ['Gestionar Ordenes de Despacho', 'img/list-ordered.svg', '?pagina=ordendespacho'],
    'Reporte de Ventas' => ['Reporte de Ventas', 'img/chart-column.svg', '?pagina=reporteVentas'],
    'Cuentas bancarias' => ['Gestionar Cuentas Bancarias', 'img/landmark.svg', '?pagina=cuenta'],
    'Finanzas' => ['Gestionar Ingresos y Egresos', 'img/dollar-sign.svg', '?pagina=finanza'],
    'Reporte de Finanzas' => ['Reporte de Finanzas', 'img/chart-column.svg', '?pagina=reporteFinanzas'],
    'permisos' => ['Gestionar Permisos', 'img/key-round.svg', '?pagina=permiso'],
    'Roles' => ['Gestionar Roles', 'img/user-round-search.svg', '?pagina=rol'],
    'bitacora' => ['Gestionar Bitácora', 'img/notebook.svg', '?pagina=bitacora'],
    'Respaldo' => ['Gestionar Respaldo', 'img/files.svg', '?pagina=backup'],
];

$permisosConsulta = [];
foreach ($modulos as $moduloBD => $info) {
    $permisosConsulta[$moduloBD] = $permisosObj->getPermisosUsuarioModulo($id_rol, $moduloBD)['ingresar'] ?? false;
}

if ($nombre_rol === 'SuperUsuario') {
    // Forzar todos los permisos como true
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = true;
    }
} else {
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = $permisosObj->getPermisosUsuarioModulo($id_rol, $moduloBD)['ingresar'] ?? false;
    }
}
if(is_file('vista/'.$pagina.'.php')){
    require_once ('vista/'.$pagina.'.php');  //si la pagina existe se carga su vista correspondiente
}else{
    echo "PAGINA EN CONSTRUCCIÓN";
}
