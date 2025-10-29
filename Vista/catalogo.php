<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles/catalogo.css">
    <?php $exclude_buttons_css = true; include 'header.php'; ?>
</head>

<body class="fondo" style="background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <?php 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Determinar qué navbar incluir basado en la sesión
    if (isset($_SESSION['nombre_rol']) && !empty($_SESSION['nombre_rol'])) {
        include 'newnavbar.php';
    } else {
        include 'navbar.php';
    }
    ?>
    <br>

    <div class="main-content">
        <section class="catalogo-container mt-4">


        <!-- Pestañas de navegación -->
        <div class="catalogo-tabs">
            <!-- Campo oculto para tasa de cambio (usado por javascript/catalogo.js) -->
            <input type="hidden" id="tasa" value="<?= htmlspecialchars($data['monitors']['bcv']['price'] ?? 0) ?>">
            <ul class="nav nav-tabs" id="catalogoTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="productos-tab">
                        <i class="bi bi-box-seam"></i> Productos Individuales
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="combos-tab">
                        <i class="bi bi-collection"></i> Combos Promocionales
                    </button>
                </li>
                <?php if ($esAdmin): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reportes-tab">
                        <i class="bi bi-bar-chart"></i> Reportes Estadísticos
                    </button>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Contenido de Productos -->
        <div id="productos-content">
            <!-- Filtros y búsqueda -->
            <div class="filtros-container">
                <div class="filters-inline">
                    <div>
                        <label for="filtroMarca">Filtrar por marca</label>
                        <select id="filtroMarca">
                            <option value="">Todas las marcas</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?= htmlspecialchars($marca['id_marca']) ?>">
                                    <?= htmlspecialchars($marca['nombre_marca']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="search-box" style="flex:1;">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchProduct" placeholder="Buscar producto...">
                    </div>
                </div>
            </div>

            <!-- Grid de Productos -->
            <div class="productos-grid">
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $producto): 
                        $precioBs = isset($data['monitors']['bcv']['price']) && isset($producto['precio']) ? 
                                   $producto['precio'] * $data['monitors']['bcv']['price'] : 0;
                        $stock = $producto['stock'] ?? 0;
                    ?>
                        <div class="producto-card" data-id="<?= $producto['id_producto'] ?>">
                            <!-- Badge de stock -->
                            <?php if ($stock > 10): ?>
                                <div class="producto-badge">En stock</div>
                            <?php elseif ($stock > 0): ?>
                                <div class="producto-badge stock-bajo">Stock bajo</div>
                            <?php else: ?>
                                <div class="producto-badge sin-stock">Agotado</div>
                            <?php endif; ?>

                            <!-- Imagen del producto -->
                            <a href="?pagina=detalle_producto&id=<?= (int)$producto['id_producto'] ?>" class="producto-imagen-container" style="display:block;">
                                <?php if (!empty($producto['imagen'])): ?>
                                    <img src="<?= htmlspecialchars($producto['imagen']) ?>" 
                                         class="producto-imagen"
                                         alt="<?= htmlspecialchars($producto['nombre_producto']) ?>"
                                         onerror="this.src='img/placeholder-product.png'">
                                <?php else: ?>
                                    <div class="producto-imagen-container img-placeholder">
                                        <i class="bi bi-image" style="font-size: 3rem; color: #6b7280;"></i>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <!-- Contenido de la card -->
                           <div class="producto-content">
                                <h3 class="producto-nombre"><a href="?pagina=detalle_producto&id=<?= (int)$producto['id_producto'] ?>" style="text-decoration:none; color:inherit;">
                                    <?= htmlspecialchars($producto['nombre_producto']) ?>
                                </a></h3>
                                <div class="producto-serial"><?= htmlspecialchars($producto['serial']) ?></div>
                                <p class="producto-descripcion"><?= htmlspecialchars($producto['descripcion_producto']) ?></p>
                                <div class="producto-marca"><?= htmlspecialchars($producto['marca']) ?></div>
                                
                                <div class="producto-precio-container">
                                    <div class="producto-precio"><?= number_format($precioBs, 2) ?> BS</div>
                                   <button type="button" 
    class="btn-agregar-carrito <?= $stock <= 0 ? 'disabled' : '' ?>"
    data-id-producto="<?= htmlspecialchars($producto['id_producto']) ?>"
    data-stock="<?= $stock ?>"
    <?= $stock <= 0 ? 'disabled' : '' ?>>
    <i class="bi bi-cart-plus"></i>
    <span class="btn-text"><?= $stock <= 0 ? 'Agotado' : 'Agregar' ?></span>
