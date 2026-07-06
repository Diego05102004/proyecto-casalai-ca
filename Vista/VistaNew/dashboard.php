<?php
// Verificar si el usuario ha iniciado sesión
require_once __DIR__ . '/../../Modelo/Config/Auth.php';

// Validar token JWT antes de cualquier otra operación
use Usuario\ProyectoCasalaiCa\Config\Auth;
$payload = Auth::requireAuth();
if (!isset($_SESSION['name'])) {
    // Redirigir al usuario a la página de inicio de sesión
    header('Location: ../..');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CasaLai</title>
    <link rel="icon" type="image/png" href="../../assets/img/LOGO.png">
    <link rel="stylesheet" href="VistaNew.css">
</head>

<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="../../assets/img/LOGO.png" alt="CasaLai Logo">
            </div>
            <h2>CasaLai C.A</h2>
        </div>
        
        <nav class="sidebar-nav">
            <a href="?pagina=dashboard" class="nav-link active">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="?pagina=cliente" class="nav-link">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Customers</span>
            </a>
            <a href="?pagina=gestionarfactura" class="nav-link">
                <span class="nav-icon">📦</span>
                <span class="nav-text">Orders</span>
            </a>
            <a href="?pagina=reporteVentas" class="nav-link">
                <span class="nav-icon">📈</span>
                <span class="nav-text">Analytics</span>
            </a>
            <a href="?pagina=notificacion" class="nav-link">
                <span class="nav-icon">💬</span>
                <span class="nav-text">Messages</span>
            </a>
            <a href="?pagina=producto" class="nav-link">
                <span class="nav-icon">🛍️</span>
                <span class="nav-text">Products</span>
            </a>
            <a href="?pagina=reporteFinanzas" class="nav-link">
                <span class="nav-icon">📋</span>
                <span class="nav-text">Reports</span>
            </a>
            <a href="?pagina=perfil" class="nav-link">
                <span class="nav-icon">⚙️</span>
                <span class="nav-text">Settings</span>
            </a>
            <a href="?pagina=producto" class="nav-link add-product">
                <span class="nav-icon">➕</span>
                <span class="nav-text">Add Product</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="#" onclick="confirmarCerrarSesion(); return false;" class="nav-link logout">
                <span class="nav-icon">🚪</span>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <h1>Dashboard</h1>
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
                            echo '<img src="../../assets/img/uploads/' . $_SESSION['foto_perfil'] . '" alt="User Avatar">';
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

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card sales">
                    <div class="card-icon">💰</div>
                    <div class="card-content">
                        <h3>Total Sales</h3>
                        <p class="card-value">$25,024</p>
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
                    <div class="card-icon">📉</div>
                    <div class="card-content">
                        <h3>Total Expenses</h3>
                        <p class="card-value">$14,160</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="45, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">45%</span>
                        </div>
                    </div>
                </div>

                <div class="summary-card income">
                    <div class="card-icon">📈</div>
                    <div class="card-content">
                        <h3>Total Income</h3>
                        <p class="card-value">$10,864</p>
                        <div class="progress-circle">
                            <svg viewBox="0 0 36 36" class="circular-chart">
                                <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                <path class="circle" stroke-dasharray="60, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                            </svg>
                            <span class="percentage">60%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="dashboard-grid">
                <!-- Recent Orders Table -->
                <div class="recent-orders">
                    <h2>Recent Orders</h2>
                    <div class="table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Product Number</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Laptop Dell XPS 15</td>
                                    <td>#ORD-001</td>
                                    <td>$1,299</td>
                                    <td><span class="status completed">Completed</span></td>
                                    <td><button class="btn-view">View</button></td>
                                </tr>
                                <tr>
                                    <td>iPhone 15 Pro Max</td>
                                    <td>#ORD-002</td>
                                    <td>$1,199</td>
                                    <td><span class="status pending">Pending</span></td>
                                    <td><button class="btn-view">View</button></td>
                                </tr>
                                <tr>
                                    <td>Samsung Galaxy S24</td>
                                    <td>#ORD-003</td>
                                    <td>$999</td>
                                    <td><span class="status completed">Completed</span></td>
                                    <td><button class="btn-view">View</button></td>
                                </tr>
                                <tr>
                                    <td>MacBook Air M3</td>
                                    <td>#ORD-004</td>
                                    <td>$1,099</td>
                                    <td><span class="status processing">Processing</span></td>
                                    <td><button class="btn-view">View</button></td>
                                </tr>
                                <tr>
                                    <td>iPad Pro 12.9</td>
                                    <td>#ORD-005</td>
                                    <td>$1,199</td>
                                    <td><span class="status completed">Completed</span></td>
                                    <td><button class="btn-view">View</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="right-sidebar">
                    <!-- Recent Updates -->
                    <div class="recent-updates">
                        <h2>Recent Updates</h2>
                        <div class="updates-list">
                            <div class="update-item">
                                <div class="update-icon">📦</div>
                                <div class="update-content">
                                    <p>New order received</p>
                                    <span class="update-time">2 min ago</span>
                                </div>
                            </div>
                            <div class="update-item">
                                <div class="update-icon">💳</div>
                                <div class="update-content">
                                    <p>Payment processed</p>
                                    <span class="update-time">15 min ago</span>
                                </div>
                            </div>
                            <div class="update-item">
                                <div class="update-icon">🚚</div>
                                <div class="update-content">
                                    <p>Order shipped</p>
                                    <span class="update-time">1 hour ago</span>
                                </div>
                            </div>
                            <div class="update-item">
                                <div class="update-icon">👤</div>
                                <div class="update-content">
                                    <p>New customer registered</p>
                                    <span class="update-time">3 hours ago</span>
                                </div>
                            </div>
                            <div class="update-item">
                                <div class="update-icon">✅</div>
                                <div class="update-content">
                                    <p>Order delivered</p>
                                    <span class="update-time">5 hours ago</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Analytics -->
                    <div class="sales-analytics">
                        <h2>Sales Analytics</h2>
                        <div class="analytics-cards">
                            <div class="analytics-card">
                                <div class="analytics-icon">🌐</div>
                                <div class="analytics-content">
                                    <h3>Online Orders</h3>
                                    <p class="analytics-value">1,234</p>
                                    <span class="analytics-change positive">+12.5%</span>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <div class="analytics-icon">🏪</div>
                                <div class="analytics-content">
                                    <h3>Offline Orders</h3>
                                    <p class="analytics-value">567</p>
                                    <span class="analytics-change negative">-3.2%</span>
                                </div>
                            </div>
                            <div class="analytics-card">
                                <div class="analytics-icon">👥</div>
                                <div class="analytics-content">
                                    <h3>New Customer</h3>
                                    <p class="analytics-value">89</p>
                                    <span class="analytics-change positive">+8.7%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Product Button -->
                    <button class="btn-add-product" onclick="window.location.href='?pagina=producto'">
                        <span class="btn-icon">➕</span>
                        Add Product
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        function confirmarCerrarSesion() {
            if (confirm('¿Está seguro que desea cerrar sesión?')) {
                window.location.href = '?pagina=cerrar_sesion';
            }
        }

        // Set today's date as default
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('dateFilter');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
            }
        });

        // View button functionality
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const productName = row.cells[0].textContent;
                const orderNumber = row.cells[1].textContent;
                alert(`Viewing details for: ${productName}\nOrder: ${orderNumber}`);
            });
        });
    </script>
</body>

</html>
