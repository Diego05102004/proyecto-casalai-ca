<?php
// Archivo: sidebar.php - Menú lateral reutilizable
?>
<!-- Sidebar Navigation -->
<aside class="sidebar" style="background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);">
    <div class="sidebar-header">
        <div class="logo">
            <img src="assets/img/LOGO.png" alt="CasaLai Logo">
        </div>
        <h2>CasaLai C.A</h2>
    </div>
    
    <nav class="sidebar-nav">
        <a href="?pagina=dashboard" class="nav-link <?php echo ($pagina_actual ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
            <span class="nav-text">Panel Principal</span>
        </a>
        <a href="?pagina=cliente" class="nav-link <?php echo ($pagina_actual ?? '') === 'cliente' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-users"></i></span>
            <span class="nav-text">Clientes</span>
        </a>
        <a href="?pagina=gestionarfactura" class="nav-link <?php echo ($pagina_actual ?? '') === 'gestionarfactura' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-box"></i></span>
            <span class="nav-text">Pedidos</span>
        </a>
        <a href="?pagina=despacho" class="nav-link <?php echo ($pagina_actual ?? '') === 'despacho' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-truck"></i></span>
            <span class="nav-text">Despachos</span>
        </a>
        <a href="?pagina=reporteVentas" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteVentas' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-chart-bar"></i></span>
            <span class="nav-text">Análisis</span>
        </a>
        <a href="?pagina=notificacion" class="nav-link <?php echo ($pagina_actual ?? '') === 'notificacion' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-bell"></i></span>
            <span class="nav-text">Mensajes</span>
        </a>
        <a href="?pagina=producto" class="nav-link <?php echo ($pagina_actual ?? '') === 'producto' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-store"></i></span>
            <span class="nav-text">Productos</span>
        </a>
        <a href="?pagina=proveedor" class="nav-link <?php echo ($pagina_actual ?? '') === 'proveedor' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-building"></i></span>
            <span class="nav-text">Proveedores</span>
        </a>
        <a href="?pagina=usuario" class="nav-link <?php echo ($pagina_actual ?? '') === 'usuario' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-user-cog"></i></span>
            <span class="nav-text">Usuarios</span>
        </a>
        <a href="?pagina=reporteFinanzas" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteFinanzas' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></span>
            <span class="nav-text">Finanzas</span>
        </a>
        <a href="?pagina=reporteInventario" class="nav-link <?php echo ($pagina_actual ?? '') === 'reporteInventario' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-warehouse"></i></span>
            <span class="nav-text">Inventario</span>
        </a>
        <a href="?pagina=perfil" class="nav-link <?php echo ($pagina_actual ?? '') === 'perfil' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-cog"></i></span>
            <span class="nav-text">Configuración</span>
        </a>
        <a href="?pagina=producto" class="nav-link add-product <?php echo ($pagina_actual ?? '') === 'producto' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fas fa-plus-circle"></i></span>
            <span class="nav-text">Agregar Producto</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="#" onclick="confirmarCerrarSesion(); return false;" class="nav-link logout">
            <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span class="nav-text">Cerrar Sesión</span>
        </a>
    </div>
</aside>