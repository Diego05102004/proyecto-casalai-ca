<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión (sistema tradicional)
if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

// Verificar y/o generar token JWT (sistema JWT)
require_once __DIR__ . '/../../Modelo/Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;

if (!Auth::validateToken() && isset($_SESSION['id_usuario']) && isset($_SESSION['nombre_rol'])) {
    try {
        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
        Auth::setTokenCookie($token);
    } catch (Exception $e) {
        error_log("Error al generar JWT en producto: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'producto';
$titulo_pagina = 'Gestión de Productos';
$permisosProducto = is_array($permisosUsuario ?? null) ? $permisosUsuario : [];
$puedeConsultarProductos = !empty($permisosProducto['consultar']);
$puedeIncluirProductos = !empty($permisosProducto['incluir']) || !empty($permisosProducto['ingresar']);
$puedeModificarProductos = !empty($permisosProducto['modificar']);
$puedeEliminarProductos = !empty($permisosProducto['eliminar']);

if (!$puedeConsultarProductos) {
    header('Location: ?pagina=acceso-denegado');
    exit();
}

// Iniciar el buffer de contenido
ob_start();
?>

<?php
// Cálculos previos para summary cards
$productos = is_array($productos ?? null) ? $productos : [];
foreach ($productos as &$producto) {
    $producto['stock_actual'] = (int)($producto['stock_actual'] ?? $producto['stock'] ?? 0);
    $producto['stock_minimo'] = (int)($producto['stock_minimo'] ?? 0);
}
unset($producto);

$total_productos = count($productos ?? []);
$stock_bajo = count(array_filter($productos, function($p) { return $p['stock_actual'] <= $p['stock_minimo']; }));
$total_categorias = count(is_array($categorias ?? null) ? $categorias : []);
$recepcionesProducto = is_array($productosMasRecibidos ?? null) ? $productosMasRecibidos : [];
$nombresProductosRecibidos = array_unique(array_filter(array_map(function($recepcion) {
    return mb_strtolower(trim((string)($recepcion['label'] ?? '')));
}, $recepcionesProducto)));
$productos_recepcion = count($nombresProductosRecibidos);

// Calcular porcentajes
$porcentaje_total = 100; // Siempre 100% para el total
$porcentaje_stock_bajo = $total_productos > 0 ? round(($stock_bajo / $total_productos) * 100) : 0;
$porcentaje_categorias = 100; // No tiene sentido calcular porcentaje aquí
$porcentaje_recepcion = $total_productos > 0 ? round(($productos_recepcion / $total_productos) * 100) : 0;
?>

            <!-- Summary Cards para Productos -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-box"></i></div>
                    <div class="card-content">
                        <h3>Total Productos</h3>
                        <p class="card-value"><?php echo $total_productos; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_total; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_total; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-chart-down"></i></div>
                    <div class="card-content">
                        <h3>Stock Bajo</h3>
                        <p class="card-value"><?php echo $stock_bajo; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_stock_bajo; ?>, 100" stroke="#ff6b6b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_stock_bajo; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-tag"></i></div>
                    <div class="card-content">
                        <h3>Categorías</h3>
                        <p class="card-value"><?php echo $total_categorias; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_categorias; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_categorias; ?>%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card recepcion">
                    <div class="card-icon"><i class="fas fa-inbox"></i></div>
                    <div class="card-content">
                        <h3>Con recepción</h3>
                        <p class="card-value"><?php echo $productos_recepcion; ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="<?php echo $porcentaje_recepcion; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage"><?php echo $porcentaje_recepcion; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <section class="analytics-section" aria-labelledby="analyticsTitle">
                <div class="analytics-header">
                    <div>
                        <span class="analytics-eyebrow"><i class="fas fa-brain" aria-hidden="true"></i> Analítica local</span>
                        <h2 id="analyticsTitle">Asistente inteligente de inventario</h2>
                        <p>Explora demanda real de facturas pagadas, patrones de compra y señales de reposición.</p>
                    </div>
                    <span class="analytics-source">Fuente: facturas pagadas</span>
                </div>

                <div class="analytics-filters">
                    <div class="analytics-filter">
                        <label for="analyticsProduct">Producto</label>
                        <select id="analyticsProduct">
                            <option value="">Seleccionar producto</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?php echo (int)$producto['id_producto']; ?>">
                                    <?php echo htmlspecialchars($producto['nombre_producto'] ?? 'Producto', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <span class="analytics-or">o</span>
                    <div class="analytics-filter">
                        <label for="analyticsCategory">Categoría</label>
                        <select id="analyticsCategory">
                            <option value="">Seleccionar categoría</option>
                            <?php foreach (($categorias ?? []) as $categoria): ?>
                                <option value="<?php echo (int)$categoria['id_categoria']; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre_categoria'] ?? 'Categoría', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="analytics-filter">
                        <label for="analyticsFrequency">Frecuencia</label>
                        <select id="analyticsFrequency">
                            <option value="diaria">Diaria</option>
                            <option value="semanal">Semanal</option>
                            <option value="mensual">Mensual</option>
                        </select>
                    </div>
                    <div class="analytics-filter">
                        <label for="analyticsHorizon">Horizonte</label>
                        <select id="analyticsHorizon">
                            <option value="15">15 días</option>
                            <option value="30" selected>30 días</option>
                            <option value="60">60 días</option>
                            <option value="90">90 días</option>
                        </select>
                    </div>
                    <button type="button" class="analytics-button" id="runAnalytics">
                        <i class="fas fa-chart-line" aria-hidden="true"></i> Analizar
                    </button>
                    <?php if ($puedeModificarProductos): ?>
                        <button type="button" class="analytics-button analytics-train" id="trainAnalytics">
                            <i class="fas fa-rotate" aria-hidden="true"></i> Entrenar modelo local
                        </button>
                    <?php endif; ?>
                </div>

                <p class="analytics-status" id="analyticsStatus" role="status" aria-live="polite">
                    El análisis general del inventario se ejecuta automáticamente al entrar. Puedes filtrar por producto o categoría y pulsar «Analizar».
                </p>

                <div class="analytics-kpis" id="analyticsKpis" hidden>
                    <article class="analytics-kpi"><span>Venta proyectada</span><strong data-metric="venta_proyectada">—</strong><small>unidades en el horizonte</small></article>
                    <article class="analytics-kpi"><span>Días hasta agotamiento</span><strong data-metric="dias_estimados_agotamiento">—</strong><small>estimación con stock actual</small></article>
                    <article class="analytics-kpi"><span>Punto de reorden</span><strong data-metric="punto_reorden">—</strong><small>stock mínimo configurado</small></article>
                    <article class="analytics-kpi"><span>Compra sugerida</span><strong data-metric="cantidad_sugerida_compra">—</strong><small>no descuenta órdenes abiertas</small></article>
                    <article class="analytics-kpi"><span>Rotación proyectada</span><strong data-metric="rotacion_proyectada">—</strong><small>veces en el horizonte</small></article>
                    <article class="analytics-kpi"><span>Explicaciones</span><strong data-metric="explicaciones">—</strong><small>del modelo</small></article>
                </div>

                <div class="analytics-chart-card">
                    <div class="analytics-chart-heading">
                        <h3>Ventas históricas y demanda estimada</h3>
                        <span>La banda representa el intervalo estimado del modelo.</span>
                    </div>
                    <div class="analytics-chart-frame">
                        <canvas id="analyticsChart" aria-label="Gráfico de ventas y demanda estimada" role="img"></canvas>
                    </div>
                
                </div>

                <div class="analytics-actions">
                    <div class="analytics-chart-heading">
                        <h3>Acciones recomendadas</h3>
                        <span>Las valoraciones ajustan la prioridad futura por tipo de recomendación.</span>
                    </div>
                    <div class="analytics-table-wrap">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>Producto / acción</th>
                                    <th>Señal</th>
                                    <th>Cantidad sugerida</th>
                                    <th>Confianza</th>
                                    <th>Calculado</th>
                                    <th>Valoración</th>
                                </tr>
                            </thead>
                            <tbody id="analyticsRecommendations">
                                <tr><td colspan="6">Aún no hay recomendaciones cargadas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Productos Grid Section -->
            <div class="products-section">
                <div class="section-header">
                    <h2>Inventario de Productos</h2>
                    <div class="section-actions">
                        <?php if ($puedeIncluirProductos): ?>
                        <button type="button" class="btn-add-product" onclick="openModal('registrar')" <?php echo empty($categoriasDinamicas) ? 'disabled' : ''; ?>>
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Agregar Producto
                        </button>
                        <?php endif; ?>
                        <button class="btn-filter" onclick="openFilterModal()">
                            <span class="btn-icon"><i class="fas fa-search"></i></span>
                            Filtrar
                        </button>
                    </div>
                </div>

                <div class="products-grid">
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $producto): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if (!empty($producto['imagen'])): ?>
                                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" class="product-img">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <i class="fas fa-box"></i>
                                        </div>
                                    <?php endif; ?>
                                    <?php 
                                    $stock_actual = $producto['stock_actual'] ?? 0;
                                    $stock_minimo = $producto['stock_minimo'] ?? 0;
                                    if ($stock_actual <= $stock_minimo): ?>
                                        <span class="product-badge low-stock">Stock Bajo</span>
                                    <?php elseif ($stock_actual < 10): ?>
                                        <span class="product-badge critical">Crítico</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($producto['nombre_producto']); ?></h3>
                                    <p class="product-category"><?php echo htmlspecialchars($producto['nombre_categoria'] ?? 'Sin categoría'); ?></p>
                                    <p class="product-brand"><?php echo htmlspecialchars($producto['nombre_marca'] ?? 'Sin marca'); ?></p>
                                    <div class="product-price">$<?php echo number_format($producto['precio'] ?? 0, 2); ?></div>
                                    <div class="product-stock">
                                        <span class="stock-label">Stock:</span>
                                        <span class="stock-value <?php 
                                            if ($stock_actual <= $stock_minimo) echo 'low';
                                            elseif ($stock_actual < 10) echo 'critical';
                                            elseif ($stock_actual > 30) echo 'high';
                                            else echo 'medium';
                                        ?>">
                                            <?php echo $stock_actual; ?> unidades
                                        </span>
                                    </div>
                                    <div class="product-actions">
                                        <?php if ($puedeModificarProductos): ?>
                                        <button type="button" class="btn-action btn-edit" onclick="openModal('editar', <?php echo (int)$producto['id_producto']; ?>)" title="Editar producto" aria-label="Editar producto"><i class="fas fa-edit"></i></button>
                                        <?php endif; ?>
                                        <?php if ($puedeEliminarProductos): ?>
                                        <button type="button" class="btn-action btn-delete" onclick="deleteProduct(<?php echo (int)$producto['id_producto']; ?>)" title="Eliminar producto" aria-label="Eliminar producto"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                        <button type="button" class="btn-action btn-view" onclick="viewProduct(<?php echo (int)$producto['id_producto']; ?>)" title="Ver producto" aria-label="Ver producto"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-products">
                            <i class="fas fa-box-open"></i>
                            <p>No hay productos registrados en el sistema</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Categorías Section -->
            <div class="categories-section">
                <h2>Categorías de Productos</h2>
                <div class="categories-grid">
                    <?php if (!empty($categoriasDinamicas)): ?>
                        <?php foreach ($categoriasDinamicas as $categoria): ?>
                            <div class="category-card">
                                <div class="category-icon">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <h3><?php echo htmlspecialchars($categoria['nombre_categoria'] ?? 'Sin nombre'); ?></h3>
                                <p><?php echo $categoria['cantidad'] ?? 0; ?> productos</p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-categories">
                            <i class="fas fa-folder-open"></i>
                            <p>No hay categorías registradas</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Producto -->
            <div id="productModal" class="modal">
                <div class="modal-content modal-large">
                    <div class="modal-header">
                        <h2 id="modalTitle">Agregar Producto</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="productForm" enctype="multipart/form-data">
                            <input type="hidden" id="productId" name="id_producto">
                            <input type="hidden" name="accion" id="productAction" value="ingresar">
                            <input type="hidden" id="productCategoryTable" name="tabla_categoria">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="productName">Nombre del Producto*</label>
                                    <input type="text" id="productName" name="nombre_producto" required 
                                           placeholder="Ej: iPhone 15 Pro Max" maxlength="50">
                                </div>
                                
                                <div class="form-group">
                                    <label for="productModel">Modelo/Marca*</label>
                                    <select id="productModel" name="modelo" required>
                                        <option value="">Seleccione un modelo</option>
                                        <?php if (!empty($modelos)): ?>
                                            <?php foreach ($modelos as $modelo): ?>
                                                <option value="<?php echo $modelo['id_modelo']; ?>">
                                                    <?php echo htmlspecialchars($modelo['nombre_modelo'] . ' - ' . $modelo['nombre_marca']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="productCategory">Categoría*</label>
                                    <select id="productCategory" name="Categoria" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php if (!empty($categoriasDinamicas)): ?>
                                        <?php foreach ($categoriasDinamicas as $categoria): ?>
                                            <option value="<?php echo htmlspecialchars($categoria['tabla'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="productPrice">Precio*</label>
                                <input type="number" id="productPrice" name="Precio" required
                                       placeholder="0.00" step="0.01" min="0">
                            </div>
                            
                            <div class="form-row stock-fields">
                                <div class="form-group">
                                    <label for="stockActual">Stock Actual*</label>
                                    <input type="number" id="stockActual" name="Stock_Actual" required 
                                           placeholder="0" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label for="stockMinimo">Stock Mínimo*</label>
                                    <input type="number" id="stockMinimo" name="Stock_Minimo" required 
                                           placeholder="0" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label for="stockMaximo">Stock Máximo*</label>
                                    <input type="number" id="stockMaximo" name="Stock_Maximo" required 
                                           placeholder="0" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="productDescription">Descripción</label>
                                <textarea id="productDescription" name="descripcion_producto" rows="3"
                                          placeholder="Descripción del producto"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="productImage">Imagen del Producto</label>
                                <input type="file" id="productImage" name="imagen" accept="image/*">
                                <div class="product-image-preview" id="productImagePreviewFrame">
                                    <img id="productImagePreview" alt="Vista previa de la imagen seleccionada" hidden>
                                    <span id="productImagePreviewPlaceholder">Seleccione una imagen para previsualizarla</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="productWarranty">Garantía</label>
                                    <input type="text" id="productWarranty" name="Clausula_garantia" required
                                       placeholder="Ej: 1 año de garantía">
                            </div>
                            
                            <div class="form-group">
                                <label for="productSerial">Serial/Código</label>
                                <input type="text" id="productSerial" name="Seriales" required
                                       placeholder="Código único del producto">
                            </div>
                            <div id="productCategoryFields" class="form-group"></div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn-save" form="productForm">Guardar</button>
                    </div>
                </div>
            </div>

            <div id="productDetailsModal" class="modal" aria-hidden="true">
                <div class="modal-content modal-large">
                    <div class="modal-header">
                        <h2 id="detailProductTitle">Detalles del Producto</h2>
                        <button type="button" class="close-modal" aria-label="Cerrar detalles">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="product-details-layout">
                            <div class="product-details-image-frame">
                                <img id="detailProductImage" alt="Imagen completa del producto" hidden>
                                <div id="detailProductImagePlaceholder" class="image-placeholder">
                                    <i class="fas fa-box-open" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="product-details-grid">
                                <div class="product-detail"><span>ID</span><strong id="detailProductId">-</strong></div>
                                <div class="product-detail"><span>Nombre</span><strong id="detailProductName">-</strong></div>
                                <div class="product-detail"><span>Categoría</span><strong id="detailProductCategory">-</strong></div>
                                <div class="product-detail"><span>Modelo</span><strong id="detailProductModel">-</strong></div>
                                <div class="product-detail"><span>Marca</span><strong id="detailProductBrand">-</strong></div>
                                <div class="product-detail"><span>Precio</span><strong id="detailProductPrice">-</strong></div>
                                <div class="product-detail"><span>Stock actual</span><strong id="detailProductStock">-</strong></div>
                                <div class="product-detail"><span>Stock mínimo</span><strong id="detailProductMinStock">-</strong></div>
                                <div class="product-detail"><span>Stock máximo</span><strong id="detailProductMaxStock">-</strong></div>
                                <div class="product-detail"><span>Serial</span><strong id="detailProductSerial">-</strong></div>
                                <div class="product-detail"><span>Estado</span><strong id="detailProductStatus">-</strong></div>
                                <div class="product-detail product-detail-wide"><span>Descripción</span><strong id="detailProductDescription">-</strong></div>
                                <div class="product-detail product-detail-wide"><span>Garantía</span><strong id="detailProductWarranty">-</strong></div>
                                <div id="detailProductCharacteristics" class="product-details-grid product-detail-wide"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" id="closeProductDetails">Cerrar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Productos */
                .analytics-section {
                    margin: 28px 0 36px;
                    padding: 24px;
                    border: 1px solid #dbeafe;
                    border-radius: 18px;
                    background: #fff;
                    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
                }

                .analytics-header, .analytics-chart-heading {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    gap: 16px;
                }

                .analytics-header h2, .analytics-chart-heading h3 {
                    margin: 6px 0;
                    color: #172554;
                }

                .analytics-header p, .analytics-chart-heading span {
                    margin: 0;
                    color: #64748b;
                    font-size: 0.9rem;
                }

                .analytics-eyebrow {
                    color: #2563eb;
                    font-size: 0.78rem;
                    font-weight: 700;
                    letter-spacing: 0.06em;
                    text-transform: uppercase;
                }

                .analytics-source {
                    padding: 7px 11px;
                    border-radius: 20px;
                    background: #eff6ff;
                    color: #1d4ed8;
                    font-size: 0.8rem;
                    font-weight: 600;
                    white-space: nowrap;
                }

                .analytics-filters {
                    display: flex;
                    flex-wrap: wrap;
                    align-items: flex-end;
                    gap: 12px;
                    margin-top: 20px;
                }

                .analytics-filter {
                    display: grid;
                    flex: 1 1 145px;
                    gap: 6px;
                    min-width: 135px;
                }

                .analytics-filter label {
                    color: #334155;
                    font-size: 0.82rem;
                    font-weight: 600;
                }

                .analytics-filter select {
                    min-height: 42px;
                    padding: 8px 10px;
                    border: 1px solid #cbd5e1;
                    border-radius: 8px;
                    background: #fff;
                    color: #1e293b;
                    font: inherit;
                }

                .analytics-or {
                    padding-bottom: 12px;
                    color: #64748b;
                    font-size: 0.82rem;
                }

                .analytics-button {
                    min-height: 42px;
                    padding: 9px 14px;
                    border: 0;
                    border-radius: 8px;
                    background: #2563eb;
                    color: #fff;
                    font: inherit;
                    font-weight: 600;
                    cursor: pointer;
                }

                .analytics-button:hover:not(:disabled) {
                    background: #1d4ed8;
                }

                .analytics-button:disabled {
                    cursor: wait;
                    opacity: 0.65;
                }

                .analytics-train {
                    background: #0f766e;
                }

                .analytics-train:hover:not(:disabled) {
                    background: #115e59;
                }

                .analytics-status {
                    min-height: 22px;
                    margin: 16px 0;
                    color: #475569;
                    font-size: 0.9rem;
                }

                .analytics-status.error {
                    color: #b91c1c;
                }

                .analytics-status.success {
                    color: #047857;
                }

                .analytics-kpis {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 12px;
                    margin: 18px 0;
                }

                .analytics-kpi {
                    display: grid;
                    gap: 7px;
                    padding: 15px;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    background: #f8fafc;
                }

                .analytics-kpi span {
                    color: #475569;
                    font-size: 0.8rem;
                    font-weight: 600;
                }

                .analytics-kpi strong {
                    color: #1d4ed8;
                    font-size: 1.35rem;
                }

                .analytics-kpi small {
                    color: #64748b;
                    line-height: 1.35;
                }

                .analytics-chart-card, .analytics-actions {
                    margin-top: 20px;
                    padding: 18px;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    background: #fff;
                }

                .analytics-chart-frame {
                    position: relative;
                    height: 320px;
                    margin-top: 16px;
                }

                .analytics-table-wrap {
                    overflow-x: auto;
                    margin-top: 12px;
                }

                .analytics-table {
                    width: 100%;
                    border-collapse: collapse;
                    text-align: left;
                }

                .analytics-table th, .analytics-table td {
                    padding: 11px 10px;
                    border-bottom: 1px solid #e2e8f0;
                    color: #334155;
                    font-size: 0.84rem;
                    vertical-align: middle;
                }

                .analytics-table th {
                    background: #f8fafc;
                    color: #475569;
                    font-weight: 700;
                    white-space: nowrap;
                }

                .analytics-rating {
                    display: flex;
                    gap: 2px;
                    white-space: nowrap;
                }

                .analytics-rating button {
                    padding: 3px;
                    border: 0;
                    background: transparent;
                    color: #94a3b8;
                    cursor: pointer;
                    font-size: 1rem;
                }

                .analytics-rating button:hover, .analytics-rating button.selected {
                    color: #f59e0b;
                }

                .analytics-rating button:disabled {
                    cursor: default;
                }

                .products-section {
                    margin-top: 30px;
                }

                .products-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 25px;
                    margin-top: 20px;
                }

                .product-card {
                    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    transition: all 0.3s ease;
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                    min-height: 380px;
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .product-card:hover {
                    transform: translateY(-8px);
                    box-shadow: 0 12px 30px rgba(33, 150, 243, 0.2);
                    border-color: rgba(33, 150, 243, 0.3);
                }

                .product-image {
                    position: relative;
                    width: 100%;
                    height: 200px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                }

                .product-image img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    object-position: center;
                }

                .image-placeholder {
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                }

                .image-placeholder i {
                    font-size: 4rem;
                    color: rgba(255, 255, 255, 0.8);
                }

                .product-badge {
                    position: absolute;
                    top: 12px;
                    right: 12px;
                    padding: 6px 14px;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    color: white;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                }

                .product-badge.bestseller {
                    background: linear-gradient(135deg, #00B4DB 0%, #0083B0 100%);
                }

                .product-badge.new {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }

                .product-badge.sale {
                    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
                }

                .product-badge.critical {
                    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                }

                .product-badge.low-stock {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                }

                .product-info {
                    padding: 20px;
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                }

                .product-info h3 {
                    font-size: 1.1rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0 0 8px 0;
                    line-height: 1.4;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    min-height: 31px;
                }

                .product-category {
                    font-size: 0.85rem;
                    color: #2196F3;
                    font-weight: 600;
                    margin: 0 0 4px 0;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .product-brand {
                    font-size: 0.8rem;
                    color: #718096;
                    margin: 0 0 12px 0;
                }

                .product-price {
                    font-size: 1.4rem;
                    font-weight: 800;
                    color: #2d3748;
                    margin: 0 0 12px 0;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }

                .product-stock {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 15px;
                    padding: 8px 12px;
                    background: rgba(33, 150, 243, 0.05);
                    border-radius: 8px;
                }

                .stock-label {
                    font-size: 0.8rem;
                    color: #718096;
                    font-weight: 600;
                }

                .stock-value {
                    font-size: 0.85rem;
                    font-weight: 700;
                    color: #2d3748;
                }

                .stock-value.high {
                    color: #48bb78;
                }

                .stock-value.medium {
                    color: #ed8936;
                }

                .stock-value.low {
                    color: #ecc94b;
                }

                .stock-value.critical {
                    color: #f56565;
                }

                .product-actions {
                    display: flex;
                    gap: 8px;
                    margin-top: auto;
                }

                .btn-action {
                    flex: 1;
                    padding: 10px 8px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 0.9rem;
                }

                .btn-action:hover {
                    transform: scale(1.05);
                }

                .btn-action.btn-edit {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-action.btn-delete {
                    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                    color: white;
                }

                .btn-action.btn-view {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-action i {
                    font-size: 1rem;
                }

                /* Categorías Section */
                .categories-section {
                    margin-top: 40px;
                }

                .categories-section h2 {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin-bottom: 20px;
                }

                .categories-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 20px;
                }

                .category-card {
                    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                    border-radius: 12px;
                    padding: 25px;
                    text-align: center;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    transition: all 0.3s ease;
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .category-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 25px rgba(33, 150, 243, 0.2);
                }

                .category-icon {
                    width: 60px;
                    height: 60px;
                    margin: 0 auto 15px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .category-icon i {
                    font-size: 1.5rem;
                    color: white;
                }

                .category-card h3 {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0 0 8px 0;
                }

                .category-card p {
                    font-size: 0.85rem;
                    color: #718096;
                    margin: 0;
                }

                /* Mensajes de vacío */
                .no-products, .no-categories {
                    text-align: center;
                    padding: 60px 40px;
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 16px;
                    grid-column: 1 / -1;
                }

                .no-products i, .no-categories i {
                    font-size: 4rem;
                    color: #2196F3;
                    margin-bottom: 20px;
                }

                .no-products p, .no-categories p {
                    font-size: 1.2rem;
                    color: #666;
                    margin: 0;
                    font-weight: 500;
                }

                /* Botones de sección */
                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }

                .section-header h2 {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0;
                }

                .section-actions {
                    display: flex;
                    gap: 12px;
                }

                .btn-add-product, .btn-filter {
                    padding: 12px 20px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-add-product {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-filter {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-add-product:hover, .btn-filter:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .btn-icon {
                    font-size: 1rem;
                }

                #productModal.modal, #productDetailsModal.modal {
                    overflow-y: auto;
                    padding: 16px 0;
                }

                #productModal .modal-content, #productDetailsModal .modal-content {
                    display: flex;
                    flex-direction: column;
                    width: min(860px, calc(100vw - 32px));
                    max-width: 860px;
                    max-height: calc(100vh - 32px);
                    margin: 0 auto;
                }

                #productModal .modal-body, #productDetailsModal .modal-body {
                    min-height: 0;
                    overflow-y: auto;
                    flex: 1 1 auto;
                }

                #productModal .modal-header, #productModal .modal-footer,
                #productDetailsModal .modal-header, #productDetailsModal .modal-footer {
                    flex: 0 0 auto;
                }

                #productModal .form-row {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 16px;
                }

                #productModal .stock-fields {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }

                #productModal .form-group {
                    min-width: 0;
                    margin-bottom: 18px;
                }

                #productModal .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    color: #333;
                    font-weight: 600;
                }

                #productModal .form-group input,
                #productModal .form-group select,
                #productModal .form-group textarea {
                    display: block;
                    width: 100%;
                    min-height: 44px;
                    padding: 11px 12px;
                    border: 1px solid #d5dbe3;
                    border-radius: 8px;
                    background: #fff;
                    color: #1f2937;
                    font: inherit;
                    transition: border-color 0.2s ease, box-shadow 0.2s ease;
                }

                #productModal .form-group textarea {
                    min-height: 90px;
                    resize: vertical;
                }

                #productModal .form-group input:focus,
                #productModal .form-group select:focus,
                #productModal .form-group textarea:focus {
                    outline: none;
                    border-color: #2196f3;
                    box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.16);
                }

                #productModal input[type="file"] {
                    padding: 8px;
                    background: #f8fafc;
                }

                #productModal .modal-header, #productDetailsModal .modal-header {
                    background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
                    color: #fff;
                }

                #productModal .modal-footer, #productDetailsModal .modal-footer {
                    border-top: 1px solid #e5e7eb;
                }

                #productModal .close-modal, #productDetailsModal .close-modal {
                    padding: 0;
                    border: 0;
                    background: transparent;
                    color: #fff;
                }

                #productModal .btn-save, #productModal .btn-cancel,
                #productDetailsModal .btn-cancel {
                    min-height: 42px;
                    padding: 10px 24px;
                    border: 0;
                    border-radius: 8px;
                    color: #fff;
                    font: inherit;
                    font-weight: 600;
                    cursor: pointer;
                }

                #productModal .btn-save {
                    background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
                }

                #productModal .btn-cancel, #productDetailsModal .btn-cancel {
                    background: #6c757d;
                }

                .product-details-layout {
                    display: grid;
                    grid-template-columns: minmax(220px, 0.85fr) minmax(0, 1.5fr);
                    gap: 24px;
                    align-items: start;
                }

                .product-details-image-frame {
                    display: grid;
                    height: min(60vh, 620px);
                    min-height: 320px;
                    place-items: center;
                    overflow: auto;
                    border: 1px solid #e5e7eb;
                    border-radius: 10px;
                    background: #f8fafc;
                    padding: 12px;
                }

                #detailProductImage {
                    display: block;
                    width: auto;
                    height: auto;
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                }

                .product-image-preview {
                    display: grid;
                    width: min(100%, 420px);
                    height: 190px;
                    margin-top: 12px;
                    padding: 10px;
                    place-items: center;
                    overflow: hidden;
                    border: 1px dashed #b8c4d1;
                    border-radius: 8px;
                    background: #f8fafc;
                    color: #64748b;
                    text-align: center;
                }

                #productImagePreview {
                    display: block;
                    width: auto;
                    height: auto;
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                }

                #productImagePreview[hidden], #detailProductImage[hidden] {
                    display: none;
                }

                .product-details-grid {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    gap: 12px;
                }

                .product-detail {
                    display: flex;
                    min-width: 0;
                    flex-direction: column;
                    gap: 5px;
                    padding: 12px;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    background: #f8fafc;
                }

                .product-detail span {
                    color: #64748b;
                    font-size: 0.82rem;
                    font-weight: 600;
                }

                .product-detail strong {
                    overflow-wrap: anywhere;
                    color: #1f2937;
                    font-weight: 600;
                    white-space: pre-wrap;
                }

                .product-detail-wide {
                    grid-column: 1 / -1;
                }

                #detailProductCharacteristics:empty {
                    display: none;
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .analytics-section {
                        padding: 16px;
                    }

                    .analytics-header, .analytics-chart-heading {
                        flex-direction: column;
                    }

                    .analytics-or {
                        display: none;
                    }

                    .analytics-chart-frame {
                        height: 250px;
                    }

                    .products-grid {
                        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                        gap: 20px;
                    }

                    .section-header {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 15px;
                    }

                    .categories-grid {
                        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                    }

                    #productModal .form-row, #productModal .stock-fields,
                    .product-details-layout {
                        grid-template-columns: 1fr;
                    }

                    .product-details-image-frame {
                        height: min(45vh, 420px);
                        min-height: 240px;
                    }
                }

                @media (max-width: 480px) {
                    .products-grid {
                        grid-template-columns: 1fr;
                    }

                    .product-card {
                        min-height: 350px;
                    }
                }
            </style>

            <script src="assets/public/js/chart.js"></script>
            <script>
                const categoriasDinamicasProducto = <?php echo json_encode($categoriasDinamicas ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
                const productosDisponibles = <?php echo json_encode($productos ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

                const productForm = document.getElementById('productForm');
                const productModal = document.getElementById('productModal');
                const productCategory = document.getElementById('productCategory');
                const productCategoryTable = document.getElementById('productCategoryTable');
                const productCategoryFields = document.getElementById('productCategoryFields');
                const productDetailsModal = document.getElementById('productDetailsModal');
                const productImageInput = document.getElementById('productImage');
                const productImagePreview = document.getElementById('productImagePreview');
                const productImagePreviewPlaceholder = document.getElementById('productImagePreviewPlaceholder');
                let temporaryProductImageUrl = null;

                function showProductImagePreview(source) {
                    if (!source) {
                        productImagePreview.removeAttribute('src');
                        productImagePreview.hidden = true;
                        productImagePreviewPlaceholder.hidden = false;
                        return;
                    }

                    productImagePreview.src = source;
                    productImagePreview.hidden = false;
                    productImagePreviewPlaceholder.hidden = true;
                }

                function clearTemporaryProductImageUrl() {
                    if (temporaryProductImageUrl) {
                        URL.revokeObjectURL(temporaryProductImageUrl);
                        temporaryProductImageUrl = null;
                    }
                }

                productImageInput.addEventListener('change', function () {
                    clearTemporaryProductImageUrl();
                    const selectedImage = this.files && this.files[0];
                    if (!selectedImage) {
                        return;
                    }
                    temporaryProductImageUrl = URL.createObjectURL(selectedImage);
                    showProductImagePreview(temporaryProductImageUrl);
                });

                function renderProductCategoryFields(tabla, valores = {}) {
                    const categoria = categoriasDinamicasProducto.find(item => item.tabla === tabla);
                    productCategoryFields.replaceChildren();
                    productCategoryTable.value = tabla || '';

                    if (!categoria) {
                        return;
                    }

                    categoria.caracteristicas.forEach(caracteristica => {
                        const group = document.createElement('div');
                        group.className = 'form-group';

                        const label = document.createElement('label');
                        label.htmlFor = `productCaracteristica_${caracteristica.nombre}`;
                        label.textContent = caracteristica.nombre.replace(/_/g, ' ');

                        const input = document.createElement('input');
                        input.id = label.htmlFor;
                        input.name = `carac[${caracteristica.nombre}]`;
                        input.type = ['int', 'float'].includes(caracteristica.tipo) ? 'number' : 'text';
                        input.required = true;
                        if (input.type === 'number') {
                            input.min = '0';
                            if (caracteristica.tipo === 'float') input.step = 'any';
                        } else {
                            input.maxLength = Number(caracteristica.max) || 255;
                        }
                        if (Object.prototype.hasOwnProperty.call(valores, caracteristica.nombre)) {
                            input.value = valores[caracteristica.nombre] ?? '';
                        }

                        group.append(label, input);
                        productCategoryFields.appendChild(group);
                    });
                }

                productCategory.addEventListener('change', function () {
                    renderProductCategoryFields(this.value);
                });

                function openModal(type, productId = null) {
                    clearTemporaryProductImageUrl();
                    productForm.reset();
                    document.getElementById('productId').value = '';
                    document.getElementById('productAction').value = 'ingresar';
                    productCategoryFields.replaceChildren();
                    productCategoryTable.value = '';
                    showProductImagePreview('');

                    if (type === 'registrar') {
                        document.getElementById('modalTitle').textContent = 'Agregar Producto';
                    } else if (type === 'editar') {
                        const producto = productosDisponibles.find(item => Number(item.id_producto) === Number(productId));
                        if (!producto) {
                            Swal.fire({ icon: 'error', title: 'Producto no encontrado', text: 'Actualiza la página e inténtalo de nuevo.' });
                            return;
                        }

                        document.getElementById('modalTitle').textContent = 'Editar Producto';
                        document.getElementById('productId').value = producto.id_producto;
                        document.getElementById('productAction').value = 'modificar';
                        document.getElementById('productName').value = producto.nombre_producto || '';
                        document.getElementById('productDescription').value = producto.descripcion_producto || '';
                        document.getElementById('productModel').value = producto.id_modelo || '';
                        document.getElementById('productPrice').value = producto.precio || '';
                        document.getElementById('stockActual').value = producto.stock_actual ?? producto.stock ?? 0;
                        document.getElementById('stockMinimo').value = producto.stock_minimo ?? 0;
                        document.getElementById('stockMaximo').value = producto.stock_maximo ?? 0;
                        document.getElementById('productWarranty').value = producto.clausula_garantia || '';
                        document.getElementById('productSerial').value = producto.serial || '';
                        showProductImagePreview(producto.imagen || '');

                        const normalizarCategoria = value => String(value || '').toLowerCase().replace(/[\s_-]+/g, '');
                        const categoria = categoriasDinamicasProducto.find(item => normalizarCategoria(item.nombre_categoria) === normalizarCategoria(producto.nombre_categoria));
                        productCategory.value = categoria ? categoria.tabla : '';
                        renderProductCategoryFields(productCategory.value, producto.caracteristicas || {});
                    }
                    productModal.style.display = 'block';
                }

                function closeModal() {
                    productModal.style.display = 'none';
                    clearTemporaryProductImageUrl();
                    productForm.reset();
                    productCategoryFields.replaceChildren();
                    productCategoryTable.value = '';
                    showProductImagePreview('');
                }

                async function enviarAccionProducto(datos) {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: datos,
                        credentials: 'same-origin'
                    });
                    const resultado = await response.json();
                    if (!response.ok || resultado.status === 'error') {
                        throw new Error(resultado.message || resultado.mensaje || 'No se pudo completar la operación.');
                    }
                    return resultado;
                }

                productForm.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!productForm.reportValidity()) return;

                    productCategoryTable.value = productCategory.value;
                    const datos = new FormData(productForm);
                    const submitButton = document.querySelector('#productModal .btn-save');
                    const title = document.getElementById('productAction').value === 'modificar' ? 'Producto modificado' : 'Producto registrado';
                    if (submitButton) submitButton.disabled = true;
                    closeModal();
                    try {
                        const resultado = await enviarAccionProducto(datos);
                        await Swal.fire({
                            icon: 'success',
                            title,
                            text: resultado.mensaje || resultado.message || 'Los cambios se guardaron correctamente.'
                        });
                        window.location.reload();
                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: error.message });
                    } finally {
                        if (submitButton) submitButton.disabled = false;
                    }
                });

                async function deleteProduct(productId) {
                    const confirmacion = await Swal.fire({
                        icon: 'warning',
                        title: '¿Eliminar producto?',
                        text: 'La operación será validada por el sistema antes de aplicarse.',
                        showCancelButton: true,
                        confirmButtonText: 'Eliminar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!confirmacion.isConfirmed) return;

                    const datos = new FormData();
                    datos.append('accion', 'eliminar');
                    datos.append('id_producto', String(productId));
                    try {
                        const resultado = await enviarAccionProducto(datos);
                        await Swal.fire({ icon: 'success', title: 'Producto eliminado', text: resultado.message || 'Se eliminó correctamente.' });
                        window.location.reload();
                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'No se pudo eliminar', text: error.message });
                    }
                }

                function viewProduct(productId) {
                    const producto = productosDisponibles.find(item => Number(item.id_producto) === Number(productId));
                    if (!producto) return;

                    const setDetail = (id, value) => {
                        document.getElementById(id).textContent = value === null || value === undefined || value === '' ? '-' : String(value);
                    };

                    document.getElementById('detailProductTitle').textContent = producto.nombre_producto || 'Detalles del Producto';
                    setDetail('detailProductId', producto.id_producto);
                    setDetail('detailProductName', producto.nombre_producto);
                    setDetail('detailProductCategory', producto.nombre_categoria);
                    setDetail('detailProductModel', producto.nombre_modelo);
                    setDetail('detailProductBrand', producto.nombre_marca);
                    setDetail('detailProductPrice', `$${Number(producto.precio || 0).toFixed(2)}`);
                    setDetail('detailProductStock', producto.stock_actual ?? producto.stock);
                    setDetail('detailProductMinStock', producto.stock_minimo);
                    setDetail('detailProductMaxStock', producto.stock_maximo);
                    setDetail('detailProductSerial', producto.serial);
                    setDetail('detailProductStatus', producto.estado);
                    setDetail('detailProductDescription', producto.descripcion_producto);
                    setDetail('detailProductWarranty', producto.clausula_garantia);

                    const image = document.getElementById('detailProductImage');
                    const placeholder = document.getElementById('detailProductImagePlaceholder');
                    if (producto.imagen) {
                        image.src = producto.imagen;
                        image.hidden = false;
                        placeholder.hidden = true;
                    } else {
                        image.removeAttribute('src');
                        image.hidden = true;
                        placeholder.hidden = false;
                    }

                    const characteristics = document.getElementById('detailProductCharacteristics');
                    characteristics.replaceChildren();
                    Object.entries(producto.caracteristicas || {}).forEach(([name, value]) => {
                        if (['id', 'id_producto'].includes(name)) return;
                        const item = document.createElement('div');
                        item.className = 'product-detail';
                        const label = document.createElement('span');
                        label.textContent = name.replace(/_/g, ' ');
                        const detailValue = document.createElement('strong');
                        detailValue.textContent = value === null || value === '' ? '-' : String(value);
                        item.append(label, detailValue);
                        characteristics.appendChild(item);
                    });

                    productDetailsModal.style.display = 'block';
                    productDetailsModal.setAttribute('aria-hidden', 'false');
                }

                function closeProductDetails() {
                    productDetailsModal.style.display = 'none';
                    productDetailsModal.setAttribute('aria-hidden', 'true');
                }

                function openFilterModal() {
                    Swal.fire({
                        title: 'Filtrar productos',
                        input: 'text',
                        inputPlaceholder: 'Nombre, categoría, modelo o marca',
                        showCancelButton: true,
                        confirmButtonText: 'Filtrar',
                        cancelButtonText: 'Limpiar'
                    }).then(result => {
                        const filtro = result.isConfirmed ? (result.value || '').trim().toLocaleLowerCase() : '';
                        document.querySelectorAll('.product-card').forEach(card => {
                            card.hidden = filtro !== '' && !card.textContent.toLocaleLowerCase().includes(filtro);
                        });
                    });
                }

                const analyticsProduct = document.getElementById('analyticsProduct');
                const analyticsCategory = document.getElementById('analyticsCategory');
                const analyticsStatus = document.getElementById('analyticsStatus');
                const analyticsKpis = document.getElementById('analyticsKpis');
                const analyticsRecommendations = document.getElementById('analyticsRecommendations');
                const analyticsCanvas = document.getElementById('analyticsChart');
                let analyticsChart = null;

                function setAnalyticsStatus(message, type = '') {
                    analyticsStatus.textContent = message;
                    analyticsStatus.className = `analytics-status ${type}`.trim();
                }

                async function postAnalytics(action, values = {}) {
                    const form = new FormData();
                    form.append('accion', action);
                    Object.entries(values).forEach(([key, value]) => form.append(key, String(value)));
                    const response = await fetch(window.location.href, { method: 'POST', body: form });
                    const result = await response.json();
                    if (!response.ok) {
                        throw new Error(result.detail || result.message || 'No se pudo completar la solicitud.');
                    }
                    return result;
                }

                function displayMetric(value, suffix = '') {
                    if (value === null || value === undefined || !Number.isFinite(Number(value))) {
                        return 'No disponible';
                    }
                    return `${Number(value).toLocaleString('es-VE', { maximumFractionDigits: 2 })}${suffix}`;
                }

                function renderAnalyticsChart(data) {
                    if (typeof Chart === 'undefined') {
                        setAnalyticsStatus('Chart.js no está disponible; los datos del análisis sí se recibieron.', 'error');
                        return;
                    }
                    const historical = Array.isArray(data.historico) ? data.historico : [];
                    const forecast = Array.isArray(data.prediccion) ? data.prediccion : [];
                    const labels = [...historical.map(item => item.fecha), ...forecast.map(item => item.fecha)];
                    const empty = Array(historical.length).fill(null);
                    const missingForecast = Array(forecast.length).fill(null);
                    const datasets = [
                        {
                            label: 'Ventas reales',
                            data: [...historical.map(item => item.valor), ...missingForecast],
                            borderColor: '#2563eb',
                            backgroundColor: '#2563eb',
                            borderWidth: 2,
                            pointRadius: 1,
                            tension: 0.2
                        },
                        {
                            label: 'Demanda estimada',
                            data: [...empty, ...forecast.map(item => item.valor)],
                            borderColor: '#f97316',
                            backgroundColor: '#f97316',
                            borderWidth: 2,
                            borderDash: [7, 5],
                            pointRadius: 1,
                            tension: 0.2
                        },
                        {
                            label: 'Límite inferior',
                            data: [...empty, ...forecast.map(item => item.limite_inferior)],
                            borderColor: 'rgba(249, 115, 22, 0)',
                            pointRadius: 0,
                            fill: false
                        },
                        {
                            label: 'Intervalo estimado',
                            data: [...empty, ...forecast.map(item => item.limite_superior)],
                            borderColor: 'rgba(249, 115, 22, 0)',
                            backgroundColor: 'rgba(249, 115, 22, 0.15)',
                            pointRadius: 0,
                            fill: 2
                        }
                    ];

                    if (analyticsChart) analyticsChart.destroy();
                    analyticsChart = new Chart(analyticsCanvas.getContext('2d'), {
                        type: 'line',
                        data: { labels, datasets },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { position: 'bottom' },
                                tooltip: {
                                    callbacks: {
                                        label: context => `${context.dataset.label}: ${context.parsed.y === null ? '—' : `${context.parsed.y.toLocaleString('es-VE', { maximumFractionDigits: 2 })} unidades`}`
                                    }
                                }
                            },
                            scales: {
                                y: { beginAtZero: true, title: { display: true, text: 'Unidades' } },
                                x: { title: { display: true, text: `Fecha · horizonte ${data.horizonte_dias} días` } }
                            }
                        }
                    });
                }

                function renderAnalyticsRecommendations(items) {
                    analyticsRecommendations.replaceChildren();
                    if (!items.length) {
                        const row = document.createElement('tr');
                        const cell = document.createElement('td');
                        cell.colSpan = 6;  // 6 columnas (sin explicación)
                        cell.textContent = 'No hay acciones recomendadas con la información disponible.';
                        row.appendChild(cell);
                        analyticsRecommendations.appendChild(row);
                        return;
                    }

                    items.forEach(item => {
                        // Fila principal con los datos del combo
                        const row = document.createElement('tr');
                        const values = [
                            item.producto || 'Producto',
                            item.senal || '—',
                            item.cantidad_sugerida === null || item.cantidad_sugerida === undefined ? '—' : item.cantidad_sugerida,
                            `${item.nivel_confianza || '—'} (${Math.round((item.confianza || 0) * 100)}%)`,
                            item.calculado_en ? new Date(item.calculado_en).toLocaleString('es-VE') : '—'
                        ];
                        values.forEach(value => {
                            const cell = document.createElement('td');
                            cell.textContent = value;
                            row.appendChild(cell);
                        });

                        const ratingCell = document.createElement('td');
                        const rating = document.createElement('div');
                        rating.className = 'analytics-rating';
                        rating.setAttribute('role', 'group');
                        rating.setAttribute('aria-label', `Valorar ${item.producto || 'recomendación'}`);
                        for (let stars = 1; stars <= 5; stars += 1) {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.textContent = '★';
                            button.dataset.recommendationId = item.recomendacion_id;
                            button.dataset.recommendationType = item.tipo;
                            button.dataset.stars = String(stars);
                            button.setAttribute('aria-label', `${stars} estrellas`);
                            button.title = `${stars} estrellas`;
                            rating.appendChild(button);
                        }
                        ratingCell.appendChild(rating);
                        row.appendChild(ratingCell);
                        analyticsRecommendations.appendChild(row);

                        // Fila secundaria para la explicación
                        if (item.explicacion) {
                            const explanationRow = document.createElement('tr');
                            explanationRow.className = 'explanation-row';
                            
                            // Celda para la explicación que abarca las primeras 5 columnas
                            const explanationCell = document.createElement('td');
                            explanationCell.colSpan = 6;
                            explanationCell.style.padding = '0';
                            explanationCell.style.backgroundColor = '#ffffff';
                            explanationCell.style.borderTop = '1px solid #e0e0e0';
                            
                            // Contenedor principal de la explicación
                            const explanationContainer = document.createElement('div');
                            explanationContainer.style.padding = '16px';
                            explanationContainer.style.display = 'flex';
                            explanationContainer.style.flexDirection = 'column';
                            explanationContainer.style.gap = '12px';
                            
                            // Parsear la explicación por secciones
                            const sections = item.explicacion.split('\n\n');
                            
                            // Agrupar secciones por tipo
                            const problemaSection = sections.find(s => s.includes('PROBLEMA IDENTIFICADO'));
                            const solucionSection = sections.find(s => s.includes('SOLUCIÓN ESTRATÉGICA'));
                            const evidenciaSection = sections.find(s => s.includes('EVIDENCIA HISTÓRICA'));
                            const impactoSection = sections.find(s => s.includes('IMPACTO FINANCIERO'));
                            const ejecucionSection = sections.find(s => s.includes('RECOMENDACIÓN DE EJECUCIÓN'));
                            const metricasSection = sections.find(s => s.includes('MÉTRICAS DE SEGUIMIENTO'));
                            

                            
                            // 2. Contenedor para las 3 tarjetas intermedias (grid de 3 columnas)
                            const middleContainer = document.createElement('div');
                            middleContainer.style.display = 'grid';
                            middleContainer.style.gridTemplateColumns = 'repeat(3, 1fr)';
                            middleContainer.style.gap = '12px';
                            
                                                        // 1. Tarjeta PROBLEMA IDENTIFICADO (ancho completo)
                            if (problemaSection) {
                                const card = document.createElement('div');
                                card.style.padding = '12px';
                                card.style.borderRadius = '8px';
                                card.style.border = '1px solid #fdba74';
                                card.style.backgroundColor = '#fff7ed';
                                card.style.fontSize = '1.5em';
                                card.style.lineHeight = '1.4';
                                card.innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:700;font-size:1.1em;color:#9a3412;">⚠️ PROBLEMA IDENTIFICADO</div>' + 
                                               '<div style="color:#9a3412;">' + problemaSection.replace('⚠️ **PROBLEMA IDENTIFICADO:**', '').trim() + '</div>';
                                middleContainer.appendChild(card);
                            }
                            // EVIDENCIA HISTÓRICA
                            if (evidenciaSection) {
                                const card = document.createElement('div');
                                card.style.padding = '12px';
                                card.style.borderRadius = '8px';
                                card.style.border = '1px solid #93c5fd';
                                card.style.backgroundColor = '#eff6ff';
                                card.style.fontSize = '1.5em';
                                card.style.lineHeight = '1.4';
                                card.innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:600;color:#1e40af;">📊 EVIDENCIA HISTÓRICA</div>' + 
                                               '<div style="color:#1e40af;">' + evidenciaSection.replace('📊 **EVIDENCIA HISTÓRICA:**', '').trim() + '</div>';
                                middleContainer.appendChild(card);
                            }
                            
                            // IMPACTO FINANCIERO
                            if (impactoSection) {
                                const card = document.createElement('div');
                                card.style.padding = '12px';
                                card.style.borderRadius = '8px';
                                card.style.border = '1px solid #fde047';
                                card.style.backgroundColor = '#fef9c3';
                                card.style.fontSize = '1.5em';
                                card.style.lineHeight = '1.4';
                                card.innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:600;color:#854d0e;">💰 IMPACTO FINANCIERO</div>' + 
                                               '<div style="color:#854d0e;">' + impactoSection.replace('💰 **IMPACTO FINANCIERO:**', '').trim() + '</div>';
                                middleContainer.appendChild(card);
                            }
                            
                            // RECOMENDACIÓN DE EJECUCIÓN
                            /*
                            if (ejecucionSection) {
                                const card = document.createElement('div');
                                card.style.padding = '12px';
                                card.style.borderRadius = '8px';
                                card.style.border = '1px solid #86efac';
                                card.style.backgroundColor = '#f0fdf4';
                                card.style.fontSize = '0.85em';
                                card.style.lineHeight = '1.4';
                                card.innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:600;color:#166534;">🎯 RECOMENDACIÓN DE EJECUCIÓN</div>' + 
                                               '<div style="color:#166534;">' + ejecucionSection.replace('🎯 **RECOMENDACIÓN DE EJECUCIÓN:**', '').trim() + '</div>';
                                middleContainer.appendChild(card);
                            }
                            */
                            explanationContainer.appendChild(middleContainer);
                            
                            // 3. Tarjeta SOLUCIÓN ESTRATÉGICA (ancho completo) que incluye MÉTRICAS como nota
                            if (solucionSection) {
                                const card = document.createElement('div');
                                card.style.padding = '16px';
                                card.style.borderRadius = '8px';
                                card.style.border = '1px solid #86efac';
                                card.style.backgroundColor = '#f0fdf4';
                                card.style.fontSize = '1.5em';
                                card.style.lineHeight = '1.5';
                                
                                const solucionText = (solucionSection || ejecucionSection || item.explicacion)
                                    .replace(/^(?:💡 )?\*\*SOLUCIÓN ESTRATÉGICA:\*\*/i, '').trim();
                                const metricasText = metricasSection
                                    ? metricasSection.replace('📈 **MÉTRICAS DE SEGUIMIENTO:**', '').trim()
                                    : '';
                                
                                let innerHTML = '<div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;font-weight:700;font-size:1.1em;color:#166534;">💡 SOLUCIÓN ESTRATÉGICA</div>' +
                                               '<div style="color:#166534;margin-bottom:16px;">' + solucionText + '</div>' +
                                               '<button class="add-combo-btn" data-combo-info=\'' + JSON.stringify({
                                                    productos: item.productos,
                                                    cantidad_congelada: item.cantidad_congelada,
                                                    cantidad_popular: item.cantidad_popular,
                                                    descuento: item.descuento_sugerido,
                                                    recomendacion_id: item.recomendacion_id
                                                }).replace(/'/g, "\\'") + '\' style="background:#22c55e;color:white;border:none;padding:12px 24px;border-radius:6px;cursor:pointer;font-size:1em;font-weight:600;margin-bottom:16px;transition:background 0.2s;" onmouseover="this.style.background=\'#16a34a\'" onmouseout="this.style.background=\'#22c55e\'">+ Agregar Combo Recomendado</button>';
                                /*
                                if (metricasText) {
                                    innerHTML += '<div style="margin-top:12px;padding-top:12px;border-top:1px solid #86efac;">' +
                                                '<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:600;color:#166534;font-size:0.9em;">📈 Nota: MÉTRICAS DE SEGUIMIENTO</div>' +
                                                '<div style="color:#166534;font-size:0.85em;">' + metricasText + '</div></div>';
                                }
                                */
                                card.innerHTML = innerHTML;
                                explanationContainer.appendChild(card);
                            }
                            
                            explanationCell.appendChild(explanationContainer);
                            explanationRow.appendChild(explanationCell);
                            
                            // Celda vacía para la columna de valoración
                            const emptyRatingCell = document.createElement('td');
                            emptyRatingCell.style.padding = '0';
                            emptyRatingCell.style.border = 'none';
                            emptyRatingCell.style.backgroundColor = '#ffffff';
                            emptyRatingCell.style.borderTop = '1px solid #e0e0e0';
                            explanationRow.appendChild(emptyRatingCell);
                            
                            analyticsRecommendations.appendChild(explanationRow);
                        }
                    });
                }

                async function loadAnalytics() {
                    const productId = analyticsProduct.value;
                    const categoryId = analyticsCategory.value;
                    
                    if (productId && categoryId) {
                        setAnalyticsStatus('Selecciona un producto o una categoría, pero no ambos.', 'error');
                        return;
                    }

                    const button = document.getElementById('runAnalytics');
                    button.disabled = true;
                    setAnalyticsStatus('Consultando ventas y calculando el análisis…');
                    try {
                        const result = await postAnalytics('analitica_dashboard', {
                            producto_id: productId || '',
                            categoria_id: categoryId || '',
                            frecuencia: document.getElementById('analyticsFrequency').value,
                            horizonte: document.getElementById('analyticsHorizon').value,
                        });
                        if (result.calidad?.estado === 'sin_datos') {
                            analyticsKpis.hidden = true;
                            renderAnalyticsRecommendations([]);
                            setAnalyticsStatus('El producto o categoría seleccionado no tiene productos activos.');
                            return;
                        }
                        if (result.calidad?.estado === 'sin_ventas') {
                            analyticsKpis.hidden = true;
                            renderAnalyticsChart(result);
                            renderAnalyticsRecommendations([]);
                            setAnalyticsStatus('No hay ventas de facturas pagadas para este filtro en los últimos 365 días.');
                            return;
                        }

                        analyticsKpis.hidden = false;
                        const metrics = result.metricas || {};
                        analyticsKpis.querySelector('[data-metric="venta_proyectada"]').textContent = displayMetric(metrics.venta_proyectada);
                        analyticsKpis.querySelector('[data-metric="dias_estimados_agotamiento"]').textContent = displayMetric(metrics.dias_estimados_agotamiento, ' días');
                        analyticsKpis.querySelector('[data-metric="punto_reorden"]').textContent = displayMetric(metrics.punto_reorden, ' unidades');
                        analyticsKpis.querySelector('[data-metric="cantidad_sugerida_compra"]').textContent = displayMetric(metrics.cantidad_sugerida_compra, ' unidades');
                        analyticsKpis.querySelector('[data-metric="rotacion_proyectada"]').textContent = displayMetric(metrics.rotacion_proyectada, 'x');
                        
                        // Mostrar explicaciones de manera especial
                        const explicacionesElement = analyticsKpis.querySelector('[data-metric="explicaciones"]');
                        if (metrics.explicaciones && typeof metrics.explicaciones === 'object') {
                            // Crear un tooltip o texto expandible con las explicaciones
                            const explicacionesText = Object.values(metrics.explicaciones).filter(e => e).join('\n\n');
                            explicacionesElement.textContent = explicacionesText.length > 100 ? explicacionesText.substring(0, 100) + '...' : explicacionesText;
                            explicacionesElement.title = explicacionesText; // Tooltip completo
                            explicacionesElement.style.cursor = 'help';
                        } else {
                            explicacionesElement.textContent = 'No disponible';
                        }
                        renderAnalyticsChart(result);
                        renderAnalyticsRecommendations(result.recomendaciones || []);

                        const quality = result.calidad || {};
                        if (quality.estado === 'insuficiente_historial') {
                            setAnalyticsStatus('Historial insuficiente: se requieren al menos 24 semanas y seis semanas con ventas para entrenar el modelo.');
                        } else if (quality.estado === 'modelo_pendiente') {
                            setAnalyticsStatus('Se muestran ventas reales. Entrena el modelo local para habilitar predicciones.');
                        } else {
                            setAnalyticsStatus(`Modelo ${quality.modelo || ''} · entrenamiento ${quality.fecha_entrenamiento || 'sin fecha'} · ${result.horizonte_dias} días.`);
                        }
                    } catch (error) {
                        setAnalyticsStatus(error.message, 'error');
                    } finally {
                        button.disabled = false;
                    }
                }

                document.getElementById('runAnalytics').addEventListener('click', loadAnalytics);
                
                // Sin filtros, el microservicio analiza el inventario completo y genera combos estratégicos.
                loadAnalytics();
                
                // Event listener para botones "Agregar Combo Recomendado"
                document.addEventListener('click', async function(event) {
                    if (event.target.classList.contains('add-combo-btn')) {
                        const comboInfo = JSON.parse(event.target.dataset.comboInfo);
                        const descuentoSugerido = Number(comboInfo.descuento);
                        if (
                            comboInfo.descuento === null ||
                            comboInfo.descuento === undefined ||
                            String(comboInfo.descuento).trim() === '' ||
                            !Number.isFinite(descuentoSugerido) ||
                            descuentoSugerido < 0 ||
                            descuentoSugerido > 100
                        ) {
                            alert('La recomendación no incluye un descuento válido. Vuelve a consultar el análisis antes de crear el combo.');
                            return;
                        }
                        
                        // Pedir nombre del combo al usuario
                        const nombreCombo = prompt('Ingresa el nombre para el combo:');
                        if (!nombreCombo || nombreCombo.trim() === '') {
                            alert('El nombre del combo es obligatorio');
                            return;
                        }
                        
                        try {
                            // Generar descripción automática
                            const descripcion = "Combo especial que Incluye " + comboInfo.cantidad_popular + " unidad(es) del producto principal y " + comboInfo.cantidad_congelada + " unidad(es) del producto congelado. Descuento aplicado: " + descuentoSugerido + "%.";
                            
                            // Formatear productos como array de objetos con id y cantidad
                            const productosFormateados = comboInfo.productos.map((productoId, index) => ({
                                id: productoId,  // Usar 'id' en lugar de 'id_producto'
                                cantidad: index === 0 ? comboInfo.cantidad_popular : comboInfo.cantidad_congelada
                            }));
                            
                            // Enviar solicitud al controlador de combos
                            const formData = new FormData();
                            formData.append('accion', 'crear_combo');
                            formData.append('nombre_combo', nombreCombo.trim());
                            formData.append('descripcion', descripcion);
                            formData.append('descuento', String(descuentoSugerido));
                            formData.append('productos', JSON.stringify(productosFormateados));
                            
                            const response = await fetch('?pagina=combo', {
                                method: 'POST',
                                body: formData
                            });
                            const result = await response.json();
                            
                            if (!response.ok || result.status === 'error') {
                                throw new Error(result.message || 'Error al crear el combo');
                            }
                            
                            alert('Combo creado exitosamente: ' + nombreCombo);
                        } catch (error) {
                            alert('Error al crear el combo: ' + error.message);
                        }
                    }
                });
                
                analyticsProduct.addEventListener('change', function () {
                    if (this.value) analyticsCategory.value = '';
                });
                analyticsCategory.addEventListener('change', function () {
                    if (this.value) analyticsProduct.value = '';
                });

                const trainAnalyticsButton = document.getElementById('trainAnalytics');
                if (trainAnalyticsButton) {
                    trainAnalyticsButton.addEventListener('click', async function () {
                        this.disabled = true;
                        setAnalyticsStatus('Entrenando el modelo local con las 24 semanas completas más recientes…');
                        try {
                            const result = await postAnalytics('analitica_entrenar');
                            if (result.estado === 'insuficiente_historial') {
                                setAnalyticsStatus('No se actualizó el modelo: ningún producto alcanzó seis semanas con ventas dentro de la ventana. El modelo anterior se conservó.');
                            } else {
                                setAnalyticsStatus(
                                    `Entrenamiento completado: ${result.modelos_entrenados} modelos guardados; ${result.productos_sin_historial_suficiente} productos sin historial suficiente.`,
                                    'success'
                                );
                            }
                            if (analyticsProduct.value || analyticsCategory.value) await loadAnalytics();
                        } catch (error) {
                            setAnalyticsStatus(error.message, 'error');
                        } finally {
                            this.disabled = false;
                        }
                    });
                }

                analyticsRecommendations.addEventListener('click', async function (event) {
                    const button = event.target.closest('button[data-recommendation-id]');
                    if (!button || button.disabled) return;
                    const group = button.parentElement;
                    group.querySelectorAll('button').forEach(star => { star.disabled = true; });
                    try {
                        await postAnalytics('analitica_feedback', {
                            recomendacion_id: button.dataset.recommendationId,
                            tipo: button.dataset.recommendationType,
                            estrellas: button.dataset.stars
                        });
                        group.querySelectorAll('button').forEach(star => {
                            if (Number(star.dataset.stars) <= Number(button.dataset.stars)) star.classList.add('selected');
                        });
                        setAnalyticsStatus('Valoración guardada. Se tendrá en cuenta al priorizar futuras recomendaciones.', 'success');
                    } catch (error) {
                        group.querySelectorAll('button').forEach(star => { star.disabled = false; });
                        setAnalyticsStatus(error.message, 'error');
                    }
                });

                // Event listeners para cerrar modal
                productModal.querySelector('.close-modal').addEventListener('click', closeModal);
                productDetailsModal.querySelector('.close-modal').addEventListener('click', closeProductDetails);
                document.getElementById('closeProductDetails').addEventListener('click', closeProductDetails);

                window.addEventListener('click', function(event) {
                    if (event.target === productModal) {
                        closeModal();
                    }
                    if (event.target === productDetailsModal) {
                        closeProductDetails();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>