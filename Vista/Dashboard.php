<?php
// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['name'])) {
    // Redirigir al usuario a la página de inicio de sesión
    header('Location: .');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <?php include 'header.php'; ?>
  <style>
    .container {
      max-width: 1200px;
      margin: 40px auto;
    }

    .card-dashboard {
      flex: 1 1 260px;
      min-width: 260px;
      max-width: 320px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
      padding: 32px 24px;
      text-align: center;
      margin: 0 auto;
    }

    .card-dashboard h4 {
      margin-bottom: 12px;
      max-width: 150px;
    }

    .modulos-dashboard {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      justify-content: center;
      margin-top: 10px;
    }

    .btn-dashboard {
      width: 150px; /* 🔹 Tamaño fijo uniforme */
      text-align: center;
      padding: 8px;
      border-radius: 6px;
      color: white;
      font-size: 14px;
      text-decoration: none;
      display: inline-block;
      transition: transform 0.2s ease, background-color 0.2s ease;
    }

    .btn-dashboard:hover {
      transform: scale(1.05);
      filter: brightness(1.1);
    }

    /* Mobile Responsive for Dashboard Cards */
    @media (max-width: 768px) {
      .container {
        margin: 20px auto;
        padding: 0 15px;
      }

      .card-dashboard {
        flex: 1 1 100%;
        min-width: 100%;
        max-width: 100%;
        padding: 20px 16px;
        margin-bottom: 20px;
      }

      .card-dashboard h4 {
        max-width: 100%;
        margin-bottom: 16px;
        font-size: 1.1rem;
      }

      .modulos-dashboard {
        flex-direction: column;
        gap: 12px;
        align-items: center;
        margin-top: 16px;
      }

      .btn-dashboard {
        width: 100%;
        max-width: 200px;
        padding: 12px 16px;
        font-size: 13px;
      }

      .card-dashboard img {
        width: 48px;
        height: 48px;
        margin-bottom: 12px;
      }
    }

    @media (max-width: 480px) {
      .container {
        margin: 15px auto;
        padding: 0 10px;
      }

      .card-dashboard {
        padding: 16px 12px;
        margin-bottom: 15px;
      }

      .card-dashboard h4 {
        font-size: 1rem;
        margin-bottom: 12px;
      }

      .modulos-dashboard {
        gap: 10px;
        margin-top: 12px;
      }

      .btn-dashboard {
        max-width: 180px;
        padding: 10px 14px;
        font-size: 12px;
      }

      .card-dashboard img {
        width: 40px;
        height: 40px;
        margin-bottom: 10px;
      }
    }

    @media (max-width: 360px) {
      .card-dashboard {
        padding: 12px 8px;
      }

      .card-dashboard h4 {
        font-size: 0.9rem;
        margin-bottom: 10px;
      }

      .btn-dashboard {
        max-width: 160px;
        padding: 8px 12px;
        font-size: 11px;
      }

      .card-dashboard img {
        width: 36px;
        height: 36px;
        margin-bottom: 8px;
      }
    }
  </style>
</head>

<body class="fondo"
  style="height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php
include 'newnavbar.php';

