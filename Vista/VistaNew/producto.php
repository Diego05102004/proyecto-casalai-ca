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

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Productos -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-box"></i></div>
                    <div class="card-content">
                        <h3>Total Productos</h3>
                        <p class="card-value"><?php echo count($productos ?? []); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="78, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">78%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-chart-down"></i></div>
                    <div class="card-content">
                        <h3>Stock Bajo</h3>
                        <p class="card-value"><?php echo count(array_filter($productos ?? [], function($p) { return ($p['stock_actual'] ?? 0) < ($p['stock_minimo'] ?? 0); })); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="23, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">23%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-tag"></i></div>
                    <div class="card-content">
                        <h3>Categorías</h3>
                        <p class="card-value"><?php echo count($categoriasDinamicas ?? []); ?></p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="90, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">90%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos Grid Section -->
            <div class="products-section">
                <div class="section-header">
                    <h2>Inventario de Productos</h2>
                    <div class="section-actions">
                        <button class="btn-add-product" onclick="openModal('registrar')">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Agregar Producto
                        </button>
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
                                        <button class="btn-action btn-edit" onclick="openModal('editar', <?php echo $producto['id_producto']; ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn-action btn-delete" onclick="deleteProduct(<?php echo $producto['id_producto']; ?>)"><i class="fas fa-trash"></i></button>
                                        <button class="btn-action btn-view" onclick="viewProduct(<?php echo $producto['id_producto']; ?>)"><i class="fas fa-eye"></i></button>
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
                        <form id="productForm">
                            <input type="hidden" id="productId" name="id">
                            
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
                                <select id="productCategory" name="categoria" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php if (!empty($categoriasDinamicas)): ?>
                                        <?php foreach ($categoriasDinamicas as $categoria): ?>
                                            <option value="<?php echo $categoria['id_categoria']; ?>">
                                                <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="productPrice">Precio*</label>
                                <input type="number" id="productPrice" name="precio" required 
                                       placeholder="0.00" step="0.01" min="0">
                            </div>
                            
                            <div class="form-row">
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
                            </div>
                            
                            <div class="form-group">
                                <label for="productWarranty">Garantía</label>
                                <input type="text" id="productWarranty" name="Clausula_garantia" 
                                       placeholder="Ej: 1 año de garantía">
                            </div>
                            
                            <div class="form-group">
                                <label for="productSerial">Serial/Código</label>
                                <input type="text" id="productSerial" name="Seriales" 
                                       placeholder="Código único del producto">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button class="btn-save" onclick="saveProduct()">Guardar</button>
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
                    border: 1px solid rgba(102, 126, 234, 0.1);
                }

                .product-card:hover {
                    transform: translateY(-8px);
                    box-shadow: 0 12px 30px rgba(102, 126, 234, 0.2);
                    border-color: rgba(102, 126, 234, 0.3);
                }

                .product-image {
                    position: relative;
                    width: 100%;
                    height: 200px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
                    color: #667eea;
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
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    background: rgba(102, 126, 234, 0.05);
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
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    border: 1px solid rgba(102, 126, 234, 0.1);
                }

                .category-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
                }

                .category-icon {
                    width: 60px;
                    height: 60px;
                    margin: 0 auto 15px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    color: #667eea;
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
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }

                .btn-filter {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-add-product:hover, .btn-filter:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
                }

                .btn-icon {
                    font-size: 1rem;
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
                function openModal(type, productId = null) {
                    if (type === 'registrar') {
                        document.getElementById('modalTitle').textContent = 'Agregar Producto';
                        document.getElementById('productForm').reset();
                        document.getElementById('productId').value = '';
                    } else if (type === 'editar') {
                        document.getElementById('modalTitle').textContent = 'Editar Producto';
                        document.getElementById('productId').value = productId;
                        // Aquí cargarías los datos del producto para editar
                        alert('Función para cargar datos del producto (conectar con backend)');
                    }
                    document.getElementById('productModal').style.display = 'block';
                }

                function closeModal() {
                    document.getElementById('productModal').style.display = 'none';
                }

                function deleteProduct(productId) {
                    if (confirm('¿Está seguro de eliminar este producto?')) {
                        alert('Función para eliminar producto (conectar con backend)');
                    }
                }

                function viewProduct(productId) {
                    alert('Función para ver detalles del producto (conectar con backend)');
                }

                function saveProduct() {
                    alert('Función para guardar producto (conectar con backend)');
                    closeModal();
                }

                function openFilterModal() {
                    alert('Función para abrir filtros (conectar con backend)');
                }

                // Event listeners para cerrar modal
                document.querySelector('#productModal .close-modal').addEventListener('click', closeModal);

                window.addEventListener('click', function(event) {
                    if (event.target === document.getElementById('productModal')) {
                        closeModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>