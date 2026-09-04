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
        error_log("Error al generar JWT en proveedor: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'proveedor';
$titulo_pagina = 'Gestión de Proveedores';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Proveedores -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-building"></i></div>
                    <div class="card-content">
                        <h3>Total Proveedores</h3>
                        <p class="card-value">56</p>
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
                    <div class="card-icon"><i class="fas fa-star"></i></div>
                    <div class="card-content">
                        <h3>Activos</h3>
                        <p class="card-value">48</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="86, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">86%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-box"></i></div>
                    <div class="card-content">
                        <h3>Pedidos Pendientes</h3>
                        <p class="card-value">12</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="21, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">21%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="action-buttons">
                <button class="btn-add-provider" onclick="openModal('agregar')">
                    <span class="btn-icon"><i class="fas fa-plus"></i></span>
                    Agregar Proveedor
                </button>
                <button class="btn-import" onclick="importProviders()">
                    <span class="btn-icon"><i class="fas fa-download"></i></span>
                    Importar
                </button>
                <button class="btn-export" onclick="exportProviders()">
                    <span class="btn-icon"><i class="fas fa-upload"></i></span>
                    Exportar
                </button>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="filters-section">
                <div class="search-bar">
                    <input type="text" id="searchProvider" placeholder="Buscar proveedor..." onkeyup="searchProviders()">
                    <span class="search-icon"><i class="fas fa-search"></i></span>
                </div>
                <div class="filter-options">
                    <select id="statusFilter" onchange="filterByStatus()">
                        <option value="">Todos los estados</option>
                        <option value="active">Activos</option>
                        <option value="inactive">Inactivos</option>
                        <option value="pending">Pendientes</option>
                    </select>
                    <select id="categoryFilter" onchange="filterByCategory()">
                        <option value="">Todas las categorías</option>
                        <option value="technology">Tecnología</option>
                        <option value="electronics">Electrónicos</option>
                        <option value="office">Oficina</option>
                        <option value="home">Hogar</option>
                    </select>
                </div>
            </div>

            <!-- Grid de Proveedores -->
            <div class="providers-grid">
                <!-- Proveedor 1 -->
                <div class="provider-card">
                    <div class="provider-header">
                        <div class="provider-avatar">
                            <span class="avatar-initial">T</span>
                        </div>
                        <div class="provider-status active">
                            <span class="status-dot"></span>
                            Activo
                        </div>
                    </div>
                    <div class="provider-body">
                        <h3>TechCorp International</h3>
                        <p class="provider-contact">contacto@techcorp.com</p>
                        <p class="provider-phone">+58 212-123-4567</p>
                        <div class="provider-stats">
                            <div class="stat-item">
                                <span class="stat-label">Pedidos:</span>
                                <span class="stat-value">45</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Valor:</span>
                                <span class="stat-value">$125K</span>
                            </div>
                        </div>
                    </div>
                    <div class="provider-actions">
                        <button class="btn-action btn-view" onclick="viewProvider(1)"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="editProvider(1)"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-delete" onclick="deleteProvider(1)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>

                <!-- Proveedor 2 -->
                <div class="provider-card">
                    <div class="provider-header">
                        <div class="provider-avatar">
                            <span class="avatar-initial">E</span>
                        </div>
                        <div class="provider-status active">
                            <span class="status-dot"></span>
                            Activo
                        </div>
                    </div>
                    <div class="provider-body">
                        <h3>ElectroWorld S.A.</h3>
                        <p class="provider-contact">info@electroworld.com</p>
                        <p class="provider-phone">+58 212-234-5678</p>
                        <div class="provider-stats">
                            <div class="stat-item">
                                <span class="stat-label">Pedidos:</span>
                                <span class="stat-value">32</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Valor:</span>
                                <span class="stat-value">$89K</span>
                            </div>
                        </div>
                    </div>
                    <div class="provider-actions">
                        <button class="btn-action btn-view" onclick="viewProvider(2)"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="editProvider(2)"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-delete" onclick="deleteProvider(2)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>

                <!-- Proveedor 3 -->
                <div class="provider-card">
                    <div class="provider-header">
                        <div class="provider-avatar">
                            <span class="avatar-initial">O</span>
                        </div>
                        <div class="provider-status inactive">
                            <span class="status-dot"></span>
                            Inactivo
                        </div>
                    </div>
                    <div class="provider-body">
                        <h3>Office Supplies Ltd</h3>
                        <p class="provider-contact">sales@officesupplies.com</p>
                        <p class="provider-phone">+58 212-345-6789</p>
                        <div class="provider-stats">
                            <div class="stat-item">
                                <span class="stat-label">Pedidos:</span>
                                <span class="stat-value">18</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Valor:</span>
                                <span class="stat-value">$34K</span>
                            </div>
                        </div>
                    </div>
                    <div class="provider-actions">
                        <button class="btn-action btn-view" onclick="viewProvider(3)"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="editProvider(3)"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-delete" onclick="deleteProvider(3)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>

                <!-- Proveedor 4 -->
                <div class="provider-card">
                    <div class="provider-header">
                        <div class="provider-avatar">
                            <span class="avatar-initial">H</span>
                        </div>
                        <div class="provider-status active">
                            <span class="status-dot"></span>
                            Activo
                        </div>
                    </div>
                    <div class="provider-body">
                        <h3>HomeTech Solutions</h3>
                        <p class="provider-contact">info@hometech.com</p>
                        <p class="provider-phone">+58 212-456-7890</p>
                        <div class="provider-stats">
                            <div class="stat-item">
                                <span class="stat-label">Pedidos:</span>
                                <span class="stat-value">27</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Valor:</span>
                                <span class="stat-value">$67K</span>
                            </div>
                        </div>
                    </div>
                    <div class="provider-actions">
                        <button class="btn-action btn-view" onclick="viewProvider(4)"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="editProvider(4)"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-delete" onclick="deleteProvider(4)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>

                <!-- Proveedor 5 -->
                <div class="provider-card">
                    <div class="provider-header">
                        <div class="provider-avatar">
                            <span class="avatar-initial">G</span>
                        </div>
                        <div class="provider-status pending">
                            <span class="status-dot"></span>
                            Pendiente
                        </div>
                    </div>
                    <div class="provider-body">
                        <h3>GlobalTech Corp</h3>
                        <p class="provider-contact">contact@globaltech.com</p>
                        <p class="provider-phone">+58 212-567-8901</p>
                        <div class="provider-stats">
                            <div class="stat-item">
                                <span class="stat-label">Pedidos:</span>
                                <span class="stat-value">5</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Valor:</span>
                                <span class="stat-value">$12K</span>
                            </div>
                        </div>
                    </div>
                    <div class="provider-actions">
                        <button class="btn-action btn-view" onclick="viewProvider(5)"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="editProvider(5)"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-delete" onclick="deleteProvider(5)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>

                <!-- Proveedor 6 -->
                <div class="provider-card">
                    <div class="provider-header">
                        <div class="provider-avatar">
                            <span class="avatar-initial">S</span>
                        </div>
                        <div class="provider-status active">
                            <span class="status-dot"></span>
                            Activo
                        </div>
                    </div>
                    <div class="provider-body">
                        <h3>SmartDevices Inc</h3>
                        <p class="provider-contact">sales@smartdevices.com</p>
                        <p class="provider-phone">+58 212-678-9012</p>
                        <div class="provider-stats">
                            <div class="stat-item">
                                <span class="stat-label">Pedidos:</span>
                                <span class="stat-value">38</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Valor:</span>
                                <span class="stat-value">$98K</span>
                            </div>
                        </div>
                    </div>
                    <div class="provider-actions">
                        <button class="btn-action btn-view" onclick="viewProvider(6)"><i class="fas fa-eye"></i></button>
                        <button class="btn-action btn-edit" onclick="editProvider(6)"><i class="fas fa-edit"></i></button>
                        <button class="btn-action btn-delete" onclick="deleteProvider(6)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>

            <!-- Modal para Agregar/Editar Proveedor -->
            <div id="providerModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalTitle">Agregar Proveedor</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="providerForm">
                            <input type="hidden" id="providerId" name="id">
                            
                            <div class="form-group">
                                <label for="providerName">Nombre del Proveedor*</label>
                                <input type="text" id="providerName" name="nombre" required 
                                       placeholder="Ej: TechCorp International">
                            </div>
                            
                            <div class="form-group">
                                <label for="providerEmail">Email de Contacto*</label>
                                <input type="email" id="providerEmail" name="email" required 
                                       placeholder="contacto@empresa.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="providerPhone">Teléfono*</label>
                                <input type="tel" id="providerPhone" name="telefono" required 
                                       placeholder="+58 212-123-4567">
                            </div>
                            
                            <div class="form-group">
                                <label for="providerAddress">Dirección</label>
                                <input type="text" id="providerAddress" name="direccion" 
                                       placeholder="Dirección física">
                            </div>
                            
                            <div class="form-group">
                                <label for="providerCategory">Categoría*</label>
                                <select id="providerCategory" name="categoria" required>
                                    <option value="">Seleccione categoría</option>
                                    <option value="technology">Tecnología</option>
                                    <option value="electronics">Electrónicos</option>
                                    <option value="office">Oficina</option>
                                    <option value="home">Hogar</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="providerStatus">Estado*</label>
                                <select id="providerStatus" name="estado" required>
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                    <option value="pending">Pendiente</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button class="btn-save" onclick="saveProvider()">Guardar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Proveedores */
                .action-buttons {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 30px;
                }

                .btn-add-provider, .btn-import, .btn-export {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .btn-add-provider:hover, .btn-import:hover, .btn-export:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                }

                .btn-import {
                    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                }

                .btn-export {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }

                .filters-section {
                    background: white;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 20px;
                }

                .search-bar {
                    flex: 1;
                    position: relative;
                }

                .search-bar input {
                    width: 100%;
                    padding: 12px 40px 12px 15px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    font-size: 1rem;
                }

                .search-icon {
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    font-size: 1.2rem;
                }

                .filter-options {
                    display: flex;
                    gap: 10px;
                }

                .filter-options select {
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    font-size: 1rem;
                }

                .providers-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                    gap: 25px;
                }

                .provider-card {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .provider-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .provider-header {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    padding: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .provider-avatar {
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    background: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: #2196F3;
                }

                .provider-status {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }

                .provider-status.active {
                    background: rgba(67, 233, 123, 0.2);
                    color: #155724;
                }

                .provider-status.inactive {
                    background: rgba(245, 87, 108, 0.2);
                    color: #721c24;
                }

                .provider-status.pending {
                    background: rgba(255, 193, 7, 0.2);
                    color: #856404;
                }

                .status-dot {
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                }

                .provider-status.active .status-dot {
                    background: #28a745;
                }

                .provider-status.inactive .status-dot {
                    background: #dc3545;
                }

                .provider-status.pending .status-dot {
                    background: #ffc107;
                }

                .provider-body {
                    padding: 20px;
                }

                .provider-body h3 {
                    margin: 0 0 10px 0;
                    color: #333;
                    font-size: 1.1rem;
                }

                .provider-contact, .provider-phone {
                    margin: 5px 0;
                    color: #666;
                    font-size: 0.9rem;
                }

                .provider-stats {
                    display: flex;
                    gap: 20px;
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 1px solid #eee;
                }

                .stat-item {
                    display: flex;
                    flex-direction: column;
                }

                .stat-label {
                    font-size: 0.75rem;
                    color: #666;
                }

                .stat-value {
                    font-weight: 700;
                    color: #2196F3;
                }

                .provider-actions {
                    padding: 15px 20px;
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    border-top: 1px solid #eee;
                }

                .btn-action {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    border: none;
                    cursor: pointer;
                    font-size: 1.2rem;
                    transition: transform 0.2s;
                }

                .btn-action:hover {
                    transform: scale(1.1);
                }

                .btn-view {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                }

                .btn-edit {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                }

                .btn-delete {
                    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                }

                @media (max-width: 768px) {
                    .filters-section {
                        flex-direction: column;
                    }
                    
                    .filter-options {
                        flex-direction: column;
                        width: 100%;
                    }
                    
                    .filter-options select {
                        width: 100%;
                    }
                    
                    .providers-grid {
                        grid-template-columns: 1fr;
                    }
                }
            </style>

            <script>
                const providerModal = document.getElementById('providerModal');
                const modalTitle = document.getElementById('modalTitle');
                const closeModalBtn = document.querySelector('#providerModal .close-modal');

                function openModal(type, providerId = null) {
                    if (type === 'agregar') {
                        modalTitle.textContent = 'Agregar Proveedor';
                        document.getElementById('providerForm').reset();
                        document.getElementById('providerId').value = '';
                    } else if (type === 'editar') {
                        modalTitle.textContent = 'Editar Proveedor';
                        document.getElementById('providerId').value = providerId;
                        // Simulación de datos
                        document.getElementById('providerName').value = 'TechCorp International';
                        document.getElementById('providerEmail').value = 'contacto@techcorp.com';
                        document.getElementById('providerPhone').value = '+58 212-123-4567';
                        document.getElementById('providerAddress').value = 'Av. Principal, Caracas';
                        document.getElementById('providerCategory').value = 'technology';
                        document.getElementById('providerStatus').value = 'active';
                    }
                    providerModal.style.display = 'block';
                }

                function closeModal() {
                    providerModal.style.display = 'none';
                }

                function viewProvider(providerId) {
                    alert('Función de ver detalles de proveedor (conectar con backend)');
                }

                function editProvider(providerId) {
                    openModal('editar', providerId);
                }

                function deleteProvider(providerId) {
                    if (confirm('¿Está seguro de eliminar este proveedor?')) {
                        alert('Función de eliminar proveedor (conectar con backend)');
                    }
                }

                function saveProvider() {
                    alert('Función de guardar proveedor (conectar con backend)');
                    closeModal();
                }

                function searchProviders() {
                    const searchTerm = document.getElementById('searchProvider').value;
                    alert('Función de búsqueda: ' + searchTerm + ' (conectar con backend)');
                }

                function filterByStatus() {
                    const status = document.getElementById('statusFilter').value;
                    alert('Filtrar por estado: ' + status + ' (conectar con backend)');
                }

                function filterByCategory() {
                    const category = document.getElementById('categoryFilter').value;
                    alert('Filtrar por categoría: ' + category + ' (conectar con backend)');
                }

                function importProviders() {
                    alert('Función de importar proveedores (conectar con backend)');
                }

                function exportProviders() {
                    alert('Función de exportar proveedores (conectar con backend)');
                }

                // Event listeners para cerrar modal
                closeModalBtn.addEventListener('click', closeModal);

                window.addEventListener('click', function(event) {
                    if (event.target === providerModal) {
                        closeModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>