// AGREGAR MÓDULOS DE REPORTES AL ARRAY PRINCIPAL (igual que en navbar)
$modulos = [
    'Usuario' => ['Gestionar Usuario', 'assets/img/users-round.svg', '?pagina=usuario'],
    'Recepcion' => ['Gestionar Recepcion', 'assets/img/package-open.svg', '?pagina=recepcion'],
    'Despacho' => ['Gestionar Despacho', 'assets/img/package-check.svg', '?pagina=despacho'],
    'Marcas' => ['Gestionar Marcas', 'assets/img/package-search.svg', '?pagina=marca'],
    'Modelos' => ['Gestionar Modelos', 'assets/img/package-search.svg', '?pagina=modelo'],
    'Productos' => ['Gestionar Productos', 'assets/img/package-search.svg', '?pagina=producto'],
    'Categorias' => ['Gestionar Categorias', 'assets/img/package-search.svg', '?pagina=categoria'],
    'Compra Física' => ['Gestionar Ventas Presenciales', 'assets/img/files.svg', '?pagina=comprafisica'],
    'Proveedores' => ['Gestionar Proveedores', 'assets/img/truck.svg', '?pagina=proveedor'],
    'Clientes' => ['Gestionar Clientes', 'assets/img/users-round.svg', '?pagina=cliente'],
    'Catalogo' => ['Gestionar Catálogo', 'assets/img/book-open.svg', '?pagina=catalogo'],
    'pasarela' => ['Gestionar Pagos', 'assets/img/credit-card.svg', '?pagina=pasarela'],
    'Pedidos' => ['Gestionar Pedidos', 'assets/img/receipt-text.svg', '?pagina=gestionarfactura'],
    'Ordenes de despacho' => ['Gestionar Ordenes de Despacho', 'assets/img/list-ordered.svg', '?pagina=ordendespacho'],
    'Cuentas bancarias' => ['Gestionar Cuentas Bancarias', 'assets/img/landmark.svg', '?pagina=cuenta'],
    'Finanzas' => ['Gestionar Ingresos y Egresos', 'assets/img/dollar-sign.svg', '?pagina=finanza'],
    'permisos' => ['Gestionar Permisos', 'assets/img/key-round.svg', '?pagina=permiso'],
    'Roles' => ['Gestionar Roles', 'assets/img/user-round-search.svg', '?pagina=rol'],
    'bitacora' => ['Gestionar Bitácora', 'assets/img/notebook.svg', '?pagina=bitacora'],
    'Backup' => ['Gestionar Backup', 'assets/img/files.svg', '?pagina=backup'],
    
    // MÓDULOS DE REPORTES (AGREGADOS)
    'reporteUsuario' => ['Reporte de Perfiles', 'assets/img/chart-bar.svg', '?pagina=reporteUsuario'],
    'reporteInventario' => ['Reporte de Inventario', 'assets/img/chart-bar.svg', '?pagina=reporteInventario'],
    'reporteProductos' => ['Reporte de Productos', 'assets/img/chart-bar.svg', '?pagina=reporteProductos'],
    'reporteProveedores' => ['Reporte de Proveedores', 'assets/img/chart-bar.svg', '?pagina=reporteProveedores'],
    'reporteVentas' => ['Reporte de Ventas', 'assets/img/chart-bar.svg', '?pagina=reporteVentas'],
    'reporteFinanzas' => ['Reporte de Finanzas', 'assets/img/chart-bar.svg', '?pagina=reporteFinanzas'],
    'reporteCliente' => ['Reporte de Clientes', 'assets/img/chart-bar.svg', '?pagina=reporteCliente'],
];

// Define los grupos de módulos y su icono (ACTUALIZADO CON CONDICIONALES)
$grupos = [
    'Administrar Perfiles' => [
        'modulos' => ['Usuario'],
        'reportes' => ['reporteUsuario'],
        'icon' => 'assets/img/users-round.svg',
        'color' => '#4e73df',
        'condicion' => !empty($permisosConsulta['Usuario']) && $nombre_rol !== 'Cliente'
    ],
    'Administrar Inventario' => [
        'modulos' => ['Recepcion'],
        'reportes' => ['reporteInventario'],
        'icon' => 'assets/img/package-open.svg',
        'color' => '#1cc88a',
        'condicion' => !empty($permisosConsulta['Recepcion']) && $nombre_rol !== 'Cliente'
    ],
    'Administrar Productos' => [
        'modulos' => ['Marcas', 'Modelos', 'Productos', 'Categorias'],
        'reportes' => ['reporteProductos'],
        'icon' => 'assets/img/package-search.svg',
        'color' => '#36b9cc',
        'condicion' => ($nombre_rol !== 'Cliente') && (
            !empty($permisosConsulta['Marcas']) || 
            !empty($permisosConsulta['Modelos']) || 
            !empty($permisosConsulta['Productos']) || 
            !empty($permisosConsulta['Categorias']) ||
            !empty($permisosConsulta['reporteProductos'])
        )
    ],
    'Administrar Proveedores' => [
        'modulos' => ['Proveedores'],
        'reportes' => ['reporteProveedores'],
        'icon' => 'assets/img/truck.svg',
        'color' => '#f6c23e',
        'condicion' => !empty($permisosConsulta['Proveedores']) && $nombre_rol !== 'Cliente'
    ],
    'Administrar Clientes' => [
        'modulos' => ['Clientes'],
        'reportes' => ['reporteCliente'],
        'icon' => 'assets/img/users-round.svg',
        'color' => '#e74a3b',
        'condicion' => !empty($permisosConsulta['Clientes']) && $nombre_rol !== 'Cliente'
    ],
    'Administrar Ventas' => [
        'modulos' => ['Catalogo', 'Compra Física', 'pasarela', 'Pedidos', 'Ordenes de despacho', 'Despacho'],
        'reportes' => ['reporteVentas'],
        'icon' => 'assets/img/shopping-cart.svg',
        'color' => '#858796',
        'condicion' => !empty($permisosConsulta['Catalogo']) || 
                      !empty($permisosConsulta['Compra Física']) || 
                      !empty($permisosConsulta['pasarela']) || 
                      !empty($permisosConsulta['Pedidos']) || 
                      !empty($permisosConsulta['Ordenes de despacho']) || 
                      !empty($permisosConsulta['Despacho']) ||
                      !empty($permisosConsulta['reporteVentas'])
    ],
    'Administrar Finanzas' => [
        'modulos' => ['Cuentas bancarias', 'Finanzas'],
        'reportes' => ['reporteFinanzas'],
        'icon' => 'assets/img/dollar-sign.svg',
        'color' => '#20c997',
        'condicion' => ((!empty($permisosConsulta['Cuentas bancarias']) || !empty($permisosConsulta['Finanzas'])) && $nombre_rol !== 'Cliente')
    ],
    'Administrar Seguridad' => [
        'modulos' => ['permisos', 'Roles', 'bitacora', 'Backup'],
        'reportes' => [],
        'icon' => 'assets/img/key-round.svg',
        'color' => '#fd7e14',
        'condicion' => !empty($permisosConsulta['permisos']) || 
                      !empty($permisosConsulta['Roles']) || 
                      !empty($permisosConsulta['bitacora']) || 
                      !empty($permisosConsulta['Backup'])
    ],
];

