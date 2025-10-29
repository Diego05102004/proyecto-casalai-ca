<?php
require_once('config/config.php');
require_once('modelo/permiso.php');

$id_rol = $_SESSION['id_rol'];
$nombre_rol = $_SESSION['nombre_rol'] ?? '';
$id_usuario = $_SESSION['id_usuario'] ?? 0;

$permisosObj = new Permisos();

// Obtener datos de la tasa BCV
require_once 'modelo/DolarService.php';
$dolarService = new DolarService();
$tasaBCV = $dolarService->obtenerPrecioDolar();
$tasaBCVFormateada = number_format($tasaBCV, 2);

$modulos = [
    'Usuario' => ['Gestionar Usuario', 'img/users-round.svg', '?pagina=usuario'],
    'Recepcion' => ['Gestionar Recepcion', 'img/package-open.svg', '?pagina=recepcion'],
    'Despacho' => ['Gestionar Despacho', 'img/package-check.svg', '?pagina=despacho'],
    'Marcas' => ['Gestionar Marcas', 'img/package-search.svg', '?pagina=marca'],
    'Modelos' => ['Gestionar Modelos', 'img/package-search.svg', '?pagina=modelo'],
    'Productos' => ['Gestionar Productos', 'img/package-search.svg', '?pagina=producto'],
    'Categorias' => ['Gestionar Categorias', 'img/package-search.svg', '?pagina=categoria'],
    'Compra Física' => ['Gestionar Ventas Presenciales', 'img/files.svg', '?pagina=comprafisica'],
    'Proveedores' => ['Gestionar Proveedores', 'img/truck.svg', '?pagina=proveedor'],
    'Clientes' => ['Gestionar Clientes', 'img/users-round.svg', '?pagina=cliente'],
    'Catalogo' => ['Gestionar Catálogo', 'img/book-open.svg', '?pagina=catalogo'],
    'pasarela' => ['Gestionar Pagos', 'img/credit-card.svg', '?pagina=pasarela'],
    'Pedidos' => ['Gestionar Pedidos', 'img/receipt-text.svg', '?pagina=gestionarfactura'],
    'Ordenes de despacho' => ['Gestionar Ordenes de Despacho', 'img/list-ordered.svg', '?pagina=ordendespacho'],
    'Cuentas bancarias' => ['Gestionar Cuentas Bancarias', 'img/landmark.svg', '?pagina=cuenta'],
    'Finanzas' => ['Gestionar Ingresos y Egresos', 'img/dollar-sign.svg', '?pagina=finanza'],
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
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = true;
    }
} else {
    foreach ($modulos as $moduloBD => $info) {
        $permisosConsulta[$moduloBD] = $permisosObj->getPermisosUsuarioModulo($id_rol, $moduloBD)['ingresar'] ?? false;
    }
}

$bd_seguridad = new BD('S');
$pdo_seguridad = $bd_seguridad->getConexion();

$bd_casalai = new BD('C');
$pdo_casalai = $bd_casalai->getConexion();

// Consulta de notificaciones
$query = "SELECT * FROM tbl_notificaciones 
          WHERE id_usuario = :id_usuario AND leido = 0
          ORDER BY fecha_hora DESC LIMIT 5";
$stmt = $pdo_seguridad->prepare($query);
$stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
$stmt->execute();
$notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($notificaciones as &$notif) {
    if ($notif['tipo'] == 'pago' && $notif['id_referencia']) {
        $query_pago = "SELECT * FROM tbl_detalles_pago WHERE id_detalles = ?";
        $stmt_pago = $pdo_casalai->prepare($query_pago);
        $stmt_pago->execute([$notif['id_referencia']]);
        $notif['detalle_pago'] = $stmt_pago->fetch(PDO::FETCH_ASSOC);
    }
}
unset($notif);

$notificaciones_count = count($notificaciones);

