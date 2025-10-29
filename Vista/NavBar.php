<?php
// Obtener datos de la tasa BCV para usuarios no logeados
require_once 'config/config.php';
require_once 'modelo/DolarService.php';
$dolarService = new DolarService();
$tasaBCV = $dolarService->obtenerPrecioDolar();
$tasaBCVFormateada = number_format($tasaBCV, 2);
?>

<div class="top-bar">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid" style="background: #0863b8; padding: 0.5rem 1rem;">
            <!-- Contenedor izquierdo con logo y botón Registrar -->
            <div class="d-flex align-items-center" style="flex-grow: 1;">
                <!-- Logo y nombre -->
                <a class="navbar-brand d-flex align-items-center" href="#" style="margin-left: 15px;">
                    <img src="img/logotipo.png" alt="Logo" width="50" height="40" style="margin-right: 12px; filter: brightness(0) invert(1);">
                    <h3 style="color: white; margin: 0; font-size: 1.4rem; font-weight: 500;">Bienvenido a Casa Lai Tu Tienda Virtual</h3>
                </a>
            </div>

            <!-- Menú colapsable -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <!-- Botón de tasa de cambio -->
                    <li class="nav-item me-3">
                        <button class="icon-btn" id="tasa-cambio-btn-guest" style="background: transparent; border: none; padding: 8px;">
                            <img src="img/currency-exchange.svg" alt="Tasa BCV" class="local-icon" style="filter: brightness(0) invert(1); width: 24px; height: 24px;">
                        </button>
                    </li>
                    
                    <li class="nav-item">
                        <a href="?pagina=login">
                            <button class="btn btn-outline-light" style="border-radius: 8px; padding: 8px 20px; font-weight: 500;">
                                <i class="fas fa-sign-in-alt me-2"></i>Ingresar
                            </button>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</div>

<!-- Panel de Tasa de Cambio para usuarios no logeados -->
<div class="tasa-cambio-panel guest" id="tasa-cambio-panel-guest">
    <h2>Tipo de Cambio <img src="img/currency-exchange.svg" alt="Tasa" class="local-icon" style="width: 20px; height: 20px;"></h2>
    <div class="tasa-info">
        <div class="tasa-valor">
            <strong>1 USD = <?= $tasaBCVFormateada ?> BS</strong>
        </div>
        <div class="tasa-actualizacion">
            <small>Actualizado: <?= date('d/m/Y H:i') ?></small>
        </div>
        <div class="tasa-fuente">
            <small>Fuente: Banco Central de Venezuela</small>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
    .navbar {
        transition: all 0.3s ease;
    }
    
    .btn-outline-light:hover {
        background-color: rgba(255,255,255,0.2);
    }
    
    .navbar-brand:hover {
        opacity: 0.9;
    }
    
    .top-bar {
        position: sticky;
        top: 0;
        z-index: 1030;
    }
    
    /* Estilos para el panel de tasa de cambio */
    .tasa-cambio-panel {
    position: fixed;
    top: 70px;
    right: 20px;
    width: 280px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1002;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    padding: 0;
}

.tasa-cambio-panel.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
    
    .tasa-cambio-panel h2 {
        padding: 15px;
        margin: 0;
        border-bottom: 1px solid #eee;
        font-size: 1.1rem;
        background-color: #f8f9fa;
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .tasa-info {
        padding: 20px 15px;
    }
    
    .tasa-valor {
        font-size: 1.2rem;
        margin-bottom: 10px;
        color: #0863b8;
    }
    
    .tasa-actualizacion {
        margin-bottom: 8px;
    }
    
    .tasa-fuente {
        color: #666;
    }
    
    .tasa-no-disponible {
        text-align: center;
        color: #666;
    }
    
    .tasa-no-disponible i {
        font-size: 2rem;
        margin-bottom: 10px;
        display: block;
        color: #ffc107;
    }
    
    /* Para el panel de invitados */
    .tasa-cambio-panel.guest {
        top: 80px;
    }
</style>

<!-- Scripts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<script src="public/js/jquery.min.js"></script>
<script src="public/js/popper.min.js"></script>
<script src="javascript/js/bootstrap.min.js"></script>

<script>
    // Efecto de scroll para el navbar
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 10) {
            navbar.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
        } else {
            navbar.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
        }
    });

    // Panel de tasa de cambio para usuarios no logeados
    document.addEventListener('DOMContentLoaded', function() {
        const tasaCambioBtn = document.getElementById('tasa-cambio-btn-guest');
        const tasaCambioPanel = document.getElementById('tasa-cambio-panel-guest');
        
        if (tasaCambioBtn && tasaCambioPanel) {
            tasaCambioBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                tasaCambioPanel.classList.toggle('active');
            });
            
            // Cerrar panel al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (tasaCambioBtn && !tasaCambioBtn.contains(e.target) && 
                    tasaCambioPanel && !tasaCambioPanel.contains(e.target)) {
                    tasaCambioPanel.classList.remove('active');
                }
            });
        }
    });
</script>