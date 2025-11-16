// Función para verificar errores de clase no encontrada
function verificarErroresClaseNoEncontrada() {
    // Verificar si hay un mensaje de error en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    
    // Verificar si hay un mensaje de error en el localStorage
    const errorStorage = localStorage.getItem('backupError');
    
    if (error || errorStorage) {
        const mensajeError = error || errorStorage;
        
        // Verificar si es un error de clase no encontrada
        if (mensajeError.includes('Class "') && mensajeError.includes('" not found')) {
            const nombreClase = mensajeError.split('Class \"')[1].split('\" not found')[0];
            
            Swal.fire({
                title: 'Error de Configuración',
                html: `No se pudo cargar la clase: <strong>${nombreClase}</strong><br><br>
                       Por favor, verifica que el autoloader de Composer esté configurado correctamente.`,
                icon: 'error',
                confirmButtonText: 'Entendido',
                allowOutsideClick: false
            });
            
            // Limpiar el error del localStorage
            localStorage.removeItem('backupError');
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const DEBUG = true;
    const dlog = (...args) => { if (DEBUG) { console.log('[BACKUP]', ...args); } };
    dlog('Inicializando módulo de respaldo...');
    
    // Verificar errores de clase no encontrada al cargar la página
    verificarErroresClaseNoEncontrada();

    // Mostrar notificaciones con SweetAlert2
    function mostrarMensaje(titulo, mensaje, tipo = 'success') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        
        Toast.fire({
            icon: tipo,
            title: titulo,
            text: mensaje
        });
    }

    // Función para confirmar acciones importantes
    function confirmarAccion(mensaje) {
        return Swal.fire({
            title: '¿Estás seguro?',
            text: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        });
    }

    // Función para formatear bytes a un formato legible
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    // Función para manejar la generación de respaldos
    function generarRespaldo(tipo) {
        const tipoTexto = tipo === 'S' ? 'seguridad' : 'principal';
        
        Swal.fire({
            title: `Generando respaldo de ${tipoTexto}...`,
            html: `Por favor, espera mientras se genera el respaldo de la base de datos ${tipoTexto}.`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                
                // Realizar la petición para generar el respaldo
                fetch(`Modelo/Controlador/backup.php?accion=generar&tipo=${tipo}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.error || 'Error en la respuesta del servidor');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            html: `
                                <div class="text-left">
                                    <p>El respaldo se generó correctamente:</p>
                                    <ul class="mt-2">
                                        <li><strong>Archivo:</strong> ${data.archivo}</li>
                                        <li><strong>Fecha:</strong> ${data.fecha || 'N/A'}</li>
                                    </ul>
                                </div>
                            `,
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                confirmButton: 'btn btn-success'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            // Actualizar la tabla de respaldos
                            actualizarTablaRespaldos();
                        });
                    } else {
                        throw new Error(data.error || 'No se pudo generar el respaldo');
                    }
                })
                .catch(error => {
                    console.error('Error al generar el respaldo:', error);
                    
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al generar el respaldo',
                        text: error.message || 'Ocurrió un error inesperado',
                        confirmButtonText: 'Entendido',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                });
            }
        });
    }

    // Configurar botones de respaldo
    document.getElementById('btn-backup-seguridad')?.addEventListener('click', (e) => {
        e.preventDefault();
        generarRespaldo('S');
    });
    
    document.getElementById('btn-backup-principal')?.addEventListener('click', (e) => {
        e.preventDefault();
        generarRespaldo('P');
    });

    // Función para actualizar la tabla de respaldos
    function actualizarTablaRespaldos() {
        fetch('Modelo/Controlador/backup.php?accion=listar', {
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            // Verificar si hay un error de clase no encontrada
            if (data && data.success === false && data.error) {
                // Guardar el error en localStorage para mostrarlo después de recargar
                localStorage.setItem('backupError', data.error);
                
                // Si hay un mensaje de depuración, mostrarlo en la consola
                if (data.debug) {
                    console.error('Error en el servidor:', data.error);
                    console.debug('Detalles del error:', data.debug);
                }
                
                // Recargar la página para mostrar el mensaje de error
                window.location.reload();
                return;
            }

            const tbody = document.querySelector('#tablaConsultas tbody');
            if (!data || data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">No hay archivos de respaldo disponibles</td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = data.map(backup => `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-database me-2 text-primary"></i>
                            <span class="fw-medium">${backup.nombre}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${backup.tipo === 'Seguridad' ? 'bg-warning' : 'bg-info'} text-dark">
                            ${backup.tipo}
                        </span>
                    </td>
                    <td>${backup.tamano}</td>
                    <td>${backup.fecha_modificacion}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary btn-descargar" 
                                data-archivo="${backup.nombre}"
                                title="Descargar Backup">
                                <i class="fas fa-download"></i>
                            </button>
                            <button type="button" class="btn btn-outline-success btn-restaurar"
                                data-archivo="${backup.nombre}"
                                title="Restaurar Backup">
                                <i class="fas fa-redo"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-eliminar"
                                data-archivo="${backup.nombre}"
                                title="Eliminar Backup">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            // Agregar eventos a los botones después de actualizar la tabla
            agregarEventosBotones();
        })
        .catch(error => {
            console.error('Error al cargar los respaldos:', error);
            mostrarMensaje('Error', 'No se pudieron cargar los respaldos', 'error');
        });
    }

    // Función para agregar eventos a los botones de la tabla
    function agregarEventosBotones() {
        // Evento para botones de descargar
        document.querySelectorAll('.btn-descargar').forEach(btn => {
            btn.addEventListener('click', function() {
                const archivo = this.getAttribute('data-archivo');
                window.location.href = `Controlador/backup.php?accion=descargar&archivo=${encodeURIComponent(archivo)}`;
            });
        });

        // Botón de restauración
        document.querySelectorAll('.btn-restaurar').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const archivo = this.getAttribute('data-archivo');
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: `¿Deseas restaurar el respaldo ${archivo}? Esta acción sobrescribirá los datos actuales.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, restaurar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`Controlador/backup.php?accion=restaurar&archivo=${encodeURIComponent(archivo)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    mostrarMensaje('Éxito', data.message || 'Respaldo restaurado correctamente', 'success');
                                    actualizarTablaRespaldos();
                                } else {
                                    throw new Error(data.error || 'Error al restaurar el respaldo');
                                }
                            })
                            .catch(error => {
                                console.error('Error al restaurar:', error);
                                mostrarMensaje('Error', error.message || 'Error al restaurar el respaldo', 'error');
                            });
                    }
                });
            });
        });

        // Botón de eliminación
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const archivo = this.getAttribute('data-archivo');
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: `¿Deseas eliminar el respaldo ${archivo}? Esta acción no se puede deshacer.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`Controlador/backup.php?accion=eliminar&archivo=${encodeURIComponent(archivo)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    mostrarMensaje('Éxito', data.message || 'Respaldo eliminado correctamente', 'success');
                                    actualizarTablaRespaldos();
                                } else {
                                    throw new Error(data.error || 'Error al eliminar el respaldo');
                                }
                            })
                            .catch(error => {
                                console.error('Error al eliminar:', error);
                                mostrarMensaje('Error', error.message || 'Error al eliminar el respaldo', 'error');
                            });
                    }
                });
            });
        });
    }

    // Restaurar el último respaldo disponible
    document.getElementById('btn-restaurar-ultimo')?.addEventListener('click', function (e) {
        e.preventDefault();
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Deseas restaurar el último respaldo disponible?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, restaurar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const urlList = 'Controlador/backup.php?accion=consultar';
                dlog('Solicitando lista de respaldos...', { url: urlList });
                
                fetch(urlList)
                    .then(async response => {
                        const text = await response.text();
                        dlog('Respuesta consultar status=', response.status, 'texto=', text);
                        try { 
                            return JSON.parse(text); 
                        } catch (e) { 
                            throw new Error(text || 'Respuesta vacía'); 
                        }
                    })
                    .then(data => {
                        if (data.length > 0) {
                            // Ordena por nombre descendente (el más reciente primero)
                            data.sort().reverse();
                            let ultimo = data[0];
                            const urlRestore = 'Controlador/backup.php?accion=restaurar&archivo=' + encodeURIComponent(ultimo);
                            
                            dlog('Restaurando último respaldo...', { archivo: ultimo, url: urlRestore });
                            
                            return fetch(urlRestore)
                                .then(async response => {
                                    const text = await response.text();
                                    dlog('Respuesta restaurar status=', response.status, 'texto=', text);
                                    try { 
                                        return JSON.parse(text); 
                                    } catch (e) { 
                                        throw new Error(text || 'Respuesta vacía'); 
                                    }
                                })
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: '¡Éxito!',
                                            text: 'La restauración se completó correctamente',
                                            confirmButtonText: 'Aceptar'
                                        }).then(() => {
                                            location.reload();
                                        });
                                    } else {
                                        throw new Error(data.error || 'Error al restaurar el respaldo');
                                    }
                                });
                        } else {
                            throw new Error('No hay respaldos disponibles');
                        }
                    })
                    .catch(err => {
                        console.error('Error al restaurar el respaldo:', err);
                        return Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo restaurar el respaldo: ' + (err.message || 'Error desconocido'),
                            confirmButtonText: 'Aceptar',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                    })
                    .finally(() => {
                        // Actualizar la tabla de respaldos
                        actualizarTablaRespaldos();
                    });
                } else {
                    throw new Error(data.error || 'No se pudo generar el respaldo');
                }
            })
            .catch(error => {
                console.error('Error al generar el respaldo:', error);
                
                let mensajeError = error.message || 'Ocurrió un error inesperado';
                
                // Mostrar mensaje de error detallado
                Swal.fire({
                    icon: 'error',
                    title: 'Error al generar el respaldo',
                    html: `
                        <div class="text-left">
                            <p>${mensajeError}</p>
                            ${data && data.debug ? 
                                `<details class="mt-3">
                                    <summary class="text-primary cursor-pointer">Ver detalles técnicos</summary>
                                    <pre class="bg-light p-2 mt-2 text-left small" style="max-height: 200px; overflow: auto;">
${JSON.stringify(JSON.parse(data.debug), null, 2)}
                                    </pre>
                                </details>` : ''
                            }
                        </div>
                    `,
                    confirmButtonText: 'Entendido',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            });
    });
    // Inicializar DataTables cuando el documento esté listo
    $(document).ready(function() {
        // Inicializar DataTable directamente
        const dataTable = $('#tablaConsultas').DataTable({
            order: [[3, 'desc']], // Ordenar por fecha de modificación (columna 4) de forma descendente
            language: {
                url: 'public/js/Spanish.json'
            },
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [4] } // Hacer que la columna de acciones no sea ordenable
            ],
            initComplete: function() {
                // Reasignar eventos después de la inicialización
                agregarEventosBotones();
            }
        });
        
        // Actualizar la tabla de respaldos
        actualizarTablaRespaldos();
    })
});