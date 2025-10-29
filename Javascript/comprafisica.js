$(document).ready(function () {
    // Formatea número a formato 1.000,50
    function formatearNumero(numero, decimales = 2, esMoneda = true) {
        if (isNaN(numero) || numero === null || numero === undefined || numero === "") {
            return esMoneda ? '0,00' : '0';
        }
        
        // Convertir a número
        // Acepta strings en formato "1,234.56" eliminando comas de miles
        numero = typeof numero === 'string' ? parseFloat(numero.replace(/,/g, '')) : parseFloat(numero);
        
        if (isNaN(numero)) {
            return esMoneda ? '0,00' : '0';
        }

        // Para números enteros (sin decimales)
        if (!esMoneda && numero % 1 === 0) {
            // Formato con coma para miles
            return numero.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Para números con decimales
        const opciones = {
            minimumFractionDigits: esMoneda ? decimales : 0,
            maximumFractionDigits: decimales,
            useGrouping: true
        };

        // Usar en-US para coma de miles y punto decimal
        let formateado = numero.toLocaleString('en-US', opciones);
        
        // Asegurar que siempre tenga los decimales especificados para moneda
        if (esMoneda) {
            const partes = formateado.split('.');
            if (partes.length === 1) {
                formateado += '.' + '0'.repeat(decimales);
            } else if (partes[1].length < decimales) {
                formateado = partes[0] + '.' + partes[1] + '0'.repeat(decimales - partes[1].length);
            }
        }

        return formateado;
    }
    // Exponer al ámbito global para uso fuera de este closure
    window.formatearNumero = formatearNumero;

    // Convierte texto formateado a número antes de cálculos (elige el separador decimal por la última aparición)
    function parsearNumeroFormateado(texto) {
        if (texto === null || texto === undefined) return 0;
        let s = String(texto).trim();
        if (s === '') return 0;
        // Quitar etiquetas/monedas/espacios
        s = s.replace(/[^0-9.,\-]/g, '');
        const lastDot = s.lastIndexOf('.');
        const lastComma = s.lastIndexOf(',');
        const decIdx = Math.max(lastDot, lastComma);
        if (decIdx >= 0) {
            const integerPart = s.slice(0, decIdx).replace(/[^0-9\-]/g, '');
            const fracPart = s.slice(decIdx + 1).replace(/[^0-9]/g, '');
            const composed = integerPart + '.' + fracPart;
            const n = parseFloat(composed);
            return isNaN(n) ? 0 : n;
        } else {
            // Sin separador decimal, quitar separadores de miles y parsear entero
            const n = parseFloat(s.replace(/[^0-9\-]/g, ''));
            return isNaN(n) ? 0 : n;
        }
    }
    // Exponer al ámbito global para uso fuera de este closure
    window.parsearNumeroFormateado = parsearNumeroFormateado;

    // Sanitiza mientras se escribe (permite coma o punto; no elimina separadores para no dañar 2,450.00)
    $(document).on("input", ".numerico, .monto, #total_cancelado, #total_restante, #total_cambio", function () {
        let valor = $(this).val();
        // Permitir solo dígitos, coma y punto
        valor = valor.replace(/[^0-9.,]/g, "");
        $(this).val(valor);
    });

    // Al salir del input, aplicar formato 1,234.56
    $(document).on('blur', ".numerico, .monto, #total_cancelado, #total_restante, #total_cambio", function () {
        const num = parsearNumeroFormateado($(this).val());
        $(this).val(formatearNumero(num));
    });

    /**
     * Inicializa el formato de todos los campos numéricos
     */
    function inicializarFormatosNumericos() {
        // Aplicar formato inicial
        aplicarFormatoMonetario();
        
        // Configurar eventos para campos de monto
        $(document).on('blur', '.monto-input, .monto-input-dolar', function() {
            const valor = $(this).val();
            if (valor && !isNaN(parsearNumeroFormateado(valor))) {
                const valorNumerico = parsearNumeroFormateado(valor);
                $(this).val(formatearNumero(valorNumerico));
            }
        });
        
        // Prevenir solo caracteres no numéricos (permitir múltiples ',' y '.'; se normaliza en blur)
        $(document).on('keypress', '.monto-input, .monto-input-dolar', function(event) {
            const key = event.key;
            const isControl = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End'].includes(key);
            if (/\d/.test(key) || key === '.' || key === ',' || isControl) {
                return true;
            }
            event.preventDefault();
            return false;
        });
    }
    // Exponer al ámbito global para uso fuera del closure
    window.marcarProductosAgregados = marcarProductosAgregados;

    /**
     * Aplica formato a todos los campos monetarios
     */
    function aplicarFormatoMonetario() {
        // Campos de totales
        $('#monto_total').val(formatearNumero($('#monto_total').val()));
        $('#monto_faltante').val(formatearNumero($('#monto_faltante').val()));
        $('#cambio_efectivo').val(formatearNumero($('#cambio_efectivo').val()));
        
        // Campo de total de compra (solo el valor numérico)
        const totalCompraTexto = $('#totalCompra').val().replace('Bs. ', '');
        if (totalCompraTexto) {
            $('#totalCompra').val('Bs. ' + formatearNumero(totalCompraTexto));
        }
    }

    // Calcula el total de la compra basado en la tabla principal y actualiza el input totalCompra
    function calcularTotal() {
        let total = 0;
        $('#recepcion1 tr').each(function() {
            const precioTxt = $(this).find('td:eq(6)').text().trim();
            const cantidadVal = $(this).find("input[name='cantidad[]']").val();
            const precio = parsearNumeroFormateado(precioTxt) || 0;
            const cantidad = parsearNumeroFormateado(cantidadVal) || 0;
            total += (precio * cantidad);
        });
        $('#totalCompra').val('Bs. ' + formatearNumero(total));
        if (typeof window.calcularCambio === 'function') { window.calcularCambio(); }
        if (typeof window.actualizarTotalesFormateados === 'function') { window.actualizarTotalesFormateados(); }
        return total;
    }
    // Exponer al ámbito global
    window.calcularTotal = calcularTotal;

    // Clase para el autocomplete de clientes
    class ClienteAutocomplete {
        constructor() {
            this.searchInput = document.getElementById('buscarCliente');
            this.dropdown = document.getElementById('clientesDropdown');
            this.clienteIdInput = document.getElementById('cliente_id');
            this.clienteSeleccionadoDiv = document.getElementById('clienteSeleccionado');
            this.clienteNombreSpan = document.getElementById('clienteNombre');
            this.clienteCedulaSpan = document.getElementById('clienteCedula');
            this.btnCambiarCliente = document.getElementById('btnCambiarCliente');
            
            this.debounceTimer = null;
            this.minSearchLength = 2;
            this.clientesCache = [];
            
            this.initEvents();
        }
        
        initEvents() {
            // Evento de búsqueda con debounce
            this.searchInput.addEventListener('input', (e) => {
                clearTimeout(this.debounceTimer);
                const query = e.target.value.trim();
                
                if (query.length >= this.minSearchLength) {
                    this.debounceTimer = setTimeout(() => {
                        this.buscarClientes(query);
                    }, 300);
                } else {
                    this.ocultarDropdown();
                }
            });
            
            // Evento para mostrar todos los clientes al hacer focus
            this.searchInput.addEventListener('focus', () => {
                if (this.searchInput.value.length >= this.minSearchLength) {
                    this.buscarClientes(this.searchInput.value);
                }
            });
            
            // Evento para cambiar cliente
            this.btnCambiarCliente.addEventListener('click', () => {
                this.limpiarSeleccion();
            });
            
            // Cerrar dropdown al hacer clic fuera
            document.addEventListener('click', (e) => {
                if (!this.searchInput.contains(e.target) && !this.dropdown.contains(e.target)) {
                    this.ocultarDropdown();
                }
            });
            
            // Navegación con teclado
            this.searchInput.addEventListener('keydown', (e) => {
                this.manejarTeclado(e);
            });
        }
        
        async buscarClientes(query) {
            try {
                this.mostrarLoading();
                
                const response = await fetch('?pagina=comprafisica', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `accion=buscar_clientes&query=${encodeURIComponent(query)}`
                });
                
                const clientes = await response.json();
                this.mostrarResultados(clientes);
                
            } catch (error) {
                console.error('Error buscando clientes:', error);
                this.mostrarError();
            }
        }
        
        mostrarResultados(clientes) {
            if (clientes.length === 0) {
                this.dropdown.innerHTML = '<div class="no-results">No se encontraron clientes</div>';
                this.mostrarDropdown();
                return;
            }
            
            let html = '';
            clientes.forEach((cliente, index) => {
                html += `
                    <div class="cliente-item" 
                         data-id="${cliente.id_clientes}" 
                         data-nombre="${this.escapeHtml(cliente.nombre)}" 
                         data-cedula="${this.escapeHtml(cliente.cedula)}"
                         data-telefono="${this.escapeHtml(cliente.telefono || '')}"
                         tabindex="0">
                        <span class="cliente-nombre">${this.resaltarCoincidencia(this.escapeHtml(cliente.nombre), this.searchInput.value)}</span>
                        <span class="cliente-cedula">C.I. ${this.escapeHtml(cliente.cedula)}</span>
                        ${cliente.telefono ? `<span class="cliente-telefono">📞 ${this.escapeHtml(cliente.telefono)}</span>` : ''}
                    </div>
                `;
            });
            
            this.dropdown.innerHTML = html;
            this.mostrarDropdown();
            this.agregarEventosItems();
        }
        
        resaltarCoincidencia(texto, busqueda) {
            if (!busqueda) return texto;
            
            const regex = new RegExp(`(${this.escapeRegex(busqueda)})`, 'gi');
            return texto.replace(regex, '<mark>$1</mark>');
        }
        
        escapeRegex(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        agregarEventosItems() {
            const items = this.dropdown.querySelectorAll('.cliente-item');
            
            items.forEach((item, index) => {
                item.addEventListener('click', () => {
                    this.seleccionarCliente(item);
                });
                
                item.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.seleccionarCliente(item);
                    }
                });
            });
        }
        
        seleccionarCliente(item) {
            const id = item.getAttribute('data-id');
            const nombre = item.getAttribute('data-nombre');
            const cedula = item.getAttribute('data-cedula');
            
            this.clienteIdInput.value = id;
            this.clienteNombreSpan.textContent = nombre;
            this.clienteCedulaSpan.textContent = `C.I. ${cedula}`;
            
            this.clienteSeleccionadoDiv.style.display = 'block';
            this.searchInput.style.display = 'none';
            this.ocultarDropdown();
            
            // Validar el campo cliente después de seleccionar
            this.validarCampoCliente();
            
            // Disparar evento personalizado para notificar la selección
            this.searchInput.dispatchEvent(new CustomEvent('clienteSeleccionado', {
                detail: { id, nombre, cedula }
            }));
        }
        
        limpiarSeleccion() {
            this.clienteIdInput.value = '';
            this.clienteSeleccionadoDiv.style.display = 'none';
            this.searchInput.style.display = 'block';
            this.searchInput.value = '';
            this.searchInput.focus();
            this.ocultarDropdown();
            
            // Limpiar validación
            $("#scliente").text("");
        }
        
        validarCampoCliente() {
            if (this.clienteIdInput.value) {
                $("#scliente").text("");
                return true;
            } else {
                $("#scliente").text("*Debe seleccionar un cliente*");
                return false;
            }
        }
        
        mostrarLoading() {
            this.dropdown.innerHTML = '<div class="loading-indicator">Buscando clientes...</div>';
            this.mostrarDropdown();
        }
        
        mostrarError() {
            this.dropdown.innerHTML = '<div class="no-results">Error al buscar clientes</div>';
            this.mostrarDropdown();
        }
        
        mostrarDropdown() {
            this.dropdown.style.display = 'block';
        }
        
        ocultarDropdown() {
            this.dropdown.style.display = 'none';
        }
        
        manejarTeclado(e) {
            if (!this.dropdown.style.display || this.dropdown.style.display === 'none') {
                return;
            }
            
            const items = this.dropdown.querySelectorAll('.cliente-item');
            const currentFocus = this.dropdown.querySelector('.cliente-item.focused');
            let index = Array.from(items).indexOf(currentFocus);
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    index = index === -1 ? 0 : (index + 1) % items.length;
                    this.actualizarFoco(items, index);
                    break;
                    
                case 'ArrowUp':
                    e.preventDefault();
                    index = index === -1 ? items.length - 1 : (index - 1 + items.length) % items.length;
                    this.actualizarFoco(items, index);
                    break;
                    
                case 'Enter':
                    if (currentFocus) {
                        e.preventDefault();
                        this.seleccionarCliente(currentFocus);
                    }
                    break;
                    
                case 'Escape':
                    this.ocultarDropdown();
                    break;
            }
        }
        
        actualizarFoco(items, index) {
            items.forEach(item => item.classList.remove('focused'));
            if (items[index]) {
                items[index].classList.add('focused');
                items[index].focus();
            }
        }
    }

    // Inicializar autocomplete de clientes
    let clienteAutocomplete;
    if (document.getElementById('buscarCliente')) {
        clienteAutocomplete = new ClienteAutocomplete();
    }


    // Función para aplicar formato a inputs numéricos
    function aplicarFormatoNumerico(input) {
        const valor = input.val();
        if (valor && !isNaN(parsearNumeroFormateado(valor))) {
            const valorNumerico = parsearNumeroFormateado(valor);
            input.val(formatearNumero(valorNumerico));
        }
    }

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", 4000, "Atención", $("#mensajes").html());
    }

    // Validación del campo cliente (usando el nuevo sistema)
    function validarCliente() {
        const clienteId = $("#cliente_id").val();
        if (!clienteId) {
            $("#scliente").text("*Debe seleccionar un cliente*");
            return false;
        } else {
            $("#scliente").text("");
            return true;
        }
    }

    // Evento para formatear cantidades al perder foco
    $(document).on('blur', 'input[name="cantidad[]"]', function() {
        aplicarFormatoNumerico($(this));
        calcularTotal();
    });

    // Evento para permitir solo números, punto y coma en cantidades
    $(document).on('keypress', 'input[name="cantidad[]"]', function(e) {
        const char = String.fromCharCode(e.which);
        const currentValue = $(this).val();
        
        // Permitir solo números, coma y punto
        if (!/[\d,.]/.test(char)) {
            e.preventDefault();
            return;
        }
        
        // Validar que solo haya una coma decimal
        if (char === ',' || char === '.') {
            if (currentValue.includes(',') || currentValue.includes('.')) {
                e.preventDefault();
                return;
            }
        }
    });

    // Evento para formatear al cargar la página
    $(document).ready(function() {
        $('input[name="cantidad[]"]').each(function() {
            aplicarFormatoNumerico($(this));
        });
    });

    // Tipo de pago (select)
    $(document).on("change blur", ".tipo-pago", function () {
        validarKeyUp(
            /^.+$/,
            $(this),
            $("#stipopago"),
            "*Debe seleccionar un tipo de pago*"
        );
    });

    // Cuenta (select)
    $(document).on("change blur", ".cuenta-pago", function () {
        validarKeyUp(
            /^.+$/,
            $(this),
            $("#scuentapago"),
            "*Debe seleccionar una cuenta*"
        );
    });

    // Referencia (10 a 15 dígitos)
    $(document).on("keypress", "input[id^='referencia_']", function (e) {
        validarKeyPress(/^[0-9]*$/, e);
        let referencia = document.getElementById("input[id^='referencia_']");
        referencia.value = space(referencia.value);
    });
    $(document).on("keyup blur", "input[id^='referencia_']", function () {
        validarKeyUp(
            /^[0-9]{10,15}$/,
            $(this),
            $("#sreferencia"),
            "*Debe contener entre 10 y 15 dígitos*"
        );
    });

    // Imagen (archivo obligatorio)
    $(document).on("change blur", ".comprobante-pago", function () {
        if (this.files.length > 0) {
            $("#simagen").text("");
        } else {
            $("#simagen").text("*Debe subir una imagen*");
        }
    });

    // Monto (numérico mayor que 0) - permitir números, coma y punto
    $(document).on("keypress", "input[id^='monto_']", function (e) {
        validarKeyPress(/^[0-9.,]*$/, e);
    });
    $(document).on("keyup blur", "input[id^='monto_']", function () {
        // Acepta miles con coma o punto y decimales con coma o punto
        const regexMonto = /^(?!0*(?:[.,]0+)?$)(\d{1,3}(?:[.,]?\d{3})*|\d+)([.,]\d{1,2})?$/;
        validarKeyUp(
            regexMonto,
            $(this),
            $("#smonto"),
            "*Debe ingresar un monto válido*"
        );
    });

    function validarEnvioVenta() {
        // Cliente (nuevo sistema)
        if (!validarCliente()) {
            mensajes('error', 'Verifique el cliente', 'Debe seleccionar un cliente');
            return false;
        }

        // Tipo de pago
        if ($(".tipo-pago").val() === null || $(".tipo-pago").val() === "") {
            $("#stipopago").text("*Debe seleccionar un tipo de pago*");
            mensajes('error', 'Verifique el tipo de pago', 'El campo está vacío');
            return false;
        } else {
            $("#stipopago").text("");
        }

        // Cuenta
        if ($(".cuenta-pago").val() === null || $(".cuenta-pago").val() === "") {
            $("#scuentapago").text("*Debe seleccionar una cuenta*");
            mensajes('error', 'Verifique la cuenta', 'El campo está vacío');
            return false;
        } else {
            $("#scuentapago").text("");
        }

        // Referencia (10 a 15 dígitos)
        let referencia = $("input[id^='referencia_']");
        referencia.val(space(referencia.val()).trim());
        if (validarKeyUp(
            /^[0-9]{10,15}$/,
            referencia,
            $("#sreferencia"),
            "*Debe contener entre 10 y 15 dígitos*"
        ) == 0) {
            mensajes('error', 'Verifique la referencia', 'Debe contener entre 10 y 15 dígitos');
            return false;
        }

        // Imagen (obligatoria)
        let imagen = $(".comprobante-pago")[0];
        if (!imagen || imagen.files.length === 0) {
            $("#simagen").text("*Debe subir una imagen*");
            mensajes('error', 'Verifique el comprobante', 'Debe adjuntar la imagen del comprobante');
            return false;
        } else {
            $("#simagen").text("");
        }

        // Monto (numérico mayor a 0)
        let monto = $("input[id^='monto_']");
        const regexMonto = /^(?!0*(?:[.,]0+)?$)(\d{1,3}(?:[.,]?\d{3})*|\d+)([.,]\d{1,2})?$/;
        if (validarKeyUp(
            regexMonto,
            monto,
            $("#smonto"),
            "*Debe ingresar un monto válido*"
        ) == 0) {
            mensajes('error', 'Verifique el monto', 'Debe ser un número mayor que 0');
            return false;
        }

        return true; // Todo válido
    }



    // Función para marcar productos ya agregados al abrir el modal
    function marcarProductosAgregados() {
        // Obtener IDs de productos ya en la lista principal
        let idsAgregados = [];
        $('#recepcion1 input[name="producto[]"]').each(function() {
            idsAgregados.push($(this).val());
        });

        // Marcar filas en el modal
        $('#modalp #listadop tr').each(function() {
            const idProducto = $(this).find('td:eq(0)').text().trim(); // ID del producto
            if (idsAgregados.includes(idProducto)) {
                $(this).addClass('agregado');
                $(this).find('.btn-agregar-prod').prop('disabled', true).text('Agregado');
            } else {
                $(this).removeClass('agregado');
                $(this).find('.btn-agregar-prod').prop('disabled', false).text('Agregar');
            }
        });
    }
    setInterval(marcarProductosAgregados, 500); // Revisa cada 2 segundos

    // Evento para agregar productos desde el modal
    $(document).on('click', '.btn-agregar-prod', function() {
        const $btn = $(this);
        const $fila = $btn.closest('tr');
        
        // Verificar si ya está agregado
        if ($fila.hasClass('agregado')) {
            return;
        }

        // Agregar producto a la tabla principal
        colocaproducto($fila);
        marcarProductosAgregados();
        // Feedback visual y bloqueo
        $fila.addClass('agregado');
        $btn.prop('disabled', true).text('Agregado');

        // SweetAlert de confirmación
        Swal.fire({
            icon: 'success',
            title: '¡Producto agregado!',
            text: 'El producto ha sido agregado a la lista de venta.',
            timer: 1500,
            showConfirmButton: false,
            position: 'top-end'
        });
    });

    // Actualizar estado de productos al abrir el modal
    $('#modalp').on('show.bs.modal', function() {
        // Pequeño delay para asegurar que el contenido esté cargado
        setTimeout(marcarProductosAgregados, 100);
    });

    // También actualizar cuando se eliminen productos de la lista principal
    $(document).on('click', '.btn-eliminar-pr', function() {
        // Pequeño delay para que se complete la eliminación
        setTimeout(marcarProductosAgregados, 100);
    });

    function formatearFecha(fechaStr) {
        // fechaStr: '2025-09-08'
        const partes = fechaStr.split('-');
        if (partes.length === 3) {
            const anio = partes[0];
            const mes = partes[1].padStart(2, '0');
            const dia = partes[2].padStart(2, '0');
            return `${dia}/${mes}/${anio}`;
        }
        return fechaStr;
    }

    function resetVenta() {
        // Limpiar selección de cliente
        if (clienteAutocomplete) {
            clienteAutocomplete.limpiarSeleccion();
        }
        
        $('.tipo-pago').val('');
        $('.cuenta-pago').val('');
        $('input[id^="referencia_"]').val('');
        $('.comprobante-pago').val('');
        $('#stipopago').text('');
        $('#scuentapago').text('');
        $('#sreferencia').text('');
        $('#simagen').text('');
        $('#smonto').text('');
    };

    // Evento para el botón de nuevo cliente
    $("#btnNuevoCliente").on("click", function() {
        window.location.href = "?pagina=cliente&accion=registrar";
    });

    // Ocultar todos los campos de pago inicialmente
    $('.campos-pago').hide();
    
    // Evento para mostrar campos según método de pago seleccionado
    $(document).on('change', '.tipo-pago', function() {
        const idx = $(this).attr('id').split('_')[1];
        const tipoSeleccionado = $(this).val();
        
        // Ocultar todos los campos primero
        $(`#campos_pago_${idx} .campo-pago`).hide();
        
        // Mostrar campos según el método seleccionado
        if (tipoSeleccionado) {
            $(`#campos_pago_${idx}`).show();
            
            if (tipoSeleccionado === "Pago Movil" || tipoSeleccionado === "Transferencia") {
                $(`#campos_pago_${idx} .campo-referencia`).show();
                $(`#campos_pago_${idx} .campo-comprobante`).show();
            }
            
            if (tipoSeleccionado === "Efectivo") {
                $(`#campos_pago_${idx} .campo-efectivo`).show();
            }
            
            $(`#campos_pago_${idx} .campo-monto`).show();
            
            // Habilitar select de cuentas
            $(`#cuenta_${idx}`).prop('disabled', false);
        } else {
            $(`#campos_pago_${idx}`).hide();
            $(`#cuenta_${idx}`).prop('disabled', true);
        }
    });
    
    // Evento click para el botón de incluir despacho
    $("#btnIncluirDespacho").on("click", function () {
        $("#f")[0].reset();
        $("#scorrelativo").text("");
        
        // Limpiar selección de cliente
        if (clienteAutocomplete) {
            clienteAutocomplete.limpiarSeleccion();
        }
        
        var modal = new bootstrap.Modal(document.getElementById('registrarCompraFisicaModal'));
        modal.show();
    });

    // Eventos para cerrar modales
    $(document).on("click", "#registrarCompraFisicaModal .close", function () {
        $("#registrarCompraFisicaModal").modal("hide");
    });

    $(document).on("click", "#modalp .close-2", function () {
        $("#modalp").modal("hide");
    });

    // Carga inicial de productos
    carga_productos();

    // Evento click para mostrar el modal de productos
    $("#listado").on("click", function () {
        $("#modalp").modal("show");
    });

    // Validación del campo descripción
    $("#descripcion").on("keypress", function (e) {
        validarkeypress(/^[A-Za-z0-9,#\b\s\u00f1\u00d1\u00E0-\u00FC-]*$/, e);
    });

    $("#descripcion").on("keyup", function () {
        validarkeyup(
            /^[A-Za-z0-9,#\b\s\u00f1\u00d1\u00E0-\u00FC-]{1,200}$/,
            $(this),
            $("#sdescripcion"),
            "No debe estar vacío y se permite un máximo 200 carácteres"
        );
    });

    // Evento keyup para buscar producto por código
    $("#codigoproducto").on("keyup", function () {
        var codigo = $(this).val();
        $("#listadop tr").each(function () {
            if (codigo == $(this).find("td:eq(1)").text()) {
                colocaproducto($(this));
            }
        });
    });

$("#registrar").on("click", function () {
    if (validarFormularioCompra()) {
        if (verificaproductos()) {
            if (!validarPagos()) {
                return;
            }
            
            // Normalizar montos antes de enviar: quitar separadores y dejar punto decimal
            $('input[name^="pagos"][name$="[monto]"]').each(function () {
                const limpio = parsearNumeroFormateado($(this).val()) || 0;
                $(this).val(limpio.toFixed(2));
            });

            $("#accion").val("registrar");
            var datos = new FormData($("#f")[0]);
            datos.append("descripcion", $("#descripcion").val());

            enviaAjax(datos, function(respuesta) {
                console.log("Respuesta completa:", respuesta);
                
                if (respuesta.resultado === "registrar") {
                    // Registro exitoso
                    if (respuesta.venta) {
                        agregarFilaVenta(respuesta.venta);
                        muestraMensaje("success", 6000, "REGISTRAR", respuesta.mensaje);
                        resetModalCompraFisica();
                    } else {
                        console.error("No se recibió objeto venta:", respuesta);
                        muestraMensaje("error", 6000, "Error", "No se recibieron los datos de la venta");
                    }
                } else if (respuesta.resultado === "error") {
                    // Error
                    muestraMensaje("error", 6000, "Error", respuesta.mensaje);
                } else {
                    // Respuesta inesperada
                    console.error("Respuesta inesperada:", respuesta);
                    muestraMensaje("warning", 6000, "Aviso", "Respuesta inesperada del servidor");
                }
            });
        } else {
            muestraMensaje("info", 4000, "Debe colocar algun producto");
        }
    }
});

// Función enviaAjax mejorada para debug
function enviaAjax(datos, callback) {
    $.ajax({
        async: true,
        url: "",
        type: "POST",
        contentType: false,
        data: datos,
        processData: false,
        cache: false,
        timeout: 20000,
        beforeSend: function () {
            console.log("%c[AJAX] Enviando datos...", "color: #007bff; font-weight: bold;");
        },
        success: function (respuesta) {
            console.group("%c[AJAX] Respuesta recibida", "color: green; font-weight: bold;");
            console.log("Respuesta bruta:", respuesta);
            
            try {
                // Parsear respuesta
                var respuestaParseada = typeof respuesta === 'string' ? JSON.parse(respuesta) : respuesta;
                console.log("Respuesta parseada:", respuestaParseada);
                
                if (callback) callback(respuestaParseada);
                
            } catch (e) {
                console.error("Error parseando JSON:", e);
                muestraMensaje("error", 7000, "Error", "Error procesando respuesta del servidor");
            }
            console.groupEnd();
        },
        error: function (xhr, status, error) {
            console.error("Error AJAX:", status, error);
            muestraMensaje("error", 7000, "Error AJAX", "Error en la comunicación con el servidor");
        }
    });
}

        function agregarFilaVenta(compra) {
        let montoTotal = 0;
        if (compra.productos && Array.isArray(compra.productos)) {
            compra.productos.forEach(p => {
                montoTotal += (Number(p.precio) || 0) * (Number(p.cantidad) || 0);
            });
        }

        const nuevaFila = [
            `<span class="campo-numeros">${compra.fecha ? formatearFecha(compra.fecha) : ''}</span>`,
            `<span class="campo-nombres">${compra.nombre_cliente}</span>
            <span class="campo-numeros">(${compra.cedula})</span>`,
            `<span class="campo-numeros">${formatearNumero(montoTotal)}</span>`,
            `<ul>
                <button class="btn-detalle ver-detalles"
                    title="Detallar"
                    data-id="${compra.id_factura}"
                    data-productos='${JSON.stringify(compra.productos)}'
                    data-pagos='${JSON.stringify(compra.pagos)}'
                    data-cliente="${compra.nombre_cliente}"
                    data-cedula="${compra.cedula}"
                    data-telefono="${compra.telefono ?? ''}"
                    data-correo="${compra.correo ?? ''}"
                    data-fecha="${compra.fecha}">
                    <img src="img/eye.svg">
                </button>
            </ul>`
        ];

        const tabla = $('#tablaConsultas').DataTable();
        const rowIdx = tabla.row.add(nuevaFila).draw(false).index();

        // Agregar atributo identificador a la fila
        $(tabla.row(rowIdx).node()).attr('data-id', compra.id_factura);

        // Mostrar siempre la última página
        tabla.page('last').draw('page');
    }
    // Función para verificar permisos en tiempo real
    function verificarPermisosEnTiempoRealRecepcion() {
        var datos = new FormData();
        datos.append("accion", "permisos_tiempo_real");
        enviarAjax(datos, function (permisos) {
            if (!permisos.consultar) {
                $("#tablaConsultas").hide();
                $(".space-btn-incluir").hide();
                if ($("#mensaje-permiso").length === 0) {
                    $(".contenedor-tabla").prepend(
                        '<div id="mensaje-permiso" style="color:red; text-align:center; margin:20px 0;">No tiene permiso para consultar los registros.</div>'
                    );
                }
                return;
            } else {
                $("#tablaConsultas").show();
                $(".space-btn-incluir").show();
                $("#mensaje-permiso").remove();
            }

            if (permisos.incluir) {
                $("#btnIncluirDespacho").show();
            } else {
                $("#btnIncluirDespacho").hide();
            }

            $(".btn-modificar").each(function () {
                if (permisos.modificar) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            $(".btn-eliminar").each(function () {
                if (permisos.eliminar) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            if (!permisos.modificar && !permisos.eliminar) {
                $("#tablaConsultas th:first-child, #tablaConsultas td:first-child").hide();
            } else {
                $("#tablaConsultas th:first-child, #tablaConsultas td:first-child").show();
            }
        });
    }

    // Verificar permisos al cargar y cada 10 segundos
    verificarPermisosEnTiempoRealRecepcion();
    setInterval(verificarPermisosEnTiempoRealRecepcion, 10000);

    // Calcular cambio para efectivo
    $(document).on('input', '#monto_efectivo', function() {
        const montoRecibido = parseFloat($(this).val()) || 0;
        const totalCompra = parseFloat($('#totalCompra').val().replace('Bs. ', '')) || 0;
        const cambio = montoRecibido - totalCompra;
        
        $('#cambio_efectivo').val(cambio.toFixed(2));
    });

    // Ocultar todos los campos de métodos de pago al cargar
    $('.metodo-campos').removeClass('active');

    // Evento para calcular total cuando cambia la cantidad de productos
    $(document).on('input change', 'input[name="cantidad[]"]', function() {
        calcularTotal();
    });

    // Función para enviar AJAX
    function enviarAjax(datos, callback) {
        $.ajax({
            async: true,
            url: "",
            type: "POST",
            data: datos,
            contentType: false,
            processData: false,
            cache: false,
            success: function (respuesta) {
                if (typeof respuesta === "string") {
                    respuesta = (typeof respuesta === "object") ? respuesta : JSON.parse(respuesta);
                }
                if (callback) callback(respuesta);
            },
            error: function () {
                Swal.fire("Error", "Error en la solicitud AJAX", "error");
            },
        });
    }

    // Función para validar el envío del formulario
    function validarFormularioRegistro() {
        let formValido = true;
        let mensajesError = [];

        // 1. Validar campos obligatorios básicos
        if (!validarCliente()) {
            mensajesError.push("El campo Cliente es obligatorio.");
            formValido = false;
        }

        // 2. Validar que hay al menos un producto en la tabla
        const filasProductos = document.querySelectorAll("#recepcion1 tr");
        if (filasProductos.length === 0) {
            mensajesError.push("Debe agregar al menos un producto a la compra.");
            formValido = false;
        }

        // 3. Validar pagos
        let montoTotal = parseFloat(document.querySelector("#monto_total").value) || 0;
        let montoPagado = 0;
        let pagoCompleto = false;

        document.querySelectorAll(".bloque-pago").forEach((bloque, idx) => {
            const tipo = bloque.querySelector(`.tipo-pago`)?.value.trim();
            const cuenta = bloque.querySelector(`.cuenta-pago`)?.value.trim();
            const referencia = bloque.querySelector(`input[name="pagos[${idx}][referencia]"]`)?.value.trim();
            const monto = parseFloat(bloque.querySelector(`input[name="pagos[${idx}][monto]"]`)?.value) || 0;
            const comprobante = bloque.querySelector(`input[name="pagos[${idx}][comprobante]"]`);

            if (tipo) {
                // Solo validar cuenta si NO es efectivo
                if (tipo !== "Efectivo" && tipo !== "Efectivo en $" && !cuenta) {
                    mensajesError.push(`En el pago ${idx + 1}: Debe seleccionar una cuenta.`);
                    formValido = false;
                }

                if (monto <= 0) {
                    mensajesError.push(`En el pago ${idx + 1}: El monto debe ser mayor que 0.`);
                    formValido = false;
                }

                if ((tipo === "Pago Movil" || tipo === "Transferencia") && !referencia) {
                    mensajesError.push(`En el pago ${idx + 1}: La referencia es obligatoria para ${tipo}.`);
                    formValido = false;
                }

                if (tipo === "Pago Movil" && comprobante && !comprobante.files[0]) {
                    mensajesError.push(`En el pago ${idx + 1}: El comprobante es obligatorio para Pago Móvil.`);
                    formValido = false;
                }

                montoPagado += monto;

                if (tipo && (tipo === "Efectivo" || tipo === "Efectivo en $" || cuenta) && monto > 0) {
                    if (tipo === "Pago Movil" || tipo === "Transferencia") {
                        if (referencia) pagoCompleto = true;
                    } else {
                        pagoCompleto = true;
                    }
                }
            }
        });

        // 4. Validar que monto pagado sea igual o mayor al monto total
        if (montoPagado < montoTotal) {
            mensajesError.push(`El monto pagado (${montoPagado.toFixed(2)}) no puede ser menor al monto total (${montoTotal.toFixed(2)}).`);
            formValido = false;
        }

        // 5. Validar que exista al menos un pago completo
        if (!pagoCompleto) {
            mensajesError.push("Debe existir al menos un pago completo con todos sus campos llenos.");
            formValido = false;
        }

        // 6. Mostrar mensajes y cancelar envío si hay errores
        if (!formValido) {
            Swal.fire({
                icon: "error",
                title: "Validación fallida",
                html: mensajesError.join("<br>"),
                confirmButtonText: "Corregir"
            });
        }

        return formValido;
    }

    // Función para cargar productos
    function carga_productos() {
        var datos = new FormData();
        datos.append("accion", "listado");
        enviaAjax(datos);
    }

    // Función para verificar si hay productos seleccionados
    function verificaproductos() {
        var existe = false;
        if ($("#recepcion1 tr").length > 0) {
            existe = true;
        }
        return existe;
    }

    setInterval(calcularTotal, 1000); // Recalcula el total cada segundo
    setInterval(calcularCambio, 1000);

    // Función para limpiar el formulario
    function borrar() {
        $("#recepcion1 tr").remove();
        $("#descripcion").val("");
    }

    // Función para mostrar mensajes
    function muestraMensaje(
        tipo = "success",
        tiempo = 4000,
        titulo = "",
        mensaje = ""
    ) {
        Swal.fire({
            icon: tipo,
            title: titulo,
            text: mensaje,
            timer: tiempo,
            showConfirmButton: false,
        });
    }

    // Función para validar por Keypress
    function validarkeypress(er, e) {
        key = e.keyCode;
        tecla = String.fromCharCode(key);
        a = er.test(tecla);

        if (!a) {
            e.preventDefault();
        }
    }

    // Función para validar por keyup
    function validarkeyup(er, etiqueta, etiquetamensaje, mensaje) {
        a = er.test(etiqueta.val());

        if (a) {
            etiquetamensaje.text("");
            return 1;
        } else {
            etiquetamensaje.text(mensaje);
            return 0;
        }
    }

    // Función para enviar AJAX
function enviaAjax(datos) {
    $.ajax({
        async: true,
        url: "", // Verifica que este valor se esté seteando dinámicamente antes de llamar a enviaAjax
        type: "POST",
        contentType: false,
        data: datos,
        processData: false,
        cache: false,
        timeout: 20000, // ⏱️ ampliamos un poco el tiempo
        beforeSend: function () {
            console.log("%c[AJAX] Enviando datos al servidor...", "color: #007bff; font-weight: bold;");
        },

        success: function (respuesta) {
            console.group("%c[AJAX] Respuesta recibida", "color: green; font-weight: bold;");
            console.log("Respuesta bruta del servidor:", respuesta);

            try {
                var lee = (typeof respuesta === "object") ? respuesta : JSON.parse(respuesta);
                console.log("JSON parseado:", lee);

                switch (lee.resultado) {
                    case "listado":
                        $("#listadop").html(lee.mensaje);
                        $("#modalp .modal-dialog")
                            .removeClass("modal-md modal-lg modal-xl")
                            .addClass(lee.modalSize);
                        console.info("✅ Listado cargado correctamente.");
                        break;

                    case "registrar":
                        muestraMensaje("success", 6000, "REGISTRAR", lee.mensaje);
                        resetModalCompraFisica();
                        console.info("✅ Registro completado correctamente.");
                        break;

                    case "encontro":
                        muestraMensaje("warning", 6000, "Atención", lee.mensaje);
                        console.warn("⚠️ Duplicado detectado:", lee.mensaje);
                        break;

                    case "error":
                        muestraMensaje("error", 6000, "Error", lee.mensaje);
                        console.error("❌ Error recibido desde PHP:", lee.mensaje);
                        break;

                    default:
                        console.warn("⚠️ Resultado desconocido:", lee.resultado);
                        muestraMensaje("warning", 6000, "Aviso", "Respuesta inesperada del servidor.");
                }
            } catch (e) {
                console.groupEnd();
                console.error("❌ Error al parsear JSON:", e);
                muestraMensaje("error", 7000, "Error en JSON", "El servidor devolvió una respuesta no válida. Revisa la consola.");
                console.log("Posible causa: echo o var_dump en PHP que rompe el JSON.");
            }

            console.groupEnd();
        },

        error: function (xhr, status, error) {
            console.group("%c[AJAX] Error detectado", "color: red; font-weight: bold;");
            console.error("🧩 Estado:", status);
            console.error("🧩 Error:", error);
            console.error("🧩 Respuesta del servidor:", xhr.responseText);

            let mensaje = "Error desconocido. Revise la consola.";
            if (status === "timeout") mensaje = "El servidor tardó demasiado en responder.";
            else if (xhr.status === 404) mensaje = "Archivo no encontrado (404). Verifique la URL.";
            else if (xhr.status === 500) mensaje = "Error interno del servidor (500). Revise los logs PHP.";
            else if (xhr.status === 0) mensaje = "No hay conexión con el servidor. Verifique su red.";

            muestraMensaje("error", 7000, "Error AJAX", mensaje);
            console.groupEnd();
        },

        complete: function () {
            console.log("%c[AJAX] Petición finalizada.", "color: gray;");
        }
    });
}

    function validarKeyPress(er, e) {
        let tecla = String.fromCharCode(e.which);
        if (!er.test(tecla)) {
            e.preventDefault();
        }
    }

    function validarKeyUp(er, input, span, mensaje) {
        let val = input.val();
        if (er.test(val)) {
            span.text("");
            return true;
        } else {
            span.text(mensaje);
            return false;
        }
    }

    function space(str) {
        const regex = /\s{2,}/g;
        var str = str.replace(regex, ' ');
        return str;
    }
});

function resetModalCompraFisica() {
    // Cierra el modal
    $('#registrarCompraFisicaModal').modal('hide');

    // Resetea el formulario
    const $form = $('#f');
    $form[0].reset();

    // Limpia tabla de productos
    $('#recepcion1').empty();

    // Reinicia totales
    $('#monto_total').val('0.00');
    $('#cambio_efectivo').val('0.00');
    $('#totalCompra').val('Bs. 0.00');

    // Limpia pagos dinámicos y vuelve a crear el primer bloque
    $('#pagos-container').empty();
    pagosCount = 0; // Reiniciamos contador
    agregarPagoBloque();

    // Remueve mensajes de error o validaciones
    $('#scorrelativo').text('');
    calcularTotal();
    calcularCambio();
}

// Función para colocar productos en la tabla
function colocaproducto(linea) {
    var id = $(linea).find("td:eq(0)").text().trim();
    var encontro = false;

    $("#recepcion1 tr").each(function () {
        var existingId = $(this).find("input[name='producto[]']").val();
        if (id === existingId) {
            encontro = true;
            var cantidadInput = $(this).find("input[name='cantidad[]']");
            let cantidadActual = parsearNumeroFormateado(cantidadInput.val()) || 0;
            let nuevaCantidad = cantidadActual + 1;
            cantidadInput.val(formatearNumero(nuevaCantidad, 0)); // 0 decimales para cantidades
            calcularTotal();
            return false;
        }
    });

    if (!encontro) {
        var l = `
            <tr>
                <td>
                    <button type="button" class="btn-eliminar-pr" onclick="borrarp(this)">Eliminar</button>
                </td>
                <td style="display:none">
                    <input type="text" name="producto[]" style="display:none" value="${id}"/>
                    ${id}
                </td>
                <td>${$(linea).find("td:eq(1)").text()}</td>
                <td>${$(linea).find("td:eq(2)").text()}</td>
                <td>${$(linea).find("td:eq(3)").text()}</td>
                <td>${$(linea).find("td:eq(4)").text()}</td>
                <td>${$(linea).find("td:eq(5)").text()}</td>
                <td>
                    <input type="text" class="numerico" name="cantidad[]" value="${formatearNumero(1, 0)}" required>
                </td>
            </tr>`;
        $("#recepcion1").append(l);
        calcularTotal();
    }
    
    marcarProductosAgregados();
}

// Recalcula el total al modificar productos/cantidades
$(document).on('input change', 'input[name="cantidad[]"]', calcularTotal);
$(document).on('DOMNodeInserted DOMNodeRemoved', '#recepcion1', calcularTotal);

// Calcula el cambio cuando el usuario ingresa el monto recibido en efectivo
$(document).on('change', 'input[id^="monto_"]', function() {
    // Solo si es efectivo
    if ($(this).attr('id').includes('efectivo')) {
        const montoRecibido = parseFloat($(this).val()) || 0;
        const total = parseFloat($("#monto_total").val()) || 0;
        const cambio = montoRecibido - total;
        $("#cambio_efectivo").val(cambio.toFixed(2));
    }
});

// Calcula el cambio considerando todos los pagos realizados
function calcularCambio() {
    let totalCompraTexto = $('#totalCompra').val().replace('Bs. ', '');
    const totalCompra = parsearNumeroFormateado(totalCompraTexto) || 0;
    
    const totalCancelado = calcularMontoTotalCancelado();

    let cambio = totalCancelado - totalCompra;
    let montoFaltante = totalCompra - totalCancelado;
    
    if (cambio < 0) cambio = 0;
    if (montoFaltante < 0) montoFaltante = 0;

    // Actualizar campos formateados - USANDO formatearNumero para TODOS
    $('#monto_total').val(formatearNumero(totalCancelado));
    $('#monto_faltante').val(formatearNumero(montoFaltante));
    $('#cambio_efectivo').val(formatearNumero(cambio));

    return cambio;
}

// Recalcula el cambio cada vez que se modifica un monto en cualquier pago
$(document).on('input', 'input[name^="pagos"][name$="[monto]"]', function() {
    // Aplicar formato al monto ingresado
    let valor = $(this).val();
    if (valor && !isNaN(parsearNumeroFormateado(valor))) {
        const valorNumerico = parsearNumeroFormateado(valor);
        $(this).val(formatearNumero(valorNumerico));
    }
    
    // Recalcular todo
    calcularCambio();
});

// También recalcula el cambio cuando se agrega o elimina un bloque de pago
$(document).on('DOMNodeInserted DOMNodeRemoved', '#pagos-container', calcularCambio);

// Recalcula el cambio cuando el monto total cambia
$(document).on('input', '#monto_total', calcularCambio);

// Inicializa el cambio al cargar
$(document).ready(function() {
    calcularCambio();
});

// Inicializa el total al cargar
$(document).ready(function() {
    calcularTotal();
});

//funcion para eliminar linea de detalle de ventas
function borrarp(boton) {
    $(boton).closest("tr").remove();
    calcularTotal();
}

/**
 * Valida el formulario completo de compra antes de enviarlo
 * @returns {boolean} true si el formulario es válido, false si hay errores
 */
function validarFormularioCompra() {
    // 1. Validar campos básicos obligatorios
    if (!validarCamposObligatorios()) {
        return false;
    }
 if (!validarPagos()) return;
    // 2. Validar que al menos hay un producto
    if ($('#recepcion1 tr').length === 0) {
        Swal.fire('Error', 'Debe agregar al menos un producto', 'error');
        return false;
    }

    // 3. Validar montos y pagos
    if (!validarMontosYPagos()) {
        return false;
    }

    // 4. Validar referencias numéricas
    if (!validarReferenciasNumericas()) {
        return false;
    }

    return true;
}

/**
 * Valida que todos los campos obligatorios estén completos
 */
function validarCamposObligatorios() {
    const clienteId = $('#cliente_id').val();
    if (!clienteId) {
        Swal.fire('Error', 'El campo Cliente es obligatorio', 'error');
        $('#buscarCliente').focus();
        return false;
    }

    return true;
}

/**
 * Valida los montos y los pagos
 */
function validarMontosYPagos() {
    // Obtener monto total de la compra (usando parsearNumeroFormateado)
    const montoTotalTexto = $('#totalCompra').val().replace('Bs. ', '');
    const montoTotal = parsearNumeroFormateado(montoTotalTexto) || 0;
    
    // Validar que el monto total sea mayor que 0
    if (montoTotal <= 0) {
        Swal.fire('Error', 'El monto total debe ser mayor a 0', 'error');
        return false;
    }

    // Validar cada bloque de pago
    let totalPagado = 0;
    let pagosValidos = 0;
    let errorEncontrado = false;
    let mensajeError = '';
    
    $('.bloque-pago').each(function(index) {
        if (errorEncontrado) return; // Salir si ya hay error
        
        const $bloque = $(this);
        const tipoPago = $bloque.find('.tipo-pago').val();
        const cuenta = $bloque.find('.cuenta-pago').val();
        const montoInput = $bloque.find('input[name$="[monto]"]');
        const montoTexto = montoInput.val();
        const monto = parsearNumeroFormateado(montoTexto) || 0;
            const referenciaInput = $bloque.find('input[name$="[referencia]"]');
    // CORREGIDO: asegurar que referenciaInput exista antes de usar .val() y .trim()
    const referencia = referenciaInput.length ? (referenciaInput.val() || '').trim() : '';


        // Si no hay método de pago seleccionado, saltar este bloque
        if (!tipoPago) {
            return true; // Continuar con siguiente iteración
        }

        // Solo validar cuenta si NO es efectivo ni efectivo en $
        if (tipoPago !== 'Efectivo' && tipoPago !== 'Efectivo en $') {
            if (!cuenta) {
                errorEncontrado = true;
                mensajeError = `En el pago ${index + 1}: Debe seleccionar una cuenta para ${tipoPago}.`;
                $bloque.find('.cuenta-pago').focus();
                return false;
            }
        }
        
        // Validar monto
        if (monto <= 0) {
            errorEncontrado = true;
            mensajeError = `En el pago ${index + 1}: El monto debe ser mayor que 0.`;
            montoInput.focus();
            return false;
        }

        // Validar referencias para métodos que las requieren
        if (tipoPago === 'Pago Movil' || tipoPago === 'Transferencia' || tipoPago === 'Zelle') {
            if (!referencia) {
                errorEncontrado = true;
                mensajeError = `En el pago ${index + 1}: La referencia es obligatoria para ${tipoPago}.`;
                referenciaInput.focus();
                return false;
            }
            
            // Validar formato de referencia para Pago Movil y Transferencia
            if ((tipoPago === 'Pago Movil' || tipoPago === 'Transferencia') && !/^\d{10,15}$/.test(referencia)) {
                errorEncontrado = true;
                mensajeError = `En el pago ${index + 1}: La referencia debe contener entre 10 y 15 dígitos para ${tipoPago}.`;
                referenciaInput.focus();
                return false;
            }
        }

        // Si llegamos aquí, el pago es válido
        totalPagado += monto;
        pagosValidos++;
    });

    // Si hubo error en la validación de algún pago
    if (errorEncontrado) {
        Swal.fire('Error', mensajeError, 'error');
        return false;
    }
    
    // Validar que haya al menos un pago completo
    if (pagosValidos === 0) {
        Swal.fire('Error', 'Debe registrar al menos un pago completo', 'error');
        return false;
    }
    
    // Validar que el total pagado sea suficiente
    if (totalPagado < montoTotal) {
        Swal.fire({
            icon: 'error',
            title: 'Pago insuficiente',
            html: `El total pagado (<strong>${formatearNumero(totalPagado)}</strong>) es menor al monto total (<strong>${formatearNumero(montoTotal)}</strong>)<br>Faltan: <strong>${formatearNumero(montoTotal - totalPagado)}</strong>`
        });
        return false;
    }
    
    return true;
}

function validarPagos() {
    let esValido = true;
    let mensajeError = "";

    $(".bloque-pago").each(function (idx) {
        const tipo = $(this).find(".tipo-pago").val();
        const monto = $(this).find('input[name^="pagos"][name$="[monto]"]').val();
        const cuenta = $(this).find(".cuenta-pago").val();
        const referencia = $(this).find('input[name^="pagos"][name$="[referencia]"]').val();
        const comprobante = $(this).find('input[type="file"].comprobante-pago')[0];

        // Validar tipo de pago
        if (!tipo) {
            esValido = false;
            mensajeError = `Debe seleccionar el tipo de pago en el pago ${idx + 1}.`;
            return false;
        }

        // Validar monto
        if (!monto || parsearNumeroFormateado(monto) <= 0) {
            esValido = false;
            mensajeError = `El monto del pago ${idx + 1} debe ser mayor que 0.`;
            return false;
        }

        // Validar cuenta solo si el método lo requiere
        if (["Pago Movil", "Transferencia", "Zelle"].includes(tipo) && !cuenta) {
            esValido = false;
            mensajeError = `Debe seleccionar una cuenta para el pago ${idx + 1}.`;
            return false;
        }

        // Validar referencia solo si el método lo requiere
        if (["Pago Movil", "Transferencia", "Zelle"].includes(tipo) && (!referencia || referencia.trim() === "")) {
            esValido = false;
            mensajeError = `Debe ingresar la referencia para el pago ${idx + 1}.`;
            return false;
        }

        // Validar comprobante solo si el método lo requiere
        if (["Pago Movil", "Transferencia", "Zelle"].includes(tipo)) {
            if (!comprobante || !comprobante.files || comprobante.files.length === 0) {
                esValido = false;
                mensajeError = `Debe adjuntar el comprobante para el pago ${idx + 1}.`;
                return false;
            }
        }
        // Para efectivo y efectivo en $, no se valida comprobante ni referencia ni cuenta
    });

    if (!esValido) {
        Swal.fire({
            icon: "warning",
            title: "Validación de pago",
            text: mensajeError,
        });
    }

    return esValido;
}

function actualizarTotalesFormateados() {
    // Total cancelado
    let totalCancelado = calcularMontoTotalCancelado();
    $('#monto_total').val(formatearNumero(totalCancelado));

    // Total restante
    let totalCompra = parsearNumeroFormateado($('#totalCompra').val().replace('Bs. ', '')) || 0;
    let restante = totalCompra - totalCancelado;
    if (restante < 0) restante = 0;
    $('#monto_faltante').val(formatearNumero(restante));

    // Cambio
    let cambio = totalCancelado - totalCompra;
    if (cambio < 0) cambio = 0;
    $('#cambio_efectivo').val(formatearNumero(cambio));
}

// Llama a esta función después de cada cambio relevante:
$(document).on('input', '.monto-input, .monto-input-dolar, input[name="cantidad[]"]', function() {
    actualizarTotalesFormateados();
});
$(document).ready(function() {
    actualizarTotalesFormateados();
});

$(document).on('blur', '.monto-input, .monto-input-dolar, input[name="cantidad[]"]', function () {
    let valor = $(this).val();
    if (valor && !isNaN(parsearNumeroFormateado(valor))) {
        const valorNumerico = parsearNumeroFormateado(valor);
        $(this).val(formatearNumero(valorNumerico));
    }
});
/**
 * Valida que las referencias solo contengan números
 */
function validarReferenciasNumericas() {
    let esValido = true;
    
    $('input[name$="[referencia]"]').each(function() {
        const referencia = $(this).val().trim();
        if (referencia && !/^\d+$/.test(referencia)) {
            Swal.fire('Error', 'Las referencias de pago solo pueden contener números', 'error');
            $(this).focus();
            esValido = false;
            return false; // Salir del each
        }
    });
    
    return esValido;
}

/**
 * Configura validación para inputs numéricos (no negativos)
 */
function configurarValidacionNumerosPositivos() {
    // Para inputs de tipo number
    $('input[type="number"]').on('input', function() {
        const value = parseFloat($(this).val());
        if (value < 0) {
            $(this).val(0);
        }
    });
    
    // Para inputs de texto que deben ser números
    $('input.numeric-positive').on('input', function() {
        $(this).val($(this).val().replace(/[^0-9.]/g, ''));
        const value = parseFloat($(this).val());
        if (value < 0) {
            $(this).val(0);
        }
    });
}

function actualizarConversionDolar(montoDolar, idx) {
    const tasa = parseFloat(document.getElementById('tasa').textContent) || 0;
    const montoBs = (montoDolar * tasa);
    
    // Mostrar conversión formateada
    $('#bolivares_' + idx).text('Equivalente: Bs. ' + formatearNumero(montoBs));
    // Guardar el valor convertido en el input hidden (sin formato para cálculos)
    $('#monto_bs_' + idx).val(montoBs.toFixed(2));
}

// Modificar el evento de input para montos en dólares (no re-formatear aquí para evitar saltos de cursor)
$(document).on('input', '.monto-input-dolar', function () {
    const idx = $(this).attr('id').split('_')[1];
    const montoDolar = parsearNumeroFormateado($(this).val());
    const tasa = parseFloat(document.getElementById('tasa').textContent) || 0;
    const montoBs = (montoDolar * tasa).toFixed(2);

    // Mostrar conversión formateada
    $('#bolivares_' + idx).text('Equivalente: Bs. ' + formatearNumero(montoBs));

    // Guardar el valor convertido en el input hidden (sin formato para cálculos)
    $('#monto_bs_' + idx).val(montoBs);

    // Recalcular
    calcularCambio();
});

// Al salir del input en dólares, sí formatear visualmente
$(document).on('blur', '.monto-input-dolar', function () {
    const montoDolar = parsearNumeroFormateado($(this).val());
    $(this).val(formatearNumero(montoDolar));
});

// ...

// 4. Calcular el monto faltante (total compra - monto cancelado)
function calcularMontoFaltante() {
    const totalCompra = parsearNumeroFormateado($('#totalCompra').val().replace('Bs. ', '')) || 0;
    const totalCancelado = calcularMontoTotalCancelado();

    let faltante = totalCompra - totalCancelado;
    if (faltante < 0) faltante = 0; // Si se pagó de más, no hay faltante
    
    // USAR formatearNumero en lugar de toFixed(2)
    $('#monto_faltante').val(formatearNumero(faltante));
    return faltante;
}

// 5. Función que actualiza todos los cálculos
function actualizarTodosLosCalculos() {
    calcularMontoTotalCancelado();
    calcularCambio();
    calcularMontoFaltante();
}

// Eventos que deben disparar los cálculos
$(document).on('input', '.monto-input, .monto-input-dolar', function() {
    actualizarTodosLosCalculos();
});

function calcularMontoTotalCancelado() {
    let totalCancelado = 0;

    // Busca todos los inputs de montos en los bloques de pagos dinámicos
    $('input[name^="pagos"][name$="[monto]"]').each(function () {
        let valor = $(this).val();
        // Usar parsearNumeroFormateado en lugar de parseFloat
        let valorNumerico = valor.includes('.') && valor.includes(',') ? 
                           parsearNumeroFormateado(valor) : 
                           parseFloat(valor) || 0;
        totalCancelado += valorNumerico;
    });

    // Actualiza el campo correspondiente en la vista (formateado como TEXTO)
    $('#monto_total').val(formatearNumero(totalCancelado));

    return totalCancelado;
}