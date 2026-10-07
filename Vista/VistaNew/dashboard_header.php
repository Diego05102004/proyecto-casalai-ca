<?php
// Archivo: dashboard_header.php - Header del dashboard reutilizable
?>
<!-- Header -->

<link rel="stylesheet" href="assets/styles/header.css">

<header class="main-header">
    <div class="header-left">
        <h1><?php echo $titulo_pagina ?? 'Panel Principal'; ?></h1>
    </div>
    <div class="header-right">
        <!-- Botón de tasa de cambio -->
        <button class="btn-icon" id="tasa-cambio-btn">
            <img src="assets/img/currency-exchange.svg" alt="Tasa de Cambio" class="local-icon">
        </button>

        <!-- Botón de carrito -->
        <?php if (isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] === 'Cliente'): ?>
            <button class="btn-icon" id="cart-btn">
                <img src="assets/img/shopping-cart2.svg" alt="Carrito" class="local-icon">
                <?php if (isset($carrito_count) && $carrito_count > 0): ?>
                    <span class="cart-count-badge"><?php echo $carrito_count; ?></span>
                <?php endif; ?>
            </button>
        <?php endif; ?>

        <!-- Botón de notificaciones -->
        <button class="btn-icon" id="notifications-btn">
            <img src="assets/img/bell.svg" alt="Notificaciones" class="local-icon">
            <?php if (isset($notificaciones_count) && $notificaciones_count > 0): ?>
                <span class="notification-badge"><?php echo $notificaciones_count; ?></span>
            <?php endif; ?>
        </button>

        <!-- Botón de ayuda -->
        <button class="btn-icon">
            <a href="assets/public/casalai-manual/index-new.php" target="_blank">
                <img src="assets/img/info.svg" alt="Ayuda" class="local-icon">
            </a>
        </button>
        <div class="user-profile">
            <div class="user-avatar">
                <?php
                $inicial = substr($_SESSION['name'] ?? 'U', 0, 1);
                if (!empty($_SESSION['foto_perfil'])) {
                    echo '<img src="assets/img/uploads/' . $_SESSION['foto_perfil'] . '" alt="Avatar de Usuario">';
                } else {
                    echo $inicial;
                }
                ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Usuario'); ?></span>
                <span class="user-role"><?php echo htmlspecialchars($_SESSION['nombre_rol'] ?? 'Rol'); ?></span>
            </div>
        </div>
    </div>
</header>