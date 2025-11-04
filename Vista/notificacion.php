
<?php
// Verificar si hay un error en la carga de notificaciones
$error = $error ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Historial de Notificaciones</title>
    <?php include 'header.php'; ?>
    <link href="css/notificacion.css" rel="stylesheet">
</head>
<body class="fondo" style="min-height: 100vh; background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'newnavbar.php'; ?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Mis Notificaciones</h4>
                    <button id="marcar-todas" class="btn btn-sm btn-light">Marcar todas como leídas</button>
                </div>
                <div class="card-body p-0">
                    <?php if ($error): ?>
                        <div class="alert alert-danger m-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div id="lista-notificaciones" class="list-group list-group-flush">
                        <!-- Las notificaciones se cargarán aquí dinámicamente -->
                        <div class="text-center p-4" id="cargando">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando notificaciones...</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Mostrando <span id="contador-notificaciones">0</span> notificaciones</small>
                        <button id="marcar-todas-leidas" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-check-double me-1"></i> Marcar todas como leídas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver notificación -->
<div class="modal fade" id="modalNotificacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNotificacionTitulo"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="modalNotificacionMensaje"></p>
                <p class="text-muted mb-0"><small id="modalNotificacionFecha"></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="js/notificacion.js"></script>

                    <div class="card-footer bg-white text-end">
                        <small class="text-muted">Mostrando las notificaciones más recientes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver notificación -->
<div class="modal fade" id="modalNotificacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNotificacionTitulo"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="modalNotificacionMensaje"></p>
                <p class="text-muted mb-0">
                    <small id="modalNotificacionFecha"></small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const listaNotificaciones = document.getElementById('lista-notificaciones');
    const cargando = document.getElementById('cargando');
    const btnMarcarTodas = document.getElementById('marcar-todas');
    const modalNotificacion = new bootstrap.Modal(document.getElementById('modalNotificacion'));

    // Cargar notificaciones
    function cargarNotificaciones() {
        fetch('?pagina=notificacion&accion=listar')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar las notificaciones');
                }
                return response.json();
            })
            .then(data => {
                if (data.exito && data.data) {
                    mostrarNotificaciones(data.data);
                } else {
                    throw new Error(data.mensaje || 'Error al procesar las notificaciones');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('No se pudieron cargar las notificaciones. Por favor, recarga la página.');
            })
            .finally(() => {
                if (cargando) cargando.style.display = 'none';
            });
    }

    // Mostrar notificaciones en la lista
    function mostrarNotificaciones(notificaciones) {
        if (!notificaciones || notificaciones.length === 0) {
            listaNotificaciones.innerHTML = `
                <div class="text-center p-4">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="mb-0">No hay notificaciones para mostrar</p>
                </div>`;
            return;
        }

        let html = '';
        notificaciones.forEach(notif => {
            const fecha = new Date(notif.fecha_creacion);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            const claseNotificacion = notif.leido ? 'notificacion-leida' : 'notificacion-no-leida';
            
            html += `
                <div class="list-group-item list-group-item-action ${claseNotificacion}" 
                     data-id="${notif.id_notificacion}" 
                     data-titulo="${notif.titulo}" 
                     data-mensaje="${notif.mensaje}" 
                     data-fecha="${fechaFormateada}">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${notif.titulo}</h6>
                        <small class="fecha-notificacion">${fechaFormateada}</small>
                    </div>
                    <p class="mb-1">${notif.mensaje.substring(0, 100)}${notif.mensaje.length > 100 ? '...' : ''}</p>
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-sm btn-outline-primary btn-marcar-leido" data-id="${notif.id_notificacion}">
                            <i class="fas fa-check"></i> Marcar como leída
                        </button>
                    </div>
                </div>`;
        });

        listaNotificaciones.innerHTML = html;
        agregarManejadoresEventos();
    }

    // Mostrar mensaje de error
    function mostrarError(mensaje) {
        listaNotificaciones.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${mensaje}
                <button class="btn btn-sm btn-outline-danger ms-3" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> Reintentar
                </button>
            </div>`;
    }

    // Agregar manejadores de eventos a las notificaciones
    function agregarManejadoresEventos() {
        // Marcar como leída al hacer clic
        document.querySelectorAll('.btn-marcar-leido').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const idNotificacion = this.getAttribute('data-id');
                marcarComoLeida(idNotificacion, this);
            });
        });

        // Mostrar detalles en modal
        document.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('click', function() {
                const titulo = this.getAttribute('data-titulo');
                const mensaje = this.getAttribute('data-mensaje');
                const fecha = this.getAttribute('data-fecha');
                
                document.getElementById('modalNotificacionTitulo').textContent = titulo;
                document.getElementById('modalNotificacionMensaje').textContent = mensaje;
                document.getElementById('modalNotificacionFecha').textContent = `Recibido: ${fecha}`;
                
                modalNotificacion.show();
                
                // Marcar como leída si no lo está
                if (this.classList.contains('notificacion-no-leida')) {
                    const idNotificacion = this.getAttribute('data-id');
                    marcarComoLeida(idNotificacion, this);
                }
            });
        });
    }

    // Función para marcar una notificación como leída
    function marcarComoLeida(idNotificacion, elemento) {
        fetch('?pagina=notificacion&accion=marcar_leida', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id_notificacion=${idNotificacion}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                // Actualizar la interfaz
                if (elemento) {
                    elemento.closest('.list-group-item').classList.remove('notificacion-no-leida');
                    elemento.closest('.list-group-item').classList.add('notificacion-leida');
                    elemento.remove();
                }
            } else {
                console.error('Error al marcar como leída:', data.mensaje);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Marcar todas como leídas
    if (btnMarcarTodas) {
        btnMarcarTodas.addEventListener('click', function() {
            fetch('?pagina=notificacion&accion=marcar_todas_leidas', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    // Actualizar la interfaz
                    document.querySelectorAll('.notificacion-no-leida').forEach(item => {
                        item.classList.remove('notificacion-no-leida');
                        item.classList.add('notificacion-leida');
                        const btnLeido = item.querySelector('.btn-marcar-leido');
                        if (btnLeido) btnLeido.remove();
                    });
                } else {
                    console.error('Error al marcar todas como leídas:', data.mensaje);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }

    // Cargar notificaciones al iniciar
    cargarNotificaciones();
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>