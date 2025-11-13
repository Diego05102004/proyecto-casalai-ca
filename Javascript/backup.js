document.addEventListener('DOMContentLoaded', function () {
    const DEBUG = true;
    const dlog = (...args) => { if (DEBUG) { console.log('[BACKUP]', ...args); } };
    dlog('Inicializando módulo de respaldo...');

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

    // Función para actualizar la tabla de respaldos
    function actualizarTablaRespaldos() {
        fetch('Modelo/Controlador/backup.php?accion=listar')
            .then(response => response.json())
            .then(data => {
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
                window.location.href = `Modelo/Controlador/backup.php?accion=descargar&archivo=${encodeURIComponent(archivo)}`;
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
                        fetch(`Modelo/Controlador/backup.php?accion=restaurar&archivo=${encodeURIComponent(archivo)}`)
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
                        fetch(`Modelo/Controlador/backup.php?accion=eliminar&archivo=${encodeURIComponent(archivo)}`)
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
            const urlList = 'Modelo/Controlador/backup.php?accion=consultar';
            dlog('Solicitando lista de respaldos...', { url: urlList });
            fetch(urlList)
                .then(async response => {
                    const text = await response.text();
                    dlog('Respuesta consultar status=', response.status, 'texto=', text);
                    try { return JSON.parse(text); } catch (e) { throw new Error(text || 'Respuesta vacía'); }
                })
                .then(data => {

                    if (data.length > 0) {
                        // Ordena por nombre descendente (el más reciente primero)
                        data.sort().reverse();
                        let ultimo = data[0];
                        const urlRestore = 'Modelo/Controlador/backup.php?accion=restaurar&archivo=' + encodeURIComponent(ultimo);
                        dlog('Restaurando último respaldo...', { archivo: ultimo, url: urlRestore });
                        fetch(urlRestore)
                            .then(async response => {
                                const text = await response.text();
                                dlog('Respuesta restaurar status=', response.status, 'texto=', text);
                                try { return JSON.parse(text); } catch (e) { throw new Error(text || 'Respuesta vacía'); }
                            })
                            .then(data => {
                                if (data.success) {
                                    alert('Restauración exitosa');
                                    location.reload();
                                } else {
                                    alert('Error al restaurar');
                                }
                            })
                            .catch(err => { console.error(err); alert('Error de respuesta del servidor (restaurar): ' + err.message); });

                    } else {
                        alert('No hay respaldos disponibles');
                    }
                });
        }
    });

                });
        

    // Inicializar la tabla al cargar la página
    actualizarTablaRespaldos();

    // Configurar los botones de generación de respaldos
    document.getElementById('btn-backup-principal').addEventListener('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Generando respaldo...',
            text: 'Por favor, espera mientras se genera el respaldo.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                fetch('Modelo/Controlador/backup.php?accion=generar&tipo=P')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                '¡Éxito!',
                                'Respaldo principal generado correctamente.',
                                'success'
                            );
                            actualizarTablaRespaldos();
                        } else {
                            throw new Error(data.error || 'Error al generar el respaldo');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            'Error',
                            error.message || 'Ocurrió un error al generar el respaldo',
                            'error'
                        );
                    });
            }
        });
    });

    // Configurar botón de respaldo de seguridad
    document.getElementById('btn-backup-seguridad').addEventListener('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Generando respaldo de seguridad...',
            text: 'Por favor, espera mientras se genera el respaldo de seguridad.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                fetch('Modelo/Controlador/backup.php?accion=generar&tipo=S')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                '¡Éxito!',
                                'Respaldo de seguridad generado correctamente.',
                                'success'
                            );
                            actualizarTablaRespaldos();
                        } else {
                            throw new Error(data.error || 'Error al generar el respaldo de seguridad');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            'Error',
                            error.message || 'Ocurrió un error al generar el respaldo de seguridad',
                            'error'
                        );
                    });
            }
        });
    });

    // Inicializar DataTables después de cargar la página
    let dataTable;
    
    // Función para inicializar DataTables
    function inicializarDataTable() {
        if ($.fn.DataTable.isDataTable('#tablaConsultas')) {
            dataTable = $('#tablaConsultas').DataTable().destroy();
        }
        
        dataTable = $('#tablaConsultas').DataTable({
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
    }
    
    // Inicializar la tabla al cargar la página
    actualizarTablaRespaldos();
    
    // Inicializar DataTables cuando el documento esté listo
    $(document).ready(function() {
        inicializarDataTable();
    });
});