// Obtener cantidad de productos en el carrito
$carrito_count = 0;
if (isset($_SESSION['id_usuario'])) {
    require_once 'modelo/carrito.php';
    $carritoObj = new Carrito();
    $carritoCliente = $carritoObj->obtenerCarritoPorCliente($_SESSION['id_usuario']);
    if ($carritoCliente) {
        $productosCarrito = $carritoObj->obtenerProductosDelCarrito($carritoCliente['id_carrito']);
        $carrito_count = count($productosCarrito);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Casa Lai</title>
    <link rel="stylesheet" href="Styles/new_menu.css">
    
    <!-- jQuery (DEBE ir antes de Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar">
        <!-- Sección izquierda con hamburguesa, logo y nombre -->
        <div class="nav-left">
            <!-- Ícono de menú hamburguesa -->
            <div class="hamburger-menu">
                <button class="icon-btn" id="hamburger-btn">
                    <img src="img/menu.svg" alt="Menú" class="local-icon">
                </button>
            </div>
            
            <!-- Logo y nombre de la empresa (clickeable para ir al dashboard) -->
            <div class="logo-container" onclick="window.location.href='?pagina=dashboard'">
                <div class="logo">
                    <img src="img/logo.png" alt="Logo Casa Lai" height="40">
                </div>
                <div class="company-name">
                    CasaLai C.A
                </div>
            </div>
        </div>
        
        <div class="nav-icons">
            <!-- Botón de tasa de cambio -->
            <button class="icon-btn" id="tasa-cambio-btn">
                <img src="img/currency-exchange.svg" alt="Tasa de Cambio" class="local-icon">
            </button>

            <!-- Botón de carrito -->
            <?php if ($_SESSION['nombre_rol'] === 'Cliente') {  ?>
                <button class="icon-btn" id="cart-btn">
                    <img src="img/shopping-cart2.svg" alt="Carrito" class="local-icon">
                    <?php if ($carrito_count > 0): ?>
                        <span class="cart-count-badge"><?php echo $carrito_count; ?></span>
                    <?php endif; ?>
                </button>
            <?php } ?>

            <!-- Botón de notificaciones -->
            <button class="icon-btn" id="notifications-btn">
                <img src="img/bell.svg" alt="Notificaciones" class="local-icon">
                <?php if ($notificaciones_count > 0): ?>
                    <span class="notification-badge"><?php echo $notificaciones_count; ?></span>
                <?php endif; ?>
            </button>

            <!-- Botón de ayuda -->
            <button class="icon-btn">
                <a href="public/casalai-manual/index.php" target="_blank">
                    <img src="img/info.svg" alt="Ayuda" class="local-icon">
                </a>
            </button>
            
            <!-- Botón de perfil -->
            <button class="icon-btn" id="profile-btn">
                <div class="user-avatar"><?php echo substr($_SESSION['name'] ?? 'U', 0, 1); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Usuario'); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($_SESSION['nombre_rol'] ?? 'Rol'); ?></div>
                </div>
            </button>
        </div>
    </nav>

    <!-- Menú lateral (se activa con el ícono de hamburguesa) -->
    <div class="side-menu" id="side-menu">
        <div class="side-menu-header">
            <h3>Menú Principal</h3>
            <button class="close-btn" id="close-menu">
                <img src="img/x.svg" alt="Cerrar" class="local-icon">
            </button>
        </div>
        
        <div class="menu-options">
            <!-- Administrar Perfiles -->
            <?php if (!empty($permisosConsulta['Usuario']) && $nombre_rol !== 'Cliente'): ?>
            <div class="menu-option" data-target="profiles">
                <span><img src="img/users-round.svg" alt="Perfiles" class="menu-icon"> Administrar Perfiles</span>
                <img src="img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="profiles">
                <div class="sub-option" onclick="window.location.href='?pagina=usuario'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Usuario
                </div>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteUsuario'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Perfiles
                </div>
            </div>
            <?php endif; ?>

            <!-- Administrar Inventario -->
            <?php if (!empty($permisosConsulta['Recepcion']) && $nombre_rol !== 'Cliente'): ?>
            <div class="menu-option" data-target="inventory">
                <span><img src="img/package-open.svg" alt="Inventario" class="menu-icon"> Administrar Inventario</span>
                <img src="img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="inventory">
                <div class="sub-option" onclick="window.location.href='?pagina=recepcion'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Recepción
                </div>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteInventario'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Inventario
                </div>
            </div>
            <?php endif; ?>

            <!-- Administrar Productos -->
            <?php if (($nombre_rol !== 'Cliente') && (!empty($permisosConsulta['Marcas']) || !empty($permisosConsulta['Modelos']) || !empty($permisosConsulta['Productos']) || !empty($permisosConsulta['Categorias']))): ?>
            <div class="menu-option" data-target="products">
                <span><img src="img/package-search.svg" alt="Productos" class="menu-icon"> Administrar Productos</span>
                <img src="img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="products">
                <?php if (!empty($permisosConsulta['Marcas'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=marca'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Marcas
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Modelos'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=modelo'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Modelos
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Productos'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=producto'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Productos
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Categorias'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=categoria'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Categorías
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Marcas']) || !empty($permisosConsulta['Modelos']) || !empty($permisosConsulta['Productos']) || !empty($permisosConsulta['Categorias'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteProductos'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Productos
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Administrar Proveedores -->
            <?php if (!empty($permisosConsulta['Proveedores']) && $nombre_rol !== 'Cliente'): ?>
            <div class="menu-option" data-target="providers">
                <span><img src="img/truck.svg" alt="Proveedores" class="menu-icon"> Administrar Proveedores</span>
                <img src="img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="providers">
                <div class="sub-option" onclick="window.location.href='?pagina=proveedor'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Proveedores
                </div>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteProveedores'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Proveedores
                </div>
            </div>
            <?php endif; ?>

            <!-- Administrar Clientes -->
            <?php if (!empty($permisosConsulta['Clientes']) && $nombre_rol !== 'Cliente'): ?>
            <div class="menu-option" data-target="clients">
                <span><img src="img/users-round.svg" alt="Clientes" class="menu-icon"> Administrar Clientes</span>
                <img src="img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="clients">
                <div class="sub-option" onclick="window.location.href='?pagina=cliente'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Clientes
                </div>
            </div>
            <?php endif; ?>

            <!-- Ventas/Compras -->
            <?php if (!empty($permisosConsulta['Catalogo']) || !empty($permisosConsulta['Compra Física']) || !empty($permisosConsulta['pasarela']) || !empty($permisosConsulta['Prefactura']) || !empty($permisosConsulta['Ordenes de despacho']) || !empty($permisosConsulta['Despacho'])): ?>
            <div class="menu-option" data-target="sales">
                <?php if ($nombre_rol === 'Cliente'): ?>
                    <span><img src="img/shopping-cart.svg" alt="Compras" class="menu-icon"> Compras</span>
                <?php else: ?>
                    <span><img src="img/shopping-cart.svg" alt="Ventas" class="menu-icon"> Administrar Ventas</span>
                <?php endif; ?>
                <img src="img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="sales">
                <?php if (!empty($permisosConsulta['Catalogo'])): ?>
                    <?php if ($nombre_rol === 'Cliente'): ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=catalogo'">
                            <img src="img/angle-right.svg" alt=">" class="menu-icon"> Catálogo
                        </div>
                    <?php else: ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=catalogo'">
                            <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Catálogo
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Compra Física']) && $nombre_rol !== 'Cliente'): ?>
                    <div class="sub-option" onclick="window.location.href='?pagina=comprafisica'">
                        <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Ventas Presenciales
                    </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['pasarela'])): ?>
                    <?php if ($nombre_rol === 'Cliente'): ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=pasarela'">
                            <img src="img/angle-right.svg" alt=">" class="menu-icon"> Pagos
                        </div>
                    <?php else: ?>
                        <div class="sub-option" onclick="window.location.href='?pagina=pasarela'">
                            <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Pagos
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Pedidos']) && $nombre_rol !== 'Cliente'): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=gestionarfactura'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Pedidos
                </div>
                <?php endif; ?>
                 <?php if (!empty($permisosConsulta['Pedidos']) && $nombre_rol == 'Cliente'): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=gestionarfactura'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Pedidos Realizados
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Ordenes de despacho']) && $nombre_rol !== 'Cliente'): ?>
                    <div class="sub-option" onclick="window.location.href='?pagina=ordendespacho'">
                        <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Ordenes de Despacho
                    </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Despacho']) && $nombre_rol !== 'Cliente'): ?>
                    <div class="sub-option" onclick="window.location.href='?pagina=despacho'">
                        <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Despacho
                    </div>
                    <div class="sub-option" onclick="window.location.href='?pagina=reporteVentas'">
                        <img src="img/angle-right.svg" alt=">" class="menu-icon"> Reporte de Ventas
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Administrar Finanzas -->
            <?php if (!empty($permisosConsulta['Cuentas bancarias']) || !empty($permisosConsulta['Finanzas'] && $nombre_rol !== 'Cliente')): ?>
            <div class="menu-option" data-target="finances">
                <span><img src="img/dollar-sign.svg" alt="Finanzas" class="menu-icon"> Administrar Finanzas</span>
                <img src="img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="finances">
                <?php if (!empty($permisosConsulta['Cuentas bancarias'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=cuenta'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Cuentas Bancarias
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Finanzas'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=finanza'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Ingresos y Egresos
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Cuentas bancarias']) || !empty($permisosConsulta['Finanzas'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=reporteFinanzas'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Reportes de Finanzas
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Administrar Seguridad -->
            <?php if (!empty($permisosConsulta['permisos']) || !empty($permisosConsulta['Roles']) || !empty($permisosConsulta['bitacora']) || !empty($permisosConsulta['Respaldo'])): ?>
            <div class="menu-option" data-target="security">
                <span><img src="img/key-round.svg" alt="Seguridad" class="menu-icon"> Administrar Seguridad</span>
                <img src="img/chevron-right.svg" alt="Expandir" class="menu-icon">
            </div>
            <div class="sub-options" id="security">
                <?php if (!empty($permisosConsulta['permisos'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=permiso'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Permisos
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Roles'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=rol'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Roles
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['bitacora'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=bitacora'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Bitácora
                </div>
                <?php endif; ?>
                <?php if (!empty($permisosConsulta['Respaldo'])): ?>
                <div class="sub-option" onclick="window.location.href='?pagina=backup'">
                    <img src="img/angle-right.svg" alt=">" class="menu-icon"> Gestionar Respaldo
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Overlay para el menú lateral -->
    <div class="overlay" id="overlay"></div>

        <!-- Panel de Tasa de Cambio -->
        <div class="tasa-cambio-panel" id="tasa-cambio-panel">
        <h2>Tipo de Cambio <img src="img/currency-exchange.svg" alt="Tasa" class="local-icon" style="width: 20px; height: 20px;"></h2>
        <div class="tasa-info">
            <div class="tasa-valor">
                <strong>1 USD = <?= $tasaBCVFormateada ?> BS</strong>
            </div>
            <div class="tasa-actualizacion">
                <small>Actualizado: <?= date('d/m/Y H:i') ?></small>
            </div>
            <div class="tasa-fuente">
                <small>Fuente: Banco Central de Venezuela</small>
            </div>
        </div>
    </div>

    <!-- Panel de Notificaciones -->
    <div class="notificacion-panel" id="notifications-panel">
        <h2>Notificaciones <span><?php echo $notificaciones_count; ?></span></h2>
        <?php if ($notificaciones_count > 0): ?>
            <?php foreach ($notificaciones as $notif): ?>
                <div class="item-notificacion">
                    <div class="texto">
                        <h4><?= htmlspecialchars($notif['titulo']) ?></h4>
                        <p><?= htmlspecialchars($notif['mensaje']) ?></p>
                        <?php if ($notif['tipo'] == 'pago' && !empty($notif['detalle_pago'])): ?>
                            <small>Referencia: <?= htmlspecialchars($notif['detalle_pago']['referencia']) ?></small>
                        <?php endif; ?>
                        <small><?= date('d/m/Y H:i', strtotime($notif['fecha_hora'])) ?></small>
                    </div>
                    <button class="marcar-leido" data-id="<?= $notif['id_notificacion'] ?>">
                        <img src="img/check.svg" alt="Marcar leído" class="local-icon">
                    </button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="item-notificacion">
                <div class="texto">
                    <p>No hay notificaciones recientes</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel de Perfil -->
    <div class="profile-panel" id="profile-panel">
        <h2>Mi Cuenta</h2>
        <div class="profile-info">
            <div class="profile-avatar"><?php echo substr($_SESSION['name'] ?? 'U', 0, 1); ?></div>
            <div class="profile-details">
                <div class="profile-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Usuario'); ?></div>
                <div class="profile-role"><?php echo htmlspecialchars($_SESSION['nombre_rol'] ?? 'Rol'); ?></div>
            </div>
        </div>
        <div class="profile-options">
            <a href="?pagina=perfil" class="profile-option">
                <img src="img/user.svg" alt="Perfil" class="local-icon">
                Mi Perfil
            </a>
            <a href="#" class="profile-option session-out" onclick="confirmarCerrarSesion(); return false;">
                <img src="img/log-out.svg" alt="Cerrar Sesión" class="local-icon">
                Cerrar Sesión
            </a>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
    // Función para confirmar cierre de sesión con SweetAlert
    function confirmarCerrarSesion() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: "¿Está seguro que desea cerrar sesión?",
            icon: 'question',
            iconColor: '#d8d508ff',
            showCancelButton: true,
            confirmButtonColor: '#0863b8',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cerrar sesión',
            cancelButtonText: 'Cancelar',
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?pagina=cerrar';
            }
        });
    }
    
    // JavaScript para el menú lateral
    document.addEventListener('DOMContentLoaded', function() {
        // Elementos del menú lateral
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const sideMenu = document.getElementById('side-menu');
        const closeMenuBtn = document.getElementById('close-menu');
        const overlay = document.getElementById('overlay');
        
        // Elementos de las opciones del menú
        const menuOptions = document.querySelectorAll('.menu-option[data-target]');
        const tasaCambioBtn = document.getElementById('tasa-cambio-btn');
        const tasaCambioPanel = document.getElementById('tasa-cambio-panel');
        const notificationsBtn = document.getElementById('notifications-btn');
        const notificationsPanel = document.getElementById('notifications-panel');
        const cartBtn = document.getElementById('cart-btn');
        const profileBtn = document.getElementById('profile-btn');
        const profilePanel = document.getElementById('profile-panel');
        
        // Verificar que todos los elementos existan antes de agregar event listeners
        if (hamburgerBtn && sideMenu) {
            // Alternar menú lateral
            hamburgerBtn.addEventListener('click', function() {
                sideMenu.classList.add('active');
                if (overlay) overlay.classList.add('active');
                // Cerrar otros paneles
                closeAllPanels();
            });
        }
        
        if (closeMenuBtn) {
            // Cerrar menú lateral
            closeMenuBtn.addEventListener('click', closeSideMenu);
        }
        
        if (overlay) {
            overlay.addEventListener('click', closeSideMenu);
        }
        
        function closeSideMenu() {
            if (sideMenu) sideMenu.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
        }
        
        // Función para cerrar todos los paneles
        function closeAllPanels() {
            if (tasaCambioPanel) tasaCambioPanel.classList.remove('active');
            if (notificationsPanel) notificationsPanel.classList.remove('active');
            if (profilePanel) profilePanel.classList.remove('active');
        }
        
        // Alternar submenús en el menú lateral (solo para opciones con data-target)
        if (menuOptions.length > 0) {
            menuOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const targetId = this.getAttribute('data-target');
                    if (!targetId) return;
                    
                    const subOptions = document.getElementById(targetId);
                    if (!subOptions) return;
                    
                    // Cerrar otros submenús abiertos
                    document.querySelectorAll('.sub-options').forEach(sub => {
                        if (sub.id !== targetId && sub.classList.contains('active')) {
                            sub.classList.remove('active');
                            if (sub.previousElementSibling && sub.previousElementSibling.classList.contains('menu-option')) {
                                sub.previousElementSibling.classList.remove('active');
                            }
                        }
                    });
                    
                    // Alternar el submenú actual
                    subOptions.classList.toggle('active');
                    this.classList.toggle('active');
                });
            });
        }
        
        // Alternar panel de tasa de cambio
        if (tasaCambioBtn && tasaCambioPanel) {
            tasaCambioBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                tasaCambioPanel.classList.toggle('active');
                // Cerrar otros paneles
                if (notificationsPanel) notificationsPanel.classList.remove('active');
                if (profilePanel) profilePanel.classList.remove('active');
            });
            
            // Cerrar panel de tasa de cambio al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (tasaCambioBtn && !tasaCambioBtn.contains(e.target) && 
                    tasaCambioPanel && !tasaCambioPanel.contains(e.target)) {
                    tasaCambioPanel.classList.remove('active');
                }
            });
        }
        
        // Alternar panel de notificaciones
        if (notificationsBtn && notificationsPanel) {
            notificationsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationsPanel.classList.toggle('active');
                // Cerrar otros paneles
                if (tasaCambioPanel) tasaCambioPanel.classList.remove('active');
                if (profilePanel) profilePanel.classList.remove('active');
            });
            
            // Cerrar panel de notificaciones al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (notificationsBtn && !notificationsBtn.contains(e.target) && 
                    notificationsPanel && !notificationsPanel.contains(e.target)) {
                    notificationsPanel.classList.remove('active');
                }
            });
        }
        
        // Redireccionar al carrito
        if (cartBtn) {
            cartBtn.addEventListener('click', function() {
                window.location.href = '?pagina=carrito';
            });
        }
        
        // Alternar panel de perfil
        if (profileBtn && profilePanel) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profilePanel.classList.toggle('active');
                // Cerrar otros paneles
                if (tasaCambioPanel) tasaCambioPanel.classList.remove('active');
                if (notificationsPanel) notificationsPanel.classList.remove('active');
            });
            
            // Cerrar panel de perfil al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (profileBtn && !profileBtn.contains(e.target) && 
                    profilePanel && !profilePanel.contains(e.target)) {
                    profilePanel.classList.remove('active');
                }
            });
        }
        
        // Manejar marcado de notificaciones como leídas
        const marcarLeidoButtons = document.querySelectorAll('.marcar-leido');
        if (marcarLeidoButtons.length > 0) {
            marcarLeidoButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const idNotificacion = this.getAttribute('data-id');
                    
                    fetch('Controlador/marcar_notificacion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id_notificacion=' + idNotificacion
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Eliminar la notificación del DOM
                            const notificacionItem = this.closest('.item-notificacion');
                            if (notificacionItem) {
                                notificacionItem.remove();
                                
                                // Actualizar el contador
                                const countElement = document.querySelector('.notification-badge');
                                const titleCountElement = document.querySelector('.notificacion-panel h2 span');
                                
                                if (countElement) {
                                    const newCount = parseInt(countElement.textContent) - 1;
                                    countElement.textContent = newCount;
                                    
                                    if (titleCountElement) {
                                        titleCountElement.textContent = newCount;
                                    }
                                    
                                    if (newCount <= 0) {
                                        countElement.style.display = 'none';
                                        
                                        // Si no hay más notificaciones, mostrar mensaje
                                        if (document.querySelectorAll('.notificacion-panel .item-notificacion').length === 0) {
                                            if (notificationsPanel) {
                                                notificationsPanel.innerHTML = `
                                                    <h2>Notificaciones <span>0</span></h2>
                                                    <div class="item-notificacion">
                                                        <div class="texto">
                                                            <p>No hay notificaciones recientes</p>
                                                        </div>
                                                    </div>
                                                `;
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            console.error('Error:', data.error);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                });
            });
        }
        
        // Actualizar notificaciones periódicamente
        setInterval(actualizarNotificaciones, 6000);
        
        // Actualizar contador del carrito periódicamente
        setInterval(actualizarCarritoCount, 5000);
        
        // Actualizar tasa de cambio periódicamente (cada 5 minutos)
        setInterval(actualizarTasaCambio, 300000);
    });

    function actualizarTasaCambio() {
        fetch('Controlador/obtener_tasa_cambio.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const tasaPanel = document.getElementById('tasa-cambio-panel');
                    if (tasaPanel) {
                        let html = `<h2>Tipo de Cambio <i class="bi bi-currency-exchange"></i></h2>`;
                        
                        if (data.tasa) {
                            html += `
                                <div class="tasa-info">
                                    <div class="tasa-valor">
                                        <strong>1 USD = ${data.tasa} BS</strong>
                                    </div>
                                    <div class="tasa-actualizacion">
                                        <small>Actualizado: ${data.actualizado}</small>
                                    </div>
                                    <div class="tasa-fuente">
                                        <small>Fuente: Banco Central de Venezuela</small>
                                    </div>
                                </div>
                            `;
                        }
                        
                        tasaPanel.innerHTML = html;
                    }
                }
            })
            .catch(err => console.error('Error actualizando tasa de cambio:', err));
    }

    function actualizarNotificaciones() {
        fetch('Controlador/obtener_notificaciones.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const notificationsPanel = document.getElementById('notifications-panel');
                    if (notificationsPanel) {
                        let html = `<h2>Notificaciones <span>${data.count}</span></h2>`;
                        
                        if (data.notificaciones.length > 0) {
                            data.notificaciones.forEach(notif => {
                                html += `
                                    <div class="item-notificacion">
                                        <div class="texto">
                                            <h4>${notif.titulo}</h4>
                                            <p>${notif.mensaje}</p>
                                            ${notif.referencia ? `<small>Referencia: ${notif.referencia}</small>` : ""}
                                            <small>${notif.fecha_hora}</small>
                                        </div>
                                        <button class="marcar-leido" data-id="${notif.id_notificacion}">
                                            <img src="img/check.svg" alt="Marcar leído" class="local-icon">
                                        </button>
                                    </div>
                                `;
                            });
                        } else {
                            html += `
                                <div class="item-notificacion">
                                    <div class="texto"><p>No hay notificaciones recientes</p></div>
                                </div>
                            `;
                        }

                        notificationsPanel.innerHTML = html;
                    }
                }
            })
            .catch(err => console.error('Error actualizando notificaciones:', err));
    }

    function actualizarCarritoCount() {
    fetch('Controlador/obtener_carrito_count.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                const cartBadge = document.querySelector('.cart-count-badge');
                const cartBtn = document.getElementById('cart-btn');
                
                if (data.count > 0) {
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        cartBadge.style.display = 'flex';
                    } else if (cartBtn) {
                        // Crear badge si no existe
                        const newBadge = document.createElement('span');
                        newBadge.className = 'cart-count-badge';
                        newBadge.textContent = data.count;
                        cartBtn.appendChild(newBadge);
                    }
                } else if (cartBadge) {
                    cartBadge.style.display = 'none';
                }
            }
        })
        .catch(err => console.error('Error actualizando carrito:', err));
    }
    </script>
</body>
</html>