</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-exclamation-circle"></i>
                        <h4>No hay productos disponibles</h4>
                        <p>En este momento no tenemos productos en nuestro catálogo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contenido de Combos -->
        <div id="combos-content" style="display: none;">
            <?php if ($esAdmin): ?>
                <div class="text-end mb-3">
                    <button type="button" class="btn btn-primary" id="nuevo_combo">
                        <i class="bi bi-plus-circle"></i> Nuevo Combo
                    </button>
                </div>
            <?php endif; ?>

            <?php if (!empty($combos)): ?>
                <div class="combos-grid">
                    <?php foreach ($combos as $combo):
                        if (!$esAdmin && !$combo['activo']) continue;

                        $detalles = $productosModel->obtenerDetallesCombo($combo['id_combo']);
                        $precioTotal = 0;
                        $todosDisponibles = true;

                        foreach ($detalles as $detalle) {
                            $producto = $productosModel->obtenerProductoPorId($detalle['id_producto']);
                            $precioTotal += ($producto['precio'] * $detalle['cantidad']);
                            $todosDisponibles = $todosDisponibles && ($producto['stock'] >= $detalle['cantidad']);
                        }

                        $ahorro = $precioTotal * 0.1;
                        $precioComboBs = ($precioTotal - $ahorro) * $data['monitors']['bcv']['price'];
                        $precioOriginalBs = $precioTotal * $data['monitors']['bcv']['price'];
                        $ahorroBs = $ahorro * $data['monitors']['bcv']['price'];
                    ?>
                        <div class="combo-card <?= !$combo['activo'] ? 'disabled-combo' : '' ?>">
                            <div class="combo-header">
                                <h4 class="combo-nombre"><?= htmlspecialchars($combo['nombre_combo']) ?></h4>
                                <?php if (!$combo['activo']): ?>
                                    <span class="badge bg-secondary">Deshabilitado</span>
                                <?php endif; ?>
                                <p class="combo-descripcion"><?= htmlspecialchars($combo['descripcion']) ?></p>
                            </div>

                            <!-- Imágenes del combo -->
                            <div class="combo-imagenes-grid">
                                <?php
                                $imagenesMostradas = 0;
                                foreach ($detalles as $detalle):
                                    if ($imagenesMostradas >= 4) break;
                                    $producto = $productosModel->obtenerProductoPorId($detalle['id_producto']);
                                    if (!empty($producto['imagen'])):
                                        $imagenesMostradas++;
                                ?>
                                        <img src="<?= htmlspecialchars($producto['imagen']) ?>" 
                                             class="combo-imagen <?= $imagenesMostradas == 1 ? 'principal' : '' ?>"
                                             alt="<?= htmlspecialchars($producto['nombre_producto']) ?>"
                                             onerror="this.src='img/placeholder-product.png'">
                                <?php
                                    endif;
                                endforeach;

                                // Placeholders para imágenes faltantes
                                while ($imagenesMostradas < 4) {
                                    echo '<div class="combo-imagen ' . ($imagenesMostradas == 0 ? 'principal' : '') . ' img-placeholder">';
                                    echo '<i class="bi bi-image"></i>';
                                    echo '</div>';
                                    $imagenesMostradas++;
                                }
                                ?>
                            </div>

                            <div class="combo-content">
                                <!-- Lista de productos -->
                                <div class="combo-productos-list">
                                    <?php foreach ($detalles as $detalle):
                                        $producto = $productosModel->obtenerProductoPorId($detalle['id_producto']);
                                        $disponible = $producto['stock'] >= $detalle['cantidad'];
                                    ?>
                                        <div class="combo-producto-item">
                                            <div>
                                                <?= htmlspecialchars($producto['nombre_producto']) ?>
                                                <?php if (!$disponible): ?>
                                                    <i class="bi bi-exclamation-triangle-fill text-danger ms-1" 
                                                       title="Stock insuficiente"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <span class="text-muted small">
                                                    <?= number_format($producto['precio'] * $data['monitors']['bcv']['price'], 2) ?> BS
                                                </span> ×
                                                <span class="badge bg-<?= $disponible ? 'primary' : 'danger' ?> rounded-pill">
                                                    <?= $detalle['cantidad'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Precios y ahorro -->
                                <div class="combo-precios">
                                    <div>
                                        <div class="precio-original"><?= number_format($precioOriginalBs, 2) ?> BS</div>
                                        <div class="precio-combo"><?= number_format($precioComboBs, 2) ?> BS</div>
                                    </div>
                                    <div class="ahorro-combo">Ahorras <?= number_format($ahorroBs, 2) ?> BS</div>
                                </div>

                                <!-- Botón agregar combo -->
                                <button class="btn-agregar-carrito btn-agregar-combo w-100 <?= !$todosDisponibles || !$combo['activo'] ? 'disabled' : '' ?>"
                                        data-id-combo="<?= $combo['id_combo'] ?>"
                                        <?= !$todosDisponibles || !$combo['activo'] ? 'disabled' : '' ?>>
                                    <i class="bi bi-cart-plus"></i>
                                    <?= !$combo['activo'] ? 'Combo no disponible' : ($todosDisponibles ? 'Agregar Combo' : 'Productos no disponibles') ?>
                                </button>

                                <!-- Acciones de admin -->
                                <?php if ($esAdmin): ?>
                                    <div class="d-flex justify-content-between mt-3">
                                        <button class="btn btn-sm btn-outline-primary btn-editar-combo"
                                                data-id-combo="<?= $combo['id_combo'] ?>">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                        <button class="btn btn-sm <?= $combo['activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-cambiar-estado"
                                                data-id-combo="<?= $combo['id_combo'] ?>"
                                                data-nombre-combo="<?= htmlspecialchars($combo['nombre_combo']) ?>"
                                                data-estado-actual="<?= $combo['activo'] ? 1 : 0 ?>">
                                            <i class="bi <?= $combo['activo'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                                            <?= $combo['activo'] ? 'Deshabilitar' : 'Habilitar' ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-info-circle"></i>
                    <h4>No hay combos disponibles</h4>
                    <p>En este momento no tenemos combos promocionales disponibles.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Los modales y contenido de reportes se mantienen igual -->
        <!-- ... (mantener el mismo código de modales y reportes) ... -->

    </section>
    </div>

    <!--footer-->

    <script src="public/js/jquery-3.7.1.min.js"></script>
    
    <script src="javascript/sweetalert2.all.min.js"></script>
    <script src="javascript/catalogo.js"></script>
    <script src="public/js/jquery.dataTables.min.js"></script>
    <script src="public/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        function MensajeInicio() {
            Swal.fire({
                icon: 'info',
                title: '¡Hola!',
                text: 'Para agregar productos al carrito, por favor inicia sesión.',
                confirmButtonText: 'Iniciar Sesión',
                showCancelButton: true,
                cancelButtonText: 'Cancelar',
                background: '#f8f9fa',
                backdrop: `
                    rgba(0,0,0,0.4)
                    url("/public/img/cart.gif")
                    center top
                    no-repeat
                `
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?pagina=login';
                }
            });
        }
    </script>
<?php include 'footer.php'; ?>
</body>
</html>