$(document).ready(function () {

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", "Atención", $("#mensajes").html());
    }

    // Inicializar DataTable de cuentas y crear dinámicamente el botón Incluir en el filtro
    var $tabla = $('#tablaConsultas');
    if ($tabla.length) {
        var tablaCuentas;
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaCuentas = $tabla.DataTable({
                language: {
                    url: 'public/js/es-ES.json'
                },
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: 6 } // Deshabilitar ordenamiento en columna de acciones
                ],
                initComplete: function () {
                    var $wrapper = $tabla.closest('.dataTables_wrapper');
                    var $filter = $wrapper.find('.dataTables_filter');
                    if (!$filter.length) return;

                    // Evitar duplicar el botón si ya existe
                    if ($filter.find('#btnIncluirCuenta').length) return;

                    // Estilos flex para alinear label + buscador + botón
                    $filter.css({
                        display: 'flex',
                        'align-items': 'center',
                        'justify-content': 'flex-end',
                        gap: '10px'
                    });

                    $filter.find('label').css({ 'margin-bottom': '0' });

                    var $btnWrapper = $('<div>', { 'class': 'space-btn-incluir' });
                    var $btn = $('<button>', {
                        id: 'btnIncluirCuenta',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Cuenta Bancaria'
                    }).append($('<img>', { src: 'img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
        } else {
            tablaCuentas = $tabla.DataTable();
        }
    }

    $("#nombre_banco").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
        let nombre = document.getElementById("nombre_banco");
        nombre.value = space(nombre.value);
    });

    $("#nombre_banco").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{3,20}$/,
            $(this),
            $("#snombre_banco"),
            "*Solo letras, de 3 a 20 caracteres*"
        );
    });

    $("#numero_cuenta").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });
    $("#numero_cuenta").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{4}-\d{2}-\d{10}$/,
            $(this),
            $("#snumero_cuenta"),
            "*Formato válido: 0100-0000-00-0000000000*"
        );
    });
    $("#numero_cuenta").on("input", function() {
        let valor_nc = $(this).val().replace(/\D/g, '');
        if(valor_nc.length > 4 && valor_nc.length <= 8)
            valor_nc = valor_nc.slice(0,4) + '-' + valor_nc.slice(4);
        else if(valor_nc.length > 8 && valor_nc.length <= 10)
            valor_nc = valor_nc.slice(0,4) + '-' + valor_nc.slice(4,8) + '-' + valor_nc.slice(8,10);
        else if(valor_nc.length > 10)
            valor_nc = valor_nc.slice(0,4) + '-' + valor_nc.slice(4,8) + '-' + valor_nc.slice(8,10) + '-' + valor_nc.slice(10,20);
        $(this).val(valor_nc);
    });

    $("#rif_cuenta").on("keypress", function(e){ 
        validarKeyPress(/^[VEJPG0-9-\b]*$/i, e); 
    });
    $("#rif_cuenta").on("keyup", function(){ 
        validarKeyUp(
            /^[VEJPG]-\d{8}-\d$/,
            $(this),
            $("#srif_cuenta"),
            "*Formato válido: (VEJPG)-12345678-9*"
        );
    });
    $("#rif_cuenta").on("input", function() {
        let valor = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');

        let resultado = '';
        if (valor.length > 0) {
            let letra = valor.charAt(0);
            if ('VEJPG'.includes(letra)) {
                resultado = letra;
            } else {
                resultado = '';
            }

            let numeros = valor.substring(1).replace(/\D/g, '');

            if (numeros.length > 0) {
                resultado += '-' + numeros.substring(0, 8);
                if (numeros.length > 8) {
                    resultado += '-' + numeros.substring(8, 9);
                }
            }
        }
        $(this).val(resultado);
    });

    $("#telefono_cuenta").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });
    $("#telefono_cuenta").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#stelefono_cuenta"),
            "*Formato válido: 0400-000-0000*"
        );
    });
    $("#telefono_cuenta").on("input", function() {
        let valor = $(this).val().replace(/\D/g, '');
        if(valor.length > 4 && valor.length <= 7)
            valor = valor.slice(0,4) + '-' + valor.slice(4);
        else if(valor.length > 7)
            valor = valor.slice(0,4) + '-' + valor.slice(4,7) + '-' + valor.slice(7,11);
        $(this).val(valor);
    });

    $("#correo_cuenta").on("keypress", function (e) {
        validarKeyPress(/^[A-Za-zÁÉÍÓÚáéíóúñÑ0-9._%+\-@\b]*$/, e);
    });

    $("#correo_cuenta").on("keyup", function () {
        validarKeyUp(
        /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
        $(this),
        $("#scorreo_cuenta"),
        "*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*"
        );
    });

    $("#tipo_moneda").on("change", function(){
        if ($(this).val()) {
            $(this).removeClass("is-invalid").addClass("is-valid");
        } else {
            $(this).removeClass("is-valid").addClass("is-invalid");
        }
    });

    function verificarPermisosEnTiempoRealCuentas() {
    var datos = new FormData();
    datos.append('accion', 'permisos_tiempo_real');
    enviarAjax(datos, function(permisos) {
        console.log(permisos);
        // Si no tiene permiso de consultar
        if (!permisos.consultar) {
            $('#tablaConsultas').hide();
            $('.space-btn-incluir').hide();
            if ($('#mensaje-permiso').length === 0) {
                $('.contenedor-tabla').prepend('<div id="mensaje-permiso" style="color:red; text-align:center; margin:20px 0;">No tiene permiso para consultar los registros.</div>');
            }
            return;
        } else {
            $('#tablaConsultas').show();
            $('.space-btn-incluir').show();
            $('#mensaje-permiso').remove();
        }

        // Mostrar/ocultar botón de incluir
        if (permisos.incluir) {
            $('#btnIncluirCuenta').show();
        } else {
            $('#btnIncluirCuenta').hide();
        }

        // Mostrar/ocultar botones de modificar/eliminar
        $('.btn-modificar').each(function() {
            if (permisos.modificar) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        $('.btn-eliminar').each(function() {
            if (permisos.eliminar) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Ocultar columna Acciones si ambos permisos son falsos
        if (!permisos.modificar && !permisos.eliminar) {
            $('#tablaConsultas th:first-child, #tablaConsultas td:first-child').hide();
        } else {
            $('#tablaConsultas th:first-child, #tablaConsultas td:first-child').show();
        }
    });
}

// Llama la función al cargar la página y luego cada 10 segundos
$(document).ready(function() {
    verificarPermisosEnTiempoRealCuentas();
    setInterval(verificarPermisosEnTiempoRealCuentas, 10000); // 10 segundos
});

    function validarEnvioCuenta(){
        // Nombre del banco
        let nombre = $("#nombre_banco");
        nombre.val(space(nombre.val()).trim());
        const nombreVal = nombre.val();
        if (nombreVal === "") {
            $("#snombre_banco").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el nombre del banco','El campo está vacío.');
            return false;
        }
        if (nombreVal.length < 2) {
            $("#snombre_banco").text("*Mínimo 2 caracteres*");
            mensajes('error','Verifique el nombre del banco','Debe tener mínimo 2 caracteres.');
            return false;
        }

        // Número de cuenta
        let numero = $("#numero_cuenta");
        const numVal = numero.val().trim();
        if (numVal === "") {
            $("#snumero_cuenta").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el número de cuenta','El campo está vacío.');
            return false;
        }
        if (!/^\d{4}-\d{4}-\d{2}-\d{10}$/.test(numVal)) {
            $("#snumero_cuenta").text("*Formato válido: 0100-0000-00-0000000000*");
            mensajes('error','Verifique el número de cuenta','Formato inválido.');
            return false;
        }

        // RIF de la cuenta
        let rif = $("#rif_cuenta");
        const rifVal = rif.val().trim();
        if (rifVal === "") {
            $("#srif_cuenta").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el RIF','El campo está vacío.');
            return false;
        }
        if (!/^[VEJPG]-\d{8}-\d$/.test(rifVal)) {
            $("#srif_cuenta").text("*Formato válido: (VEJPG)-12345678-9*");
            mensajes('error','Verifique el RIF','Formato inválido.');
            return false;
        }

        // Teléfono de la cuenta
        let tel = $("#telefono_cuenta");
        const telVal = tel.val().trim();
        if (telVal === "") {
            $("#stelefono_cuenta").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el teléfono','El campo está vacío.');
            return false;
        }
        if (!/^\d{4}-\d{3}-\d{4}$/.test(telVal)) {
            $("#stelefono_cuenta").text("*Formato válido: 0400-000-0000*");
            mensajes('error','Verifique el teléfono','Formato inválido.');
            return false;
        }

        // Correo de la cuenta
        let correo = $("#correo_cuenta");
        const correoVal = correo.val().trim();
        if (correoVal === "") {
            $("#scorreo_cuenta").text("*Este campo es obligatorio*");
            mensajes('error','Verifique el correo','El campo está vacío.');
            return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoVal)) {
            $("#scorreo_cuenta").text("*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*");
            mensajes('error','Verifique el correo','Formato inválido.');
            return false;
        }

        let tipo_moneda = $("#tipo_moneda");
        if (!tipo_moneda.val()) {
            tipo_moneda.addClass("is-invalid");
            mensajes('error','Verifique el tipo de moneda','Debe seleccionar un tipo de moneda para la cuenta.');
            return false;
        } else {
            tipo_moneda.removeClass("is-invalid");
        }

        // Métodos de pago
        if ($('input[name="metodos_pago[]"]:checked').length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Verifique los métodos de pago',
                text: 'Debe seleccionar al menos un método de pago.'
            });
            return false;
        }
        return true;
    }

    function agregarFilaCuenta(cuenta) {
        const tabla = $('#tablaConsultas').DataTable();
        const nuevaFila = [
            `<span class="campo-numeros">${cuenta.id_cuenta}</span>`,
            `<span class="campo-nombres">${cuenta.nombre_banco}</span>`,
            `<span class="campo-numeros">${cuenta.numero_cuenta}</span>`,
            `<span class="campo-numeros">${cuenta.telefono_cuenta}</span>`,
            `<span class="campo-nombres">${(cuenta.metodos || '').split(',').join('<br>')}</span>`,
            `<span class="campo-estatus ${cuenta.estado === 'habilitado' ? 'habilitado' : 'inhabilitado'}"
                data-id="${cuenta.id_cuenta}"
                style="cursor: pointer;"
                title="Cambiar Estatus">
                ${cuenta.estado}
            </span>`,
            `<ul>
                <button class="btn-detalle"
                    title="Ver Detalles"
                    data-iddtl="${cuenta.id_cuenta}"
                    data-nombredtl="${cuenta.nombre_banco}"
                    data-numerodtl="${cuenta.numero_cuenta}"
                    data-rifdtl="${cuenta.rif_cuenta}"
                    data-telefonodtl="${cuenta.telefono_cuenta}"
                    data-correodtl="${cuenta.correo_cuenta}"
                    data-metodosdtl="${cuenta.metodos}"
                    data-estatusdtl="${cuenta.estado}">
                    <img src="assets/img/eye.svg">
                </button>
                <button class="btn-modificar" 
                    id="btnModificarCuenta"
                    title="Modificar Cuenta"
                    data-id="${cuenta.id_cuenta}"
                    data-nombre="${cuenta.nombre_banco}"
                    data-numero="${cuenta.numero_cuenta}"
                    data-rif="${cuenta.rif_cuenta}"
                    data-telefono="${cuenta.telefono_cuenta}"
                    data-correo="${cuenta.correo_cuenta}"
                    data-metodos="${cuenta.metodos}">
                    <img src="assets/img/pencil.svg">
                </button>
                <button class="btn-eliminar"
                    title="Eliminar Cuenta"
                    data-id="${cuenta.id_cuenta}">
                    <img src="assets/img/circle-x.svg">
                </button>
            </ul>`
        ];
        const rowNode = tabla.row.add(nuevaFila).draw(false).node();
        $(rowNode).attr('data-id', cuenta.id_cuenta);
    }

    function resetCuenta() {
        $('#nombre_banco').val('');
        $('#numero_cuenta').val('');
        $('#rif_cuenta').val('');
        $('#telefono_cuenta').val('');
        $('#correo_cuenta').val('');
        $('#snombre_banco').text('');
        $('#snumero_cuenta').text('');
        $('#srif_cuenta').text('');
        $('#stelefono_cuenta').text('');
        $('#scorreo_cuenta').text('');
        $("#tipo_moneda").val("");
        $("#tipo_moneda").removeClass("is-valid is-invalid");
        $('.metodos-bs').removeClass('d-none');
        $('.metodos-usd').addClass('d-none');
        $('#pagoMovil, #transferencia, #zelle').prop('checked', false);
    }

    $('#registrarCuenta').on('reset', function() {
        setTimeout(function() {
            $("#tipo_moneda").val("");
            $("#tipo_moneda").removeClass("is-valid is-invalid");
        }, 0);
    });

    // Abrir modal de registro (botón Incluir Cuenta dentro del DataTable)
    $(document).on('click', '#btnIncluirCuenta', function() {
        $('#registrarCuenta')[0].reset();
        $('#snombre_banco').text('');
        $('#snumero_cuenta').text('');
        $('#srif_cuenta').text('');
        $('#stelefono_cuenta').text('');
        $('#scorreo_cuenta').text('');
        $("#tipo_moneda").removeClass("is-valid is-invalid");
        $('.metodos-bs').removeClass('d-none');
        $('.metodos-usd').addClass('d-none');
        $('#pagoMovil, #transferencia, #zelle').prop('checked', false);
        $('#registrarCuentaModal').modal('show');
    });

    $('#registrarCuenta').on('submit', function(e) {
        e.preventDefault();

        if(validarEnvioCuenta()){
            var formData = new FormData(this);
            formData.append('accion', 'registrar');
        
            enviarAjax(formData, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: respuesta.message || respuesta.msg || 'Cuenta registrada correctamente'
                    });
                    if(respuesta.status === "success" && respuesta.cuenta){
                        agregarFilaCuenta(respuesta.cuenta);
                        resetCuenta();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || respuesta.msg || 'No se pudo registrar la cuenta'
                    });
                }
            });
        }
    });

    $(document).on('click', '#registrarCuentaModal .close', function() {
        $('#registrarCuentaModal').modal('hide');
    });

    function enviarAjax(datos, callback) {
        let esFormData = (typeof datos === "object" && typeof datos.append === "function");
        $.ajax({
            url: '',
            type: 'POST',
            data: datos,
            processData: !esFormData ? true : false,
            contentType: !esFormData ? 'application/x-www-form-urlencoded; charset=UTF-8' : false,
            dataType: 'json',
            success: function (respuesta) {
                if(callback) callback(respuesta);
            },
            error: function () {
                Swal.fire('Error', 'Error en la solicitud AJAX', 'error');
            }
        });
    }

    $("#modificar_nombre_banco").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
        let nombre = document.getElementById("modificar_nombre_banco");
        nombre.value = Espacios(nombre.value);
    });
    $("#modificar_nombre_banco").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{3,20}$/,
            $(this),
            $("#smnombre_banco"),
            "*Solo letras, de 3 a 20 caracteres*"
        );
    });

    $("#modificar_numero_cuenta").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });
    $("#modificar_numero_cuenta").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{4}-\d{2}-\d{10}$/,
            $(this),
            $("#smnumero_cuenta"),
            "*Formato válido: 0100-0000-00-0000000000*"
        );
    });
    $("#modificar_numero_cuenta").on("input", function() {
        let valor_nc = $(this).val().replace(/\D/g, '');
        if(valor_nc.length > 4 && valor_nc.length <= 8)
            valor_nc = valor_nc.slice(0,4) + '-' + valor_nc.slice(4);
        else if(valor_nc.length > 8 && valor_nc.length <= 10)
            valor_nc = valor_nc.slice(0,4) + '-' + valor_nc.slice(4,8) + '-' + valor_nc.slice(8,10);
        else if(valor_nc.length > 10)
            valor_nc = valor_nc.slice(0,4) + '-' + valor_nc.slice(4,8) + '-' + valor_nc.slice(8,10) + '-' + valor_nc.slice(10,20);
        $(this).val(valor_nc);
    });

    $("#modificar_rif_cuenta").on("keypress", function(e){
        validarKeyPress(/^[vejpg0-9-\b]*$/i, e);
    });
    $("#modificar_rif_cuenta").on("keyup", function(){
        validarKeyUp(
            /^[vejpg0-9-\b]*$/i,
            $(this),
            $("#smrif_cuenta"),
            "*Formato válido: (VEJPG)-12345678-9*"
        );
    });
    $("#modificar_rif_cuenta").on("input", function() {
        let valor = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g, '');

        let resultado = '';
        if (valor.length > 0) {
            let letra = valor.charAt(0);
            if ('VEJPG'.includes(letra)) {
                resultado = letra;
            } else {
                resultado = '';
            }

            let numeros = valor.substring(1).replace(/\D/g, '');

            if (numeros.length > 0) {
                resultado += '-' + numeros.substring(0, 8);
                if (numeros.length > 8) {
                    resultado += '-' + numeros.substring(8, 9);
                }
            }
        }
        $(this).val(resultado);
    });

    $("#modificar_telefono_cuenta").on("keypress", function(e){
        validarKeyPress(/^[0-9]*$/, e);
    });
    $("#modificar_telefono_cuenta").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#smtelefono_cuenta"),
            "*El teléfono debe tener exactamente 11 dígitos*"
        );
    });
    $("#modificar_telefono_cuenta").on("input", function() {
        let valor = $(this).val().replace(/\D/g, '');
        if(valor.length > 4 && valor.length <= 7)
            valor = valor.slice(0,4) + '-' + valor.slice(4);
        else if(valor.length > 7)
            valor = valor.slice(0,4) + '-' + valor.slice(4,7) + '-' + valor.slice(7,11);
        $(this).val(valor);
    });

    $("#modificar_correo_cuenta").on("keypress", function (e) {
        validarKeyPress(/^[A-Za-zÁÉÍÓÚáéíóúñÑ0-9._%+\-@\b]*$/, e);
    });

    $("#modificar_correo_cuenta").on("keyup", function () {
        validarKeyUp(
        /^[A-Za-z0-9._%+\-ÁÉÍÓÚáéíóúñÑ]+@(gmail\.com|outlook\.com|yahoo\.com|icloud\.com)$/,
        $(this),
        $("#smcorreo_cuenta"),
        "*Debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com*"
        );
    });

    $("#tipo_moneda_modificar").on("change", function(){
        if ($(this).val()) {
            $(this).removeClass("is-invalid").addClass("is-valid");
        } else {
            $(this).removeClass("is-valid").addClass("is-invalid");
        }
    });

    function validarCuenta(datos) {
        let errores = [];

        // Nombre del banco
        const nombreVal = (datos.nombre_banco || '').trim();
        if (nombreVal === "") {
            errores.push("Verifique el nombre del banco: El campo está vacío.");
        } else if (nombreVal.length < 2) {
            errores.push("Verifique el nombre del banco: Debe tener mínimo 2 caracteres.");
        }

        // Número de cuenta
        const numVal = (datos.numero_cuenta || '').trim();
        if (numVal === "") {
            errores.push("Verifique el número de cuenta: El campo está vacío.");
        } else if (!/^\d{4}-\d{4}-\d{2}-\d{10}$/.test(numVal)) {
            errores.push("Verifique el número de cuenta: Formato inválido (use 0100-0000-00-0000000000).");
        }

        // RIF
        const rifVal = (datos.rif_cuenta || '').trim();
        if (rifVal === "") {
            errores.push("Verifique el RIF: El campo está vacío.");
        } else if (!/^[VEJPG]-\d{8}-\d$/.test(rifVal)) {
            errores.push("Verifique el RIF: Formato inválido (use (VEJPG)-12345678-9).");
        }

        // Teléfono
        const telVal = (datos.telefono_cuenta || '').trim();
        if (telVal === "") {
            errores.push("Verifique el teléfono: El campo está vacío.");
        } else if (!/^\d{4}-\d{3}-\d{4}$/.test(telVal)) {
            errores.push("Verifique el teléfono: Formato inválido (use 0400-000-0000).");
        }

        // Correo
        const correoVal = (datos.correo_cuenta || '').trim();
        if (correoVal === "") {
            errores.push("Verifique el correo: El campo está vacío.");
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoVal)) {
            errores.push("Verifique el correo: Formato inválido (debe terminar en @gmail.com, @outlook.com, @yahoo.com o @icloud.com).");
        }

        const tipoMonedaMod = $('#tipo_moneda_modificar');
        if (!tipoMonedaMod.val()) {
            tipoMonedaMod.removeClass('is-valid').addClass('is-invalid');
            mensajes('error','Verifique el tipo de moneda','Debe seleccionar un tipo de moneda.');
            return;
        } else {
            tipoMonedaMod.removeClass('is-invalid').addClass('is-valid');
        }

        if ($('input[name="metodos_pago[]"]:checked').length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Verifique los métodos de pago',
                text: 'Debe seleccionar al menos un método de pago.'
            });
            return;
        }

        return errores;
    }

    $(document).on('click', '#btnModificarCuenta', function () {
        $('#modificar_id_cuenta').val($(this).data('id'));
        $('#modificar_nombre_banco').val($(this).data('nombre'));
        $('#modificar_numero_cuenta').val($(this).data('numero'));
        $('#modificar_rif_cuenta').val($(this).data('rif'));
        $('#modificar_telefono_cuenta').val($(this).data('telefono'));
        $('#modificar_correo_cuenta').val($(this).data('correo'));

        $('#smnombre_banco').text('');
        $('#smnumero_cuenta').text('');
        $('#smrif_cuenta').text('');
        $('#smtelefono_cuenta').text('');
        $('#smcorreo_cuenta').text('');

        // Limpia selección de métodos
        $('#pagoMovil_modificar, #transferencia_modificar, #zelle_modificar').prop('checked', false);

        // Inicializa según moneda actual
        const moneda = $('#tipo_moneda_modificar').val();
        if (moneda === 'bs') {
            $('.metodos-bs_modificar').removeClass('d-none');
            $('.metodos-usd_modificar').addClass('d-none');
        } else if (moneda === 'usd') {
            $('.metodos-usd_modificar').removeClass('d-none');
            $('.metodos-bs_modificar').addClass('d-none');
            $('#zelle_modificar').prop('checked', true);
        } else {
            $('.metodos-bs_modificar, .metodos-usd_modificar').addClass('d-none');
        }

        $('#modificarCuentaModal').modal('show');
    });

    $('#modificarCuenta').on('submit', function(e) {
        e.preventDefault();

        const datos = {
            nombre_banco: $('#modificar_nombre_banco').val(),
            numero_cuenta: $('#modificar_numero_cuenta').val(),
            rif_cuenta: $('#modificar_rif_cuenta').val(),
            telefono_cuenta: $('#modificar_telefono_cuenta').val(),
            correo_cuenta: $('#modificar_correo_cuenta').val()
        };

        const errores = validarCuenta(datos);

        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: errores.join('<br>')
            });
            return;
        }

        var formData = new FormData(this);
        formData.append('accion', 'modificar');

        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#modificarCuentaModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'La cuenta se ha modificado correctamente'
                    });
                    const tabla = $("#tablaConsultas").DataTable();
                    const id = $("#modificar_id_cuenta").val();
                    const fila = tabla.row(`tr[data-id="${id}"]`);
                    const cuenta = response.cuenta;
                    if (fila.length) {
                        fila.data([
                            `<span class="campo-numeros">${cuenta.id_cuenta}</span>`,
                            `<span class="campo-nombres">${cuenta.nombre_banco}</span>`,
                            `<span class="campo-numeros">${cuenta.numero_cuenta}</span>`,
                            `<span class="campo-numeros">${cuenta.telefono_cuenta}</span>`,
                            `<span class="campo-nombres">${(cuenta.metodos || '').split(',').join('<br>')}</span>`,
                            `<span class="campo-estatus ${cuenta.estado === 'habilitado' ? 'habilitado' : 'inhabilitado'}"
                                data-id="${cuenta.id_cuenta}"
                                style="cursor: pointer;"
                                title="Cambiar Estatus">
                                ${cuenta.estado}
                            </span>`,
                            `<ul>
                                <button class="btn-detalle"
                                    title="Ver Detalles"
                                    data-iddtl="${cuenta.id_cuenta}"
                                    data-nombredtl="${cuenta.nombre_banco}"
                                    data-numerodtl="${cuenta.numero_cuenta}"
                                    data-rifdtl="${cuenta.rif_cuenta}"
                                    data-telefonodtl="${cuenta.telefono_cuenta}"
                                    data-correodtl="${cuenta.correo_cuenta}"
                                    data-metodosdtl="${cuenta.metodos}"
                                    data-estatusdtl="${cuenta.estado}">
                                    <img src="assets/img/eye.svg">
                                </button>
                                <button class="btn-modificar"
                                    id="btnModificarCuenta"
                                    title="Modificar Cuenta"
                                    data-id="${cuenta.id_cuenta}"
                                    data-nombre="${cuenta.nombre_banco}"
                                    data-numero="${cuenta.numero_cuenta}"
                                    data-rif="${cuenta.rif_cuenta}"
                                    data-telefono="${cuenta.telefono_cuenta}"
                                    data-correo="${cuenta.correo_cuenta}"
                                    data-metodos="${cuenta.metodos}">
                                    <img src="assets/img/pencil.svg">
                                </button>
                                <button class="btn-eliminar"
                                    title="Eliminar Cuenta"
                                    data-id="${cuenta.id_cuenta}">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                            </ul>`
                        ]).draw(false);
                        const filaNode = fila.node();
                        const botonModificar = $(filaNode).find(".btn-modificar");
                        botonModificar.data('nombre', cuenta.nombre_banco);
                        botonModificar.data('numero', cuenta.numero_cuenta);
                        botonModificar.data('rif', cuenta.rif_cuenta);
                        botonModificar.data('telefono', cuenta.telefono_cuenta);
                        botonModificar.data('correo', cuenta.correo_cuenta);
                        botonModificar.data('metodos', cuenta.metodos);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudo modificar la cuenta'
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al modificar la Cuenta:', textStatus, errorThrown);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al modificar la cuenta'
                });
            }
        });
    });

    $(document).on('click', '#modificarCuentaModal .close', function() {
        $('#modificarCuentaModal').modal('hide');
    });

    $(document).on('click', '.btn-eliminar', function (e) {
        e.preventDefault();
        Swal.fire({
            title: '¿Está seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminarla!'
        }).then((result) => {
            if (result.isConfirmed) {
                var id_cuenta = $(this).data('id');
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id_cuenta', id_cuenta);
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: datos,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (respuesta) {
                        if (respuesta.status === 'success') {
                            Swal.fire(
                                'Eliminada!',
                                'La cuenta ha sido eliminada.',
                                'success'
                            );
                            eliminarFilaCuenta(id_cuenta);
                        } else {
                            muestraMensaje(respuesta.message);
                        }
                    },
                    error: function () {
                        muestraMensaje('Error en la solicitud AJAX');
                    }
                });
            }
        });
    });

    function eliminarFilaCuenta(id_cuenta) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${id_cuenta}"]`);
        tabla.row(fila).remove().draw();
    }

    $(document).on('click', '.campo-estatus', function() {
        const id_cuenta = $(this).data('id');
        cambiarEstado(id_cuenta);
    });

    function cambiarEstado(id_cuenta) {
        const span = $(`span.campo-estatus[data-id="${id_cuenta}"]`);
        const estadoActual = span.text().trim();
        const nuevoEstado = estadoActual === 'habilitado' ? 'inhabilitado' : 'habilitado';
        
        span.addClass('cambiando');
            
        $.ajax({
            url: '',
            type: 'POST',
            dataType: 'json',
            data: {
                accion: 'cambiar_estado',
                id_cuenta: id_cuenta,
                estado: nuevoEstado
            },
            success: function(data) {
                span.removeClass('cambiando');
                if (data.status === 'success') {
                    span.text(nuevoEstado);
                    span.removeClass('habilitado inhabilitado').addClass(nuevoEstado);
                    Swal.fire({
                        icon: 'success',
                        title: '¡Estatus actualizado!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    span.text(estadoActual);
                    span.removeClass('habilitado inhabilitado').addClass(estadoActual);
                    Swal.fire('Error', data.message || 'Error al cambiar el estatus', 'error');
                }
            },
            error: function(xhr, status, error) {
                span.removeClass('cambiando');
                span.text(estadoActual);
                span.removeClass('habilitado inhabilitado').addClass(estadoActual);
                Swal.fire('Error', 'Error en la conexión', 'error');
            }
        });
    }

    function mensajes(icono, titulo, mensaje){
        Swal.fire({
            icon: icono,
            title: titulo,
            text: mensaje,
            showConfirmButton: true,
            confirmButtonText: 'Aceptar',
        });
    }

    function validarKeyPress(er, e) {
        key = e.keyCode;
        tecla = String.fromCharCode(key);
        a = er.test(tecla);

        if (!a) {
            e.preventDefault();
        }
    }

    function validarKeyUp(er, etiqueta, etiquetamensaje, mensaje) {
        a = er.test(etiqueta.val());

        if (a) {
            etiquetamensaje.text("");
            return 1;
        } else {
            etiquetamensaje.text(mensaje);
            return 0;
        }
    }

    function space(str) {
        const regex = /\s{2,}/g;
        var str = str.replace(regex, ' ');
        return str;
    }
    
    function muestraMensaje(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: mensaje
        });
    }

    // Event listeners para el formulario de registro
    $('input[name="metodos_pago[]"]').on('change', function() {
        actualizarCampos();
    });

    $('#tipo_moneda').on('change', function () {
        const moneda = $(this).val();

        // Estado visual similar a select de rol en usuario
        if (moneda) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }

        if (moneda === 'bs') {
            $('.metodos-bs').removeClass('d-none');
            $('.metodos-usd').addClass('d-none');
            $('#zelle').prop('checked', false);
            $('#pagoMovil, #transferencia').prop('checked', false);
        } else if (moneda === 'usd') {
            $('.metodos-usd').removeClass('d-none');
            $('.metodos-bs').addClass('d-none');
            $('#pagoMovil, #transferencia').prop('checked', false);
            $('#zelle').prop('checked', true); // Selecciona Zelle automáticamente
        } else {
            $('.metodos-bs, .metodos-usd').addClass('d-none');
            $('#pagoMovil, #transferencia, #zelle').prop('checked', false);
        }
    });

    // Event listeners para el formulario de modificación
    $('#modificarCuentaModal').on('shown.bs.modal', function() {
        $('input[name="metodos_pago[]"]', this).on('change', function() {
            actualizarCamposModificar();
        });
    });

    $('#tipo_moneda_modificar').on('change', function () {
        const moneda = $(this).val();
        if (moneda === 'bs') {
            $('.metodos-bs_modificar').removeClass('d-none');
            $('.metodos-usd_modificar').addClass('d-none');
            $('#zelle_modificar').prop('checked', false);
            $('#pagoMovil_modificar, #transferencia_modificar').prop('checked', false);
        } else if (moneda === 'usd') {
            $('.metodos-usd_modificar').removeClass('d-none');
            $('.metodos-bs_modificar').addClass('d-none');
            $('#pagoMovil_modificar, #transferencia_modificar').prop('checked', false);
            $('#zelle_modificar').prop('checked', true); // Selecciona Zelle automáticamente
        } else {
            $('.metodos-bs_modificar, .metodos-usd_modificar').addClass('d-none');
            $('#pagoMovil_modificar, #transferencia_modificar, #zelle_modificar').prop('checked', false);
        }
    });

    // Inicializar estado de los campos
    actualizarCampos();
});

// Modal de detalles
$(document).on('click', '.btn-detalle', function () {
    const modal = document.getElementById('modalDetallesCuenta');
    document.getElementById('detalle-id').textContent = this.dataset.iddtl;
    document.getElementById('detalle-nombre').textContent = this.dataset.nombredtl;
    document.getElementById('detalle-numero').textContent = this.dataset.numerodtl;
    document.getElementById('detalle-rif').textContent = this.dataset.rifdtl;
    document.getElementById('detalle-telefono').textContent = this.dataset.telefonodtl;
    document.getElementById('detalle-correo').textContent = this.dataset.correodtl;
    document.getElementById('detalle-metodo').textContent = this.dataset.metodosdtl;
    document.getElementById('detalle-estatus').textContent = this.dataset.estatusdtl;
    modal.classList.add('mostrar');
});

$('#cerrarModalDetalles').on('click', function () {
    $('#modalDetallesCuenta').removeClass('mostrar');
});

$(window).on('click', function (e) {
    const modal = document.getElementById('modalDetallesCuenta');
    if (e.target === modal) {
        $(modal).removeClass('mostrar');
    }
});