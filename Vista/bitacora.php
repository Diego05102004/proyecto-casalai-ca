<?php if (session_status() === PHP_SESSION_NONE) { session_start(); }
$idRol = $_SESSION['id_rol'] ?? 0; // Rol por defecto en entorno de pruebas
$idModulo = 19;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) {?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora del Sistema</title>
    <link rel="stylesheet" href="public/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/js/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="styles/tabla_consulta.css">
    <?php include 'header.php'; ?>
    <style>
        .contenedor-tabla {
            margin-top: 40px;
        }
        .table-responsive {
            margin-top: 20px;
        }
    </style>
</head>
<body class="fondo" style="background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 100vh;">
<?php include 'newnavbar.php'; ?>

<div class="contenedor-tabla">
    <div class="tabla-header">
        <div class="ghost"></div>
        <h3>Bitácora del Sistema</h3>
        <div class="ghost"></div>
    </div>

    <div class="table-responsive">
        <table class="tablaConsultas" id="tablaBitacora">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha y Hora</th>
                    <th>Acción Realizada</th>
                    <th>Descripcion</th>
                    <th>Módulo</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody id="tbodyBitacora">
                <?php if (!empty($registros)): ?>
                    <?php foreach ($registros as $registro): ?>
                        <tr>
                            <td>
                                <span class="campo-numeros">
                                    <?= htmlspecialchars($registro['id_bitacora']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="campo-numeros">
                                    <?= date('d/m/Y', strtotime($registro['fecha_hora'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="campo-tex-num">
                                    <?= htmlspecialchars($registro['accion']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="campo-nombres">
                                    <?= htmlspecialchars($registro['descripcion']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="campo-tex-num">
                                    <?= htmlspecialchars($registro['modulo']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="campo-nombres">
                                    <?= htmlspecialchars($registro['username']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay registros en la bitácora.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="public/js/jquery-3.7.1.min.js"></script>
<script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/js/jquery.dataTables.min.js"></script>
<script src="public/js/dataTables.bootstrap5.min.js"></script>
<script src="javascript/bitacora.js"></script>
</body>
</html>

</script>
</body>
</html>
<?php } else {
    header("Location: ?pagina=acceso-denegado");
    exit;
} ?>
