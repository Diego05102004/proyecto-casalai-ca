$(document).ready(function () {
    var nombre_rol = "<?php echo $_SESSION['nombre_rol'] ?? ''; ?>";
    var esSuperUsuario = nombre_rol === 'SuperUsuario';

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", 4000, "Atención", $("#mensajes").html());
    }
    

    $("#nombre_categoria").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]*$/, e);
        let nombre = document.getElementById("nombre_categoria");
        nombre.value = space(nombre.value);
    });
    
    $("#nombre_categoria").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/,
            $(this),
            $("#snombre_categoria"),
            "*El formato permite letras y números*"
        );
    });

    function verificarPermisosEnTiempoRealCategoria() {
        var datos = new FormData();
        datos.append('accion', 'permisos_tiempo_real');
        enviarAjax(datos, function(permisos) {
            // Si es SuperUsuario, mostrar todo y salir
            if (esSuperUsuario) {
                $('#tablaConsultas').show();
                $('.space-btn-incluir').show();
                $('#btnIncluirCategoria').show();
                $('.btn-modificar').show();
                $('.btn-eliminar').show();
                $('#mensaje-permiso').remove();
                $('#tablaConsultas th:first-child, #tablaConsultas td:first-child').show();
                return;
            }
            
            console.log(permisos); // Para depuración
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
                $('#btnIncluirCategoria').show();
            } else {
                $('#btnIncluirCategoria').hide();
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
    verificarPermisosEnTiempoRealCategoria();
    setInterval(verificarPermisosEnTiempoRealCategoria, 10000);

    function validarEnvioCategoria(){
        let nombre = document.getElementById("nombre_categoria");
        nombre.value = space(nombre.value).trim();

        if(validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/,
            $("#nombre_categoria"),
            $("#snombre_categoria"),
            "*El nombre debe tener letras y/o números*"
        )==0){
            mensajes('error',4000,'Verifique el nombre de la categoria','Debe tener letras y/o números');
            return false;
        }
        return true;
    }

    function validarCaracteristicas() {
        let validacionCorrecta = true;
        let mensajeError = '';

        const regexNombre = /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ]+(?: [a-zA-ZÁÉÍÓÚñÑáéíóúüÜ]+)*$/;
        let hayString = false;

        $('.caracteristica-item').each(function(index, item) {
            const nombre = $(item).find('input[name*="[nombre]"]');
            const tipo = $(item).find('select[name*="[tipo]"]');
            const max = $(item).find('input[name*="[max]"]');

            const nombreVal = $.trim(nombre.val());

            if (nombreVal === '') {
                mensajeError = 'El nombre de una característica está vacío.';
                nombre.focus();
                validacionCorrecta = false;
                return false;
            }

            if (!regexNombre.test(nombreVal)) {
                mensajeError = 'El nombre de la característica solo puede contener letras y un espacio entre palabras.';
                nombre.focus();
                validacionCorrecta = false;
                return false;
            }

            if (tipo.val() === '') {
                mensajeError = 'Debe seleccionar un tipo para cada característica.';
                tipo.focus();
                validacionCorrecta = false;
                return false;
            }

            if (tipo.val() === 'string') {
                hayString = true;
                if ($.trim(max.val()) === '' || parseInt(max.val()) <= 0) {
                    mensajeError = 'El campo "Máx. caracteres" debe ser mayor a 0.';
                    max.focus();
                    validacionCorrecta = false;
                    return false;
                }
            }
        });

        if (!validacionCorrecta) {
            mensajes('error', 4000, 'Error de validación', mensajeError);
        }

        return validacionCorrecta;
    }

    function agregarFilaCategoria(categoria) {
        const tabla = $('#tablaConsultas').DataTable();
        const nuevaFila = [
            `<span class="campo-numeros">${categoria.id_categoria}</span>`,
            `<span class="campo-nombres">${categoria.nombre_categoria}</span>`,
            `<ul>
                <button class="btn-modificar"
                    id="btnModificarCategoria"
                    title="Modificar Categoria"
                    data-id="${categoria.id_categoria}"
                    data-nombre="${categoria.nombre_categoria}">
                    <img src="img/pencil.svg">
                </button>
                <button class="btn-eliminar"
                    title="Eliminar Categoria"
                    data-id="${categoria.id_categoria}">
                    <img src="img/circle-x.svg">
                </button>
            </ul>`
        ];
        const rowNode = tabla.row.add(nuevaFila).draw(false).node();
        $(rowNode).attr('data-id', categoria.id_categoria);
    }

    function resetCategoria() {
        $("#nombre_categoria").val('');
        $("#snombre_categoria").text('');
    }

    $('#btnIncluirCategoria').on('click', function() {
        $('#registrarCategoria')[0].reset();
        $('#snombre_categoria').text('');
        $('#registrarCategoriaModal').modal('show');
    });

    $('#registrarCategoria').on('submit', function(e) {
        e.preventDefault();

        if(validarEnvioCategoria() && validarCaracteristicas()) {
            var datos = new FormData(this);
            datos.append('accion', 'registrar');
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: respuesta.message || respuesta.msg || 'Categoria registrada correctamente'
                    });
                    agregarFilaCategoria(respuesta.categoria);
                    $('#registrarCategoriaModal').modal('hide');    
                    resetCategoria();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || respuesta.msg || 'No se pudo registrar la categoria'
                    });
                }
            });
        }
    });

    $(document).on('click', '#registrarCategoriaModal .close', function() {
        $('#registrarCategoriaModal').modal('hide');
    });

    function enviarAjax(datos, callback) {
        $.ajax({
            url: '',
            type: 'POST',
            data: datos,
            contentType: false,
            processData: false,
            cache: false,
            dataType: 'json', // Esperamos una respuesta JSON
            success: function (respuesta) {
                // Si llegamos aquí, la respuesta es JSON válido
                if(callback) callback(respuesta);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('Error en la solicitud AJAX:', {
                    textStatus: textStatus,
                    errorThrown: errorThrown,
                    responseText: jqXHR.responseText
                });

                let errorMessage = 'Error en la solicitud al servidor';
                let responseData = null;

                try {
                    // Intentar parsear la respuesta como JSON
                    if (jqXHR.responseText) {
                        responseData = JSON.parse(jqXHR.responseText);
                        if (responseData && responseData.message) {
                            errorMessage = responseData.message;
                        }
                    } else if (jqXHR.status === 0) {
                        errorMessage = 'No se pudo conectar con el servidor. Verifica tu conexión a internet.';
                    } else if (jqXHR.status === 404) {
                        errorMessage = 'No se encontró el recurso solicitado.';
                    } else if (jqXHR.status === 500) {
                        errorMessage = 'Error interno del servidor. Por favor, inténtalo de nuevo más tarde.';
                    }
                } catch (e) {
                    // Si la respuesta no es JSON, mostrarla como texto plano
                    console.log('La respuesta no es un JSON válido, mostrando como texto plano');
                    if (jqXHR.responseText) {
                        // Limpiar cualquier HTML o script que pueda estar en la respuesta
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = jqXHR.responseText;
                        const textContent = tempDiv.textContent || tempDiv.innerText || '';
                        
                        // Mostrar solo las primeras líneas para evitar mensajes muy largos
                        const lines = textContent.split('\n').filter(line => line.trim() !== '');
                        errorMessage = 'Error en el servidor: ' + (lines[0] || 'Error desconocido');
                        
                        // Si hay un error de PHP, mostrar más detalles en la consola
                        if (textContent.includes('Fatal error') || textContent.includes('Parse error')) {
                            console.error('Error de PHP detectado:', textContent);
                            errorMessage = 'Error en el servidor (ver consola para más detalles)';
                        }
                    }
                }

                // Mostrar el error al usuario
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMessage,
                    footer: 'Si el problema persiste, contacta al administrador.'
                });

                // Llamar al callback con un objeto de error estándar
                if (callback) {
                    callback({
                        status: 'error',
                        message: errorMessage,
                        error: {
                            status: jqXHR.status,
                            statusText: jqXHR.statusText,
                            responseText: jqXHR.responseText
                        }
                    });
                }
            }
        });
    }

    $("#modificar_nombre_categoria").on("keypress", function(e){
        validarKeyPress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]*$/, e);
        let nombre = document.getElementById("modificar_nombre_categoria");
        nombre.value = space(nombre.value);
    });
    
    $("#modificar_nombre_categoria").on("keyup", function(){
        validarKeyUp(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/,
            $(this),
            $("#smnombre_categoria"),
            "*El formato permite letras y números*"
        );
    });

    function validarCategoria(datos) {
        let errores = [];
        if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9\s\b]{2,20}$/.test(datos.nombre_categoria)) {
            errores.push("El nombre debe tener letras y/o números.");
        }
        return errores;
    }

    $(document).on('click', '#btnModificarCategoria', function () {
        $('#modificar_id_categoria').val($(this).data('id'));
        $('#modificar_nombre_categoria').val($(this).data('nombre'));
        $('#smnombre_categoria').text('');
        $('#modificarCategoriaModal').modal('show');
    });

    $('#modificarCategoria').on('submit', function(e) {
        e.preventDefault();

        const datos = {
            nombre_categoria: $('#modificar_nombre_categoria').val().trim()
        };

        const errores = validarCategoria(datos);

        if (errores.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: errores.join('<br>')
            });
            return;
        }

        // Crear array para almacenar las características
        const caracteristicas = [];
        
        // Buscar en el contenedor de características
        const contenedor = document.getElementById('modificar_caracteristicasContainer');
        if (contenedor) {
            const caracteristicasElements = contenedor.querySelectorAll('.caracteristica-item');
            
            caracteristicasElements.forEach((caracteristica, index) => {
                const nombreInput = caracteristica.querySelector('input[name$="[nombre]"]');
                const tipoSelect = caracteristica.querySelector('select[name$="[tipo]"]');
                const maxInput = caracteristica.querySelector('input[name$="[max]"]');
                
                const nombre = nombreInput ? nombreInput.value.trim() : '';
                const tipo = tipoSelect ? tipoSelect.value : 'string';
                const max = maxInput ? maxInput.value : '255';
                
                // Incluir la característica aunque el nombre esté vacío
                caracteristicas.push({
                    nombre: nombre || 'caracteristica_' + Date.now(), // Nombre por defecto si está vacío
                    tipo: tipo,
                    max: tipo === 'string' ? max : undefined
                });
            });
        }

        // Crear objeto con todos los datos para depuración
        const datosEnvio = {
            accion: 'modificar',
            id_categoria: $('#modificar_id_categoria').val(),
            nombre_categoria: datos.nombre_categoria,
            caracteristicas: caracteristicas
        };

        // Mostrar datos que se enviarán
        console.log('📤 Datos a enviar al servidor:', datosEnvio);

        // Crear FormData con todos los datos necesarios
        const formData = new FormData();
        formData.append('accion', 'modificar');
        formData.append('id_categoria', datosEnvio.id_categoria);
        formData.append('nombre_categoria', datosEnvio.nombre_categoria);
        formData.append('caracteristicas', JSON.stringify(datosEnvio.caracteristicas));
        $.ajax({
            url: '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#modificarCategoriaModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Modificado',
                        text: 'La categoria se ha modificado correctamente'
                    });

                    const tabla = $("#tablaConsultas").DataTable();
                    const id = $("#modificar_id_categoria").val();
                    const fila = tabla.row(`tr[data-id="${id}"]`);
                    const categoria = response.categoria;

                    if (fila.length) {
                        fila.data([
                            `<span class="campo-numeros">${categoria.id_categoria}</span>`,
                            `<span class="campo-nombres">${categoria.nombre_categoria}</span>`,
                            `<ul>
                                <button class="btn-modificar"
                                    id="btnModificarCategoria"
                                    title="Modificar Categoria"
                                    data-id="${categoria.id_categoria}"
                                    data-nombre="${categoria.nombre_categoria}">
                                    <img src="img/pencil.svg">
                                </button>
                                <button class="btn-eliminar"
                                    title="Eliminar Categoria"
                                    data-id="${categoria.id_categoria}">
                                    <img src="img/circle-x.svg">
                                </button>
                            </ul>`
                        ]).draw(false);

                        const filaNode = fila.node();
                        const botonModificar = $(filaNode).find(".btn-modificar");
                        botonModificar.data('nombre', categoria.nombre_categoria);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'No se pudo modificar la categoria'
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al modificar la categoria:', textStatus, errorThrown, jqXHR.responseText);
                let errorMsg = 'Error al modificar la categoría. Intente nuevamente.';
                
                // Intentar parsear la respuesta como JSON primero
                try {
                    const response = JSON.parse(jqXHR.responseText);
                    if (response && response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    // Si no es JSON válido, mostrar un mensaje genérico
                    console.error('La respuesta no es un JSON válido:', e);
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg,
                    footer: 'Si el problema persiste, contacte al administrador.'
                });
            }
        });
    });

    $(document).on('click', '#modificarCategoriaModal .close', function() {
        $('#modificarCategoriaModal').modal('hide');
    });

    // FUNCIÓN DE ELIMINACIÓN CON VALIDACIÓN DE PRODUCTOS ASOCIADOS
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
                var id_categoria = $(this).data('id');
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id_categoria', id_categoria);
                
                enviarAjax(datos, function(respuesta){
                    if (respuesta.status === 'success') {
                        Swal.fire(
                            'Eliminada!',
                            'La categoría ha sido eliminada.',
                            'success'
                        );
                        eliminarFilaCategoria(id_categoria);
                    } else {
                        // Mostrar mensaje específico si hay productos asociados
                        if (respuesta.message && respuesta.message.includes('productos registrados') || 
                            respuesta.message && respuesta.message.includes('productos asociados')) {
                            let mensaje = respuesta.message;
                            
                            // Si tenemos información de productos específicos, mostrarla
                            if (respuesta.productos && respuesta.productos.length > 0) {
                                mensaje += '<br><br><strong>Productos asociados (' + (respuesta.total_productos || respuesta.productos.length) + '):</strong><ul style="text-align: left; max-height: 200px; overflow-y: auto;">';
                                respuesta.productos.forEach(function(producto) {
                                    mensaje += '<li><strong>' + (producto.nombre_producto || producto.nombre) + '</strong>';
                                    if (producto.codigo_producto) {
                                        mensaje += ' (Código: ' + producto.codigo_producto + ')';
                                    }
                                    mensaje += '</li>';
                                });
                                
                                if (respuesta.total_productos > respuesta.productos.length) {
                                    mensaje += '<li>... y ' + (respuesta.total_productos - respuesta.productos.length) + ' productos más</li>';
                                }
                                
                                mensaje += '</ul>';
                                
                                mensaje += '<br><strong>Acción requerida:</strong><br>';
                                mensaje += '1. Elimine todos los productos de esta categoría, o<br>';
                                mensaje += '2. Reasigne los productos a otra categoría antes de eliminar esta.';
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'No se puede eliminar',
                                html: mensaje,
                                width: '700px',
                                customClass: {
                                    popup: 'scrollable-swal'
                                }
                            });
                        } else {
                            Swal.fire('Error', respuesta.message || 'Error al eliminar la categoría', 'error');
                        }
                    }
                });
            }
        });
    });

    function eliminarFilaCategoria(id_categoria) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${id_categoria}"]`);
        tabla.row(fila).remove().draw();
    }

    // FUNCIONES PARA MANEJO DE CARACTERÍSTICAS
    document.addEventListener('DOMContentLoaded', () => {
        const contenedor = document.getElementById('caracteristicasContainer');
        const btnAgregar = document.getElementById('agregarCaracteristica');

        let contador = 0;
        const maxCaracteristicas = 5;

        const crearInputCaracteristica = (id, puedeEliminar = true) => {
            const div = document.createElement('div');
            div.classList.add('caracteristica-item');
            div.dataset.index = id;

            div.innerHTML = `
                <input type="text" name="caracteristicas[${id}][nombre]" placeholder="Nombre" class="form-control" maxlength="20" required>
                <select name="caracteristicas[${id}][tipo]" class="form-select tipo-caracteristica" required>
                    <option value="" disable hidden>Tipo</option>
                    <option value="int">Entero</option>
                    <option value="float">Decimal</option>
                    <option value="string">Texto</option>
                </select>
                <input type="number" name="caracteristicas[${id}][max]" placeholder="Máx. caracteres" class="form-control max-caracteres" min="1" max="255" style="display:none;">
                ${puedeEliminar ? `<button type="button" class="btn btn-danger btn-eliminar-caracteristicas">✖</button>` : ''}
            `;

            // Mostrar/ocultar el campo de max según el tipo
            const selectTipo = div.querySelector('.tipo-caracteristica');
            const inputMax = div.querySelector('.max-caracteres');
            selectTipo.addEventListener('change', function() {
                if (this.value === 'string') {
                    inputMax.style.display = '';
                    inputMax.required = true;
                } else {
                    inputMax.style.display = 'none';
                    inputMax.value = '';
                    inputMax.required = false;
                }
            });

            if (puedeEliminar) {
                div.querySelector('.btn-eliminar-caracteristicas').addEventListener('click', () => {
                    contenedor.removeChild(div);
                    contador--;
                    btnAgregar.disabled = false;
                });
            }

            contenedor.appendChild(div);
        };

        // Agrega una característica inicial no eliminable
        crearInputCaracteristica(contador++, false);

        btnAgregar.addEventListener('click', () => {
            if (contador < maxCaracteristicas) {
                crearInputCaracteristica(contador++);
                if (contador === maxCaracteristicas) {
                    btnAgregar.disabled = true;
                }
            }
        });
    });

    // Utilidad para crear inputs de características en el modal de modificar
    function crearInputCaracteristicaMod(id, nombre = '', tipo = '', max = '', puedeEliminar = true) {
        const div = document.createElement('div');
        div.classList.add('caracteristica-item');
        div.dataset.index = id;

        div.innerHTML = `
            <input type="text" name="caracteristicas[${id}][nombre]" placeholder="Nombre" class="form-control" maxlength="20" required value="${nombre}">
            <select name="caracteristicas[${id}][tipo]" class="form-select tipo-caracteristica" required>
                <option value="" disable hidden>Tipo</option>
                <option value="int" ${tipo === 'int' ? 'selected' : ''}>Entero</option>
                <option value="float" ${tipo === 'float' ? 'selected' : ''}>Decimal</option>
                <option value="string" ${tipo === 'string' ? 'selected' : ''}>Texto</option>
            </select>
            <input type="number" name="caracteristicas[${id}][max]" placeholder="Máx. caracteres" class="form-control max-caracteres" min="1" max="255" value="${tipo === 'string' ? max : ''}" style="${tipo === 'string' ? '' : 'display:none;'}">
            ${puedeEliminar ? `<button type="button" class="btn btn-danger btn-eliminar-caracteristicas">✖</button>` : ''}
        `;

        // Mostrar/ocultar el campo de max según el tipo
        const selectTipo = div.querySelector('.tipo-caracteristica');
        const inputMax = div.querySelector('.max-caracteres');
        selectTipo.addEventListener('change', function() {
            if (this.value === 'string') {
                inputMax.style.display = '';
                inputMax.required = true;
            } else {
                inputMax.style.display = 'none';
                inputMax.value = '';
                inputMax.required = false;
            }
        });

        if (puedeEliminar) {
            div.querySelector('.btn-eliminar-caracteristicas').addEventListener('click', () => {
                div.parentNode.removeChild(div);
                modificarContador--;
                modificarBtnAgregar.disabled = false;
            });
        }

        return div;
    }

    let modificarContador = 0;
    const modificarMaxCaracteristicas = 5;
    let modificarBtnAgregar = null;

    // Abrir modal de modificar y cargar datos
    $(document).on('click', '.btn-modificar', function () {
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
        // Limpiar el formulario
        $('#modificarCategoria')[0].reset();
        $('#modificar_id_categoria').val(id);
        $('#modificar_nombre_categoria').val(nombre);
        $('#smnombre_categoria').text('');

        // Limpiar contenedor de características
        const contenedor = document.getElementById('modificar_caracteristicasContainer');
        contenedor.innerHTML = '';
        modificarContador = 0;

        // AJAX para obtener datos de la categoría y sus características
        const datos = new FormData();
        datos.append('accion', 'obtener_categoria');
        datos.append('id_categoria', id);

        // Mostrar loading
        $('#modificarCategoriaModal').modal('show');
        $('#modificarCategoriaModal .modal-body').append('<div id="loading" class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Cargando...</div>');

        $.ajax({
            url: '',
            type: 'POST',
            data: datos,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (categoria) {
                // Ocultar loading
                $('#loading').remove();
                
                // Establecer valores del formulario
                $('#modificar_nombre_categoria').val(categoria.nombre_categoria);

                // Limpiar contenedor primero
                contenedor.innerHTML = '';
                
                // Cargar características si existen
                if (categoria.caracteristicas && categoria.caracteristicas.length > 0) {
                    categoria.caracteristicas.forEach((carac, index) => {
                        // Solo permitir eliminar si no es la primera característica
                        const puedeEliminar = index > 0;
                        const div = crearInputCaracteristicaMod(
                            modificarContador++,
                            carac.nombre || '',
                            carac.tipo || 'string',
                            carac.max || '255',
                            puedeEliminar
                        );
                        contenedor.appendChild(div);
                    });
                } else {
                    // Si no hay características, agrega una vacía no eliminable
                    contenedor.appendChild(crearInputCaracteristicaMod(modificarContador++, '', '', '', false));
                }

                // Habilitar o deshabilitar el botón de agregar
                modificarBtnAgregar = document.getElementById('modificar_agregarCaracteristica');
                modificarBtnAgregar.disabled = modificarContador >= modificarMaxCaracteristicas;

                $('#modificarCategoriaModal').modal('show');
            },
            error: function () {
                Swal.fire('Error', 'No se pudo obtener la categoría.', 'error');
            }
        });
    });

    // Agregar característica en el modal de modificar
    $(document).on('click', '#modificar_agregarCaracteristica', function () {
        const contenedor = document.getElementById('modificar_caracteristicasContainer');
        if (modificarContador < modificarMaxCaracteristicas) {
            contenedor.appendChild(crearInputCaracteristicaMod(modificarContador++));
            if (modificarContador === modificarMaxCaracteristicas) {
                this.disabled = true;
            }
        }
    });

    // FUNCIONES UTILITARIAS
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