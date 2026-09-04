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
        error_log("Error al generar JWT en cliente: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'cliente';
$titulo_pagina = 'Gestión de Clientes';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Clientes -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="card-content">
                        <h3>Total Clientes</h3>
                        <p class="card-value">1,234</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="85, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">85%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="card-content">
                        <h3>Clientes Activos</h3>
                        <p class="card-value">1,089</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="92, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">92%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-user-plus"></i></div>
                    <div class="card-content">
                        <h3>Nuevos este Mes</h3>
                        <p class="card-value">45</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="67, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">67%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clientes Table Section -->
            <div class="clients-section">
                <div class="section-header">
                    <h2>Lista de Clientes</h2>
                    <div class="section-actions">
                        <button class="btn-add-client" onclick="openModal('registrar')">
                            <span class="btn-icon"><i class="fas fa-plus"></i></span>
                            Agregar Cliente
                        </button>
                        <button class="btn-export" onclick="exportData()">
                            <span class="btn-icon"><i class="fas fa-download"></i></span>
                            Exportar
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="clients-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cédula</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="client-info">
                                        <div class="client-avatar">J</div>
                                        <div class="client-details">
                                            <span class="client-name">Juan Pérez</span>
                                            <span class="client-email">juan@email.com</span>
                                        </div>
                                    </div>
                                </td>
                                <td>12.345.678</td>
                                <td>0414-123-4567</td>
                                <td>juan@email.com</td>
                                <td><span class="status active">Activo</span></td>
                                <td>
                                    <button class="btn-action btn-edit" title="Editar" onclick="openModal('editar', 1)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="Eliminar" onclick="deleteClient(1)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn-action btn-view" title="Ver detalles" onclick="viewClient(1)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="client-info">
                                        <div class="client-avatar">M</div>
                                        <div class="client-details">
                                            <span class="client-name">María González</span>
                                            <span class="client-email">maria@email.com</span>
                                        </div>
                                    </div>
                                </td>
                                <td>15.678.901</td>
                                <td>0424-234-5678</td>
                                <td>maria@email.com</td>
                                <td><span class="status active">Activo</span></td>
                                <td>
                                    <button class="btn-action btn-edit" title="Editar" onclick="openModal('editar', 2)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="Eliminar" onclick="deleteClient(2)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn-action btn-view" title="Ver detalles" onclick="viewClient(2)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="client-info">
                                        <div class="client-avatar">C</div>
                                        <div class="client-details">
                                            <span class="client-name">Carlos Rodríguez</span>
                                            <span class="client-email">carlos@email.com</span>
                                        </div>
                                    </div>
                                </td>
                                <td>18.901.234</td>
                                <td>0412-345-6789</td>
                                <td>carlos@email.com</td>
                                <td><span class="status inactive">Inactivo</span></td>
                                <td>
                                    <button class="btn-action btn-edit" title="Editar" onclick="openModal('editar', 3)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="Eliminar" onclick="deleteClient(3)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn-action btn-view" title="Ver detalles" onclick="viewClient(3)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="client-info">
                                        <div class="client-avatar">A</div>
                                        <div class="client-details">
                                            <span class="client-name">Ana Martínez</span>
                                            <span class="client-email">ana@email.com</span>
                                        </div>
                                    </div>
                                </td>
                                <td>21.234.567</td>
                                <td>0416-456-7890</td>
                                <td>ana@email.com</td>
                                <td><span class="status active">Activo</span></td>
                                <td>
                                    <button class="btn-action btn-edit" title="Editar" onclick="openModal('editar', 4)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="Eliminar" onclick="deleteClient(4)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn-action btn-view" title="Ver detalles" onclick="viewClient(4)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="client-info">
                                        <div class="client-avatar">P</div>
                                        <div class="client-details">
                                            <span class="client-name">Pedro Sánchez</span>
                                            <span class="client-email">pedro@email.com</span>
                                        </div>
                                    </div>
                                </td>
                                <td>24.567.890</td>
                                <td>0426-567-8901</td>
                                <td>pedro@email.com</td>
                                <td><span class="status active">Activo</span></td>
                                <td>
                                    <button class="btn-action btn-edit" title="Editar" onclick="openModal('editar', 5)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" title="Eliminar" onclick="deleteClient(5)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn-action btn-view" title="Ver detalles" onclick="viewClient(5)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Clientes Section -->
            <div class="top-clients-section">
                <h2>Top Clientes por Compras</h2>
                <div class="top-clients-grid">
                    <div class="top-client-card">
                        <div class="client-rank"><i class="fas fa-trophy"></i></div>
                        <div class="client-info-top">
                            <h3>Juan Pérez</h3>
                            <p>156 productos comprados</p>
                            <span class="total-spent">$12,450 gastados</span>
                        </div>
                    </div>
                    <div class="top-client-card">
                        <div class="client-rank"><i class="fas fa-medal"></i></div>
                        <div class="client-info-top">
                            <h3>María González</h3>
                            <p>134 productos comprados</p>
                            <span class="total-spent">$10,890 gastados</span>
                        </div>
                    </div>
                    <div class="top-client-card">
                        <div class="client-rank"><i class="fas fa-medal"></i></div>
                        <div class="client-info-top">
                            <h3>Carlos Rodríguez</h3>
                            <p>98 productos comprados</p>
                            <span class="total-spent">$8,760 gastados</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para Registrar/Editar Cliente -->
            <div id="clientModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalTitle">Agregar Cliente</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="clientForm">
                            <input type="hidden" id="clientId" name="id">
                            
                            <div class="form-group">
                                <label for="clientName">Nombre Completo*</label>
                                <input type="text" id="clientName" name="nombre" required 
                                       placeholder="Nombres y apellidos" maxlength="100">
                            </div>
                            
                            <div class="form-group">
                                <label for="clientCedula">Cédula*</label>
                                <input type="text" id="clientCedula" name="cedula" required 
                                       placeholder="12.345.678" maxlength="10">
                            </div>
                            
                            <div class="form-group">
                                <label for="clientPhone">Teléfono*</label>
                                <input type="text" id="clientPhone" name="telefono" required 
                                       placeholder="0414-123-4567" maxlength="13">
                            </div>
                            
                            <div class="form-group">
                                <label for="clientEmail">Correo Electrónico*</label>
                                <input type="email" id="clientEmail" name="correo" required 
                                       placeholder="ejemplo@email.com" maxlength="50">
                            </div>
                            
                            <div class="form-group">
                                <label for="clientAddress">Dirección*</label>
                                <textarea id="clientAddress" name="direccion" required 
                                          placeholder="Estado/Ciudad/Calle o Avenida..." rows="3" maxlength="100"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button class="btn-save" onclick="saveClient()">Guardar</button>
                    </div>
                </div>
            </div>

            <!-- Modal de Detalles del Cliente -->
            <div id="clientDetailsModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Detalles del Cliente</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="client-details-content">
                            <div class="detail-row">
                                <span class="detail-label">Nombre:</span>
                                <span class="detail-value" id="detailName">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Cédula:</span>
                                <span class="detail-value" id="detailCedula">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Teléfono:</span>
                                <span class="detail-value" id="detailPhone">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Correo:</span>
                                <span class="detail-value" id="detailEmail">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Dirección:</span>
                                <span class="detail-value" id="detailAddress">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value" id="detailStatus">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Total Compras:</span>
                                <span class="detail-value" id="detailPurchases">-</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Total Gastado:</span>
                                <span class="detail-value" id="detailSpent">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-close" onclick="closeDetailsModal()">Cerrar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Clientes */
                .clients-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                    margin-bottom: 30px;
                }

                .section-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 20px;
                }

                .section-header h2 {
                    color: #333;
                    margin: 0;
                    font-size: 1.5rem;
                }

                .section-actions {
                    display: flex;
                    gap: 10px;
                }

                .btn-add-client, .btn-export {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .btn-add-client:hover, .btn-export:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
                }

                .btn-export {
                    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                }

                .clients-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .clients-table th {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                }

                .clients-table th:first-child {
                    border-radius: 8px 0 0 0;
                }

                .clients-table th:last-child {
                    border-radius: 0 8px 0 0;
                }

                .clients-table td {
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                }

                .clients-table tr:hover {
                    background-color: #f8f9fa;
                }

                .client-info {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .client-avatar {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 1.1rem;
                }

                .client-details {
                    display: flex;
                    flex-direction: column;
                }

                .client-name {
                    font-weight: 600;
                    color: #333;
                }

                .client-email {
                    font-size: 0.85rem;
                    color: #666;
                }

                .status {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .status.active {
                    background-color: #d4edda;
                    color: #155724;
                }

                .status.inactive {
                    background-color: #f8d7da;
                    color: #721c24;
                }

                .btn-action {
                    background: none;
                    border: none;
                    cursor: pointer;
                    font-size: 1.2rem;
                    padding: 5px;
                    transition: transform 0.2s;
                    margin-right: 5px;
                }

                .btn-action:hover {
                    transform: scale(1.2);
                }

                .btn-edit:hover {
                    color: #667eea;
                }

                .btn-delete:hover {
                    color: #dc3545;
                }

                .btn-view:hover {
                    color: #28a745;
                }

                .top-clients-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .top-clients-section h2 {
                    color: #333;
                    margin: 0 0 20px 0;
                    font-size: 1.5rem;
                }

                .top-clients-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                }

                .top-client-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 12px;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    transition: transform 0.2s, box-shadow 0.2s;
                }

                .top-client-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                }

                .client-rank {
                    font-size: 2.5rem;
                }

                .client-rank i {
                    color: #ffd700;
                }

                .client-info-top h3 {
                    margin: 0 0 5px 0;
                    color: #333;
                    font-size: 1.1rem;
                }

                .client-info-top p {
                    margin: 0 0 8px 0;
                    color: #666;
                    font-size: 0.9rem;
                }

                .total-spent {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 4px 10px;
                    border-radius: 12px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                .form-group label {
                    display: block;
                    margin-bottom: 8px;
                    font-weight: 600;
                    color: #333;
                }

                .form-group input, .form-group textarea {
                    width: 100%;
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    font-size: 1rem;
                    transition: border-color 0.3s;
                }

                .form-group input:focus, .form-group textarea:focus {
                    outline: none;
                    border-color: #667eea;
                }

                .btn-cancel, .btn-close {
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 10px 25px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 1rem;
                    margin-right: 10px;
                }

                .btn-save {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    padding: 10px 25px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 1rem;
                }

                .client-details-content {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }
            </style>

            <script>
                // Funciones para modales
                const clientModal = document.getElementById('clientModal');
                const detailsModal = document.getElementById('clientDetailsModal');
                const modalTitle = document.getElementById('modalTitle');
                const closeModalBtn = document.querySelector('#clientModal .close-modal');
                const closeDetailsBtn = document.querySelector('#clientDetailsModal .close-modal');

                function openModal(type, clientId = null) {
                    if (type === 'registrar') {
                        modalTitle.textContent = 'Agregar Cliente';
                        document.getElementById('clientForm').reset();
                        document.getElementById('clientId').value = '';
                    } else if (type === 'editar') {
                        modalTitle.textContent = 'Editar Cliente';
                        // Aquí cargarías los datos del cliente desde la base de datos
                        document.getElementById('clientId').value = clientId;
                        // Simulación de datos
                        document.getElementById('clientName').value = 'Cliente Ejemplo';
                        document.getElementById('clientCedula').value = '12.345.678';
                        document.getElementById('clientPhone').value = '0414-123-4567';
                        document.getElementById('clientEmail').value = 'cliente@email.com';
                        document.getElementById('clientAddress').value = 'Dirección de ejemplo';
                    }
                    clientModal.style.display = 'block';
                }

                function closeModal() {
                    clientModal.style.display = 'none';
                }

                function viewClient(clientId) {
                    // Aquí cargarías los datos del cliente desde la base de datos
                    // Simulación de datos
                    document.getElementById('detailName').textContent = 'Juan Pérez';
                    document.getElementById('detailCedula').textContent = '12.345.678';
                    document.getElementById('detailPhone').textContent = '0414-123-4567';
                    document.getElementById('detailEmail').textContent = 'juan@email.com';
                    document.getElementById('detailAddress').textContent = 'Av. Principal #123, Caracas';
                    document.getElementById('detailStatus').textContent = 'Activo';
                    document.getElementById('detailPurchases').textContent = '156 productos';
                    document.getElementById('detailSpent').textContent = '$12,450';
                    
                    detailsModal.style.display = 'block';
                }

                function closeDetailsModal() {
                    detailsModal.style.display = 'none';
                }

                function saveClient() {
                    // Aquí implementarías la lógica para guardar el cliente
                    alert('Función de guardar cliente (conectar con backend)');
                    closeModal();
                }

                function deleteClient(clientId) {
                    if (confirm('¿Está seguro de eliminar este cliente?')) {
                        // Aquí implementarías la lógica para eliminar el cliente
                        alert('Función de eliminar cliente (conectar con backend)');
                    }
                }

                function exportData() {
                    // Aquí implementarías la lógica para exportar datos
                    alert('Función de exportar datos (conectar con backend)');
                }

                // Event listeners para cerrar modales
                closeModalBtn.addEventListener('click', closeModal);
                closeDetailsBtn.addEventListener('click', closeDetailsModal);

                window.addEventListener('click', function(event) {
                    if (event.target === clientModal) {
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