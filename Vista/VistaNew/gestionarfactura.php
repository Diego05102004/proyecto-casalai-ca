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
        error_log("Error al generar JWT en gestionarfactura: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'gestionarfactura';
$titulo_pagina = 'Gestión de Pedidos';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Pedidos -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-box"></i></div>
                    <div class="card-content">
                        <h3>Total Pedidos</h3>
                        <p class="card-value">1,567</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="82, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">82%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                    <div class="card-content">
                        <h3>Pendientes</h3>
                        <p class="card-value">89</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="35, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">35%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-check"></i></div>
                    <div class="card-content">
                        <h3>Completados</h3>
                        <p class="card-value">1,423</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="91, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">91%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pedidos Section -->
            <div class="orders-section">
                <div class="section-header">
                    <h2>Gestión de Pedidos</h2>
                    <div class="section-actions">
                        <button class="btn-add-order" onclick="openModal('crear')">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Crear Pedido
                        </button>
                        <button class="btn-filter" onclick="openFilterModal()">
                            <span class="btn-icon"><i class="fas fa-search"></i></span>
                            Filtrar
                        </button>
                    </div>
                </div>

                <div class="orders-table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#ORD-001</td>
                                <td>
                                    <div class="client-info-mini">
                                        <div class="client-avatar-mini">J</div>
                                        <span class="client-name-mini">Juan Pérez</span>
                                    </div>
                                </td>
                                <td>2024-01-15</td>
                                <td>$1,299</td>
                                <td><span class="status completed">Completado</span></td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewOrder(1)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="openModal('editar', 1)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-print" onclick="printOrder(1)"><i class="fas fa-print"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-002</td>
                                <td>
                                    <div class="client-info-mini">
                                        <div class="client-avatar-mini">M</div>
                                        <span class="client-name-mini">María González</span>
                                    </div>
                                </td>
                                <td>2024-01-16</td>
                                <td>$2,450</td>
                                <td><span class="status processing">Procesando</span></td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewOrder(2)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="openModal('editar', 2)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-print" onclick="printOrder(2)"><i class="fas fa-print"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-003</td>
                                <td>
                                    <div class="client-info-mini">
                                        <div class="client-avatar-mini">C</div>
                                        <span class="client-name-mini">Carlos Rodríguez</span>
                                    </div>
                                </td>
                                <td>2024-01-17</td>
                                <td>$890</td>
                                <td><span class="status pending">Pendiente</span></td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewOrder(3)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="openModal('editar', 3)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-print" onclick="printOrder(3)"><i class="fas fa-print"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-004</td>
                                <td>
                                    <div class="client-info-mini">
                                        <div class="client-avatar-mini">A</div>
                                        <span class="client-name-mini">Ana Martínez</span>
                                    </div>
                                </td>
                                <td>2024-01-18</td>
                                <td>$3,200</td>
                                <td><span class="status completed">Completado</span></td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewOrder(4)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="openModal('editar', 4)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-print" onclick="printOrder(4)"><i class="fas fa-print"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-005</td>
                                <td>
                                    <div class="client-info-mini">
                                        <div class="client-avatar-mini">P</div>
                                        <span class="client-name-mini">Pedro Sánchez</span>
                                    </div>
                                </td>
                                <td>2024-01-19</td>
                                <td>$1,560</td>
                                <td><span class="status shipped">Enviado</span></td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewOrder(5)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="openModal('editar', 5)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-print" onclick="printOrder(5)"><i class="fas fa-print"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Estadísticas de Pedidos -->
            <div class="orders-stats-section">
                <h2>Estadísticas de Pedidos</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                        <div class="stat-info">
                            <h3>Ingresos del Mes</h3>
                            <p class="stat-value">$45,678</p>
                            <span class="stat-change positive">+15.3%</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="stat-info">
                            <h3>Promedio por Pedido</h3>
                            <p class="stat-value">$892</p>
                            <span class="stat-change positive">+8.7%</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-bolt"></i></div>
                        <div class="stat-info">
                            <h3>Tiempo Promedio</h3>
                            <p class="stat-value">2.3 días</p>
                            <span class="stat-change negative">-12.1%</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
                        <div class="stat-info">
                            <h3>Tasa de Completación</h3>
                            <p class="stat-value">94.5%</p>
                            <span class="stat-change positive">+2.4%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para Crear/Editar Pedido -->
            <div id="orderModal" class="modal">
                <div class="modal-content modal-large">
                    <div class="modal-header">
                        <h2 id="modalTitle">Crear Pedido</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="orderForm">
                            <input type="hidden" id="orderId" name="id">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="orderClient">Cliente*</label>
                                    <select id="orderClient" name="cliente" required>
                                        <option value="">Seleccione un cliente</option>
                                        <option value="1">Juan Pérez</option>
                                        <option value="2">María González</option>
                                        <option value="3">Carlos Rodríguez</option>
                                        <option value="4">Ana Martínez</option>
                                        <option value="5">Pedro Sánchez</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="orderDate">Fecha*</label>
                                    <input type="date" id="orderDate" name="fecha" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="orderProducts">Productos*</label>
                                <div class="products-selector">
                                    <select id="orderProducts" name="productos[]" multiple required>
                                        <option value="1">iPhone 15 Pro Max - $1,199</option>
                                        <option value="2">MacBook Air M3 - $1,099</option>
                                        <option value="3">AirPods Pro 2 - $249</option>
                                        <option value="4">Apple Watch Ultra - $799</option>
                                        <option value="5">Canon EOS R5 - $3,899</option>
                                    </select>
                                    <small class="help-text">Mantén presionado Ctrl para seleccionar múltiples productos</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="orderStatus">Estado*</label>
                                    <select id="orderStatus" name="estado" required>
                                        <option value="pending">Pendiente</option>
                                        <option value="processing">Procesando</option>
                                        <option value="shipped">Enviado</option>
                                        <option value="completed">Completado</option>
                                        <option value="cancelled">Cancelado</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="orderPayment">Método de Pago*</label>
                                    <select id="orderPayment" name="pago" required>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta de Crédito</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="paypal">PayPal</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="orderNotes">Notas</label>
                                <textarea id="orderNotes" name="notas" 
                                          placeholder="Notas adicionales sobre el pedido..." rows="3" maxlength="300"></textarea>
                            </div>
                            
                            <div class="order-summary">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">$0.00</span>
                                </div>
                                <div class="summary-row">
                                    <span>IVA (16%):</span>
                                    <span id="tax">$0.00</span>
                                </div>
                                <div class="summary-row total">
                                    <span>Total:</span>
                                    <span id="total">$0.00</span>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button class="btn-save" onclick="saveOrder()">Guardar Pedido</button>
                    </div>
                </div>
            </div>

            <!-- Modal de Detalles del Pedido -->
            <div id="orderDetailsModal" class="modal">
                <div class="modal-content modal-large">
                    <div class="modal-header">
                        <h2>Detalles del Pedido</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="order-details-content">
                            <div class="order-info-section">
                                <h3>Información del Pedido</h3>
                                <div class="detail-row">
                                    <span class="detail-label">Número:</span>
                                    <span class="detail-value" id="detailNumber">-</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Cliente:</span>
                                    <span class="detail-value" id="detailClient">-</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Fecha:</span>
                                    <span class="detail-value" id="detailDate">-</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Estado:</span>
                                    <span class="detail-value" id="detailStatus">-</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Método de Pago:</span>
                                    <span class="detail-value" id="detailPayment">-</span>
                                </div>
                            </div>
                            
                            <div class="order-products-section">
                                <h3>Productos del Pedido</h3>
                                <div class="products-list" id="detailProducts">
                                    <!-- Productos se cargarán dinámicamente -->
                                </div>
                            </div>
                            
                            <div class="order-totals-section">
                                <h3>Resumen del Pedido</h3>
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span id="detailSubtotal">-</span>
                                </div>
                                <div class="summary-row">
                                    <span>IVA (16%):</span>
                                    <span id="detailTax">-</span>
                                </div>
                                <div class="summary-row total">
                                    <span>Total:</span>
                                    <span id="detailTotal">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-print" onclick="printCurrentOrder()"><i class="fas fa-print"></i> Imprimir</button>
                        <button class="btn-close" onclick="closeDetailsModal()">Cerrar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Pedidos */
                .orders-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .orders-table-container {
                    overflow-x: auto;
                }

                .orders-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .orders-table th {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                }

                .orders-table th:first-child {
                    border-radius: 8px 0 0 0;
                }

                .orders-table th:last-child {
                    border-radius: 0 8px 0 0;
                }

                .orders-table td {
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                }

                .orders-table tr:hover {
                    background-color: #f8f9fa;
                }

                .client-info-mini {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .client-avatar-mini {
                    width: 35px;
                    height: 35px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 0.9rem;
                }

                .client-name-mini {
                    font-weight: 500;
                    color: #333;
                }

                .status {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .status.completed {
                    background-color: #d4edda;
                    color: #155724;
                }

                .status.processing {
                    background-color: #fff3cd;
                    color: #856404;
                }

                .status.pending {
                    background-color: #f8d7da;
                    color: #721c24;
                }

                .status.shipped {
                    background-color: #d1ecf1;
                    color: #0c5460;
                }

                .orders-stats-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .orders-stats-section h2 {
                    color: #333;
                    margin: 0 0 20px 0;
                    font-size: 1.5rem;
                }

                .stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                }

                .stat-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 12px;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .stat-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .stat-icon {
                    font-size: 2.5rem;
                }

                .stat-icon i {
                    color: #667eea;
                }

                .stat-info h3 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1rem;
                }

                .stat-value {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #667eea;
                    margin: 0 0 5px 0;
                }

                .stat-change {
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .stat-change.positive {
                    color: #28a745;
                }

                .stat-change.negative {
                    color: #dc3545;
                }

                .products-selector select {
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    font-size: 1rem;
                    min-height: 150px;
                }

                .help-text {
                    color: #666;
                    font-size: 0.85rem;
                    margin-top: 5px;
                }

                .order-summary {
                    background: #f8f9fa;
                    border-radius: 8px;
                    padding: 15px;
                    margin-top: 20px;
                }

                .summary-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 8px 0;
                    border-bottom: 1px solid #ddd;
                }

                .summary-row:last-child {
                    border-bottom: none;
                }

                .summary-row.total {
                    font-weight: 700;
                    font-size: 1.1rem;
                    color: #667eea;
                    border-top: 2px solid #667eea;
                    padding-top: 12px;
                    margin-top: 8px;
                }

                .order-details-content {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 25px;
                }

                .order-info-section, .order-products-section, .order-totals-section {
                    background: #f8f9fa;
                    border-radius: 8px;
                    padding: 20px;
                }

                .order-info-section h3, .order-products-section h3, .order-totals-section h3 {
                    margin: 0 0 15px 0;
                    color: #333;
                    font-size: 1.1rem;
                }

                .products-list {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .product-item {
                    background: white;
                    padding: 10px;
                    border-radius: 6px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .btn-print {
                    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 1rem;
                    margin-right: 10px;
                }

                @media (max-width: 768px) {
                    .order-details-content {
                        grid-template-columns: 1fr;
                    }
                    
                    .stats-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <script>
                // Funciones para modales
                const orderModal = document.getElementById('orderModal');
                const detailsModal = document.getElementById('orderDetailsModal');
                const modalTitle = document.getElementById('modalTitle');
                const closeModalBtn = document.querySelector('#orderModal .close-modal');
                const closeDetailsBtn = document.querySelector('#orderDetailsModal .close-modal');

                function openModal(type, orderId = null) {
                    if (type === 'crear') {
                        modalTitle.textContent = 'Crear Pedido';
                        document.getElementById('orderForm').reset();
                        document.getElementById('orderId').value = '';
                        // Establecer fecha actual
                        document.getElementById('orderDate').value = new Date().toISOString().split('T')[0];
                    } else if (type === 'editar') {
                        modalTitle.textContent = 'Editar Pedido';
                        // Aquí cargarías los datos del pedido desde la base de datos
                        document.getElementById('orderId').value = orderId;
                        // Simulación de datos
                        document.getElementById('orderClient').value = '1';
                        document.getElementById('orderDate').value = '2024-01-15';
                        document.getElementById('orderStatus').value = 'completed';
                        document.getElementById('orderPayment').value = 'tarjeta';
                    }
                    orderModal.style.display = 'block';
                }

                function closeModal() {
                    orderModal.style.display = 'none';
                }

                function viewOrder(orderId) {
                    // Aquí cargarías los datos del pedido desde la base de datos
                    // Simulación de datos
                    document.getElementById('detailNumber').textContent = '#ORD-001';
                    document.getElementById('detailClient').textContent = 'Juan Pérez';
                    document.getElementById('detailDate').textContent = '2024-01-15';
                    document.getElementById('detailStatus').textContent = 'Completado';
                    document.getElementById('detailPayment').textContent = 'Tarjeta de Crédito';
                    
                    // Simulación de productos
                    document.getElementById('detailProducts').innerHTML = `
                        <div class="product-item">
                            <span>iPhone 15 Pro Max (x1)</span>
                            <span>$1,199</span>
                        </div>
                        <div class="product-item">
                            <span>AirPods Pro 2 (x1)</span>
                            <span>$249</span>
                        </div>
                    `;
                    
                    document.getElementById('detailSubtotal').textContent = '$1,448';
                    document.getElementById('detailTax').textContent = '$231.68';
                    document.getElementById('detailTotal').textContent = '$1,679.68';
                    
                    detailsModal.style.display = 'block';
                }

                function closeDetailsModal() {
                    detailsModal.style.display = 'none';
                }

                function saveOrder() {
                    // Aquí implementarías la lógica para guardar el pedido
                    alert('Función de guardar pedido (conectar con backend)');
                    closeModal();
                }

                function printOrder(orderId) {
                    // Aquí implementarías la lógica para imprimir el pedido
                    alert('Función de imprimir pedido (conectar con backend)');
                }

                function printCurrentOrder() {
                    alert('Función de imprimir pedido actual (conectar con backend)');
                }

                function openFilterModal() {
                    // Aquí implementarías la lógica para abrir el modal de filtros
                    alert('Función de filtros (conectar con backend)');
                }

                // Calcular totales al seleccionar productos
                document.getElementById('orderProducts').addEventListener('change', function() {
                    const selectedOptions = this.selectedOptions;
                    let subtotal = 0;
                    
                    for (let option of selectedOptions) {
                        const price = parseFloat(option.text.split(' - $')[1]);
                        subtotal += price;
                    }
                    
                    const tax = subtotal * 0.16;
                    const total = subtotal + tax;
                    
                    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
                    document.getElementById('tax').textContent = '$' + tax.toFixed(2);
                    document.getElementById('total').textContent = '$' + total.toFixed(2);
                });

                // Event listeners para cerrar modales
                closeModalBtn.addEventListener('click', closeModal);
                closeDetailsBtn.addEventListener('click', closeDetailsModal);

                window.addEventListener('click', function(event) {
                    if (event.target === orderModal) {
                        closeModal();
                    }
                    if (event.target === detailsModal) {
                        closeDetailsModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>