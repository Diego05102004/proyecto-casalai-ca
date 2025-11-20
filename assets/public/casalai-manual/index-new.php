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
            <div class="col-lg-3 mb-4">
                <div class="toc">
                    <h5 class="toc-title">Tabla de Contenidos</h5>
                    <ul class="toc-list">
                        <li class="toc-item"><a href="#introduccion" class="toc-link"><i class="bi bi-house-door"></i> Introducción</a></li>
                        
                        <?php if (isset($_SESSION['id_usuario'])): ?>
                            <li class="toc-item"><a href="#dashboard" class="toc-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                            <li class="toc-item"><a href="#mi-cuenta" class="toc-link"><i class="bi bi-person"></i> Mi Cuenta</a></li>
                            
                            <?php if ($esCliente): ?>
                                <li class="toc-item"><a href="#seccion-cliente" class="toc-link"><i class="bi bi-cart"></i> Compras</a></li>
                                <li class="toc-item"><a href="#mis-pedidos" class="toc-link"><i class="bi bi-list-check"></i> Mis Pedidos</a></li>
                                <li class="toc-item"><a href="#facturas" class="toc-link"><i class="bi bi-receipt"></i> Mis Facturas</a></li>
                            <?php endif; ?>
                            
                            <?php if ($esAdministrador): ?>
                                <!-- Módulo de Inventario -->
                                <li class="toc-item">
                                    <a href="#modulo-inventario" class="toc-link">
                                        <i class="bi bi-box-seam"></i> Inventario
                                    </a>
                                    <ul class="toc-sublist ms-3 mt-2">
                                        <li><a href="#gestion-productos" class="toc-link">Productos</a></li>
                                        <li><a href="#categorias" class="toc-link">Categorías</a></li>
                                        <li><a href="#marcas" class="toc-link">Marcas</a></li>
                                        <li><a href="#modelos" class="toc-link">Modelos</a></li>
                                    </ul>
                                </li>
                                
                                <!-- Módulo de Ventas -->
                                <li class="toc-item">
                                    <a href="#modulo-ventas" class="toc-link">
                                        <i class="bi bi-cash-coin"></i> Ventas
                                    </a>
                                    <ul class="toc-sublist ms-3 mt-2">
                                        <li><a href="#nueva-venta" class="toc-link">Nueva Venta</a></li>
                                        <li><a href="#historial-ventas" class="toc-link">Historial</a></li>
                                        <li><a href="#devoluciones" class="toc-link">Devoluciones</a></li>
                                    </ul>
                                </li>
                                
                                <!-- Módulo de Compras -->
                                <li class="toc-item">
                                    <a href="#modulo-compras" class="toc-link">
                                        <i class="bi bi-cart-plus"></i> Compras
                                    </a>
                                    <ul class="toc-sublist ms-3 mt-2">
                                        <li><a href="#nueva-compra" class="toc-link">Nueva Compra</a></li>
                                        <li><a href="#historial-compras" class="toc-link">Historial</a></li>
                                        <li><a href="#proveedores" class="toc-link">Proveedores</a></li>
                                    </ul>
                                </li>
                                
                                <!-- Módulo de Clientes -->
                                <li class="toc-item">
                                    <a href="#modulo-clientes" class="toc-link">
                                        <i class="bi bi-people"></i> Clientes
                                    </a>
                                    <ul class="toc-sublist ms-3 mt-2">
                                        <li><a href="#lista-clientes" class="toc-link">Lista de Clientes</a></li>
                                        <li><a href="#creditos" class="toc-link">Créditos</a></li>
                                        <li><a href="#pagos" class="toc-link">Pagos</a></li>
                                    </ul>
                                </li>
                                
                                <!-- Módulo de Reportes -->
                                <li class="toc-item">
                                    <a href="#modulo-reportes" class="toc-link">
                                        <i class="bi bi-graph-up"></i> Reportes
                                    </a>
                                    <ul class="toc-sublist ms-3 mt-2">
                                        <li><a href="#ventas-reporte" class="toc-link">Ventas</a></li>
                                        <li><a href="#inventario-reporte" class="toc-link">Inventario</a></li>
                                        <li><a href="#financiero-reporte" class="toc-link">Financiero</a></li>
                                    </ul>
                                </li>
                                
                                <!-- Módulo de Administración -->
                                <li class="toc-item">
                                    <a href="#modulo-administracion" class="toc-link">
                                        <i class="bi bi-gear"></i> Administración
                                    </a>
                                    <ul class="toc-sublist ms-3 mt-2">
                                        <li><a href="#usuarios" class="toc-link">Usuarios</a></li>
                                        <li><a href="#roles" class="toc-link">Roles y Permisos</a></li>
                                        <li><a href="#configuracion" class="toc-link">Configuración</a></li>
                                        <li><a href="#backup" class="toc-link">Respaldo</a></li>
                                    </ul>
                                </li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li class="toc-item"><a href="#iniciar-sesion" class="toc-link"><i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión</a></li>
                        <?php endif; ?>
                        
                        <li class="toc-item"><a href="#preguntas-frecuentes" class="toc-link"><i class="bi bi-question-circle"></i> Preguntas Frecuentes</a></li>
                        <li class="toc-item"><a href="#soporte" class="toc-link"><i class="bi bi-headset"></i> Soporte Técnico</a></li>
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
                                        <?php if ($esAdministrador): ?>
                                            <a href="#seccion-administrador" class="btn btn-outline-primary text-start"><i class="bi bi-shield-lock me-2"></i> Ver Sección Administrador</a>
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
                    <h2 class="section-title">Dashboard</h2>
                    <p>El Dashboard es su centro de control principal donde podrá acceder rápidamente a todas las funciones del sistema según su nivel de acceso.</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Barra Lateral</h4>
                            <p>La barra lateral contiene los accesos directos a todas las secciones del sistema. Las opciones disponibles variarán según su rol de usuario.</p>
                            <?= renderImagen("dashboard", "barra-lateral.png") ?>
                        </div>
                        <div class="col-md-6">
                            <h4>Vista General</h4>
                            <p>El dashboard muestra un resumen de la información más relevante, incluyendo estadísticas, alertas y accesos rápidos.</p>
                            <?= renderImagen("dashboard", "vista2.png") ?>
                        </div>
                    </div>
                    
                    <div class="note mt-4">
                        <i class="bi bi-lightbulb-fill me-2"></i>
                        <strong>Consejo:</strong> Utilice las tarjetas del dashboard para acceder rápidamente a las secciones más utilizadas.
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
                                "Preferencias de Notificación"
                            ],
                            "instrucciones" => [
                                "Actualice sus datos personales",
                                "Cambie su contraseña regularmente",
                                "Configure cómo desea recibir notificaciones"
                            ]
                        ];
                        plantilla("inicio", $datos_perfil);
                        ?>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h4>Acceso al Perfil</h4>
                                <p>Haga clic en su nombre de usuario en la esquina superior derecha para acceder a su perfil.</p>
                                <?= renderImagen("dashboard", "mi-cuenta.png") ?>
                            </div>
                            <div class="col-md-6">
                                <h4>Editar Información</h4>
                                <p>En la sección de perfil podrá actualizar su información personal y cambiar su contraseña.</p>
                                <?= renderImagen("perfil", "perfil-informacion-personal.png") ?>
                            </div>
                        </div>
                        
                        <div class="warning mt-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Importante:</strong> Mantenga su contraseña segura y no la comparta con nadie.
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($esCliente || $esAdministrador): ?>
                    <!-- Sección para Clientes -->
                    <?php if ($esCliente): ?>
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
                                    "Agregar productos al carrito",
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
                                <strong>Nota:</strong> Los productos en el carrito se mantendrán hasta que cierre sesión o los elimine manualmente.
                            </div>
                        </section>

                        <!-- Proceso de Compra -->
                        <section id="proceso-compra" class="section-card">
                            <h2 class="section-title">Proceso de Compra</h2>
                            <?php
                            $datos_compra = [
                                "id" => "compra",
                                "nombre_singular" => "Paso del Proceso de Compra",
                                "nombre_plural" => "Pasos del Proceso de Compra",
                                "gestionable" => [
                                    "Selección de método de envío",
                                    "Datos de facturación",
                                    "Método de pago",
                                    "Confirmación de pedido"
                                ],
                                "instrucciones" => [
                                    "Complete sus datos de envío y facturación",
                                    "Seleccione su método de pago preferido",
                                    "Revise su pedido antes de confirmar",
                                    "Recibirá un correo de confirmación con los detalles"
                                ]
                            ];
                            plantilla("inicio", $datos_compra);
                            ?>
                            
                            <div class="warning mt-4">
                                <i class="bi bi-shield-lock me-2"></i>
                                <strong>Seguridad:</strong> Todas las transacciones están protegidas con cifrado SSL.
                            </div>
                        </section>

                        <!-- Mis Pedidos -->
                        <section id="mis-pedidos" class="section-card">
                            <h2 class="section-title">Mis Pedidos</h2>
                            <?php
                            $datos_pedidos = [
                                "id" => "pedidos",
                                "nombre_singular" => "Pedido",
                                "nombre_plural" => "Mis Pedidos",
                                "gestionable" => [
                                    "Ver historial de pedidos",
                                    "Ver estado de envío",
                                    "Descargar facturas",
                                    "Solicitar devoluciones"
                                ],
                                "instrucciones" => [
                                    "Consulte el estado de sus pedidos recientes",
                                    "Siga el envío en tiempo real",
                                    "Descargue sus facturas en formato PDF"
                                ]
                            ];
                            plantilla("inicio", $datos_pedidos);
                            ?>
                            
                            <div class="tip mt-4">
                                <i class="bi bi-lightbulb-fill me-2"></i>
                                <strong>Consejo:</strong> Puede hacer seguimiento de sus pedidos en esta sección.
                            </div>
                        </section>

                        <!-- Facturas -->
                        <section id="facturas" class="section-card">
                            <h2 class="section-title">Mis Facturas</h2>
                            <?php
                            $datos_facturas = [
                                "id" => "facturas",
                                "nombre_singular" => "Factura",
                                "nombre_plural" => "Mis Facturas",
                                "gestionable" => [
                                    "Ver historial de facturas",
                                    "Descargar facturas en PDF",
                                    "Imprimir facturas",
                                    "Solicitar factura electrónica"
                                ],
                                "instrucciones" => [
                                    "Consulte sus facturas anteriores",
                                    "Descargue o imprima copias para sus registros",
                                    "Solicite factura electrónica si es necesario"
                                ]
                            ];
                            plantilla("inicio", $datos_facturas);
                            ?>
                        </section>
                    <?php endif; ?>
                    
                    <!-- Sección para Administradores -->
                    <?php if ($esAdministrador): ?>
                        <?php include 'plantillas/seccion-almacenista.php'; ?>
                        <?php include 'plantillas/seccion-administrador.php'; ?>
                    <?php endif; ?>
                <?php else: ?>
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
                <?php endif; ?>

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

                <!-- Soporte Técnico -->
                <section id="soporte" class="section-card">
                    <h2 class="section-title">Soporte Técnico</h2>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-envelope me-2"></i> Contacto por Correo</h5>
                                    <p class="card-text">Para reportar problemas o solicitar asistencia, envíe un correo a:</p>
                                    <p class="h5 text-center my-4">
                                        <a href="mailto:soporte@casalai.com" class="btn btn-primary">soporte@casalai.com</a>
                                    </p>
                                    <p class="small text-muted">Por favor incluya capturas de pantalla y una descripción detallada del problema.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-telephone me-2"></i> Soporte Telefónico</h5>
                                    <p class="card-text">Nuestro equipo de soporte está disponible en los siguientes horarios:</p>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><strong>Lunes a Viernes:</strong> 8:00 AM - 6:00 PM</li>
                                        <li class="mb-2"><strong>Sábados:</strong> 9:00 AM - 1:00 PM</li>
                                        <li><strong>Teléfono:</strong> (123) 456-7890</li>
                                    </ul>
                                    <div class="alert alert-warning mt-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Para una atención más rápida, tenga a la mano su número de cliente o factura.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-chat-square-text me-2"></i> Formulario de Contacto</h5>
                            <form class="mt-3">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo</label>
                                    <input type="text" class="form-control" id="nombre" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="asunto" class="form-label">Asunto</label>
                                    <select class="form-select" id="asunto" required>
                                        <option value="">Seleccione un asunto</option>
                                        <option value="consulta">Consulta General</option>
                                        <option value="problema">Reportar un Problema</option>
                                        <option value="sugerencia">Sugerencia</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="mensaje" class="form-label">Mensaje</label>
                                    <textarea class="form-control" id="mensaje" rows="4" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="archivo" class="form-label">Adjuntar Archivo (opcional)</label>
                                    <input class="form-control" type="file" id="archivo">
                                    <div class="form-text">Puede adjuntar capturas de pantalla o documentos relacionados.</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Enviar Mensaje</button>
                            </form>
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
