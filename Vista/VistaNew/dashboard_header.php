<?php
// Archivo: dashboard_header.php - Header del dashboard reutilizable
?>
<!-- Header -->
<header class="main-header">
    <div class="header-left">
        <h1><?php echo $titulo_pagina ?? 'Panel Principal'; ?></h1>
    </div>
    <div class="header-right">
        <div class="date-picker">
            <input type="date" id="dateFilter" class="date-input">
        </div>
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