$(document).ready(function () {

    // MENSAJE //
    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", 4000, "Atención", $("#mensajes").html());
    }

    $("#nombre").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
        let nombre = document.getElementById("nombre");
        nombre.value = space(nombre.value);
    });

    $("#nombre").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,100}$/,
            $(this),
            $("#snombre"),
            "*El formato solo permite letras*"
        );
    });

    $("#cedula").on("keypress", function(e){
        validarKeyPress(/^[0-9.]*$/, e);
    });

    $("#cedula").on("keyup", function(){
        validarkeyup(
            /^(?:\d{1,2}\.\d{3}\.\d{3})$/,
            $(this),
            $("#scedula"),
            "*El formato debe ser 1.234.567 o 12.345.678*"
        );
    });
    $("#cedula").on("input", function() {
        let d = $(this).val().replace(/\D/g, '');
        let out = d;
        if (d.length === 7) {
            // X.XXX.XXX
            out = d.slice(0,1) + '.' + d.slice(1,4) + '.' + d.slice(4,7);
        } else if (d.length === 8) {
            // XX.XXX.XXX
            out = d.slice(0,2) + '.' + d.slice(2,5) + '.' + d.slice(5,8);
        }
        $(this).val(out);
    });

    $("#telefono").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });

    $("#telefono").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#stelefono"),
            "*Formato válido: 0400-000-0000*"
        );
    });
    $("#telefono").on("input", function() {
        let valor_t1 = $(this).val().replace(/\D/g, '');
        if(valor_t1.length > 4 && valor_t1.length <= 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4);
        else if(valor_t1.length > 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4,7) + '-' + valor_t1.slice(7,11);
        $(this).val(valor_t1);
    });

    $("#direccion").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
        let direccion = document.getElementById("direccion");
        direccion.value = space(direccion.value);
    });

    $("#direccion").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,100}$/,
            $(this),
            $("#sdireccion"),
            "*El formato permite letras y números*"
        );
    });

    $("#correo").on("keypress", function (e) {
        validarKeyPress(/^[a-zA-ZñÑ_0-9@,.\b]*$/, e);
    });

    $("#correo").on("keyup", function(){
        validarKeyUp(
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            $(this),
            $("#scorreo"),
            "*Formato válido: ejemplo@gmail.com*"
        );
    });
