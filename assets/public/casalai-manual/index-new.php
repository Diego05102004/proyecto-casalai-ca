<?php 
require_once "utils.php";
session_start();

// Determinar tipo de usuario basado en la sesión
$esCliente = isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] == 'Cliente';
$esAdministrador = isset($_SESSION['nombre_rol']) && ($_SESSION['nombre_rol'] == 'Administrador' || $_SESSION['nombre_rol'] == 'SuperUsuario');
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="img/logo.png">
    <title>Manual de Usuario - Casa Lai.Ca</title>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }
        
        .manual-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .section-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid var(--secondary-color);
        }
        
        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .nav-pills .nav-link {
            color: var(--primary-color);
            border-radius: 5px;
            margin: 5px 0;
            font-weight: 500;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .nav-pills .nav-link:hover:not(.active) {
            background-color: #f0f0f0;
        }
        
        .step-number {
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .quick-links {
            position: sticky;
            top: 20px;
        }
        
        .screenshot {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 100%;
            height: auto;
        }
        
        .note {
            background-color: #e7f4ff;
            border-left: 4px solid var(--secondary-color);
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .warning {
            background-color: #fff8e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .tip {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .hidden-section {
            display: none;
        }
        
        .access-message {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }
        
        .toc {
            position: sticky;
            top: 20px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .toc-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .toc-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        
        .toc-item {
            margin-bottom: 8px;
        }
        
        .toc-link {
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .toc-link:hover {
            background-color: #f0f7ff;
            color: var(--secondary-color);
        }
        
        .toc-link i {
            margin-right: 8px;
            color: var(--secondary-color);
        }
        
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        
        .feature-card {
            height: 100%;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .feature-card .card-body {
            padding: 1.5rem;
        }
        
        .feature-card .icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: rgba(52, 152, 219, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .feature-card .icon-wrapper i {
            font-size: 1.5rem;
            color: var(--secondary-color);
        }
        
        .feature-card .card-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .feature-card .card-text {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="manual-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-3">Manual de Usuario</h1>
                    <p class="lead mb-0">Guía completa para el uso del Sistema de Gestión de Inventario y Ventas</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <img src="img/logo-lg.png" alt="Casa Lai.Ca" style="height: 85px;">
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="row">
            <!-- Sidebar Navigation -->
<!-- Sidebar Navigation -->
<div class="col-lg-3 mb-4">
    <div class="toc">
        <h5 class="toc-title">Tabla de Contenidos</h5>
        <ul class="toc-list">
            <li class="toc-item"><a href="#introduccion" class="toc-link"><i class="bi bi-house-door"></i> Introducción</a></li>
            
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <li class="toc-item"><a href="#dashboard" class="toc-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="toc-item"><a href="#mi-cuenta" class="toc-link"><i class="bi bi-person"></i> Mi Cuenta</a></li>
                
                <?php if ($esAdministrador): ?>
                    <!-- Módulo de Perfiles -->
                    <li class="toc-item">
                        <a href="#administrar-perfiles" class="toc-link"><i class="bi bi-people"></i> Administrar Perfiles</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#gestion-usuarios" class="toc-link">Gestionar Usuarios</a></li>
                            <li><a href="#gestion-roles" class="toc-link">Gestionar Roles</a></li>
                            <li><a href="#gestion-permisos" class="toc-link">Gestionar Permisos</a></li>
                        </ul>
                    </li>

                    <!-- Módulo de Inventario -->
                    <li class="toc-item">
                        <a href="#gestion-inventario" class="toc-link"><i class="bi bi-box-seam"></i> Gestión de Inventario</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#gestion-productos" class="toc-link">Gestionar Productos</a></li>
                            <li><a href="#gestion-categorias" class="toc-link">Gestionar Categorías</a></li>
                            <li><a href="#gestion-marcas" class="toc-link">Gestionar Marcas</a></li>
                            <li><a href="#gestion-modelos" class="toc-link">Gestionar Modelos</a></li>
                            <li><a href="#reportes-inventario" class="toc-link">Reportes de Inventario</a></li>
                        </ul>
                    </li>

                    <!-- Módulo de Compras -->
                    <li class="toc-item">
                        <a href="#gestion-compras" class="toc-link"><i class="bi bi-cart-plus"></i> Gestión de Compras</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#gestion-proveedores" class="toc-link">Gestionar Proveedores</a></li>
                            <li><a href="#reporte-proveedores" class="toc-link">Reporte de Proveedores</a></li>
                            <li><a href="#gestion-recepcion" class="toc-link">Gestión de Recepción</a></li>
                        </ul>
                    </li>

                    <!-- Módulo de Ventas -->
                    <li class="toc-item">
                        <a href="#gestion-ventas" class="toc-link"><i class="bi bi-currency-dollar"></i> Gestión de Ventas</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#catalogo" class="toc-link">Catálogo</a></li>
                            <li><a href="#gestion-pedidos" class="toc-link">Gestión de Pedidos</a></li>
                            <li><a href="#gestion-clientes" class="toc-link">Gestión de Clientes</a></li>
                        </ul>
                    </li>

                    <!-- Módulo de Finanzas -->
                    <li class="toc-item">
                        <a href="#gestion-finanzas" class="toc-link"><i class="bi bi-cash-stack"></i> Gestión Financiera</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#gestion-cuentas" class="toc-link">Gestión de Cuentas Bancarias</a></li>
                            <li><a href="#ingresos-egresos" class="toc-link">Ingresos y Egresos</a></li>
                            <li><a href="#reportes-financieros" class="toc-link">Reportes Financieros</a></li>
                        </ul>
                    </li>

                    <!-- Módulo de Logística -->
                    <li class="toc-item">
                        <a href="#gestion-logistica" class="toc-link"><i class="bi bi-truck"></i> Gestión Logística</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#ordenes-despacho" class="toc-link">Órdenes de Despacho</a></li>
                            <li><a href="#gestion-despachos" class="toc-link">Gestión de Despachos</a></li>
                        </ul>
                    </li>

                    <!-- Módulo de Seguridad -->
                    <li class="toc-item">
                        <a href="#gestion-seguridad" class="toc-link"><i class="bi bi-shield-lock"></i> Seguridad</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#bitacora" class="toc-link">Bitácora del Sistema</a></li>
                            <li><a href="#copias-seguridad" class="toc-link">Copias de Seguridad</a></li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($esCliente): ?>
                    <!-- Módulo de Cliente -->
                    <li class="toc-item">
                        <a href="#cliente" class="toc-link"><i class="bi bi-person"></i> Área de Cliente</a>
                        <ul class="toc-sublist ms-3 mt-2">
                            <li><a href="#catalogo-cliente" class="toc-link">Catálogo de Productos</a></li>
                            <li><a href="#mis-pedidos" class="toc-link">Mis Pedidos</a></li>
                            <li><a href="#mis-facturas" class="toc-link">Mis Facturas</a></li>
                            <li><a href="#mis-datos" class="toc-link">Mis Datos</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            <?php else: ?>
                <li class="toc-item"><a href="#iniciar-sesion" class="toc-link"><i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión</a></li>
            <?php endif; ?>
            
            <li class="toc-item"><a href="#preguntas-frecuentes" class="toc-link"><i class="bi bi-question-circle"></i> Preguntas Frecuentes</a></li>
        </ul>
    </div>
</div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Introduction Section -->
                <section id="introduccion" class="section-card">
                    <h2 class="section-title">Introducción</h2>
                    <div class="row">
                        <div class="col-md-8">
                            <p>Bienvenido al <strong>Sistema de Gestión de Inventario y Ventas</strong> de <strong>Casa Lai, C.A.</strong> Esta plataforma ha sido diseñada para optimizar y agilizar los procesos de gestión de inventario, ventas y administración de su negocio.</p>
                            
                            <div class="note">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Nota:</strong> Este manual le guiará a través de todas las funcionalidades del sistema según su rol de usuario.
                            </div>
                            
                            <h4 class="mt-4 mb-3">Características Principales</h4>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="feature-card card h-100 card-hover">
                                        <div class="card-body text-center">
                                            <div class="icon-wrapper mx-auto">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                            <h5 class="card-title">Gestión de Inventario</h5>
                                            <p class="card-text">Control completo sobre el inventario de productos, categorías y existencias.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-card card h-100 card-hover">
                                        <div class="card-body text-center">
                                            <div class="icon-wrapper mx-auto">
                                                <i class="bi bi-cart"></i>
                                            </div>
                                            <h5 class="card-title">Ventas y Facturación</h5>
                                            <p class="card-text">Proceso de venta simplificado con generación de facturas electrónicas.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-card card h-100 card-hover">
                                        <div class="card-body text-center">
                                            <div class="icon-wrapper mx-auto">
                                                <i class="bi bi-people"></i>
                                            </div>
                                            <h5 class="card-title">Gestión de Clientes</h5>
                                            <p class="card-text">Mantenga un registro detallado de sus clientes y su historial de compras.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="feature-card card h-100 card-hover">
                                        <div class="card-body text-center">
                                            <div class="icon-wrapper mx-auto">
                                                <i class="bi bi-graph-up"></i>
                                            </div>
                                            <h5 class="card-title">Reportes y Análisis</h5>
                                            <p class="card-text">Genere informes detallados para el análisis del rendimiento del negocio.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Acceso Rápido</h5>
                                    <div class="d-grid gap-2">
                                        <a href="#dashboard" class="btn btn-outline-primary text-start"><i class="bi bi-speedometer2 me-2"></i> Ir al Dashboard</a>
                                        <?php if ($esCliente): ?>
                                            <a href="#seccion-cliente" class="btn btn-outline-primary text-start"><i class="bi bi-cart me-2"></i> Ver Sección Cliente</a>
                                        <?php endif; ?>
                                        <a href="#preguntas-frecuentes" class="btn btn-outline-secondary text-start mt-3"><i class="bi bi-question-circle me-2"></i> Preguntas Frecuentes</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-body">
                                    <h5 class="card-title">Soporte Técnico</h5>
                                    <p class="card-text small">¿Necesita ayuda? Nuestro equipo de soporte está disponible para asistirle.</p>
                                    <a href="#soporte" class="btn btn-outline-success w-100"><i class="bi bi-headset me-2"></i> Contactar Soporte</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

<!-- Dashboard Section -->
<section id="dashboard" class="section-card">
    <h2 class="section-title">
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </h2>
    
    <div class="row">
        <div class="">
            <p>El Dashboard es el centro de control principal del sistema. Aquí encontrará un resumen de la información más relevante según su rol de usuario.</p>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Vista General</h5>
                    <p>Al iniciar sesión, será dirigido al Dashboard que muestra:</p>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <?= renderImagen("dashboard", "vista2.png") ?>
                            <p class="text-muted small mt-2">Vista principal del Dashboard</p>
                        </div>
                        <div class="col-md-6">
                            <?= renderImagen("dashboard", "barra-lateral.png") ?>
                            <p class="text-muted small mt-2">Barra lateral de navegación</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Barra Superior</h5>
                    <p>En la parte superior derecha encontrará:</p>
                    <div class="text-center mb-3">
                        <?= renderImagen("dashboard", "perfil2.png") ?>
                    </div>
                    <ul>
                        <li><strong>Icono de Conversión de Dólar</strong>: Muestra la tasa de cambio actual del BCV.</li>
                        <li><strong>Icono de Notificaciones</strong>: Muestra las notificaciones recientes del sistema.</li>
                        <li><strong>Icono de Ayuda</strong>: Acceso directo a este manual de usuario.</li>
                        <li><strong>Perfil de Usuario</strong>: Muestra su nombre y rol actual.</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Conversión de Dólar</h5>
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p>Para consultar la tasa de cambio:</p>
                            <ol>
                                <li>Haga clic en el icono de conversión de dólar <i class="bi bi-currency-exchange"></i></li>
                                <li>Se mostrará un panel con las tasas actualizadas</li>
                                <li>Las tasas se actualizan automáticamente según el BCV</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <?= renderImagen("dashboard", "conversion-dolar-abierto.png") ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Notificaciones</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p>El sistema le notificará sobre:</p>
                            <ul>
                                <li>Nuevos mensajes</li>
                                <li>Actualizaciones del sistema</li>
                                <li>Actividad reciente</li>
                                <li>Recordatorios importantes</li>
                            </ul>
                            <p>Para ver todas las notificaciones, haga clic en "Ver más" en el panel de notificaciones.</p>
                        </div>
                        <div class="col-md-6">
                            <?= renderImagen("dashboard", "notificaciones-abiertas.png") ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        

    </div>
    
    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle-fill me-2"></i>
        El Dashboard se adapta automáticamente según su rol de usuario, mostrando solo la información relevante para sus funciones.
    </div>
</section>
                <!-- Mi Cuenta Section -->
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <section id="mi-cuenta" class="section-card">
                        <h2 class="section-title">Mi Cuenta</h2>
                        <p>Administre la información de su perfil y preferencias de usuario.</p>
                        
                        <?php
                        $datos_perfil = [
                            "id" => "perfil",
                            "nombre_singular" => "Perfil",
                            "gestionable" => [
                                "Información Personal",
                                "Cambio de Contraseña",
                                "Cambio de Correo Electronico"
                            ],
                            "instrucciones" => [
                                "Actualice sus datos personales",
                                "Cambie su contraseña regularmente",
                                "Cambie su correo electrónico"
                            ]
                        ];
                        plantilla("inicio-perfil", $datos_perfil);
                        ?>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h4>Acceso al Perfil</h4>
                                <p>Haga clic en su nombre de usuario en la esquina superior derecha para acceder a su perfil.</p>
                                <?= renderImagen("dashboard", "mi-cuenta.png") ?>
                            </div>
                            <div class="col-md-6">
                                <h4>Editar Información Personal</h4>
                                <p>En la sección de perfil podrá actualizar su información personal.</p>
                                <?= renderImagen("perfil", "perfil-informacion-personal.png") ?>
                            </div>
                            <div class="col-md-6">
                                <h4>Editar Contraseña</h4>
                                <p>En la sección de perfil podrá actualizar y cambiar su contraseña.</p>
                                <?= renderImagen("perfil", "perfil-password.png") ?>
                            </div>
                            <div class="col-md-6">
                                <h4>Editar Correo   </h4>
                                <p>En la sección de perfil podrá actualizar y cambiar su correo Electronico.</p>
                                <?= renderImagen("perfil", "perfil-cuenta.png") ?>
                            </div>
                        </div>
                        
                        <div class="warning mt-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Importante:</strong> Mantenga su informacion personal y contraseña segura y no la comparta con nadie por seguridad.
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Sección Clientes -->
                <?php /* if ($esCliente): */ ?>
                <section id="seccion-cliente" class="section-card">
                    <h2 class="section-title">
                        <i class="bi bi-person me-2"></i>Sección para Clientes
                    </h2>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <p>Como cliente, tendrá acceso a las siguientes funcionalidades para realizar sus compras de manera sencilla y segura.</p>
                            
                            <!-- Catálogo de Productos -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-grid-3x3-gap me-2"></i>Catálogo de Productos
                                    </h5>
                                    <p>Acceda al catálogo completo de productos disponibles para su compra.</p>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <?= renderImagen("catalogo", "vista.png") ?>
                                            <p class="text-muted small mt-2">Vista principal del catálogo</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Características:</h6>
                                            <ul>
                                                <li>Navegación por categorías</li>
                                                <li>Búsqueda de productos</li>
                                                <li>Filtros avanzados</li>
                                                <li>Visualización de precios</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        En la parte superior encontrará pestañas para acceder a <strong>Combos Promocionales</strong> con ofertas especiales.
                                    </div>
                                    
                                    <!-- Pasos para agregar productos al carrito -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">Pasos para Agregar Productos al Carrito</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <ol>
                                                        <li class="mb-2">Navegue por el catálogo y encuentre el producto deseado</li>
                                                        <li class="mb-2">Haga clic en el botón <strong>"Agregar"</strong> en la parte izquierda del producto</li>
                                                        <li class="mb-2">El producto se añadirá automáticamente a su carrito</li>
                                                        <li>Verá un mensaje de confirmación y el contador del carrito se actualizará</li>
                                                    </ol>
                                                </div>
                                                <div class="col-md-6">
                                                    <?= renderImagen("catalogo", "agregar.png") ?>
                                                    <p class="text-muted small mt-2">Botón para agregar producto al carrito</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Combos Promocionales -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-tags me-2"></i>Combos Promocionales
                                    </h5>
                                    <p>Descubra nuestras ofertas especiales y paquetes con descuento.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <?= renderImagen("catalogo", "vista-2.png") ?>
                                            <p class="text-muted small mt-2">Vista de combos promocionales</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Ventajas de los combos:</h6>
                                            <ul>
                                                <li>Precios especiales</li>
                                                <li>Productos complementarios</li>
                                                <li>Ahorro garantizado</li>
                                                <li>Stock limitado</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Carrito de Compras -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-cart3 me-2"></i>Gestión del Carrito
                                    </h5>
                                    <p>Administre los productos que desea comprar antes de finalizar su pedido.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <?= renderImagen("carrito", "carrito.png") ?>
                                            <p class="text-muted small mt-2">Vista del carrito de compras</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Operaciones disponibles:</h6>
                                            <ul>
                                                <li><strong>Ajustar cantidad</strong>: Aumentar o disminuir unidades</li>
                                                <li><strong>Eliminar producto</strong>: Quitar items individuales</li>
                                                <li><strong>Vaciar carrito</strong>: Eliminar todo el contenido</li>
                                                <li><strong>Actualizar totales</strong>: Ver costos en tiempo real</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <!-- Pasos detallados para gestionar el carrito -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0">Pasos para Gestionar el Carrito</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h6 class="text-primary">1. Ajustar Cantidad de Productos</h6>
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <ol>
                                                                <li>Ubique el producto que desea modificar</li>
                                                                <li>Use los botones <strong>+</strong> y <strong>-</strong> para aumentar o disminuir la cantidad</li>
                                                                <li>El total se actualizará automáticamente</li>
                                                                <li>Puede ingresar directamente la cantidad deseada</li>
                                                            </ol>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="alert alert-light border">
                                                                <i class="bi bi-plus-circle text-success me-2"></i>
                                                                <i class="bi bi-dash-circle text-danger me-2"></i>
                                                                Botones de cantidad
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <hr>
                                            
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h6 class="text-primary">2. Eliminar Productos del Carrito</h6>
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <ol>
                                                                <li>Encuentre el producto que desea eliminar</li>
                                                                <li>Haga clic en el ícono de <strong>basura</strong> 🗑️ junto al producto</li>
                                                                <li>Confirme la eliminación en el mensaje emergente</li>
                                                                <li>El producto será removido y el total actualizado</li>
                                                            </ol>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="alert alert-light border">
                                                                <i class="bi bi-trash text-danger me-2"></i>
                                                                Eliminar producto
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <hr>
                                            
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <h6 class="text-primary">3. Vaciar Todo el Carrito</h6>
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <ol>
                                                                <li>Haga clic en el botón <strong>"Vaciar Carrito"</strong></li>
                                                                <li>Confirme que desea eliminar todos los productos</li>
                                                                <li>El carrito quedará completamente vacío</li>
                                                                <li>Podrá comenzar a agregar nuevos productos</li>
                                                            </ol>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="alert alert-warning border">
                                                                <i class="bi bi-trash3 text-warning me-2"></i>
                                                                Vaciar carrito
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Prefactura -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-file-earmark-text me-2"></i>Prefacturar Compra
                                    </h5>
                                    <p>Genere una prefactura para revisar su pedido antes de confirmar la compra.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <?= renderImagen("carrito", "prefacturar.png") ?>
                                            <p class="text-muted small mt-2">Proceso de prefacturación</p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Proceso de prefacturación:</h6>
                                            <ol>
                                                <li>Revise los productos seleccionados</li>
                                                <li>Confirme las cantidades</li>
                                                <li>Verifique los totales</li>
                                                <li>Haga clic en "Prefacturar"</li>
                                                <li>Recibirá un resumen de su pedido</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Guía Rápida</h5>
                                </div>
                                <div class="card-body">
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">1</div>
                                            <div>
                                                <h6 class="mb-1">Explorar Catálogo</h6>
                                                <p class="small text-muted mb-0">Navegue por productos y combos</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">2</div>
                                            <div>
                                                <h6 class="mb-1">Agregar al Carrito</h6>
                                                <p class="small text-muted mb-0">Seleccione los productos deseados</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">3</div>
                                            <div>
                                                <h6 class="mb-1">Revisar Carrito</h6>
                                                <p class="small text-muted mb-0">Ajuste cantidades si es necesario</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step">
                                        <div class="d-flex">
                                            <div class="step-number">4</div>
                                            <div>
                                                <h6 class="mb-1">Prefacturar</h6>
                                                <p class="small text-muted mb-0">Genere su resumen de compra</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Consejos de Compra</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <i class="bi bi-tag-fill text-warning me-2"></i>
                                            Aproveche los combos promocionales
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-search text-primary me-2"></i>
                                            Use el buscador para encontrar productos
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-heart-fill text-danger me-2"></i>
                                            Guarde sus productos favoritos
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-bell-fill text-info me-2"></i>
                                            Reciba notificaciones de ofertas
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php /* endif; */ ?>

                <!-- Sección Almacenista -->
                <?php /* if ($esAdministrador): */ ?>
                <section id="seccion-almacenista" class="section-card">
                    <h2 class="section-title">
                        <i class="bi bi-box-seam me-2"></i>Sección para Almacenistas
                    </h2>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <p>Como almacenista, tendrá acceso completo a la gestión de inventario y control de productos del sistema.</p>
                            
                            <!-- Recepción -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-truck me-2"></i>Recepción de Productos
                                    </h5>
                                    <p>Registre la entrada de nuevos productos al inventario desde proveedores.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información gestionable:</h6>
                                            <ul>
                                                <li>Fecha de recepción</li>
                                                <li>Correlación</li>
                                                <li>Proveedor</li>
                                                <li>Producto</li>
                                                <li>Cantidad recibida</li>
                                                <li>Costo de inversión</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Proceso de recepción:</h6>
                                            <ol>
                                                <li>Ingrese el correlativo del producto</li>
                                                <li>Seleccione el proveedor</li>
                                                <li>Elija los productos de la lista</li>
                                                <li>Registre cantidades y costos</li>
                                                <li>Confirme la recepción</li>
                                            </ol>
                                        </div>
                                    </div>
                                    
                                    <!-- Pasos detallados para incluir recepción -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">Pasos para Incluir Nueva Recepción</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <ol>
                                                        <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"Nueva Recepción"</strong> en la parte superior derecha</li>
                                                        <li class="mb-2"><strong>Paso 2:</strong> Ingrese el <strong>correlativo</strong> único para la recepción</li>
                                                        <li class="mb-2"><strong>Paso 3:</strong> Seleccione el <strong>proveedor</strong> de la lista desplegable</li>
                                                        <li class="mb-2"><strong>Paso 4:</strong> Haga clic en <strong>"Agregar Producto"</strong> y seleccione de la lista</li>
                                                        <li class="mb-2"><strong>Paso 5:</strong> Ingrese la <strong>cantidad</strong> recibida y el <strong>costo unitario</strong></li>
                                                        <li class="mb-2"><strong>Paso 6:</strong> Repita el paso 4-5 para cada producto</li>
                                                        <li class="mb-2"><strong>Paso 7:</strong> Revise el resumen y haga clic en <strong>"Guardar Recepción"</strong></li>
                                                    </ol>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="alert alert-light border">
                                                        <i class="bi bi-plus-circle text-success me-2"></i>
                                                        <strong>Nueva Recepción</strong>
                                                        <br><small>Botón principal</small>
                                                    </div>
                                                    <div class="alert alert-info border mt-2">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        <strong>Tip:</strong> Puede agregar y remover productos antes de guardar
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Pasos para modificar recepción -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0">Pasos para Modificar Recepción</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <ol>
                                                        <li class="mb-2"><strong>Paso 1:</strong> Busque la recepción en la lista principal</li>
                                                        <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de <strong>editar</strong> ✏️ junto a la recepción</li>
                                                        <li class="mb-2"><strong>Paso 3:</strong> Modifique los datos necesarios (proveedor, productos, cantidades)</li>
                                                        <li class="mb-2"><strong>Paso 4:</strong> Puede agregar nuevos productos o remover existentes</li>
                                                        <li class="mb-2"><strong>Paso 5:</strong> Haga clic en <strong>"Actualizar Recepción"</strong> para guardar cambios</li>
                                                    </ol>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="alert alert-light border">
                                                        <i class="bi bi-pencil text-warning me-2"></i>
                                                        <strong>Modificar</strong>
                                                        <br><small>Ícono de edición</small>
                                                    </div>
                                                    <div class="alert alert-warning border mt-2">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        <strong>Nota:</strong> Solo se pueden modificar recepciones no procesadas
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="note mt-3">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        Puede agregar y remover productos de la recepción si es necesario antes de confirmar.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Despacho -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-box-arrow-right me-2"></i>Despacho de Productos
                                    </h5>
                                    <p>Gestione la salida de productos del inventario hacia los clientes.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información gestionable:</h6>
                                            <ul>
                                                <li>Fecha de despacho</li>
                                                <li>Correlación</li>
                                                <li>Cliente</li>
                                                <li>Producto</li>
                                                <li>Cantidad despachada</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Proceso de despacho:</h6>
                                            <ol>
                                                <li>Ingrese el correlativo</li>
                                                <li>Seleccione el cliente</li>
                                                <li>Elija los productos</li>
                                                <li>Verifique el stock disponible</li>
                                                <li>Confirme el despacho</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Marcas -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-tag me-2"></i>Gestión de Marcas
                                    </h5>
                                    <p>Administre las marcas de productos disponibles en el sistema.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Operaciones disponibles:</h6>
                                            <ul>
                                                <li><strong>Agregar</strong>: Nueva marca</li>
                                                <li><strong>Modificar</strong>: Editar nombre existente</li>
                                                <li><strong>Eliminar</strong>: Remover marca del sistema</li>
                                                <li><strong>Consultar</strong>: Ver lista de marcas</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Información requerida:</h6>
                                            <ul>
                                                <li>Nombre de la marca</li>
                                                <li>Descripción (opcional)</li>
                                                <li>Estado (activo/inactivo)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modelos -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-layers me-2"></i>Gestión de Modelos
                                    </h5>
                                    <p>Administre los modelos de productos asociados a cada marca.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Operaciones disponibles:</h6>
                                            <ul>
                                                <li><strong>Agregar</strong>: Nuevo modelo</li>
                                                <li><strong>Modificar</strong>: Editar modelo existente</li>
                                                <li><strong>Eliminar</strong>: Remover modelo</li>
                                                <li><strong>Consultar</strong>: Ver lista de modelos</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Información requerida:</h6>
                                            <ul>
                                                <li>Seleccionar una marca</li>
                                                <li>Nombre del modelo</li>
                                                <li>Descripción (opcional)</li>
                                                <li>Estado (activo/inactivo)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Productos -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-box me-2"></i>Gestión de Productos
                                    </h5>
                                    <p>Administre el catálogo completo de productos del inventario.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información gestionable:</h6>
                                            <ul>
                                                <li>Foto del producto</li>
                                                <li>Nombre y descripción</li>
                                                <li>Stock Actual/Máximo/Mínimo</li>
                                                <li>Número de serial</li>
                                                <li>Cláusula de garantía</li>
                                                <li>Categoría</li>
                                                <li>Precio</li>
                                                <li>Estado</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Operaciones disponibles:</h6>
                                            <ul>
                                                <li><strong>Incluir</strong>: Nuevo producto</li>
                                                <li><strong>Modificar</strong>: Actualizar datos</li>
                                                <li><strong>Eliminar</strong>: Remover producto</li>
                                                <li><strong>Reporte</strong>: Generar informes</li>
                                                <li><strong>Estado</strong>: Cambiar disponibilidad</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <!-- Pasos detallados para incluir producto -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">Pasos para Incluir Nuevo Producto</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <ol>
                                                        <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"Nuevo Producto"</strong> en la parte superior</li>
                                                        <li class="mb-2"><strong>Paso 2:</strong> Complete el <strong>nombre del producto</strong> (campo obligatorio)</li>
                                                        <li class="mb-2"><strong>Paso 3:</strong> Agregue una <strong>descripción detallada</strong> del producto</li>
                                                        <li class="mb-2"><strong>Paso 4:</strong> Suba la <strong>foto del producto</strong> (formato JPG/PNG)</li>
                                                        <li class="mb-2"><strong>Paso 5:</strong> Seleccione la <strong>categoría</strong> correspondiente</li>
                                                        <li class="mb-2"><strong>Paso 6:</strong> Configure el <strong>stock</strong> (actual, mínimo, máximo)</li>
                                                        <li class="mb-2"><strong>Paso 7:</strong> Ingrese el <strong>número de serial</strong> si aplica</li>
                                                        <li class="mb-2"><strong>Paso 8:</strong> Establezca el <strong>precio de venta</strong></li>
                                                        <li class="mb-2"><strong>Paso 9:</strong> Agregue la <strong>cláusula de garantía</strong></li>
                                                        <li class="mb-2"><strong>Paso 10:</strong> Seleccione el <strong>estado</strong> (activo/inactivo)</li>
                                                        <li class="mb-2"><strong>Paso 11:</strong> Haga clic en <strong>"Guardar Producto"</strong></li>
                                                    </ol>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="alert alert-light border">
                                                        <i class="bi bi-plus-circle text-success me-2"></i>
                                                        <strong>Nuevo Producto</strong>
                                                        <br><small>Botón principal</small>
                                                    </div>
                                                    <div class="alert alert-info border mt-2">
                                                        <i class="bi bi-camera me-2"></i>
                                                        <strong>Imagen:</strong> Tamaño recomendado 500x500px
                                                    </div>
                                                    <div class="alert alert-warning border mt-2">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        <strong>Requerido:</strong> Nombre, categoría, precio
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Pasos para modificar producto -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0">Pasos para Modificar Producto</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <ol>
                                                        <li class="mb-2"><strong>Paso 1:</strong> Busque el producto en la lista usando el buscador</li>
                                                        <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de <strong>editar</strong> ✏️ en la parte izquierda</li>
                                                        <li class="mb-2"><strong>Paso 3:</strong> Modifique los campos necesarios (nombre, descripción, precio, etc.)</li>
                                                        <li class="mb-2"><strong>Paso 4:</strong> Puede cambiar la <strong>foto del producto</strong> si lo desea</li>
                                                        <li class="mb-2"><strong>Paso 5:</strong> Actualice el <strong>stock</strong> si ha habido movimientos</li>
                                                        <li class="mb-2"><strong>Paso 6:</strong> Modifique la <strong>cláusula de garantía</strong> si es necesario</li>
                                                        <li class="mb-2"><strong>Paso 7:</strong> Cambie el <strong>estado</strong> (activo/inactivo) si requiere</li>
                                                        <li class="mb-2"><strong>Paso 8:</strong> Haga clic en <strong>"Actualizar Producto"</strong></li>
                                                    </ol>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="alert alert-light border">
                                                        <i class="bi bi-pencil text-warning me-2"></i>
                                                        <strong>Modificar</strong>
                                                        <br><small>Ícono en parte izquierda</small>
                                                    </div>
                                                    <div class="alert alert-info border mt-2">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        <strong>Tip:</strong> Puede actualizar todos los campos incluyendo la imagen
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Pasos para eliminar producto -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="mb-0">Pasos para Eliminar Producto</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <ol>
                                                        <li class="mb-2"><strong>Paso 1:</strong> Localice el producto que desea eliminar</li>
                                                        <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de <strong>eliminar</strong> 🗑️ en la parte izquierda</li>
                                                        <li class="mb-2"><strong>Paso 3:</strong> Lea el mensaje de confirmación cuidadosamente</li>
                                                        <li class="mb-2"><strong>Paso 4:</strong> Confirme que desea eliminar el producto</li>
                                                        <li class="mb-2"><strong>Paso 5:</strong> El producto y todos sus datos serán eliminados permanentemente</li>
                                                    </ol>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="alert alert-light border">
                                                        <i class="bi bi-trash text-danger me-2"></i>
                                                        <strong>Eliminar</strong>
                                                        <br><small>Ícono en parte izquierda</small>
                                                    </div>
                                                    <div class="alert alert-danger border mt-2">
                                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                        <strong>¡Cuidado!</strong> Esta acción no se puede deshacer
                                                    </div>
                                                    <div class="alert alert-warning border mt-2">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        Se eliminará la imagen asociada
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Al eliminar un producto, se eliminarán todos sus datos incluyendo la imagen asociada.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Categorías -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-folder me-2"></i>Gestión de Categorías
                                    </h5>
                                    <p>Administre las categorías para organizar mejor el catálogo de productos.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información gestionable:</h6>
                                            <ul>
                                                <li>Nombre de la categoría</li>
                                                <li>Características</li>
                                                <li>Descripción</li>
                                                <li>Estado</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Requisitos importantes:</h6>
                                            <ul>
                                                <li>Debe haber al menos una característica</li>
                                                <li>Nombre único</li>
                                                <li>Descripción clara</li>
                                                <li>Estado definido</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="note mt-3">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        Las categorías ayudan a organizar y filtrar productos en el catálogo.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Flujo de Trabajo</h5>
                                </div>
                                <div class="card-body">
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">1</div>
                                            <div>
                                                <h6 class="mb-1">Configurar Categorías</h6>
                                                <p class="small text-muted mb-0">Establezca la estructura base</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">2</div>
                                            <div>
                                                <h6 class="mb-1">Agregar Marcas</h6>
                                                <p class="small text-muted mb-0">Registre los proveedores</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">3</div>
                                            <div>
                                                <h6 class="mb-1">Crear Modelos</h6>
                                                <p class="small text-muted mb-0">Defina las variantes</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step mb-3">
                                        <div class="d-flex">
                                            <div class="step-number">4</div>
                                            <div>
                                                <h6 class="mb-1">Registrar Productos</h6>
                                                <p class="small text-muted mb-0">Complete el inventario</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="step">
                                        <div class="d-flex">
                                            <div class="step-number">5</div>
                                            <div>
                                                <h6 class="mb-1">Gestionar Movimientos</h6>
                                                <p class="small text-muted mb-0">Controlar entradas y salidas</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0">Alertas de Inventario</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">
                                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                                            Stock mínimo alcanzado
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                            Producto sin stock
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-arrow-up-circle-fill text-info me-2"></i>
                                            Stock máximo excedido
                                        </li>
                                        <li class="list-group-item">
                                            <i class="bi bi-clock-fill text-primary me-2"></i>
                                            Productos por recibir
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php /* endif; */ ?>

                <!-- Sección Administrador -->
                <?php /* if ($esAdministrador): */ ?>
                <section id="seccion-administrador" class="section-card">
                    <h2 class="section-title">
                        <i class="bi bi-shield-check me-2"></i>Sección para Administradores
                    </h2>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <p>Como administrador, tendrá control total sobre el sistema incluyendo gestión de usuarios, finanzas y configuración general.</p>
                            
                            <!-- Proveedores -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-building me-2"></i>Gestión de Proveedores
                                    </h5>
                                    <p>Administre la información de todos los proveedores de productos y servicios.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información gestionable:</h6>
                                            <ul>
                                                <li>Nombre del proveedor</li>
                                                <li>RIF/Cédula</li>
                                                <li>Teléfono y correo</li>
                                                <li>Dirección</li>
                                                <li>Contacto principal</li>
                                                <li>Tipo de proveedor</li>
                                                <li>Estado (activo/inactivo)</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Operaciones disponibles:</h6>
                                            <ul>
                                                <li><strong>Agregar</strong>: Nuevo proveedor</li>
                                                <li><strong>Modificar</strong>: Actualizar datos</li>
                                                <li><strong>Eliminar</strong>: Remover proveedor</li>
                                                <li><strong>Consultar</strong>: Ver lista completa</li>
                                                <li><strong>Reporte</strong>: Generar informes</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Clientes -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-people me-2"></i>Gestión de Clientes
                                    </h5>
                                    <p>Administre la base de datos de clientes del sistema.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información gestionable:</h6>
                                            <ul>
                                                <li>Nombre completo</li>
                                                <li>Cédula/RIF</li>
                                                <li>Teléfono y correo</li>
                                                <li>Dirección de entrega</li>
                                                <li>Historial de compras</li>
                                                <li>Estado de cuenta</li>
                                                <li>Preferencias</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Operaciones disponibles:</h6>
                                            <ul>
                                                <li><strong>Agregar</strong>: Nuevo cliente</li>
                                                <li><strong>Modificar</strong>: Actualizar datos</li>
                                                <li><strong>Eliminar</strong>: Remover cliente</li>
                                                <li><strong>Consultar</strong>: Ver historial</li>
                                                <li><strong>Reporte</strong>: Estadísticas de compras</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bancos -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-bank me-2"></i>Gestión de Cuentas Bancarias
                                    </h5>
                                    <p>Administre las cuentas bancarias para transacciones financieras.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información gestionable:</h6>
                                            <ul>
                                                <li>Nombre del banco</li>
                                                <li>Número de cuenta</li>
                                                <li>Tipo de cuenta</li>
                                                <li>Titular de la cuenta</li>
                                                <li>Moneda</li>
                                                <li>Estado</li>
                                                <li>Saldo actual</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Operaciones disponibles:</h6>
                                            <ul>
                                                <li><strong>Agregar</strong>: Nueva cuenta</li>
                                                <li><strong>Modificar</strong>: Actualizar datos</li>
                                                <li><strong>Eliminar</strong>: Cerrar cuenta</li>
                                                <li><strong>Consultar</strong>: Ver movimientos</li>
                                                <li><strong>Conciliación</strong>: Balance de cuentas</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Usuarios -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-person-badge me-2"></i>Gestión de Usuarios del Sistema
                                    </h5>
                                    <p>Administre los usuarios que tienen acceso al sistema.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información gestionable:</h6>
                                            <ul>
                                                <li>Nombre de usuario</li>
                                                <li>Contraseña</li>
                                                <li>Correo electrónico</li>
                                                <li>Rol asignado</li>
                                                <li>Permisos específicos</li>
                                                <li>Estado de cuenta</li>
                                                <li>Último acceso</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Operaciones disponibles:</h6>
                                            <ul>
                                                <li><strong>Agregar</strong>: Nuevo usuario</li>
                                                <li><strong>Modificar</strong>: Actualizar datos</li>
                                                <li><strong>Eliminar</strong>: Desactivar usuario</li>
                                                <li><strong>Resetear</strong>: Cambiar contraseña</li>
                                                <li><strong>Auditoría</strong>: Ver actividad</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <!-- Pasos detallados para incluir usuario -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">Pasos para Incluir Nuevo Usuario</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <ol>
                                                        <li class="mb-2"><strong>Paso 1:</strong> Haga clic en el botón <strong>"Nuevo Usuario"</strong> en la parte superior</li>
                                                        <li class="mb-2"><strong>Paso 2:</strong> Ingrese el <strong>nombre de usuario</strong> (único en el sistema)</li>
                                                        <li class="mb-2"><strong>Paso 3:</strong> Proporcione el <strong>correo electrónico</strong> válido</li>
                                                        <li class="mb-2"><strong>Paso 4:</strong> Asigne una <strong>contraseña temporal</strong> o genere una automática</li>
                                                        <li class="mb-2"><strong>Paso 5:</strong> Seleccione el <strong>rol</strong> apropiado (Administrador, Almacenista, Vendedor, Cliente)</li>
                                                        <li class="mb-2"><strong>Paso 6:</strong> Configure los <strong>permisos específicos</strong> según el rol</li>
                                                        <li class="mb-2"><strong>Paso 7:</strong> Establezca el <strong>estado inicial</strong> (activo/inactivo)</li>
                                                        <li class="mb-2"><strong>Paso 8:</strong> Haga clic en <strong>"Crear Usuario"</strong></li>
                                                        <li class="mb-2"><strong>Paso 9:</strong> Envíe las credenciales al usuario por correo</li>
                                                    </ol>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="alert alert-light border">
                                                        <i class="bi bi-person-plus text-success me-2"></i>
                                                        <strong>Nuevo Usuario</strong>
                                                        <br><small>Botón principal</small>
                                                    </div>
                                                    <div class="alert alert-info border mt-2">
                                                        <i class="bi bi-key me-2"></i>
                                                        <strong>Contraseña:</strong> Mínimo 8 caracteres
                                                    </div>
                                                    <div class="alert alert-warning border mt-2">
                                                        <i class="bi bi-shield-check me-2"></i>
                                                        <strong>Seguridad:</strong> Asigne solo permisos necesarios
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Pasos para modificar usuario -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0">Pasos para Modificar Usuario</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <ol>
                                                        <li class="mb-2"><strong>Paso 1:</strong> Busque el usuario en la lista por nombre o correo</li>
                                                        <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de <strong>editar</strong> ✏️ junto al usuario</li>
                                                        <li class="mb-2"><strong>Paso 3:</strong> Actualice los <strong>datos personales</strong> si es necesario</li>
                                                        <li class="mb-2"><strong>Paso 4:</strong> Puede cambiar el <strong>rol</strong> del usuario</li>
                                                        <li class="mb-2"><strong>Paso 5:</strong> Modifique los <strong>permisos específicos</strong> según requiera</li>
                                                        <li class="mb-2"><strong>Paso 6:</strong> Cambie el <strong>estado</strong> (activo/inactivo) si es necesario</li>
                                                        <li class="mb-2"><strong>Paso 7:</strong> Haga clic en <strong>"Actualizar Usuario"</strong></li>
                                                        <li class="mb-2"><strong>Paso 8:</strong> Notifique al usuario de los cambios realizados</li>
                                                    </ol>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="alert alert-light border">
                                                        <i class="bi bi-pencil text-warning me-2"></i>
                                                        <strong>Modificar</strong>
                                                        <br><small>Ícono de edición</small>
                                                    </div>
                                                    <div class="alert alert-info border mt-2">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        <strong>Tip:</strong> Verifique el historial de accesos antes de modificar
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Pasos para resetear contraseña -->
                                    <div class="card mt-3">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">Pasos para Resetear Contraseña</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <ol>
                                                        <li class="mb-2"><strong>Paso 1:</strong> Localice el usuario que necesita reseteo de contraseña</li>
                                                        <li class="mb-2"><strong>Paso 2:</strong> Haga clic en el ícono de <strong>llave</strong> 🔑 junto al usuario</li>
                                                        <li class="mb-2"><strong>Paso 3:</strong> Confirme que desea resetear la contraseña</li>
                                                        <li class="mb-2"><strong>Paso 4:</strong> El sistema generará una <strong>contraseña temporal</strong></li>
                                                        <li class="mb-2"><strong>Paso 5:</strong> La nueva contraseña se enviará automáticamente al correo del usuario</li>
                                                        <li class="mb-2"><strong>Paso 6:</strong> El usuario deberá cambiarla en el próximo inicio de sesión</li>
                                                    </ol>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="alert alert-light border">
                                                        <i class="bi bi-key text-info me-2"></i>
                                                        <strong>Resetear Contraseña</strong>
                                                        <br><small>Ícono de llave</small>
                                                    </div>
                                                    <div class="alert alert-warning border mt-2">
                                                        <i class="bi bi-envelope me-2"></i>
                                                        <strong>Importante:</strong> El usuario debe tener correo válido
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-shield-exclamation me-2"></i>
                                        Solo los administradores pueden gestionar usuarios del sistema.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Roles -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-person-badge-fill me-2"></i>Gestión de Roles y Permisos
                                    </h5>
                                    <p>Defina los roles y permisos para controlar el acceso al sistema.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Roles del sistema:</h6>
                                            <ul>
                                                <li><strong>SuperUsuario</strong>: Acceso total</li>
                                                <li><strong>Administrador</strong>: Gestión completa</li>
                                                <li><strong>Almacenista</strong>: Control de inventario</li>
                                                <li><strong>Vendedor</strong>: Gestión de ventas</li>
                                                <li><strong>Cliente</strong>: Acceso al catálogo</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Permisos configurables:</h6>
                                            <ul>
                                                <li>Acceso a módulos</li>
                                                <li>Operaciones CRUD</li>
                                                <li>Visibilidad de datos</li>
                                                <li>Generación de reportes</li>
                                                <li>Configuración del sistema</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Catálogo de Combos -->
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="bi bi-tags-fill me-2"></i>Catálogo de Combos Promocionales
                                    </h5>
                                    <p>Configure y gestione los combos promocionales para ofrecer mejores precios a los clientes.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Información gestionable:</h6>
                                            <ul>
                                                <li>Nombre del combo</li>
                                                <li>Descripción</li>
                                                <li>Productos incluidos</li>
                                                <li>Precio especial</li>
                                                <li>Descuento aplicado</li>
                                                <li>Fecha de vigencia</li>
                                                <li>Estado (activo/inactivo)</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Operaciones disponibles:</h6>
                                            <ul>
                                                <li><strong>Crear</strong>: Nuevo combo</li>
                                                <li><strong>Modificar</strong>: Actualizar productos</li>
                                                <li><strong>Eliminar</strong>: Desactivar combo</li>
                                                <li><strong>Duplicar</strong>: Copiar combo existente</li>
                                                <li><strong>Promocionar</strong>: Destacar combo</li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="note mt-3">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        Los combos ayudan a aumentar las ventas y mejorar la satisfacción del cliente.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0">Panel de Control</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <div class="display-6 text-danger mb-1">
                                            <i class="bi bi-shield-check"></i>
                                        </div>
                                        <h6>Acceso Administrativo</h6>
                                    </div>
                                    
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-people me-2"></i>Usuarios</span>
                                            <span class="badge bg-primary rounded-pill">Admin</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-person-badge me-2"></i>Roles</span>
                                            <span class="badge bg-primary rounded-pill">Admin</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-bank me-2"></i>Finanzas</span>
                                            <span class="badge bg-primary rounded-pill">Admin</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-gear me-2"></i>Configuración</span>
                                            <span class="badge bg-primary rounded-pill">Admin</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Estadísticas del Sistema</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="display-6 text-info mb-1">100%</div>
                                            <p class="small text-muted mb-0">Uso del Sistema</p>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="display-6 text-info mb-1">24/7</div>
                                            <p class="small text-muted mb-0">Disponibilidad</p>
                                        </div>
                                        <div class="col-6">
                                            <div class="display-6 text-info mb-1">0</div>
                                            <p class="small text-muted mb-0">Errores Críticos</p>
                                        </div>
                                        <div class="col-6">
                                            <div class="display-6 text-info mb-1">99.9</div>
                                            <p class="small text-muted mb-0">Rendimiento</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">Accesos Rápidos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-download me-2"></i>Generar Backup
                                        </button>
                                        <button class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-arrow-repeat me-2"></i>Sincronizar Datos
                                        </button>
                                        <button class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-graph-up me-2"></i>Ver Reportes
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-shield-exclamation me-2"></i>Auditoría
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <?php /* endif; */ ?>

                <?php /* if ($esCliente || $esAdministrador): */ ?>
                    <!-- Sección para Clientes -->
                    <?php /* if ($esCliente): */ ?>
                        <!-- Catálogo de Productos -->
                        <section id="seccion-cliente" class="section-card">
                            <h2 class="section-title">Catálogo de Productos</h2>
                            <?php
                            $datos_catalogo = [
                                "id" => "catalogo",
                                "nombre_singular" => "Producto",
                                "nombre_plural" => "Productos",
                                "gestionable" => [
                                    "Ver lista de productos disponibles",
                                    "Filtrar productos por categoría",
                                    "Buscar productos específicos",
                                    "Ver detalles completos de cada producto"
                                ],
                                "instrucciones" => [
                                    "Navegue por las diferentes categorías de productos",
                                    "Utilice la barra de búsqueda para encontrar productos específicos",
                                    "Haga clic en un producto para ver más detalles"
                                ]
                            ];
                            plantilla("inicio", $datos_catalogo);
                            ?>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <h4>Vista de Productos</h4>
                                    <p>Explore los productos disponibles en el catálogo.</p>
                                    <?= renderImagen("catalogo", "vista-productos.png") ?>
                                </div>
                                <div class="col-md-6">
                                    <h4>Detalles del Producto</h4>
                                    <p>Vea información detallada de cada producto.</p>
                                    <?= renderImagen("catalogo", "detalle-producto.png") ?>
                                </div>
                            </div>
                        </section>

                        <!-- Carrito de Compras -->
                        <section id="carrito" class="section-card">
                            <h2 class="section-title">Carrito de Compras</h2>
                            <?php
                            $datos_carrito = [
                                "id" => "carrito",
                                "nombre_singular" => "Producto en Carrito",
                                "nombre_plural" => "Productos en Carrito",
                                "gestionable" => [
                                    "Ajustar cantidades",
                                    "Eliminar productos",
                                    "Ver resumen de compra"
                                ],
                                "instrucciones" => [
                                    "Haga clic en 'Agregar al carrito' para incluir productos",
                                    "Ajuste las cantidades según necesite",
                                    "Revise el resumen antes de proceder al pago"
                                ]
                            ];
                            plantilla("inicio", $datos_carrito);
                            ?>
                            
                            <div class="note mt-4">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Nota:</strong> Los productos en el carrito se mantendrán hasta que los elimine manualmente.
                            </div>
                        </section>


                        <!-- Mis Pedidos -->
                        <section id="mis-pedidos" class="section-card">
                            <h2 class="section-title">Pedidos Realizados</h2>
                            <?php
                            $datos_pedidos = [
                                "id" => "pedidos",
                                "nombre_singular" => "Pedido",
                                "nombre_plural" => "Pedidos Realizados",
                                "gestionable" => [
                                    "Ver historial de pedidos",
                                    "Ver estado de pedido",
                                    "Descargar facturas",
                                    "Anular pedidos",
                                    "Llevar a Cabo el pago por los productos pedidos"
                                ],
                                "instrucciones" => [
                                    "Consulte el estado de sus pedidos recientes",
                                    "Consulte el estatus del pedido en tiempo real",
                                    "Descargue sus facturas en formato PDF",
                                    "Anule el pedido si es necesario"
                                ]
                            ];
                            plantilla("inicio", $datos_pedidos);
                            ?>
                            
                            <div class="tip mt-4">
                                <i class="bi bi-lightbulb-fill me-2"></i>
                                <strong>Consejo:</strong> Puede hacer seguimiento de sus pedidos en esta sección.
                            </div>
                        </section>

                    <?php /* endif; */ ?>
                    
                    <!-- Sección para Administradores -->
                    <?php /* if ($esAdministrador): */ ?>
                        <?php /* include 'plantillas/seccion-almacenista.php'; */ ?>
                        <?php /* include 'plantillas/seccion-administrador.php'; */ ?>
                    <?php /* endif; */ ?>
                <?php /* else: */ ?>
                    <!-- Sección de Inicio de Sesión -->
                    <section id="iniciar-sesion" class="section-card">
                        <h2 class="section-title">Iniciar Sesión</h2>
                        <p>Para acceder al sistema, siga estos pasos:</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Paso 1: Ingrese sus credenciales</h5>
                                        <p class="card-text">Ingrese su nombre de usuario y contraseña en los campos correspondientes.</p>
                                        <?= renderImagen("login", "login.png") ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Paso 2: Acceda al sistema</h5>
                                        <p class="card-text">Haga clic en el botón "Iniciar Sesión" para acceder a su cuenta.</p>
                                        <?= renderImagen("login", "dashboard.png") ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="note mt-4">
                            <i class="bi bi-question-circle-fill me-2"></i>
                            <strong>¿Problemas para iniciar sesión?</strong> Contacte al administrador del sistema para restablecer su contraseña.
                        </div>
                    </section>
                <?php /* endif; */ ?>

                <!-- Preguntas Frecuentes -->
                <section id="preguntas-frecuentes" class="section-card">
                    <h2 class="section-title">Preguntas Frecuentes</h2>
                    
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    ¿Cómo cambio mi contraseña?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Para cambiar su contraseña, siga estos pasos:</p>
                                    <ol>
                                        <li>Haga clic en su nombre de usuario en la esquina superior derecha.</li>
                                        <li>Seleccione "Mi Perfil".</li>
                                        <li>Haga clic en "Cambiar Contraseña".</li>
                                        <li>Ingrese su contraseña actual y la nueva contraseña dos veces.</li>
                                        <li>Haga clic en "Guardar Cambios".</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    ¿Cómo realizo una nueva venta?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Para realizar una nueva venta:</p>
                                    <ol>
                                        <li>Vaya a la sección "Ventas" en el menú lateral.</li>
                                        <li>Haga clic en "Nueva Venta".</li>
                                        <li>Seleccione el cliente o cree uno nuevo.</li>
                                        <li>Agregue los productos al carrito.</li>
                                        <li>Revise el resumen y haga clic en "Finalizar Venta".</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    ¿Cómo genero un reporte de ventas?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Para generar un reporte de ventas:</p>
                                    <ol>
                                        <li>Vaya a la sección "Reportes" en el menú lateral.</li>
                                        <li>Seleccione el tipo de reporte "Ventas".</li>
                                        <li>Establezca el rango de fechas.</li>
                                        <li>Seleccione los filtros adicionales si es necesario.</li>
                                        <li>Haga clic en "Generar Reporte".</li>
                                        <li>Puede exportar el reporte a PDF o Excel según necesite.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
               
                <!-- Footer -->
                <footer class="text-center text-muted py-4 mt-5 border-top">
                    <p class="mb-1">© <?= date('Y') ?> Casa Lai, C.A. Todos los derechos reservados.</p>
                    <p class="mb-0">Versión del Sistema: 2.0.0</p>
                </footer>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Activar popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Smooth scrolling para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 20,
                        behavior: 'smooth'
                    });
                    
                    // Actualizar URL sin recargar la página
                    history.pushState(null, null, targetId);
                }
            });
        });
        
        // Manejar el tema claro/oscuro
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const html = document.documentElement;
                const currentTheme = html.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                // Actualizar ícono
                const icon = this.querySelector('i');
                if (newTheme === 'dark') {
                    icon.classList.remove('bi-moon');
                    icon.classList.add('bi-sun');
                } else {
                    icon.classList.remove('bi-sun');
                    icon.classList.add('bi-moon');
                }
            });
            
            // Cargar tema guardado
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            
            // Actualizar ícono según el tema guardado
            const icon = themeToggle.querySelector('i');
            if (savedTheme === 'dark') {
                icon.classList.remove('bi-moon');
                icon.classList.add('bi-sun');
            }
        }
    </script>
</body>
</html>
