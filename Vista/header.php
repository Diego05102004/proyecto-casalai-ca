<link rel="icon" type="image/png" href="assets/img/LOGO.png">

<!-- jQuery (solo una versión) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 5 CSS -->
<link href="assets/public/bootstrap/css/bootstrap.min.css" rel="stylesheet">

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css"/>
<link rel="stylesheet" href="assets/public/datatables-custom.css">

<!-- Estilos personalizados -->
<link rel="stylesheet" href="assets/styles/new_menu.css">
<link rel="stylesheet" href="assets/styles/formulario.css">
<link rel="stylesheet" href="assets/styles/tabla_consulta.css">
<link rel="stylesheet" href="assets/styles/modalmodificar.css">
<link rel="stylesheet" href="assets/styles/modaldetalles.css">
<link rel="stylesheet" href="assets/styles/fondo.css">
<link rel="stylesheet" href="assets/styles/perfil.css">
<link rel="stylesheet" href="assets/styles/grafica.css">
<link rel="stylesheet" href="assets/styles/reportes.css">
<link rel="stylesheet" href="assets/styles/global.css">
<link rel="stylesheet" href="assets/styles/catalogo.css">
<?php if (empty($exclude_buttons_css)) { ?>
<link rel="stylesheet" href="assets/styles/button.css">
<?php } ?>

<!-- Bootstrap JS Bundle with Popper -->
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="assets/public/js/sweetalert2.js"></script>
<link rel="stylesheet" href="assets/public/css/sweetalert2.css">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- DataTables JS -->
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons -->
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<!-- PDFMake (requerido para botón PDF) -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

<!-- JSZip (requerido para botón Excel) -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<!-- Script de inicialización automática de WebSocket -->
<script>
// Asegurar que el WebSocket se inicialice automáticamente después del login
document.addEventListener('DOMContentLoaded', function() {
    const usuarioId = '<?php echo isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : ''; ?>';
    
    // Si hay un usuario logueado y no existe el WebSocket, inicializarlo
    if (usuarioId && usuarioId !== '0' && typeof window.notificacionesWS === 'undefined') {
        console.log('Inicializando WebSocket automáticamente para usuario:', usuarioId);
        
        // Esperar a que la clase NotificacionesWebSocket esté disponible
        const initWebSocket = () => {
            if (typeof NotificacionesWebSocket !== 'undefined') {
                window.notificacionesWS = new NotificacionesWebSocket(usuarioId);
                console.log('WebSocket inicializado automáticamente');
            } else {
                // Si la clase aún no está cargada, reintentar en 100ms
                setTimeout(initWebSocket, 100);
            }
        };
        
        initWebSocket();
    } else if (usuarioId && usuarioId !== '0' && window.notificacionesWS) {
        // Si ya existe, asegurar que esté conectado
        console.log('WebSocket ya existe, verificando conexión...');
        if (window.notificacionesWS.socket && window.notificacionesWS.socket.readyState !== WebSocket.OPEN) {
            console.log('WebSocket no conectado, reconectando...');
            window.notificacionesWS.reconectar();
        }
    }
});
</script>