function verificarPermisosEnTiempoRealClientes() {
    var datos = new FormData();
    datos.append('accion', 'permisos_tiempo_real');
    enviarAjax(datos, function(permisos) {
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
            $('#btnIncluirCliente').show();
        } else {
            $('#btnIncluirCliente').hide();
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
    verificarPermisosEnTiempoRealClientes();
    setInterval(verificarPermisosEnTiempoRealClientes, 10000); // 10 segundos
});
    function validarEnvioCliente(){
        let nombre = document.getElementById("nombre");
        nombre.value = space(nombre.value).trim();

        let direccion = document.getElementById("direccion");
        direccion.value = space(direccion.value).trim();

        if(validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,100}$/,
            $("#nombre"),
            $("#snombre"),
            "*El nombre debe tener solo letras*"
        )==0){
            mensajes('error',4000,'Verifique el nombre','Debe tener solo letras');
            return false;
        }
        else if(validarKeyUp(
            /^(?:\d{1,2}\.\d{3}\.\d{3})$/,
            $("#cedula"),
            $("#scedula"),
            "*Formato válido: 1.234.567 o 12.345.678*"
        )==0){
            mensajes('error',4000,'Verifique el número de Cedula','El formato solo permite números.');
            return false;
        }
        else if(validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $("#telefono"),
            $("#stelefono"),
            "*Formato válido: 0400-000-0000*"
        )==0){
            mensajes('error',4000,'Verifique el teléfono','El formato solo permite números.');
            return false;
        }
        else if(validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,100}$/,
            $("#direccion"),
            $("#sdireccion"),
            "*Puede haber letras y números*"
        )==0){
            mensajes('error',4000,'Verifique la dirección','Debe tener solo letras y números');
            return false;
        }
        else if(validarKeyUp(
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            $("#correo"),
            $("#scorreo"),
            "*Formato correcto: ejemplo@gmail.com*"
        )==0){
            mensajes('error',4000,'Verifique el correo','Correo no válido');
            return false;
        }
        return true;
    }

    function agregarFilaCliente(cliente) {
        const tabla = $('#tablaConsultas').DataTable();
        const nuevaFila = [
            `<span class="campo-nombres">${cliente.nombre}</span>`,
            `<span class="campo-tex-num">${cliente.cedula}</span>`,
            `<span class="campo-nombres">${cliente.direccion}</span>`,
            `<span class="campo-numeros">${cliente.telefono}</span>`,
            `<span class="campo-tex-num">${cliente.correo}</span>`,
            `<ul>
                <button class="btn-modificar"
                    id="btnModificarCliente"
                    title="Modificar Cliente"
                    data-id="${cliente.id_clientes}"
                    data-nombre="${cliente.nombre}"
                    data-cedula="${cliente.cedula}"
                    data-direccion="${cliente.direccion}"
                    data-telefono="${cliente.telefono}"
                    data-correo="${cliente.correo}">
                    <img src="img/pencil.svg">
                </button>
                <button class="btn-eliminar"
                    title="Eliminar Cliente"
                    data-id="${cliente.id_clientes}">
                    <img src="img/circle-x.svg">
                </button>
            </ul>`
        ];
        const rowNode = tabla.row.add(nuevaFila).draw(false).node();
        $(rowNode).attr('data-id', cliente.id_clientes);
    }

    function resetCliente() {
        $("#nombre").val('');
        $("#cedula").val('');
        $("#direccion").val('');
        $("#telefono").val('');
        $("#correo").val('');
        $("#snombre").text('');
        $("#scedula").text('');
        $("#sdireccion").text('');
        $("#stelefono").text('');
        $("#scorreo").text('');
    }

    $('#btnIncluirCliente').on('click', function() {
        $('#ingresarclientes')[0].reset();
        $('#snombre').text('');
        $('#scedula').text('');
        $('#sdireccion').text('');
        $('#stelefono').text('');
        $('#scorreo').text('');
        $('#registrarClienteModal').modal('show');
    });

    $('#ingresarclientes').on('submit', function(e) {
    e.preventDefault();

        if(validarEnvioCliente()){
            var datos = new FormData(this);
            datos.append('accion', 'registrar');
            
            $.ajax({
                url: '',
                type: 'POST',
                data: datos,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(respuesta){
                    if(respuesta.status === "success"){
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: respuesta.message || 'Cliente registrado correctamente'
                        }).then(() => {
                            if(respuesta.status === "success" && respuesta.cliente){
                                agregarFilaCliente(respuesta.cliente);
                                resetCliente();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: respuesta.message || 'Error al registrar el cliente'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'Ocurrió un error al comunicarse con el servidor'
                    });
                }
            });
        }
    });

    $(document).on('click', '#registrarClienteModal .close', function() {
        $('#registrarClienteModal').modal('hide');
    });

    function enviarAjax(datos, callback) {
        $.ajax({
            url: '',
            type: 'POST',
            data: datos,
            contentType: false,
            processData: false,
            cache: false,
            success: function (respuesta) {
                if (typeof respuesta === "string") {
                    respuesta = JSON.parse(respuesta);
                }
                if(callback) callback(respuesta);
            },
            error: function () {
                Swal.fire('Error', 'Error en la solicitud AJAX', 'error');
            }
        });
    }

    $(document).on('click', '#btnModificarCliente', function () {
        $('#modificar_id_clientes').val($(this).data('id'));
        $('#modificarnombre').val($(this).data('nombre'));
        $('#modificarcedula').val($(this).data('cedula'));
        $('#modificardireccion').val($(this).data('direccion'));
        $('#modificartelefono').val($(this).data('telefono'));
        $('#modificarcorreo').val($(this).data('correo'));
        $('#smodificarnombre').text('');
        $('#smodificarcedula').text('');
        $('#smodificardireccion').text('');
        $('#smodificartelefono').text('');
        $('#smodificarcorreo').text('');
        $('#modificar_clientes_modal').modal('show');
    });

    $("#modificarnombre").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
        let nombre = document.getElementById("nombre");
        nombre.value = space(nombre.value);
    });

    $("#modificarnombre").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,100}$/,
            $(this),
            $("#smodificarnombre"),
            "*El formato solo permite letras*"
        );
    });

    $("#modificarcedula").on("keypress", function(e){
        validarKeyPress(/^[0-9]*$/, e);
    });

    $("#modificarcedula").on("keyup", function(){
        validarKeyUp(
            /^(?:\d{1,2}\.\d{3}\.\d{3})$/,
            $(this),
            $("#scedula"),
            "*Formato válido: 1.234.567 o 12.345.678*"
        );
    });

    $("#modificartelefono").on("keypress", function(e){
        validarKeyPress(/^[0-9-]*$/, e);
    });

    $("#modificartelefono").on("keyup", function(){
        validarKeyUp(
            /^\d{4}-\d{3}-\d{4}$/,
            $(this),
            $("#smodificartelefono"),
            "*Formato válido: 0400-000-0000*"
        );
    });
    $("#modificartelefono").on("input", function() {
        let valor_t1 = $(this).val().replace(/\D/g, '');
        if(valor_t1.length > 4 && valor_t1.length <= 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4);
        else if(valor_t1.length > 7)
            valor_t1 = valor_t1.slice(0,4) + '-' + valor_t1.slice(4,7) + '-' + valor_t1.slice(7,11);
        $(this).val(valor_t1);
    });

    $("#modificardireccion").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
        let direccion = document.getElementById("direccion");
        direccion.value = space(direccion.value);
    });

    $("#modificardireccion").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,100}$/,
            $(this),
            $("#smodificardireccion"),
            "*El formato permite letras y números*"
        );
    });

    $("#modificarcorreo").on("keypress", function (e) {
        validarKeyPress(/^[a-zA-ZñÑ_0-9@,.\b]*$/, e);
    });

    $("#modificarcorreo").on("keyup", function(){
        validarKeyUp(
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            $(this),
            $("#smodificarcorreo"),
            "*Formato válido: example@gmail.com*"
        );
    });

    function validarCliente(datos) {
        let errores = [];
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{2,100}$/.test(datos.nombre)) {
            errores.push("El nombre debe tener solo letras.");
        }
        if (!/^[VEJPG0-9-.\b]*$/.test(datos.cedula)) {
            errores.push("Formato correcto: 00.000.000");
        }
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,100}$/.test(datos.direccion)) {
            errores.push("El formato permite letras y números.");
        }
        if (!/^\d{4}-\d{3}-\d{4}$/.test(datos.telefono)) {
            errores.push("Formato correcto: 0400.000.0000.");
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(datos.correo)) {
            errores.push("Formato correcto: ejemplo@gmail.com.");
        }
        return errores;
    }

    $('#modificarclientes').on('submit', function(e) {
        e.preventDefault();

        const datos = {
            nombre: $('#modificarnombre').val(),
            cedula: $('#modificarcedula').val(),
            direccion: $('#modificardireccion').val(),
            telefono: $('#modificartelefono').val(),
            correo: $('#modificarcorreo').val()
        }

        const errores = validarCliente(datos);

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
            processData: false,
            contentType: false,
            cache: false,
            data: formData,
            dataType: 'json',
            success: function(response) {
            if (response.status === 'success') {
                $('#modificar_clientes_modal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Modificado',
                    text: 'El Cliente se ha modificado correctamente'
                });

                const tabla = $("#tablaConsultas").DataTable();
                const id = $("#modificar_id_clientes").val();
                const fila = tabla.row(`tr[data-id="${id}"]`);
                const cliente = response.cliente;

                if (fila.length) {
                    fila.data([
                        `<span class="campo-nombres">${cliente.nombre}</span>`,
                        `<span class="campo-tex-num">${cliente.cedula}</span>`,
                        `<span class="campo-nombres">${cliente.direccion}</span>`,
                        `<span class="campo-numeros">${cliente.telefono}</span>`,
                        `<span class="campo-tex-num">${cliente.correo}</span>`,
                        `<ul>
                            <div>
                                <button class="btn-modificar"
                                    id="btnModificarCliente"
                                    title="Modificar Cliente"
                                    data-id="${cliente.id_clientes}"
                                    data-nombre="${cliente.nombre}"
                                    data-cedula="${cliente.cedula}"
                                    data-direccion="${cliente.direccion}"
                                    data-telefono="${cliente.telefono}"
                                    data-correo="${cliente.correo}">
                                    <img src="img/pencil.svg">
                                </button>
                            </div>
                            <div>
                                <button class="btn-eliminar"
                                    title="Eliminar Cliente"
                                    data-id="${cliente.id_clientes}">
                                    <img src="img/circle-x.svg">
                                </button>
                            </div>
                        </ul>`
                    ]).draw(false);

                    const filaNode = fila.node();
                    const botonModificar = $(filaNode).find(".btn-modificar");
                    botonModificar.data('nombre', cliente.nombre);
                    botonModificar.data('cedula', cliente.cedula);
                    botonModificar.data('direccion', cliente.direccion);
                    botonModificar.data('telefono', cliente.telefono);
                    botonModificar.data('correo', cliente.correo);
                }
            } else {
                    muestraMensaje(response.message || 'No se pudo modificar el cliente');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                muestraMensaje('Error al modificar el Cliente.');
            }
        });
    });

    $(document).on('click', '#modificar_clientes_modal .close', function() {
        $('#modificar_clientes_modal').modal('hide');
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
                var id_clientes = $(this).data('id');
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id_clientes', id_clientes);
                enviarAjax(datos, function(respuesta){
                    if (respuesta.status === 'success') {
                        Swal.fire(
                            'Eliminada!',
                            'El proveedor ha sido eliminada.',
                            'success'
                        );
                        eliminarFilaProveedor(id_clientes);
                    } else {
                        Swal.fire('Error', respuesta.message, 'error');
                    }
                });
            }
        });
    });

    function eliminarFilaProveedor(id_clientes) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${id_clientes}"]`);
        tabla.row(fila).remove().draw();
    }

    function mensajes(icono, tiempo, titulo, mensaje){
        Swal.fire({
            icon: icono,
            timer: tiempo,
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
});