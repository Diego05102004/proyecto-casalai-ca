// catalogo.js - controlador principal del módulo de catálogo

$(document).ready(function () {
    // Variables globales
    const tasaEl = document.getElementById('tasa');
    let tasa = 0;
    if (tasaEl && typeof tasaEl.getAttribute === 'function') {
        const raw = tasaEl.getAttribute('value');
        tasa = isNaN(parseFloat(raw)) ? 0 : parseFloat(raw);
        // Limpiar chips (delegado)
        $(document).on('click', '.crumb-close', function(){
            const type = $(this).data('type');
            if (type === 'brand') {
                $('#filtroMarca').val('');
                filtrarPorMarca();
            }
            if (type === 'search') {
                $('#searchProduct').val('');
                filtrarYBuscarProductos();
            }
            renderCrumbs();
        });

        // Render inicial
        renderCrumbs();
    }

    let productosSeleccionados = [];
    let currentProductId = null;
    let currentComboId = null;
    let accesosSemanalesChart = null;
    let usuariosActivosChart = null;

    // Inicialización
    init();

    // Función de inicialización
    function init() {
        setupEventListeners();
        checkTabPreference();
        updateCartCount();
    }

    // Configurar event listeners
    function setupEventListeners() {
        // Navegación entre tabs
        $('#productos-tab').on('click', mostrarProductos);
        $('#combos-tab').on('click', mostrarCombos);
        $('#reportes-tab').on('click', mostrarReportes);

        // Filtrado de productos
        $('#filtroMarca').on('change', function(){
            renderCrumbs();
            filtrarPorMarca();
        });
        $('#btnSearch').on('click', function(){
            renderCrumbs();
            buscarProductos();
        });
        $('#searchProduct').on('keyup', function (e) {
            // Filtrado inmediato en grid; mantener Enter para fallback
            if ($('.productos-grid').length) {
                filtrarYBuscarProductos();
                renderCrumbs();
            }
            if (e.key === 'Enter') buscarProductos();
        });

        // Carrito
        // Solo productos: excluir botones de combo que comparten la clase
        $(document).on('click', '.btn-agregar-carrito:not(.btn-agregar-combo)', agregarAlCarrito);
        $(document).on('click', '.btn-agregar-combo', agregarComboAlCarrito);

        sincronizarCarritoNavbar();

        // Gestión de combos (admin)
        $('#nuevo_combo').on('click', mostrarModalNuevoCombo);
        $(document).on('click', '.btn-editar-combo', editarCombo);
        $(document).on('click', '.btn-cambiar-estado', mostrarModalCambioEstado);
        $('#agregar_producto').on('click', agregarProductoACombo);
        $(document).on('click', '.btn-eliminar-producto', eliminarProductoDeCombo);
        $('#guardar_combo').on('click', guardarCombo);
        $('#confirmarCambioEstado').on('click', cambiarEstadoCombo);

        // Modal de cantidad
        $('#confirmarCantidad').on('click', confirmarCantidad);

        

        
    }

    // Verificar preferencia de tab
    function checkTabPreference() {
        const tabPreference = localStorage.getItem('catalogoTab');

        if (tabPreference === 'combos') {
            mostrarCombos();
        } else if (tabPreference === 'reportes' && $('#reportes-tab').length) {
            mostrarReportes();
        } else {
            mostrarProductos();
        }
    }

    // Breadcrumb nuevo: rectángulo blanco + sección actual en burbuja azul
    function renderCrumbs(){
        const $wrap = $('#catalogoCrumbs');
        if ($wrap.length === 0) return;
        const brandVal = ($('#filtroMarca').val() || '').toString();
        const brandText = ($('#filtroMarca option:selected').text() || '').trim();
        const term = ($('#searchProduct').val() || '').trim();

        // Determinar cuál es el "actual" para pintar como burbuja azul
        let current = '';
        if (term) current = 'search'; else if (brandVal) current = 'brand'; else current = 'catalog';

        let html = '<div class="bc-wrap"><div class="bc-list">';
        if (current === 'catalog') {
            html += '<span class="bc-current">Catálogo</span>';
        } else {
            html += '<span class="bc-item"><a href="?pagina=catalogo">Catálogo</a></span>';
        }
        if (brandVal) {
            html += '<span class="bc-sep">/</span>';
            if (current === 'brand') {
                html += `<span class="bc-current">Marca: ${escapeHtml(brandText)}</span>`;
            } else {
                html += `<span class="bc-item"><a href="#" data-type="brand">Marca: ${escapeHtml(brandText)}</a></span>`;
            }
        }
        if (term) {
            html += '<span class="bc-sep">/</span>';
            html += `<span class="bc-current">Búsqueda: ${escapeHtml(term)}</span>`;
        }
        html += '</div></div>';
        $wrap.html(html);

        // Delegar click para limpiar filtros
        $wrap.find('a[data-type="brand"]').on('click', function(e){ e.preventDefault(); $('#filtroMarca').val(''); filtrarPorMarca(); renderCrumbs(); });
        $wrap.find('a[data-type="search"]').on('click', function(e){ e.preventDefault(); $('#searchProduct').val(''); filtrarYBuscarProductos(); renderCrumbs(); });
    }

    function escapeHtml(s){
        return String(s)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#039;');
    }

    // (revert) sin refreshMarcas

    // Mostrar sección de productos
    function mostrarProductos() {
        $('#productos-content').show();
        $('#combos-content').hide();
        $('#reportes-content').hide();

        $('#productos-tab').addClass('active');
        $('#combos-tab').removeClass('active');
        $('#reportes-tab').removeClass('active');

        localStorage.setItem('catalogoTab', 'productos');
    }

    // Mostrar sección de combos
    function mostrarCombos() {
        $('#productos-content').hide();
        $('#combos-content').show();
        $('#reportes-content').hide();

        $('#productos-tab').removeClass('active');
        $('#combos-tab').addClass('active');
        $('#reportes-tab').removeClass('active');

        localStorage.setItem('catalogoTab', 'combos');
    }

    // Mostrar sección de reportes
    function mostrarReportes() {
        $('#productos-content').hide();
        $('#combos-content').hide();
        $('#reportes-content').show();

        $('#productos-tab').removeClass('active');
        $('#combos-tab').removeClass('active');
        $('#reportes-tab').addClass('active');

        localStorage.setItem('catalogoTab', 'reportes');

        cargarDatosReportes();
    }

    // Filtrar productos por marca (grid primero, tabla como fallback)
    function filtrarPorMarca() {
        if ($('.productos-grid').length) {
            filtrarYBuscarProductos();
            return;
        }

        const idMarca = $(this).val();

        if ($.fn.DataTable && $.fn.DataTable.isDataTable && $.fn.DataTable.isDataTable('#tablaProductos')) {
            $('#tablaProductos').DataTable().destroy();
        }

        if (!$('#tablaProductos').length) return; // no tabla presente

        $.ajax({
            url: '?pagina=catalogo',
            type: 'POST',
            data: { accion: 'filtrar_por_marca', id_marca: idMarca },
            beforeSend: function () {
                $('#tablaProductos tbody').html(`
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                `);
            },
            success: function (response) {
                try {
                    const data = typeof response === 'object' ? response : JSON.parse(response);
                    if (data.status === 'success') {
                        $('#tablaProductos tbody').html(data.html);
                    } else {
                        $('#tablaProductos tbody').html(data.html || '<tr><td colspan="6" class="text-center py-4">No hay productos</td></tr>');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    $('#tablaProductos tbody').html('<tr><td colspan="6" class="text-center py-4">Error al procesar la respuesta</td></tr>');
                }
                if (typeof inicializarDataTableProductos === 'function') {
                    inicializarDataTableProductos();
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.status, xhr.statusText);
                $('#tablaProductos tbody').html('<tr><td colspan="6" class="text-center py-4">Error de conexión</td></tr>');
                if (typeof inicializarDataTableProductos === 'function') {
                    inicializarDataTableProductos();
                }
            }
        });
    }

    // Buscar productos (grid primero, tabla como fallback)
    function buscarProductos() {
        if ($('.productos-grid').length) {
            filtrarYBuscarProductos();
            return;
        }

        const termino = $('#searchProduct').val().trim();
        if (termino.length < 2) {
            Swal.fire('Búsqueda', 'Ingrese al menos 2 caracteres', 'info');
            return;
        }

        if (!$('#tablaProductos').length) return; // no tabla presente

        $.ajax({
            url: '?pagina=catalogo',
            type: 'POST',
            data: { accion: 'buscar_productos', termino: termino },
            beforeSend: function () {
                $('#tablaProductos').html(`
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </td>
                    </tr>
                `);
            },
            success: function (response) {
                try {
                    const data = typeof response === 'object' ? response : JSON.parse(response);
                    if (data.status === 'success') {
                        $('#tablaProductos').html(data.html);
                    } else {
                        mostrarErrorEnTabla(data.message || 'No se encontraron resultados');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    mostrarErrorEnTabla('Error al procesar la respuesta');
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.status, xhr.statusText);
                mostrarErrorEnTabla('Error de conexión');
            }
        });
    }

    // Filtrado en grid por marca y término de búsqueda
    function filtrarYBuscarProductos() {
        const term = ($('#searchProduct').val() || '').trim().toLowerCase();
        const brandVal = ($('#filtroMarca').val() || '').toString();
        const brandText = ($('#filtroMarca option:selected').text() || '').trim().toLowerCase();

        let visibles = 0;
        $('.producto-card').each(function () {
            const $card = $(this);
            const name = ($card.find('.producto-nombre').text() || '').toLowerCase();
            const serial = ($card.find('.producto-serial').text() || '').toLowerCase();
            const desc = ($card.find('.producto-descripcion').text() || '').toLowerCase();
            const brand = ($card.find('.producto-marca').text() || '').toLowerCase();

            const brandMatch = !brandVal || brand === brandText;
            const termMatch = term.length < 2 || name.includes(term) || serial.includes(term) || desc.includes(term) || brand.includes(term);

            const show = brandMatch && termMatch;
            $card.toggle(show);
            if (show) visibles++;
        });

        // Opcional: manejar estado vacío
        const $grid = $('.productos-grid');
        if ($grid.length) {
            let $empty = $grid.siblings('.empty-state.grid-empty');
            if (visibles === 0) {
                if ($empty.length === 0) {
                    $empty = $('<div class="empty-state grid-empty"><i class="bi bi-exclamation-circle"></i><h4>No hay resultados</h4><p>Ajusta los filtros o la búsqueda</p></div>');
                    $grid.after($empty);
                }
                $empty.show();
            } else {
                $empty.hide();
            }
        }
    }

    function sincronizarCarritoNavbar() {
        $.ajax({
            url: 'Controlador/obtener_carrito_count.php',
            method: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.success && data.count > 0) {
                    const cartBadge = document.querySelector('.cart-count-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        cartBadge.style.display = 'flex';
                    } else {
                        const cartBtn = document.getElementById('cart-btn');
                        if (cartBtn) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'cart-count-badge';
                            newBadge.textContent = data.count;
                            cartBtn.appendChild(newBadge);
                        }
                    }
                } else {
                    const cartBadge = document.querySelector('.cart-count-badge');
                    if (cartBadge) {
                        cartBadge.style.display = 'none';
                    }
                }
            },
            error: function(error) {
                console.error('Error sincronizando carrito:', error);
            }
        });
    }

// Agregar producto al carrito (cards) - cantidad fija 1
function agregarAlCarrito(e) {
    e.preventDefault();
    const button = $(this);
    const idProducto = button.data('id-producto');
    const stockDisponible = parseInt(button.data('stock'));

    if (!idProducto) return;
    if (isNaN(stockDisponible) || stockDisponible <= 0) {
        Swal.fire({ icon: 'error', title: 'Sin stock', text: 'Este producto no tiene stock disponible', timer: 1500, showConfirmButton: false });
        return;
    }

    if (typeof usuarioNoLogueado !== 'undefined' && usuarioNoLogueado) {
        MensajeInicio();
        return;
    }

    confirmarAgregarAlCarrito(idProducto, 1, button);
}

// Agregar combo al carrito (puedes dejarlo igual, ya que combos sí pueden tener varias cantidades)
function agregarComboAlCarrito() {
    const button = $(this);
    const idCombo = button.data('id-combo');

    button.prop('disabled', true);
    button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...');

    Swal.fire({
        title: 'Agregando combo',
        html: 'Por favor espere mientras se agregan los productos...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: '?pagina=catalogo',
        type: 'POST',
        data: {
            accion: 'agregar_combo_al_carrito',
            id_combo: idCombo
        },
        success: function (response) {
            try {
                const data = typeof response === 'object' ? response : JSON.parse(response);

                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message || 'Combo agregado al carrito',
                        footer: `Se agregaron ${data.productos_agregados || 'varios'} productos al carrito`,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        updateCartCount();
                        location.reload();
                    });
                    sincronizarCarritoNavbar();
                } else {
                    Swal.fire('Error', data.message || 'Error al agregar combo', 'error');
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                Swal.fire('Error', 'Error al procesar la respuesta', 'error');
            }
        },
        error: function (xhr) {
            console.error('AJAX Error:', xhr.status, xhr.statusText);
            Swal.fire('Error', 'Error de conexión', 'error');
        },
        complete: function () {
            button.prop('disabled', false);
            button.html('<i class="bi bi-cart-plus"></i> Agregar Combo');
        }
    });
}

