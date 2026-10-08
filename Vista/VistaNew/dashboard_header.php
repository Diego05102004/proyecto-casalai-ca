<?php
// Archivo: dashboard_header.php - Header del dashboard reutilizable
$id_usuario_header = (int) ($_SESSION['id_usuario'] ?? 0);

$dolarService = new \Usuario\ProyectoCasalaiCa\Modelo\Clases\DolarService();
$registroDolar = $dolarService->obtenerRegistroDelDia();
$tasaBCV = isset($registroDolar['precio'])
    ? (float) $registroDolar['precio']
    : $dolarService->obtenerPrecioDelDia();
$tasaBCVFormateada = number_format($tasaBCV, 2);
$tasaFecha = $registroDolar['fecha'] ?? date('Y-m-d H:i:s');
$tasaFechaFormateada = date('d/m/Y H:i', strtotime($tasaFecha));

$notificaciones = [];
$notificaciones_count = 0;

if ($id_usuario_header > 0) {
    $bd_seguridad = new \Usuario\ProyectoCasalaiCa\Config\BD('S');
    try {
        $pdo_seguridad = $bd_seguridad->getConexion();
        $query = "SELECT * FROM tbl_notificaciones
                  WHERE id_usuario = :id_usuario AND leido = 0
                  ORDER BY fecha_hora DESC LIMIT 5";
        $stmt = $pdo_seguridad->prepare($query);
        $stmt->bindValue(':id_usuario', $id_usuario_header, PDO::PARAM_INT);
        $stmt->execute();
        $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } finally {
        $bd_seguridad->cerrar();
    }

    $bd_casalai = null;
    $stmt_pago = null;
    foreach ($notificaciones as &$notificacion) {
        if ($notificacion['tipo'] !== 'pago' || empty($notificacion['id_referencia'])) {
            continue;
        }

        if ($bd_casalai === null) {
            $bd_casalai = new \Usuario\ProyectoCasalaiCa\Config\BD('C');
            $stmt_pago = $bd_casalai->getConexion()->prepare(
                'SELECT referencia FROM tbl_detalles_pago WHERE id_detalles = ?'
            );
        }

        $stmt_pago->execute([$notificacion['id_referencia']]);
        $notificacion['detalle_pago'] = $stmt_pago->fetch(PDO::FETCH_ASSOC);
    }
    unset($notificacion);
    if ($bd_casalai !== null) {
        $bd_casalai->cerrar();
    }

    $notificaciones_count = count($notificaciones);
}
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

<div class="tasa-cambio-panel header-popover" id="tasa-cambio-panel">
    <h2>
        Tipo de Cambio
        <img src="assets/img/currency-exchange.svg" alt="" class="local-icon">
    </h2>
    <div class="tasa-info">
        <div class="tasa-valor">
            <strong>1 USD = <?php echo htmlspecialchars($tasaBCVFormateada); ?> BS</strong>
        </div>
        <div class="tasa-actualizacion">
            <small>Actualizado: <?php echo htmlspecialchars($tasaFechaFormateada); ?></small>
        </div>
        <div class="tasa-fuente">
            <small>Fuente: Banco Central de Venezuela</small>
        </div>
    </div>
</div>

<div class="notificacion-panel header-popover" id="notifications-panel">
    <h2>
        Notificaciones
        <a href="?pagina=notificacion" class="small">Ver más</a>
        <span class="notification-count"><?php echo $notificaciones_count; ?></span>
    </h2>
    <div id="notifications-list">
        <?php if ($notificaciones_count > 0): ?>
            <?php foreach ($notificaciones as $notificacion): ?>
                <?php
                $fecha_notificacion = $notificacion['fecha_creacion'] ?? $notificacion['fecha_hora'] ?? '';
                $timestamp_notificacion = $fecha_notificacion !== '' ? strtotime($fecha_notificacion) : false;
                ?>
                <div class="item-notificacion notificacion-no-leida"
                     data-id="<?php echo (int) $notificacion['id_notificacion']; ?>">
                    <div class="texto">
                        <h4><?php echo htmlspecialchars($notificacion['titulo'] ?? ''); ?></h4>
                        <p><?php echo htmlspecialchars($notificacion['mensaje'] ?? ''); ?></p>
                        <?php if (!empty($notificacion['detalle_pago']['referencia'])): ?>
                            <small>Referencia: <?php echo htmlspecialchars($notificacion['detalle_pago']['referencia']); ?></small>
                        <?php endif; ?>
                        <?php if ($timestamp_notificacion !== false): ?>
                            <small class="fecha-notificacion">
                                <?php echo date('d/m/Y H:i:s', $timestamp_notificacion); ?>
                            </small>
                        <?php endif; ?>
                    </div>
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
</div>

<link rel="stylesheet" href="assets/styles/tablas_section_styles.css">
<link rel="stylesheet" href="assets/styles/modal-registrar.css">
<link rel="stylesheet" href="assets/styles/modal-detallar.css">
<link rel="stylesheet" href="assets/styles/modal-eliminar-anular.css">

<script>
document.addEventListener('DOMContentLoaded', function () {
    const panels = [
        [document.getElementById('tasa-cambio-btn'), document.getElementById('tasa-cambio-panel')],
        [document.getElementById('notifications-btn'), document.getElementById('notifications-panel')]
    ];

    panels.forEach(function ([button, panel]) {
        if (!button || !panel) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.stopPropagation();
            const shouldOpen = !panel.classList.contains('active');
            panels.forEach(function (entry) {
                if (entry[1]) {
                    entry[1].classList.remove('active');
                }
            });
            if (shouldOpen) {
                panel.classList.add('active');
            }
        });

        panel.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });

    document.addEventListener('click', function () {
        panels.forEach(function (entry) {
            if (entry[1]) {
                entry[1].classList.remove('active');
            }
        });
    });
});
</script>