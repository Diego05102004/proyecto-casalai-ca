document.addEventListener('DOMContentLoaded', function() {
    const listaNotificaciones = document.getElementById('lista-notificaciones');
    const cargando = document.getElementById('cargando');
    const btnMarcarTodas = document.getElementById('marcar-todas');
    
    // Cargar notificaciones al cargar la página
    if (listaNotificaciones) {
        cargarNotificaciones();
    }
    
    // Configurar el botón para marcar todas como leídas
    if (btnMarcarTodas) {
        btnMarcarTodas.addEventListener('click', marcarTodasLeidas);
    }
    
    // Función para formatear la fecha en un formato legible
    function formatearFecha(fechaString) {
        if (!fechaString) return 'Hoy';
        
        try {
            // Asegurarse de que la fecha tenga el formato correcto
            let fecha = new Date(fechaString);
            
            // Si la fecha no es válida, intentar con formato ISO
            if (isNaN(fecha.getTime())) {
                // Intentar con formato ISO 8601 (MySQL/MariaDB)
                fecha = new Date(fechaString.replace(' ', 'T') + 'Z');
                
                // Si aún no es válida, devolver 'Hoy'
                if (isNaN(fecha.getTime())) return 'Hoy';
            }
            
            const ahora = new Date();
            const diferencia = Math.floor((ahora - fecha) / 1000); // Diferencia en segundos
            
            if (diferencia < 60) {
                return 'Hace unos segundos';
            } else if (diferencia < 3600) {
                const minutos = Math.floor(diferencia / 60);
                return `Hace ${minutos} minuto${minutos > 1 ? 's' : ''}`;
            } else if (diferencia < 86400) {
                const horas = Math.floor(diferencia / 3600);
                return `Hace ${horas} hora${horas > 1 ? 's' : ''}`;
            } else if (diferencia < 604800) { // Menos de una semana
                const dias = Math.floor(diferencia / 86400);
                return `Hace ${dias} día${dias > 1 ? 's' : ''}`;
            } else {
                return fecha.toLocaleDateString('es-ES', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        } catch (e) {
            console.error('Error al formatear fecha:', e, 'Valor recibido:', fechaString);
            return 'Hoy';
        }
    }
    
    // Función para cargar las notificaciones
    function cargarNotificaciones() {
        if (!listaNotificaciones) return;
        
        // Mostrar indicador de carga
        if (cargando) cargando.style.display = 'block';
        
        fetch('?pagina=notificacion&accion=listar')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al cargar las notificaciones: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data); // Para depuración
                if (data && data.exito && Array.isArray(data.data)) {
                    mostrarNotificaciones(data.data);
                } else {
                    throw new Error(data?.mensaje || 'Formato de respuesta inesperado');
                }
            })
            .catch(error => {
                console.error('Error al cargar notificaciones:', error);
                mostrarError('No se pudieron cargar las notificaciones. ' + 
                           'Por favor, recarga la página. Detalles en consola.');
            })
            .finally(() => {
                if (cargando) cargando.style.display = 'none';
            });
    }
    
    // Función para marcar una notificación como leída
    function marcarComoLeida(idNotificacion, elemento) {
        if (!idNotificacion) {
            console.error('ID de notificación no válido');
            return;
        }

        // Mostrar indicador de carga
        if (elemento && elemento.classList) {
            const originalHTML = elemento.innerHTML;
            elemento.disabled = true;
            elemento.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...';
        }

        const formData = new FormData();
        formData.append('id_notificacion', idNotificacion);
        
        fetch('?pagina=notificacion&accion=marcar_leida', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.exito) {
                // Actualizar la interfaz
                if (elemento) {
                    const item = elemento.closest ? 
                        elemento.closest('.list-group-item') : 
                        document.querySelector(`.list-group-item[data-id="${idNotificacion}"]`);
                    
                    if (item) {
                        item.classList.remove('notificacion-no-leida');
                        item.classList.add('notificacion-leida');
                        const botonMarcarLeido = item.querySelector('.btn-marcar-leido');
                        if (botonMarcarLeido) botonMarcarLeido.remove();
                    }
                }
                // Actualizar contador en la barra de navegación
                const contador = document.getElementById('contador-notificaciones');
                if (contador) {
                    const nuevoContador = Math.max(0, parseInt(contador.textContent) - 1);
                    contador.textContent = nuevoContador > 0 ? nuevoContador : '0';
                }
                
                mostrarMensaje('Notificación marcada como leída', 'success');
            } else {
                throw new Error(data.mensaje || 'Error al marcar como leída');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error al marcar la notificación como leída: ' + error.message);
        });
    }
    
    // Función para marcar todas las notificaciones como leídas
    function marcarTodasLeidas() {
        if (!confirm('¿Estás seguro de que deseas marcar todas las notificaciones como leídas?')) {
            return;
        }

        fetch('?pagina=notificacion&accion=marcar_todas_leidas', {
            method: 'POST'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.exito) {
                // Actualizar la interfaz
                const itemsNoLeidos = document.querySelectorAll('.list-group-item.notificacion-no-leida');
                itemsNoLeidos.forEach(item => {
                    item.classList.remove('notificacion-no-leida');
                    item.classList.add('notificacion-leida');
                    const boton = item.querySelector('.btn-marcar-leido');
                    if (boton) boton.remove();
                });
                
                // Actualizar contador en la barra de navegación
                const contador = document.getElementById('contador-notificaciones');
                if (contador) contador.textContent = '0';
                
                mostrarMensaje('Todas las notificaciones han sido marcadas como leídas', 'success');
            } else {
                throw new Error(data.mensaje || 'Error al marcar todas las notificaciones como leídas');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('No se pudieron marcar todas las notificaciones como leídas: ' + error.message);
        });
    }
    
    // Función para mostrar notificaciones
    function mostrarNotificaciones(notificaciones) {
        if (!listaNotificaciones) return;
        
        // Mostrar indicador de carga
        if (cargando) cargando.style.display = 'none';
        
        if (!notificaciones || !Array.isArray(notificaciones) || notificaciones.length === 0) {
            listaNotificaciones.innerHTML = `
                <div class="text-center p-4">
                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                    <p class="mb-0">No hay notificaciones para mostrar</p>
                </div>`;
            return;
        }
    
        let html = '';
        notificaciones.forEach(notif => {
            try {
                // Obtener y formatear la fecha
                const fechaOriginal = notif.fecha_creacion || notif.fecha_hora || new Date().toISOString();
                const fechaFormateada = formatearFecha(fechaOriginal);
                
                // Determinar si la notificación está leída (compatibilidad con diferentes formatos)
                // Por defecto, asumir que no está leída si no se especifica lo contrario
                const estaLeida = (
                    notif.leido === '1' || notif.leido === 1 || notif.leido === true || 
                    notif.leida === '1' || notif.leida === 1 || notif.leida === true ||
                    notif.estado === 'leido' || notif.estado === 'leída' ||
                    notif.estado === '1' || notif.estado === 1
                );
                
                const claseNotificacion = estaLeida ? 'notificacion-leida' : 'notificacion-no-leida';
                const idNotificacion = notif.id_notificacion || notif.id || `temp-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
                
                // Depuración: Mostrar información de la notificación
                console.log('Notificación:', {
                    id: idNotificacion,
                    titulo: notif.titulo,
                    leido: notif.leido,
                    leida: notif.leida,
                    estado: notif.estado,
                    marcadaComoLeida: estaLeida
                });
                
                if (!idNotificacion) {
                    console.warn('Notificación sin ID:', notif);
                    return;
                }
                
                // Validar y escapar los datos
                const titulo = escapeHtml(notif.titulo || 'Notificación sin título');
                const mensaje = escapeHtml(notif.mensaje || 'Sin descripción');
                
                html += `
                    <div class="list-group-item list-group-item-action ${claseNotificacion}" 
                         data-id="${escapeHtml(String(idNotificacion))}">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${titulo}</h6>
                            <small class="fecha-notificacion text-muted">${fechaFormateada}</small>
                        </div>
                        <p class="mb-1">${truncateText(mensaje, 100)}</p>`;
                
                // Mostrar el botón en todas las notificaciones que no estén marcadas como leídas
                // Verificar explícitamente si está marcada como no leída
                const mostrarBoton = (
                    notif.leido === '0' || notif.leido === 0 || notif.leido === false ||
                    notif.leida === '0' || notif.leida === 0 || notif.leida === false ||
                    notif.estado === '0' || notif.estado === 0 || notif.estado === 'no_leido' ||
                    notif.leido === undefined || notif.leida === undefined || notif.estado === undefined
                );
                
                if (mostrarBoton) {
                    html += `
                        <div class="d-flex justify-content-end mt-2">
                            <button class="btn btn-sm btn-outline-primary btn-marcar-leido" 
                                    data-id="${escapeHtml(String(idNotificacion))}"
                                    data-leido="${estaLeida ? '1' : '0'}">
                                <i class="fas fa-check me-1"></i> Marcar como leída
                            </button>
                        </div>`;
                    
                    // Si la notificación no está marcada como leída, forzar la clase no-leida
                    if (!estaLeida) {
                        html = html.replace('list-group-item-action', 'list-group-item-action notificacion-no-leida');
                    }
                }
                
                html += `
                    </div>`;
                    
            } catch (error) {
                console.error('Error al procesar notificación:', error, notif);
            }
        });
        
        listaNotificaciones.innerHTML = html;
        agregarManejadoresEventos();
    }

    // Agregar manejadores de eventos
    function agregarManejadoresEventos() {
        // Marcar como leída al hacer clic en el botón
        document.querySelectorAll('.btn-marcar-leido').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const idNotificacion = this.getAttribute('data-id');
                if (idNotificacion) {
                    marcarComoLeida(idNotificacion, this);
                } else {
                    console.error('No se pudo obtener el ID de la notificación');
                }
            });
        });
        
        // Marcar como leída al hacer clic en la notificación
        document.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Evitar marcar como leída si se hace clic en un botón dentro de la notificación
                if (e.target.closest('.btn-marcar-leido')) {
                    return;
                }
                
                const idNotificacion = this.getAttribute('data-id');
                if (idNotificacion && !this.classList.contains('notificacion-leida')) {
                    marcarComoLeida(idNotificacion, this);
                }
            });
        });
    }

    // Funciones auxiliares
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
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

    function mostrarError(mensaje) {
        console.error(mensaje);
        // Mostrar mensaje de error en la interfaz
        listaNotificaciones.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>`;
        
        if (cargando) cargando.style.display = 'none';
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