// Implementación faltante: confirmar y agregar producto al carrito
function confirmarAgregarAlCarrito(idProducto, cantidad, button) {
    if (!idProducto || !cantidad) return;

    // Deshabilitar botón y mostrar spinner
    const originalHtml = button.html();
    button.prop('disabled', true);
    button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    $.ajax({
        url: '?pagina=catalogo',
        type: 'POST',
        data: {
            accion: 'agregar_al_carrito',
            id_producto: idProducto,
            cantidad: cantidad
        },
        success: function (response) {
            try {
                const data = typeof response === 'object' ? response : JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Agregado!',
                        text: data.message || 'Producto agregado al carrito',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    updateCartCount();
                    sincronizarCarritoNavbar();
                } else {
                    Swal.fire('Error', data.message || 'No se pudo agregar al carrito', 'error');
                }
            } catch (e) {
                console.error('Error parsing response:', e, response);
                Swal.fire('Error', 'Error al procesar la respuesta del servidor', 'error');
            }
        },
        error: function (xhr) {
            console.error('AJAX Error:', xhr.status, xhr.statusText);
            Swal.fire('Error', 'Error de conexión', 'error');
        },
        complete: function () {
            button.prop('disabled', false);
            button.html(originalHtml);
        }
    });
}

// ...resto del código...

