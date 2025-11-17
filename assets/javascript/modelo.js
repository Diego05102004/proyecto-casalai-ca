function protegerSelects(selectIds, interval = 1000) {
    const originales = {};

    // Guardar opciones originales de cada select
    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;

        originales[id] = Array.from(select.options).map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));
    });

    // Monitorear periódicamente cambios en las opciones
    setInterval(() => {
        selectIds.forEach(id => {
            const select = document.getElementById(id);
            if (!select) return;

            const opsActuales = Array.from(select.options);
            const opsOriginales = originales[id];
            if (!opsOriginales) return;

            const alterado =
                opsActuales.length !== opsOriginales.length ||
                opsActuales.some((o, i) =>
                    !opsOriginales[i] ||
                    o.value !== opsOriginales[i].value ||
                    o.textContent !== opsOriginales[i].text
                );

            if (alterado) {
                select.innerHTML = "";
                opsOriginales.forEach(optData => {
                    const opt = document.createElement("option");
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    select.appendChild(opt);
                });

                console.warn(`⚠ Opciones del <select id="${id}"> fueron alteradas. Restauradas automáticamente.`);
            }
        });
    }, interval);
}

$(document).ready(function () {

    // Inicializar DataTable de modelos y crear dinámicamente el botón Incluir en el filtro
    var $tabla = $('#tablaConsultas');
    if ($tabla.length) {
        var tablaModelos;
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaModelos = $tabla.DataTable({
                language: {
                    "url": "assets/public/js/es-ES.json"
                },
                order: [[0, 'desc']],
                initComplete: function () {
                    var $wrapper = $tabla.closest('.dataTables_wrapper');
                    var $filter = $wrapper.find('.dataTables_filter');
                    if (!$filter.length) return;

                    // Evitar duplicar el botón si ya existe
                    if ($filter.find('#btnIncluirModelo').length) return;

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
                        id: 'btnIncluirModelo',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Modelo'
                    }).append($('<img>', { src: 'assets/img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
        } else {
            tablaModelos = $tabla.DataTable();
        }
    }

    protegerSelects(['id_marca', 'modificar_marca_modelo']);

    // Validación select de marca (registrar)
    $("#id_marca").on("change", function(){
        if ($(this).val()) {
            $(this).removeClass("is-invalid").addClass("is-valid");
        } else {
            $(this).removeClass("is-valid").addClass("is-invalid");
        }
    });

    // Validación select de marca (modificar)
    $("#modificar_marca_modelo").on("change", function(){
        if ($(this).val()) {
            $(this).removeClass("is-invalid").addClass("is-valid");
        } else {
            $(this).removeClass("is-valid").addClass("is-invalid");
        }
    });

    // Validación en tiempo real para registro
    $("#nombre_modelo").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9-\/\s\b]*$/, e);
        let nombre = document.getElementById("nombre_modelo");
        nombre.value = space(nombre.value);
    });
    
    $("#nombre_modelo").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9-\/\s\b]{2,25}$/,
            $(this),
            $("#snombre_modelo"),
            "*El formato permite letras, números y (-/)*"
        );
    });

    function verificarPermisosEnTiempoRealModelos() {
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
                $('#btnIncluirModelo').show();
            } else {
                $('#btnIncluirModelo').hide();
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
    verificarPermisosEnTiempoRealModelos();
    setInterval(verificarPermisosEnTiempoRealModelos, 10000); // 10 segundos

    function validarEnvioModelo(){
        // Validar selección de marca
        const $marca = $("#id_marca");
        if (!$marca.val()) {
            $marca.removeClass("is-valid").addClass("is-invalid");
            mensajes('error', 'Verifique la marca', 'Debe seleccionar una marca');
            return false;
        } else {
            $marca.removeClass("is-invalid").addClass("is-valid");
        }

        let $nombre = $("#nombre_modelo");
        $nombre.val(space($nombre.val()).trim());
        const val = $nombre.val();

        if (val === "") {
            $("#snombre_modelo").text("*Este campo es obligatorio*");
            mensajes('error', 'Verifique el nombre del modelo', 'El campo está vacío.');
            return false;
        }
        if (val.length < 2) {
            $("#snombre_modelo").text("*Mínimo 2 caracteres*");
            mensajes('error', 'Verifique el nombre del modelo', 'Debe tener mínimo 2 caracteres.');
            return false;
        }

        return true;
    }

    function agregarFilaModelo(modelo) {
        const nuevaFila = `
            <tr data-id="${modelo.id_modelo}">
                <td><span class="campo-numeros">${modelo.id_modelo}</span></td>
                <td><span class="campo-nombres">${modelo.nombre_marca}</span></td>
                <td><span class="campo-nombres">${modelo.nombre_modelo}</span></td>
                <td>
                    <ul>
                        <button class="btn-modificar"
                            title="Modificar Modelo"
                            data-id="${modelo.id_modelo}"
                            data-marcaid="${modelo.id_marca}"
                            data-nombre="${modelo.nombre_modelo}">
                            <img src="assets/img/pencil.svg">
                        </button>
                        <button class="btn-eliminar"
                            title="Eliminar Modelo"
                            data-id="${modelo.id_modelo}">
                            <img src="assets/img/circle-x.svg">
                        </button>
                    </ul>
                </td>
            </tr>`;
        const tabla = $('#tablaConsultas').DataTable();
        tabla.row.add($(nuevaFila));
        tabla.order([0, 'desc']).draw(false);
        tabla.page('first').draw('page');
    }

    function resetModelo() {
        $("#id_marca").val('');
        $("#nombre_modelo").val('');
        $("#snombre_modelo").text('');
    }

    $(document).on('click', '#btnIncluirModelo', function() {
        $('#registrarModelo')[0].reset();
        $('#snombre_modelo').text('');
        $('#registrarModeloModal').modal('show');
    });

    $('#registrarModelo').on('submit', function(e) {
        e.preventDefault();

        if(validarEnvioModelo()){
            var datos = new FormData(this);
            datos.append('accion', 'registrar');
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: respuesta.message || respuesta.msg || 'Modelo registrado correctamente'
                    });
                    agregarFilaModelo(respuesta.modelo);
                    resetModelo();
                    $('#registrarModeloModal').modal('hide');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || respuesta.msg || 'No se pudo registrar el modelo'
                    });
                }
            });
        }
    });

    $(document).on('click', '#registrarModeloModal .close', function() {
        $('#registrarModeloModal').modal('hide');
    });

    $("#modificar_nombre_modelo").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9-\/\s\b]*$/, e);
        let nombre = document.getElementById("modificar_nombre_modelo");
        nombre.value = space(nombre.value);
    });
    
    $("#modificar_nombre_modelo").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9-\/\s\b]{2,25}$/,
            $(this),
            $("#smnombre_modelo"),
            "*El formato permite letras, números y (-/)*"
        );
    });

    $(document).on('click', '.btn-modificar', function () {
        $('#modificar_id_modelo').val($(this).data('id'));
        llenarSelectMarcasModal($(this).data('marcaid'));
        $('#modificar_nombre_modelo').val($(this).data('nombre'));
        $('#smnombre_modelo').text('');
        $('#modificarModeloModal').modal('show');
    });

    $('#modificarModelo').on('submit', function(e) {
        e.preventDefault();
        // Validación de marca seleccionada (modificar)
        const $marcaM = $("#modificar_marca_modelo");
        if (!$marcaM.val()) {
            $marcaM.removeClass("is-valid").addClass("is-invalid");
            mensajes('error', 'Verifique la marca', 'Debe seleccionar una marca');
            return;
        } else {
            $marcaM.removeClass("is-invalid").addClass("is-valid");
        }

        // Validación de nombre (modificar): vacío y mínimo 2
        const $nombreM = $("#modificar_nombre_modelo");
        $nombreM.val(space($nombreM.val()).trim());
        const nombreModelo = $nombreM.val();
        if (nombreModelo === "") {
            $("#smnombre_modelo").text("*Este campo es obligatorio*");
            mensajes('error', 'Verifique el nombre del modelo', 'El campo está vacío.');
            return;
        }
        if (nombreModelo.length < 2) {
            $("#smnombre_modelo").text("*Mínimo 2 caracteres*");
            mensajes('error', 'Verifique el nombre del modelo', 'Debe tener mínimo 2 caracteres.');
            return;
        }

        var datos = new FormData(this);
        datos.append('accion', 'modificar');
        enviarAjax(datos, function(respuesta){
            if(respuesta.status === "success" || respuesta.resultado === "success"){
                $('#modificarModeloModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Modificado',
                    text: respuesta.message || 'El modelo se ha modificado correctamente'
                });
                
                // Actualizar la fila en la tabla con el mismo formato
                let modelo = respuesta.modelo; // El backend debe retornar el modelo actualizado
                let fila = $(`tr[data-id="${modelo.id_modelo}"]`);
                const nuevaFila = [
                    `<span class="campo-numeros">${modelo.id_modelo}</span>`,
                    `<span class="campo-nombres">${modelo.nombre_marca}</span>`,
                    `<span class="campo-nombres">${modelo.nombre_modelo}</span>`,
                    `<ul>
                        <button class="btn-modificar"
                            id="btnModificarModelo"
                            title="Modificar Modelo"
                            data-id="${modelo.id_modelo}"
                            data-marcaid="${modelo.id_marca}"
                            data-nombre="${modelo.nombre_modelo}">
                            <img src="assets/img/pencil.svg">
                        </button>
                        <button class="btn-eliminar"
                            title="Eliminar Modelo"
                            data-id="${modelo.id_modelo}">
                            <img src="assets/img/circle-x.svg">
                        </button>
                    </ul>`
                ];
                
                const tabla = $('#tablaConsultas').DataTable();
                const page = tabla.page();
                fila = tabla.row(`tr[data-id="${modelo.id_modelo}"]`);
                
                if (fila.length) {
                    fila.data(nuevaFila).draw(false);
                    tabla.page(page).draw(false);

                    const filaNode = fila.node();
                    const botonModificar = $(filaNode).find(".btn-modificar");
                    botonModificar.data("marcaid", modelo.id_marca);
                    botonModificar.data("nombre", modelo.nombre_modelo);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: respuesta.message || 'No se pudo modificar el modelo'
                });
            }
        });
    });

    $(document).on('click', '#modificarModeloModal .close', function() {
        $('#modificarModeloModal').modal('hide');
    });

    function llenarSelectMarcasModal(idSeleccionada) {
        let select = $('#modificar_marca_modelo');
        select.empty();
        select.append('<option value="">Seleccione una marca</option>');
        window.marcasDisponibles.forEach(function(marca) {
            let selected = marca.id_marca == idSeleccionada ? 'selected' : '';
            select.append(`<option value="${marca.id_marca}" ${selected}>${marca.nombre_marca}</option>`);
        });
    }

    $(document).on('click', '.btn-eliminar', function () {
    let id_modelo = $(this).data('id');
    Swal.fire({
        title: '¿Está seguro?',
        text: "¡No podrás revertir esto!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminarlo!'
    }).then((result) => {
        if (result.isConfirmed) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id_modelo', id_modelo);
            enviarAjax(datos, function(respuesta){
                if (respuesta.status === 'success') {
                    Swal.fire('Eliminado!', 'El modelo ha sido eliminado.', 'success');
                    const tabla = $('#tablaConsultas').DataTable();
                    const fila = $(`#tablaConsultas tbody tr[data-id="${id_modelo}"]`);
                    tabla.row(fila).remove().draw();
                } else if (respuesta.status === 'error' && respuesta.productos) {
                    // Crear mensaje detallado con los productos asociados
                    let mensaje = respuesta.message + '\n\n';
                    mensaje += 'Total de productos asociados: ' + respuesta.total_productos + '\n\n';
                    
                    if (respuesta.productos && respuesta.productos.length > 0) {
                        mensaje += 'Algunos productos asociados:\n';
                        respuesta.productos.forEach(function(producto, index) {
                            if (index < 5) { // Mostrar máximo 5 productos
                                mensaje += '• ' + producto.nombre_producto;
                                if (producto.codigo_producto) {
                                    mensaje += ' (Código: ' + producto.codigo_producto + ')';
                                }
                                mensaje += '\n';
                            }
                        });
                        
                        if (respuesta.total_productos > 5) {
                            mensaje += '... y ' + (respuesta.total_productos - 5) + ' productos más';
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'No se puede eliminar',
                        html: mensaje.replace(/\n/g, '<br>'),
                        width: '600px'
                    });
                } else {
                    Swal.fire('Error', respuesta.message || 'Error al eliminar el modelo', 'error');
                }
            });
        }
    });
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

    function validarKeyPress(regex, e) {
        let key = String.fromCharCode(!e.charCode ? e.which : e.charCode);
        if (!regex.test(key)) {
            e.preventDefault();
            return false;
        }
        return true;
    }
    
    function validarKeyUp(regex, input, span, mensaje) {
        if (!regex.test(input.val())) {
            span.text(mensaje);
            return 0;
        } else {
            span.text('');
            return 1;
        }
    }
    
    function space(text) {
        return text.replace(/\s{2,}/g, ' ');
    }
    
    function mensajes(icono, titulo, mensaje) {
        Swal.fire({
        icon: icono,
        title: titulo,
        text: mensaje,
        showConfirmButton: true,
        confirmButtonText: "Aceptar",
        });
    }
});