// FUNCIÓN PARA VERIFICAR SI UN MÓDULO DEBE MOSTRARSE
function mostrarModulo($modulo, $permisosConsulta, $nombre_rol) {
    // Si es Cliente, mostrar solo módulos específicos
    if ($nombre_rol === 'Cliente') {
        $modulosCliente = ['Catalogo', 'pasarela', 'Pedidos'];
        return in_array($modulo, $modulosCliente) && !empty($permisosConsulta[$modulo]);
    }
    
    // Para otros roles, verificar permiso
    return !empty($permisosConsulta[$modulo]);
}
?>

<div class="container">
  <h3 class="tabla-titulo-2" style="margin-top:20px; margin-bottom:20px;">Panel Principal</h3>
  <div class="row" style="display:flex; flex-wrap:wrap; gap:32px; justify-content:center;">
    <?php
    foreach ($grupos as $grupo => $info) {
        // Verificar si el grupo debe mostrarse (igual que en navbar)
        if (!$info['condicion']) {
            continue;
        }

        $modulosPermitidos = [];

        // Agregar módulos principales con permisos
        foreach ($info['modulos'] as $mod) {
            if (mostrarModulo($mod, $permisosConsulta, $nombre_rol)) {
                $modulosPermitidos[] = $mod;
            }
        }

        // Agregar reportes con permisos (usando la misma lógica que en el navbar)
        foreach ($info['reportes'] as $reporte) {
            $mostrarReporte = false;
            switch ($reporte) {
                case 'reporteUsuario':
                    $mostrarReporte = !empty($permisosConsulta['Usuario']) && $nombre_rol !== 'Cliente';
                    break;
                case 'reporteInventario':
                    $mostrarReporte = !empty($permisosConsulta['Recepcion']) && $nombre_rol !== 'Cliente';
                    break;
                case 'reporteProductos':
                    $mostrarReporte = ($nombre_rol !== 'Cliente') && (
                        !empty($permisosConsulta['Marcas']) || 
                        !empty($permisosConsulta['Modelos']) || 
                        !empty($permisosConsulta['Productos']) || 
                        !empty($permisosConsulta['Categorias'])
                    );
                    break;
                case 'reporteProveedores':
                    $mostrarReporte = !empty($permisosConsulta['Proveedores']) && $nombre_rol !== 'Cliente';
                    break;
                case 'reporteVentas':
                    $mostrarReporte = !empty($permisosConsulta['Despacho']) && $nombre_rol !== 'Cliente';
                    break;
                case 'reporteFinanzas':
                    $mostrarReporte = ((!empty($permisosConsulta['Cuentas bancarias']) || !empty($permisosConsulta['Finanzas'])) && $nombre_rol !== 'Cliente');
                    break;
                case 'reporteCliente':
                    $mostrarReporte = !empty($permisosConsulta['Clientes']) && $nombre_rol !== 'Cliente';
                    break;
                default:
                    $mostrarReporte = !empty($permisosConsulta[$reporte]);
            }
            if ($mostrarReporte && !empty($modulos[$reporte])) {
                $modulosPermitidos[] = $reporte;
            }
        }

        // Mostrar el grupo solo si tiene módulos o reportes permitidos
        if (count($modulosPermitidos) > 0) {
            $icono = $info['icon'];
            $color = $info['color'];
    ?>
    <div class="card-dashboard" style="border-top:6px solid <?php echo $color; ?>;">
      <img src="<?php echo $icono; ?>" alt="icono" style="width:56px; height:56px; margin-bottom:18px;">
      <div class="modulos-dashboard">
        <h4 style="color:<?php echo $color; ?>;"><?php echo htmlspecialchars($grupo); ?></h4>
        <?php foreach ($modulosPermitidos as $modulo): ?>
        <a href="<?php echo $modulos[$modulo][2]; ?>" class="btn-dashboard"
          style="background:<?php echo $color; ?>;">
          <?php echo htmlspecialchars($modulos[$modulo][0]); ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
        }
    }
    ?>
  </div>
</div>

  <?php include 'footer.php'; ?>
  <script>
    const sesion = <?php echo json_encode($_SESSION); ?>;
    console.log('Sesión actual:', sesion);
  </script>
</body>
</html>