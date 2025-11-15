<?php if ($_SESSION['nombre_rol'] == 'Administrador' || $_SESSION['nombre_rol'] == 'SuperUsuario') { ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Backups</title>
    <?php include 'header.php'; ?>
</head>

<?php include 'newnavbar.php'; ?>

<body  class="fondo" style=" height: 100vh; background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<div class="contenedor-tabla">
    <h3 class="tabla-titulo-2" style="margin-bottom: 20px;">Gestión de Backups</h3>
    <div class="grupo-form" style="margin-bottom: 20px; display: flex; justify-content: center; gap: 15px;">
        <button id="btn-backup-principal"
            class="btn-generar-p">
            <img src="img/save.svg"> Generar Backup Principal
        </button>
        <button id="btn-backup-seguridad"
            class="btn-generar-s">
            <img src="img/save.svg"> Generar Backup Seguridad
        </button>
    </div>
    <h3 class="tabla-titulo-2" style="margin-bottom: 20px;">Backups Disponibles</h3>
    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Tipo</th>
                <th>Tamaño</th>
                <th>Fecha de Modificación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($backups as $backup): ?>
            <tr>
                <td>
                    <span class="campo-nombres">
                        <?= htmlspecialchars($backup['nombre'] ?? '') ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($backup['tipo'] ?? '') ?></td>
                <td><?= htmlspecialchars($backup['tamano'] ?? '') ?></td>
                <td><?= htmlspecialchars($backup['fecha_modificacion'] ?? '') ?></td>
                <td>
                    <div class="d-flex">
                            <button class="btn btn-sm btn-outline-primary me-2 btn-descargar"
                                data-archivo="<?= htmlspecialchars($backup['nombre'] ?? ''); ?>"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Descargar">
                                <i style="color: #007bff;" class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning btn-restaurar"
                                data-archivo="<?= htmlspecialchars($backup['nombre'] ?? ''); ?>"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Restaurar">
                                <i style="color: #FFC107;" class="fas fa-redo"></i>
                            </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
<script src="Javascript/backup.js"></script>
<script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/js/jquery-3.7.1.min.js"></script>
<script src="public/js/jquery.dataTables.min.js"></script>
<script src="public/js/dataTables.bootstrap5.min.js"></script>
<script src="public/js/datatable.js"></script>
<script>
$(document).ready(function() {
    // Inicializar DataTable
    var table = $('#tablaConsultas').DataTable({
        order: [[0, 'desc']],
        language: {
            url: 'public/js/es-ES.json'
        },
        responsive: true
    });
    
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Manejar clic en botones de acción
    $(document).on('click', '.btn-descargar, .btn-restaurar', function(e) {
        e.preventDefault();
        const accion = $(this).hasClass('btn-descargar') ? 'descargar' : 'restaurar';
        const archivo = $(this).data('archivo');
        const nombreArchivo = archivo.split('/').pop();
        
        if (accion === 'descargar') {
            window.location.href = 'Controlador/backup.php?accion=descargar&archivo=' + encodeURIComponent(nombreArchivo);
        } else {
            if (confirm('¿Está seguro que desea restaurar el backup: ' + nombreArchivo + '?')) {
                window.location.href = 'Controlador/backup.php?accion=restaurar&archivo=' + encodeURIComponent(nombreArchivo);
            }
        }
    });
});
</script>
</body>
</html>
<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>