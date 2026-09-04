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
        error_log("Error al generar JWT en usuario: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'usuario';
$titulo_pagina = 'Gestión de Usuarios';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Summary Cards para Usuarios -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="card-content">
                        <h3>Total Usuarios</h3>
                        <p class="card-value">124</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="75, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">75%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card expenses">
                    <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="card-content">
                        <h3>Activos</h3>
                        <p class="card-value">98</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="79, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">79%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon"><i class="fas fa-crown"></i></div>
                    <div class="card-content">
                        <h3>Administradores</h3>
                        <p class="card-value">8</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="6, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">6%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="action-buttons">
                <button class="btn-add-user" onclick="openModal('agregar')">
                    <span class="btn-icon"><i class="fas fa-plus"></i></span>
                    Agregar Usuario
                </button>
                <button class="btn-import" onclick="importUsers()">
                    <span class="btn-icon"><i class="fas fa-download"></i></span>
                    Importar
                </button>
                <button class="btn-export" onclick="exportUsers()">
                    <span class="btn-icon"><i class="fas fa-upload"></i></span>
                    Exportar
                </button>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="filters-section">
                <div class="search-bar">
                    <input type="text" id="searchUser" placeholder="Buscar usuario..." onkeyup="searchUsers()">
                    <span class="search-icon"><i class="fas fa-search"></i></span>
                </div>
                <div class="filter-options">
                    <select id="roleFilter" onchange="filterByRole()">
                        <option value="">Todos los roles</option>
                        <option value="admin">Administrador</option>
                        <option value="gerente">Gerente</option>
                        <option value="vendedor">Vendedor</option>
                        <option value="almacen">Almacén</option>
                    </select>
                    <select id="statusFilter" onchange="filterByStatus()">
                        <option value="">Todos los estados</option>
                        <option value="active">Activos</option>
                        <option value="inactive">Inactivos</option>
                        <option value="pending">Pendientes</option>
                    </select>
                </div>
            </div>

            <!-- Tabla de Usuarios -->
            <div class="users-table-section">
                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Último Acceso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-small">
                                            <span class="avatar-initial">J</span>
                                        </div>
                                        <span class="username">jperrez</span>
                                    </div>
                                </td>
                                <td>Juan Pérez</td>
                                <td>juan.perez@casalai.com</td>
                                <td><span class="role-badge admin">Administrador</span></td>
                                <td><span class="status active">Activo</span></td>
                                <td>2024-01-19 10:30</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewUser(1)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editUser(1)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteUser(1)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-small">
                                            <span class="avatar-initial">M</span>
                                        </div>
                                        <span class="username">mgonzalez</span>
                                    </div>
                                </td>
                                <td>María González</td>
                                <td>maria.gonzalez@casalai.com</td>
                                <td><span class="role-badge gerente">Gerente</span></td>
                                <td><span class="status active">Activo</span></td>
                                <td>2024-01-19 09:15</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewUser(2)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editUser(2)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteUser(2)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-small">
                                            <span class="avatar-initial">C</span>
                                        </div>
                                        <span class="username">crodriguez</span>
                                    </div>
                                </td>
                                <td>Carlos Rodríguez</td>
                                <td>carlos.rodriguez@casalai.com</td>
                                <td><span class="role-badge vendedor">Vendedor</span></td>
                                <td><span class="status active">Activo</span></td>
                                <td>2024-01-18 16:45</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewUser(3)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editUser(3)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteUser(3)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-small">
                                            <span class="avatar-initial">A</span>
                                        </div>
                                        <span class="username">amartinez</span>
                                    </div>
                                </td>
                                <td>Ana Martínez</td>
                                <td>ana.martinez@casalai.com</td>
                                <td><span class="role-badge almacen">Almacén</span></td>
                                <td><span class="status inactive">Inactivo</span></td>
                                <td>2024-01-15 14:20</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewUser(4)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editUser(4)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteUser(4)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-small">
                                            <span class="avatar-initial">P</span>
                                        </div>
                                        <span class="username">psanchez</span>
                                    </div>
                                </td>
                                <td>Pedro Sánchez</td>
                                <td>pedro.sanchez@casalai.com</td>
                                <td><span class="role-badge vendedor">Vendedor</span></td>
                                <td><span class="status pending">Pendiente</span></td>
                                <td>Nunca</td>
                                <td>
                                    <button class="btn-action btn-view" onclick="viewUser(5)"><i class="fas fa-eye"></i></button>
                                    <button class="btn-action btn-edit" onclick="editUser(5)"><i class="fas fa-edit"></i></button>
                                    <button class="btn-action btn-delete" onclick="deleteUser(5)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal para Agregar/Editar Usuario -->
            <div id="userModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="modalTitle">Agregar Usuario</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="userForm">
                            <input type="hidden" id="userId" name="id">
                            
                            <div class="form-group">
                                <label for="username">Nombre de Usuario*</label>
                                <input type="text" id="username" name="username" required 
                                       placeholder="Ej: jperrez">
                            </div>
                            
                            <div class="form-group">
                                <label for="firstName">Nombre*</label>
                                <input type="text" id="firstName" name="nombre" required 
                                       placeholder="Ej: Juan">
                            </div>
                            
                            <div class="form-group">
                                <label for="lastName">Apellido*</label>
                                <input type="text" id="lastName" name="apellido" required 
                                       placeholder="Ej: Pérez">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email*</label>
                                <input type="email" id="email" name="email" required 
                                       placeholder="juan.perez@casalai.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="cedula">Cédula*</label>
                                <input type="text" id="cedula" name="cedula" required 
                                       placeholder="Ej: V-12345678">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Teléfono</label>
                                <input type="tel" id="phone" name="telefono" 
                                       placeholder="+58 412-123-4567">
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Rol*</label>
                                <select id="role" name="rol" required>
                                    <option value="">Seleccione rol</option>
                                    <option value="admin">Administrador</option>
                                    <option value="gerente">Gerente</option>
                                    <option value="vendedor">Vendedor</option>
                                    <option value="almacen">Almacén</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Estado*</label>
                                <select id="status" name="estado" required>
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                    <option value="pending">Pendiente</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Contraseña*</label>
                                <input type="password" id="password" name="password" required 
                                       placeholder="Mínimo 6 caracteres">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirmPassword">Confirmar Contraseña*</label>
                                <input type="password" id="confirmPassword" name="confirm_password" required 
                                       placeholder="Repetir contraseña">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="closeModal()">Cancelar</button>
                        <button class="btn-save" onclick="saveUser()">Guardar</button>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos específicos de Usuarios */
                .action-buttons {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 30px;
                }

                .btn-add-user, .btn-import, .btn-export {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

                .btn-add-user:hover, .btn-import:hover, .btn-export:hover {
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

                .users-table-section {
                    background: white;
                    border-radius: 12px;
                    padding: 25px;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }

                .users-table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .users-table th {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 15px;
                    text-align: left;
                    font-weight: 600;
                }

                .users-table th:first-child {
                    border-radius: 8px 0 0 0;
                }

                .users-table th:last-child {
                    border-radius: 0 8px 0 0;
                }

                .users-table td {
                    padding: 15px;
                    border-bottom: 1px solid #eee;
                }

                .users-table tr:hover {
                    background-color: #f8f9fa;
                }

                .user-cell {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .user-avatar-small {
                    width: 35px;
                    height: 35px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: 700;
                    font-size: 0.9rem;
                }

                .username {
                    font-weight: 500;
                    color: #333;
                }

                .role-badge {
                    padding: 5px 12px;
                    border-radius: 20px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }

                .role-badge.admin {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }

                .role-badge.gerente {
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                    color: white;
                }

                .role-badge.vendedor {
                    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
                    color: white;
                }

                .role-badge.almacen {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
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

                .status.pending {
                    background-color: #fff3cd;
                    color: #856404;
                }

                .btn-action {
                    width: 35px;
                    height: 35px;
                    border-radius: 50%;
                    border: none;
                    cursor: pointer;
                    font-size: 1rem;
                    transition: transform 0.2s;
                    margin-right: 5px;
                }

                .btn-action:hover {
                    transform: scale(1.1);
                }

                .btn-view {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    
                    .users-table {
                        font-size: 0.85rem;
                    }
                    
                    .users-table th, .users-table td {
                        padding: 10px;
                    }
                }
            </style>

            <script>
                const userModal = document.getElementById('userModal');
                const modalTitle = document.getElementById('modalTitle');
                const closeModalBtn = document.querySelector('#userModal .close-modal');

                function openModal(type, userId = null) {
                    if (type === 'agregar') {
                        modalTitle.textContent = 'Agregar Usuario';
                        document.getElementById('userForm').reset();
                        document.getElementById('userId').value = '';
                    } else if (type === 'editar') {
                        modalTitle.textContent = 'Editar Usuario';
                        document.getElementById('userId').value = userId;
                        // Simulación de datos
                        document.getElementById('username').value = 'jperrez';
                        document.getElementById('firstName').value = 'Juan';
                        document.getElementById('lastName').value = 'Pérez';
                        document.getElementById('email').value = 'juan.perez@casalai.com';
                        document.getElementById('cedula').value = 'V-12345678';
                        document.getElementById('phone').value = '+58 412-123-4567';
                        document.getElementById('role').value = 'admin';
                        document.getElementById('status').value = 'active';
                    }
                    userModal.style.display = 'block';
                }

                function closeModal() {
                    userModal.style.display = 'none';
                }

                function viewUser(userId) {
                    alert('Función de ver detalles de usuario (conectar con backend)');
                }

                function editUser(userId) {
                    openModal('editar', userId);
                }

                function deleteUser(userId) {
                    if (confirm('¿Está seguro de eliminar este usuario?')) {
                        alert('Función de eliminar usuario (conectar con backend)');
                    }
                }

                function saveUser() {
                    const password = document.getElementById('password').value;
                    const confirmPassword = document.getElementById('confirmPassword').value;

                    if (password !== confirmPassword) {
                        alert('Las contraseñas no coinciden');
                        return;
                    }

                    if (password.length < 6) {
                        alert('La contraseña debe tener al menos 6 caracteres');
                        return;
                    }

                    alert('Función de guardar usuario (conectar con backend)');
                    closeModal();
                }

                function searchUsers() {
                    const searchTerm = document.getElementById('searchUser').value;
                    alert('Función de búsqueda: ' + searchTerm + ' (conectar con backend)');
                }

                function filterByRole() {
                    const role = document.getElementById('roleFilter').value;
                    alert('Filtrar por rol: ' + role + ' (conectar con backend)');
                }

                function filterByStatus() {
                    const status = document.getElementById('statusFilter').value;
                    alert('Filtrar por estado: ' + status + ' (conectar con backend)');
                }

                function importUsers() {
                    alert('Función de importar usuarios (conectar con backend)');
                }

                function exportUsers() {
                    alert('Función de exportar usuarios (conectar con backend)');
                }

                // Event listeners para cerrar modal
                closeModalBtn.addEventListener('click', closeModal);

                window.addEventListener('click', function(event) {
                    if (event.target === userModal) {
                        closeModal();
                    }
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>