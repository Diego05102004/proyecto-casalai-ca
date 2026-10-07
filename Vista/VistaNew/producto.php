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