// Confirmar cantidad y agregar al carrito
function confirmarCantidad() {
    const cantidad = parseInt($('#productoCantidad').val());
    const stockDisponible = parseInt($('#stockActual').text());

    if (isNaN(cantidad) || cantidad < 1) {
        Swal.fire('Error', 'Ingrese una cantidad válida', 'error');
        return;
    }
    if (cantidad > stockDisponible) {
        Swal.fire('Error', 'No hay suficiente stock disponible', 'error');
        return;
    }

    const button = $('.btn-agregar-carrito[data-id-producto="' + currentProductId + '"]');
    confirmarAgregarAlCarrito(currentProductId, cantidad, button);
    $('#cantidadModal').modal('hide');
}

    // Eliminado: duplicados de confirmarCantidad y confirmarAgregarAlCarrito (mantener versiones superiores con complete)

 

    // Mostrar modal para nuevo combo
    function mostrarModalNuevoCombo() {
        $('#comboModalLabel').text('Crear Nuevo Combo');
        $('#id_combo').val('');
        $('#nombre_combo').val('');
        $('#descripcion').val('');
        productosSeleccionados = [];
        actualizarTablaProductosCombo();
        $('#comboModal').modal('show');
    }

    // Editar combo existente
    function editarCombo() {
        const idCombo = $(this).data('id-combo');
        currentComboId = idCombo;

        $.ajax({
            url: '?pagina=catalogo',
            type: 'POST',
            data: {
                accion: 'obtener_detalles_combo',
                id_combo: idCombo
            },
            beforeSend: function () {
                $('#comboModal').modal('show');
                $('#comboModalLabel').html('<span class="spinner-border spinner-border-sm" role="status"></span> Cargando combo...');
            },
            success: function (response) {
                try {
                    const data = typeof response === 'object' ? response : JSON.parse(response);

                    if (data.status === 'success') {
                        $('#comboModalLabel').text('Editar Combo: ' + data.combo.nombre_combo);
                        $('#id_combo').val(idCombo);
                        $('#nombre_combo').val(data.combo.nombre_combo);
                        $('#descripcion').val(data.combo.descripcion);

                        // Cargar productos del combo
                        productosSeleccionados = data.detalles.map(item => {
                            return {
                                id: item.id_producto,
                                nombre: item.nombre_producto,
                                cantidad: item.cantidad,
                                precio: item.precio*tasa
                            };
                        });

                        actualizarTablaProductosCombo();
                    } else {
                        Swal.fire('Error', data.message || 'Error al cargar el combo', 'error');
                        $('#comboModal').modal('hide');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    Swal.fire('Error', 'Error al procesar la respuesta', 'error');
                    $('#comboModal').modal('hide');
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.status, xhr.statusText);
                Swal.fire('Error', 'Error al cargar el combo', 'error');
                $('#comboModal').modal('hide');
            }
        });
    }

    // Mostrar modal para cambiar estado de combo
    function mostrarModalCambioEstado() {
        const idCombo = $(this).data('id-combo');
        const nombreCombo = $(this).data('nombre-combo');
        const estadoActual = $(this).data('estado-actual');

        const accion = estadoActual ? 'deshabilitar' : 'habilitar';
        const textoAccion = estadoActual ? 'Deshabilitar' : 'Habilitar';

        $('#estadoComboModalLabel').text(`${textoAccion} Combo: ${nombreCombo}`);
        $('#accionEstado').text(accion);
        $('#comboIdEstado').val(idCombo);
        $('#estadoComboMensaje').html(`
            ¿Estás seguro de que deseas <strong>${accion}</strong> el combo <strong>${nombreCombo}</strong>?
            <br><small class="text-muted">${estadoActual ? 'Los clientes no podrán ver este combo.' : 'Los clientes podrán ver y comprar este combo.'}</small>
        `);

        // Cambiar color del botón según la acción
        const btnConfirmar = $('#confirmarCambioEstado');
        btnConfirmar.removeClass('btn-primary btn-danger btn-success');
        btnConfirmar.addClass(estadoActual ? 'btn-danger' : 'btn-success');
        btnConfirmar.text(textoAccion);

        $('#estadoComboModal').modal('show');
    }

    // Cambiar estado de combo (habilitar/deshabilitar)
    function cambiarEstadoCombo() {
        const idCombo = $('#comboIdEstado').val();
        const btn = $(this);

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Procesando...');

        $.ajax({
            url: '?pagina=catalogo',
            type: 'POST',
            dataType: 'json',
            data: {
                accion: 'cambiar_estado_combo',
                id_combo: idCombo
            },
            success: function (data) {
                if (data.status === 'success') {
                    // Actualizar la interfaz sin recargar
                    const comboCard = $(`.btn-cambiar-estado[data-id-combo="${idCombo}"]`).closest('.combo-card');
                    const estadoBtn = $(`.btn-cambiar-estado[data-id-combo="${idCombo}"]`);
                    const btnAgregarCombo = comboCard.find('.btn-agregar-combo');
                    const esActivo = data.nuevo_estado;

                    // Cambiar clases y apariencia
                    comboCard.toggleClass('disabled-combo', !esActivo);

                    // Actualizar botón de cambiar estado
                    estadoBtn
                        .toggleClass('btn-outline-warning btn-outline-success', esActivo)
                        .toggleClass('btn-outline-success btn-outline-warning', !esActivo)
                        .html(`<i class="bi ${esActivo ? 'bi-eye-slash' : 'bi-eye'}"></i> ${esActivo ? 'Deshabilitar' : 'Habilitar'}`)
                        .data('estado-actual', esActivo ? 1 : 0);

                    // Habilitar o deshabilitar el botón "Agregar Combo"
                    if (esActivo) {
                        btnAgregarCombo.prop('disabled', false)
                            .removeClass('btn-secondary')
                            .addClass('btn-success')
                            .html('<i class="bi bi-cart-plus"></i> Agregar Combo');
                    } else {
                        btnAgregarCombo.prop('disabled', true)
                            .removeClass('btn-success')
                            .addClass('btn-secondary')
                            .html('<i class="bi bi-cart-plus"></i> Combo no disponible');
                    }

                    // Mostrar notificación
                    Swal.fire({
                        icon: 'success',
                        title: 'Estado actualizado',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    $('#estadoComboModal').modal('hide');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.status, xhr.statusText);
                let errorMsg = 'Error al cambiar el estado del combo';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch (e) {}

                Swal.fire('Error', errorMsg, 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html('Confirmar');
            }
        });
    }

    // Agregar producto al combo en edición
    function agregarProductoACombo() {
        const idProducto = $('#producto_select').val();
        const productoTexto = $('#producto_select option:selected').text();
        const cantidad = $('#producto_cantidad').val();
        const precio = $('#producto_select option:selected').data('precio')*tasa;
        const stock = $('#producto_select option:selected').data('stock');

        if (!idProducto) {
            Swal.fire('Error', 'Debes seleccionar un producto', 'error');
            return;
        }

        if (cantidad < 1) {
            Swal.fire('Error', 'La cantidad debe ser al menos 1', 'error');
            return;
        }

        if (parseInt(cantidad) > parseInt(stock)) {
            Swal.fire('Error', 'La cantidad no puede ser mayor al stock disponible', 'error');
            return;
        }

        // Verificar si el producto ya está agregado
        const index = productosSeleccionados.findIndex(p => p.id == idProducto);

        if (index >= 0) {
            // Actualizar cantidad si ya existe
            productosSeleccionados[index].cantidad = cantidad;
        } else {
            // Agregar nuevo producto
            productosSeleccionados.push({
                id: idProducto,
                nombre: productoTexto,
                cantidad: cantidad,
                precio: precio
            });
        }

        actualizarTablaProductosCombo();

        // Resetear controles
        $('#producto_select').val('');
        $('#producto_cantidad').val(1);
    }

    // Eliminar producto del combo en edición
    function eliminarProductoDeCombo() {
        const idProducto = $(this).data('id-producto');
        productosSeleccionados = productosSeleccionados.filter(p => p.id != idProducto);
        actualizarTablaProductosCombo();
    }

    // Actualizar tabla de productos del combo
    function actualizarTablaProductosCombo() {
        const tbody = $('#productos_combo_table tbody');
        tbody.empty();

        if (productosSeleccionados.length === 0) {
            tbody.append('<tr><td colspan="4" class="text-center py-3 text-muted">No hay productos agregados</td></tr>');
            return;
        }

        productosSeleccionados.forEach(producto => {
            tbody.append(`
                <tr>
                    <td>${producto.nombre}</td>
                    <td>${producto.cantidad}</td>
                    <td>${(producto.precio * producto.cantidad).toFixed(2)} BS</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-danger btn-eliminar-producto" 
                                data-id-producto="${producto.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // Guardar combo (crear o actualizar)
    function guardarCombo() {
        const idCombo = $('#id_combo').val();
        const nombreCombo = $('#nombre_combo').val().trim();
        const descripcion = $('#descripcion').val().trim();

        if (!nombreCombo) {
            Swal.fire('Error', 'El nombre del combo es requerido', 'error');
            return;
        }

        if (productosSeleccionados.length === 0) {
            Swal.fire('Error', 'Debes agregar al menos un producto al combo', 'error');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Guardando...');

        const accion = idCombo ? 'actualizar_combo' : 'crear_combo';

        $.ajax({
            url: '?pagina=catalogo',
            type: 'POST',
            data: {
                accion: accion,
                id_combo: idCombo,
                nombre_combo: nombreCombo,
                descripcion: descripcion,
                productos: productosSeleccionados
            },
            success: function (response) {
                try {
                    const data = typeof response === 'object' ? response : JSON.parse(response);

                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            $('#comboModal').modal('hide');
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    Swal.fire('Error', 'Error al procesar la respuesta', 'error');
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr.status, xhr.statusText);
                Swal.fire('Error', 'Error al guardar el combo', 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Combo');
            }
        });
    }

    // Cargar datos para reportes
    function cargarDatosReportes() {
    // Mostrar spinner
    $('#datosEstadisticas').html(`
        <tr>
            <td colspan="4" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </td>
        </tr>
    `);

    $.ajax({
        url: '?pagina=catalogo',
        type: 'POST',
        data: { accion: 'obtener_datos_reportes' },
        dataType: 'json',
        success: function(data) {
            if (data.status === 'success') {
                if (data.estadisticas) {
                    crearGraficoAccesos(data.estadisticas);
                    actualizarTablaEstadisticas(data.estadisticas);
                }
                if (data.usuarios) {
                    crearGraficoUsuarios(data.usuarios);
                }
            } else {
                mostrarErrorEnTabla(data.message || 'Error al cargar datos');
            }
        },
        error: function(xhr) {
            console.error('Error al cargar datos para reportes:', xhr.status, xhr.statusText);
            mostrarErrorEnTabla('Error de conexión al cargar reportes');
        }
    });
}

    // Crear gráfico de accesos semanales
    function crearGraficoAccesos(datos) {
        if (accesosSemanalesChart) {
            accesosSemanalesChart.destroy();
        }

        const semanas = datos.semanas.map(item => 'Sem ' + item.semana.toString().substring(4));
        const accesos = datos.semanas.map(item => item.total_accesos);
        const usuariosUnicos = datos.semanas.map(item => item.usuarios_unicos);

        const ctx = document.getElementById('accesosSemanalesChart').getContext('2d');
        accesosSemanalesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: semanas,
                datasets: [
                    {
                        label: 'Total Accesos',
                        data: accesos,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Usuarios Únicos',
                        data: usuariosUnicos,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Accesos al Catálogo por Semana',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Accesos'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Semanas'
                        }
                    }
                }
            }
        });
    }

    // Crear gráfico de usuarios más activos
    function crearGraficoUsuarios(usuarios) {
        if (usuariosActivosChart) {
            usuariosActivosChart.destroy();
        }

        const nombres = usuarios.map(user => user.username);
        const accesos = usuarios.map(user => user.total_accesos);
        const porcentajes = usuarios.map(user => user.porcentaje);

        const ctx = document.getElementById('usuariosActivosChart').getContext('2d');
        usuariosActivosChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: nombres,
                datasets: [{
                    label: 'Total de Accesos',
                    data: accesos,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Top 10 Usuarios con Más Accesos',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const porcentaje = porcentajes[context.dataIndex];
                                return [
                                    `Accesos: ${context.raw}`,
                                    `Participación: ${porcentaje}%`
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Accesos'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Usuarios'
                        }
                    }
                }
            }
        });
    }

    // Actualizar tabla de estadísticas
    function actualizarTablaEstadisticas(datos) {
    const tbody = $('#datosEstadisticas');
    tbody.empty();

    // Resumen general
    tbody.append(`
        <tr class="table-primary">
            <td colspan="2"><strong>Total General</strong></td>
            <td><strong>${numberFormat(datos.total)}</strong></td>
            <td><strong>${numberFormat(datos.unicos)}</strong></td>
        </tr>
    `);

    // Detalle por semana
    datos.semanas.forEach(item => {
        const semana = item.semana.toString();
        const semanaFormateada = semana.substring(0, 4) + '-S' + semana.substring(4);
        
        tbody.append(`
            <tr>
                <td>${semanaFormateada}</td>
                <td>${numberFormat(item.promedio_diario, 1)}</td>
                <td>${numberFormat(item.total_accesos)}</td>
                <td>${numberFormat(item.usuarios_unicos)}</td>
            </tr>
        `);
    });
}

    // Actualizar contador del carrito
    function updateCartCount() {
        $.ajax({
            url: '?pagina=carrito',
            type: 'POST',
            data: {accion: 'obtener_cantidad_carrito'},
            dataType: 'json',
            success: function (data) {
                if (data.cantidad > 0) {
                    $('.cart-count').text(data.cantidad).removeClass('d-none');
                } else {
                    $('.cart-count').addClass('d-none');
                }
            },
            error: function (xhr) {
                console.error('Error al obtener cantidad del carrito:', xhr.status, xhr.statusText);
            }
        });
    }

    function numberFormat(num, decimals = 0) {
    return num.toLocaleString(undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

    // Mostrar error en tabla de productos
    function mostrarErrorEnTabla(mensaje) {
        $('#tablaProductos').html(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="bi bi-exclamation-triangle"></i> ${mensaje}
                </td>
            </tr>
        `);
    }

    // Inicializar DataTable para la tabla de productos y el buscador personalizado
    function inicializarDataTableProductos() {
        // Si no existe la tabla, no inicializar DataTable ni re-vincular el buscador
        if (!$('#tablaProductos').length || !$.fn.DataTable) {
            return;
        }

        const tablaProductosDT = $('#tablaProductos').DataTable({
            language: {
                url: 'public/js/es-ES.json'
            },
            responsive: true,
            columnDefs: [
                { orderable: false, targets: 0 }
            ],
            dom: 'rtip'
        });

        // Sincronizar el input de búsqueda personalizado con el DataTable
        $('#searchProduct').off('keyup').on('keyup', function () {
            tablaProductosDT.search(this.value).draw();
        });
    }

    // Inicialización al cargar la página
    inicializarDataTableProductos();
});