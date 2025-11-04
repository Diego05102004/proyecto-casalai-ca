document.addEventListener('DOMContentLoaded', function() {
    // Cargar notificaciones al cargar la página
    cargarNotificaciones();
    
    // Configurar el botón para marcar todas como leídas
    document.getElementById('marcar-todas-leidas').addEventListener('click', marcarTodasComoLeidas);
    
    // Configurar el modal de Bootstrap
    const modalNotificacion = new bootstrap.Modal(document.getElementById('modalNotificacion'));
    
    // Función para cargar las notificaciones
    function cargarNotificaciones() {
        fetch('controlador/notificacion.controlador.php?accion=listarNotificaciones')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar las notificaciones');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    mostrarError(data.error);
                    return;
                }
                
                const contenedor = document.getElementById('lista-notificaciones');
                
                if (data.length === 0) {
                    contenedor.innerHTML = `
                        <div class="text-center p-4">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <p class="mb-0">No tienes notificaciones</p>
                        </div>
                    `;
                    document.getElementById('contador-notificaciones').textContent = '0';
                    return;
                }
                
                // Actualizar contador
                const noLeidas = data.filter(notif => !notif.leido).length;
                document.getElementById('contador-notificaciones').textContent = data.length;
                
                // Generar HTML de las notificaciones
                contenedor.innerHTML = data.map(notificacion => `
                    <div class="notificacion-item ${notificacion.leido ? '' : 'no-leida'}" 
                         data-id="${notificacion.id_notificacion}" 
                         data-titulo="${escapeHtml(notificacion.titulo)}"
                         data-mensaje="${escapeHtml(notificacion.mensaje)}"
                         data-fecha="${notificacion.fecha_formateada || notificacion.fecha_creacion}">
                        <div class="notificacion-contenido">
                            <div class="d-flex justify-content-between align-items-start">
                                <h6 class="notificacion-titulo mb-1">${escapeHtml(notificacion.titulo)}</h6>
                                ${notificacion.leido ? '' : '<span class="badge bg-primary">Nuevo</span>'}
                            </div>
                            <p class="notificacion-mensaje mb-1">${truncateText(notificacion.mensaje, 80)}</p>
                            <small class="notificacion-fecha">${formatearFecha(notificacion.fecha_formateada || notificacion.fecha_creacion)}</small>
                        </div>
                    </div>
                `).join('');
                
                // Agregar manejadores de eventos a las notificaciones
                document.querySelectorAll('.notificacion-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const titulo = this.getAttribute('data-titulo');
                        const mensaje = this.getAttribute('data-mensaje');
                        const fecha = this.getAttribute('data-fecha');
                        
                        // Actualizar el modal con los datos de la notificación
                        document.getElementById('modalNotificacionTitulo').textContent = titulo;
                        document.getElementById('modalNotificacionMensaje').textContent = mensaje;
                        document.getElementById('modalNotificacionFecha').textContent = formatearFecha(fecha);
                        
                        // Mostrar el modal
                        modalNotificacion.show();
                        
                        // Marcar como leída si no lo está
                        if (this.classList.contains('no-leida')) {
                            const idNotificacion = this.getAttribute('data-id');
                            marcarComoLeida(idNotificacion, this);
                        }
                    });
                });
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar las notificaciones. Por favor, inténtalo de nuevo.');
            });
    }
    
    // Función para marcar una notificación como leída
    function marcarComoLeida(idNotificacion, elemento) {
        fetch('controlador/notificacion.controlador.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `accion=marcarComoLeida&id_notificacion=${idNotificacion}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                elemento.classList.remove('no-leida');
                elemento.classList.add('notificacion-leida');
                
                // Actualizar contador
                const contador = document.getElementById('contador-notificaciones');
                const nuevoContador = parseInt(contador.textContent) - 1;
                contador.textContent = nuevoContador > 0 ? nuevoContador : '0';
                
                // Eliminar el badge de "Nuevo"
                const badge = elemento.querySelector('.badge');
                if (badge) {
                    badge.remove();
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Función para marcar todas las notificaciones como leídas
    function marcarTodasComoLeidas() {
        fetch('controlador/notificacion.controlador.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'accion=marcarTodasComoLeidas'
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                // Actualizar la interfaz
                document.querySelectorAll('.notificacion-item').forEach(item => {
                    item.classList.remove('no-leida');
                    item.classList.add('notificacion-leida');
                    const badge = item.querySelector('.badge');
                    if (badge) {
                        badge.remove();
                    }
                });
                
                // Actualizar contador
                document.getElementById('contador-notificaciones').textContent = '0';
                
                // Mostrar mensaje de éxito
                mostrarMensaje('Todas las notificaciones han sido marcadas como leídas', 'success');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al marcar las notificaciones como leídas');
        });
    }
    
    // Funciones auxiliares
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    function truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
    
    function formatearFecha(fechaString) {
        if (!fechaString) return '';
        
        const fecha = new Date(fechaString);
        if (isNaN(fecha.getTime())) return fechaString;
        
        return fecha.toLocaleString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    function mostrarError(mensaje) {
        const contenedor = document.getElementById('lista-notificaciones');
        contenedor.innerHTML = `
            <div class="alert alert-danger m-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        `;
    }
    
    function mostrarMensaje(mensaje, tipo = 'success') {
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        alerta.role = 'alert';
        alerta.style.zIndex = '1060';
        alerta.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        `;
        
        document.body.appendChild(alerta);
        
        // Eliminar la alerta después de 5 segundos
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alerta);
            bsAlert.close();
        }, 5000);
    }
});
