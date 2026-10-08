// Modal de Ayuda - Integración para Recepción
$(document).ready(function() {
    let modalAyudaInstance = null;
    
    // Función para cargar y mostrar el modal de ayuda con contexto específico
    function cargarYMostrarModalAyuda(contexto = null) {
        // Cargar CSS si no está cargado
        if (!$('link[href*="ayuda/css/modal.css"]').length) {
            $('<link>')
                .attr({
                    'rel': 'stylesheet',
                    'type': 'text/css',
                    'href': 'assets/public/ayuda/css/modal.css'
                })
                .appendTo('head');
        }
        
        // Cargar HTML del modal
        $.get('assets/public/ayuda/recepcion.php')
            .done(function(html) {
                // Solo agregar modal si no existe
                if (!$('#modalAyuda').length) {
                    $('body').append(html);
                }
                
                // Cargar JS del modal si no está cargado
                if (!$('script[src*="ayuda/js/modal.js"]').length) {
                    $.getScript('assets/public/ayuda/js/modal.js')
                        .done(function() {
                            // Inicializar modal
                            if (typeof inicializarModalAyudaUsuario === 'function') {
                                modalAyudaInstance = inicializarModalAyudaUsuario();
                                
                                // Abrir modal con contexto si se proporciona
                                if (contexto) {
                                    setTimeout(() => {
                                        const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                                        if (slideIndex !== undefined) {
                                            modalAyudaInstance.goToSlide(slideIndex);
                                        }
                                    }, 300);
                                }
                                
                                // Abrir modal
                                modalAyudaInstance.openModal();
                            } else {
                                console.error('La función inicializarModalAyudaUsuario no está disponible');
                            }
                        })
                        .fail(function() {
                            console.error('Error al cargar el JavaScript del modal de ayuda');
                        });
                } else {
                    // Si el JS ya está cargado, solo abrir el modal existente
                    if (typeof inicializarModalAyudaUsuario === 'function') {
                        modalAyudaInstance = inicializarModalAyudaUsuario();
                        
                        // Abrir modal con contexto si se proporciona
                        if (contexto) {
                            setTimeout(() => {
                                const slideIndex = modalAyudaInstance.mapeoContextos[contexto];
                                if (slideIndex !== undefined) {
                                    modalAyudaInstance.goToSlide(slideIndex);
                                }
                            }, 300);
                        }
                        
                        // Abrir modal
                        modalAyudaInstance.openModal();
                    }
                }
            })
        .fail(function() {
            console.error('Error al cargar el HTML del modal de recepción');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cargar el contenido de ayuda'
            });
        });
    }
    
    // Botón de ayuda principal (si existe)
    $('.btn-ayuda').off('click.ayuda').on('click.ayuda', function(e) {
        e.preventDefault();
        console.log('Clic en botón de ayuda detectado');
        cargarYMostrarModalAyuda();
    });
    
    // Botón de ayuda dentro de modales
    $(document).on('click.ayuda', '.btn-ayuda-modal', function(e) {
        e.preventDefault();
        const contexto = $(this).data('contexto');
        console.log('Clic en botón de ayuda modal con contexto:', contexto);
        cargarYMostrarModalAyuda(contexto);
    });
});