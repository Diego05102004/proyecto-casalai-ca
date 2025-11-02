$(document).ready(function () {

    const regexTexto = /^[a-zA-Z0-9@\.\-\sÁÉÍÓÚáéíóúñÑ]+$/;
    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", 4000, "Atención", $("#mensajes").html());
    }

    $("#nombre_producto").on("keypress", function(e){
        validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
        let nombre = document.getElementById("nombre_producto");
        nombre.value = space(nombre.value);
    });

    $("#nombre_producto").on("keyup", function(){
        validarkeyup(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{3,20}$/,
            $(this),
            $("#snombre_producto"),
            "*El formato solo permite letras*"
        );
    });

    $("#modelo").on("change blur", function() {
        validarkeyup(
            /^.+$/,
            $(this),
            $("#smodelo"),
            "*Debe seleccionar un modulo y marca*"
        );
    });

    $("#imagen").on("change blur", function() {
        validarkeyup(
            /^.+$/,
            $(this),
            $("#simagen"),
            "*Debe seleccionar una imagen*"
        );
    });

    $("#descripcion_producto").on("keypress", function(e){
        validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
        let descripcion = document.getElementById("descripcion_producto");
        descripcion.value = space(descripcion.value);
    });

    $("#descripcion_producto").on("keyup", function(){
        validarkeyup(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,50}$/,
            $(this),
            $("#sdescripcion_producto"),
            "*El formato permite letras y números*"
        );
    });

    $("#Stock_Actual").on("keypress", function (e) {
        validarkeypress(/^[0-9]*$/, e); 
    });

    $("#Stock_Actual").on("keyup", function () {
        validarkeyup(
            /^[0-9]{1,3}$/,
            $(this),
            $("#sStock_Actual"),
            "*El formato solo permite números*"
        );
    });

    $("#Stock_Maximo").on("keypress", function (e) {
        validarkeypress(/^[0-9]*$/, e); 
    });

    $("#Stock_Maximo").on("keyup", function () {
        validarkeyup(
            /^[0-9]{1,3}$/,
            $(this),
            $("#sStock_Maximo"),
            "*El formato solo permite números*"
        );
    });

    $("#Stock_Minimo").on("keypress", function (e) {
        validarkeypress(/^[0-9]*$/, e); 
    });

    $("#Stock_Minimo").on("keyup", function () {
        validarkeyup(
            /^[0-9]{1,3}$/,
            $(this),
            $("#sStock_Minimo"),
            "*El formato solo permite números*"
        );
    });

    $("#Clausula_garantia").on("keypress", function(e){
        validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
        let descripcion = document.getElementById("Clausula_garantia");
        descripcion.value = space(descripcion.value);
    });

    $("#Clausula_garantia").on("keyup", function(){
        validarkeyup(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,50}$/,
            $(this),
            $("#sClausula_garantia"),
            "*El formato permite letras y números*"
        );
    });

    $("#Categoria").on("change blur", function() {
        validarkeyup(
            /^.+$/,
            $(this),
            $("#sCategoria"),
            "*Debe seleccionar una categoria*"
        );
    });

    $("#Seriales").on("keypress", function (e) {
        validarkeypress(/^[A-Z0-9\b]*$/, e); 
    });

    $("#Seriales").on("keyup", function () {
        validarkeyup(
            /^[A-Z0-9]{1,20}$/,
            $(this),
            $("#sSeriales"),
            "*El formato solo permite letras y números*"
        );
    });

    $("#Precio").on("keypress", function (e) {
        validarkeypress(/^[0-9]*$/, e); 
    });

    $("#Precio").on("keyup", function () {
        validarkeyup(
            /^[0-9]{1,10}$/,
            $(this),
            $("#sPrecio"),
            "*El formato solo permite números*"
        );
    });

    // Al abrir el modal de modificar, carga los datos del producto y sus características
$(document).on('click', '.btn-modificar', function () {
    // 1. Resetear el formulario primero
    $('#modificarProductoForm')[0].reset();
    
    // 2. Datos generales
    const $this = $(this);
    const dataset = $this.data();
    
    // Establecer los valores de los campos
    $('#modificarIdProducto').val(dataset.id);
    $('#modificarNombreProducto').val(dataset.nombre);
    $('#modificarDescripcionProducto').val(dataset.descripcion);
    $('#modificarMarca').val(dataset.marca);
    $('#modificarStockActual').val(dataset.stockactual);
    $('#modificarStockMaximo').val(dataset.stockmaximo);
    $('#modificarStockMinimo').val(dataset.stockminimo);
    $('#modificarClausulaGarantia').val(dataset.clausula);
    $('#modificarSeriales').val(dataset.seriales);
    $('#modificarPrecio').val(dataset.precio);
    
    // Establecer el valor del modelo usando el ID del modelo
    setTimeout(() => {
        const $selectModelo = $('#modificarModelo');
        const modeloId = dataset.modeloid; // Usamos el ID del modelo del data-attribute
        
        if (modeloId) {
            // Buscar la opción que coincida con el ID del modelo
            $selectModelo.val(modeloId).trigger('change');
        }
    }, 100);

    // 3. Categoría y tabla dinámica
    const tablaCategoria = dataset.tabla_categoria || dataset.categoria;
    $('#modificarCategoria').val(tablaCategoria).trigger('change');
    $('#modificar_tabla_categoria').val(tablaCategoria);

    // 4. Imagen
    const preview = document.getElementById('modificarImagenPreview');
    if (dataset.imagen) {
        preview.src = dataset.imagen;
        preview.style.display = 'block';
    } else {
        preview.src = '#';
        preview.style.display = 'none';
    }

    // 5. Cargar características dinámicas
    const categoriaObj = categoriasDinamicas.find(cat => cat.tabla === tablaCategoria);
    if (categoriaObj) {
        // Esperar un momento para que se generen los campos dinámicos
        setTimeout(() => {
            categoriaObj.caracteristicas.forEach(carac => {
                // Procesar el nombre para que coincida con el dataset (camelCase)
                const camelName = carac.nombre.replace(/_([a-z])/g, g => g[1].toUpperCase());
                const valor = dataset[camelName];
                const $campo = $(`#modificar_${carac.nombre}`);
                
                if ($campo.length && valor !== undefined) {
                    // Manejar campos de voltaje específicamente
                    if (carac.nombre.toLowerCase().includes('voltaje')) {
                        // Asegurarse de que el valor sea numérico y tenga el formato correcto
                        const valorNumerico = parseFloat(valor);
                        if (!isNaN(valorNumerico)) {
                            $campo.val(valorNumerico);
                        }
                    } else {
                        $campo.val(valor);
                    }
                }
            });
        }, 200);
    }
    
    // Mostrar el modal
    $('#modificarProductoModal').modal('show');
});

function verificarPermisosEnTiempoRealProductos() {
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
            $('#btnIncluirProducto').show();
        } else {
            $('#btnIncluirProducto').hide();
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
    verificarPermisosEnTiempoRealProductos();
    setInterval(verificarPermisosEnTiempoRealProductos, 10000); // 10 segundos
});
    
    

    
    $('#modificarProductoForm').on('submit', function(e) {
        e.preventDefault();
        
        // Mostrar indicador de carga
        Swal.fire({
            title: 'Procesando...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Actualizar el valor de la tabla de categoría
        $('#modificar_tabla_categoria').val($('#modificarCategoria').val());
        
        // Validar características numéricas
        let caracteristicasInvalidas = [];
        $('#caracteristicasCategoria input[type="number"]').each(function() {
            const valor = parseFloat($(this).val());
            if (isNaN(valor) || valor < 0) {
                caracteristicasInvalidas.push($(this).attr('name'));
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Validar características de texto
        let caracteristicasTextoInvalidas = [];
        $('#caracteristicasCategoriaModificar input[type="text"]').each(function() {
            const valor = $(this).val().trim();
            if (valor !== '' && !regexTexto.test(valor)) {
                caracteristicasTextoInvalidas.push($(this).attr('name'));
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Mostrar errores de validación si los hay
        if (caracteristicasTextoInvalidas.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error en características',
                text: 'Las características de texto solo pueden contener letras, números, espacios, @, punto y guion.'
            });
            return;
        }

        if (caracteristicasInvalidas.length > 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error en características',
                text: 'Las características numéricas deben ser valores positivos.'
            });
            return;
        }

        // Preparar datos del formulario
        var formData = new FormData(this);
        formData.append('accion', 'modificar');

        // Enviar petición AJAX
        $.ajax({
            url: '', 
            type: 'POST',
            processData: false,
            contentType: false,
            cache: false,
            data: formData,
            success: function(response) {
                Swal.close();
                console.log('Respuesta del servidor:', response);
                
                // Parsear respuesta
                let res;
                try {
                    res = typeof response === 'string' ? JSON.parse(response) : response;
                } catch (e) {
                    // Intentar extraer JSON de respuesta mal formada
                    try {
                        const txt = String(response).trim();
                        const first = txt.indexOf('{');
                        const last = txt.lastIndexOf('}');
                        if (first !== -1 && last !== -1 && last > first) {
                            res = JSON.parse(txt.substring(first, last + 1));
                        } else {
                            throw new Error('Respuesta no válida del servidor');
                        }
                    } catch (parseError) {
                        console.error('Error al parsear respuesta:', parseError);
                        throw new Error('Error al procesar la respuesta del servidor');
                    }
                }

                if (res && res.status === 'success') {
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: res.message || res.mensaje || 'El producto se ha modificado correctamente',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Cerrar modal
                        $('#modificarProductoModal').modal('hide');
                        
                        // Actualizar la fila de la tabla si hay datos del producto
                        if (res.producto) {
                            actualizarFilaEnTabla(res.producto);
                        } else {
                            // Recargar como último recurso si no hay datos del producto
                            location.reload();
                        }
                    });
                } else {
                    // Mostrar mensaje de error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: (res && (res.message || res.mensaje)) || 'Error al modificar el producto',
                        confirmButtonText: 'Aceptar'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('Error al modificar el producto:', status, error, xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor. Por favor, intente nuevamente.',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    });

$(document).on('click', '.eliminar', function (e) {
    e.preventDefault();
    var id_producto = $(this).data('id');
    
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
            datos.append('id_producto', id_producto);
            
            $.ajax({
                url: '',
                type: 'POST',
                data: datos,
                contentType: false,
                processData: false,
                success: function(response) {
                    try {
                        // Si ya es objeto, úsalo. Si es string, intenta parsear.
                        var res = response;
                        if (typeof response === 'string') {
                            var txt = response.trim();
                            try {
                                res = JSON.parse(txt);
                            } catch (err) {
                                // Intentar extraer JSON si hay HTML/texto envolvente
                                var first = txt.indexOf('{');
                                var last = txt.lastIndexOf('}');
                                if (first !== -1 && last !== -1 && last > first) {
                                    var sub = txt.substring(first, last + 1);
                                    res = JSON.parse(sub);
                                } else {
                                    throw err;
                                }
                            }
                        }

                        console.log('Respuesta eliminar (parsed):', res);

                        if (res && res.status === 'success') {
                            Swal.fire(
                                'Eliminado!',
                                res.message || 'El producto ha sido eliminado.',
                                'success'
                            ).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                (res && (res.message || res.msg)) || 'Error al eliminar el producto',
                                'error'
                            );
                        }
                    } catch (e) {
                        console.error('Error procesando respuesta eliminar:', e, response);
                        Swal.fire(
                            'Error!',
                            'Error al procesar la respuesta del servidor. Revisa la consola para más detalles.',
                            'error'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX eliminar error:', status, error, xhr.responseText);
                    Swal.fire(
                        'Error!',
                        'Error en la solicitud AJAX: ' + (error || status),
                        'error'
                    );
                }
            });
        }
    });
});

/**
 * Actualiza una fila en la tabla de productos con los nuevos datos
 * @param {Object} producto - Objeto con los datos actualizados del producto
 */
function actualizarFilaEnTabla(producto) {
    const fila = $(`tr[data-id="${producto.id_producto}"]`);
    if (!fila.length) {
        console.warn('No se encontró la fila del producto a actualizar');
        location.reload(); // Recargar como último recurso
        return;
    }

    try {
        // Actualizar datos visibles en la fila
        fila.find('.campo-nombres').first().text(producto.nombre_producto || '');
        fila.find('td:eq(3) span').text(producto.nombre_modelo || '');
        fila.find('td:eq(4) span').text(producto.nombre_marca || '');
        fila.find('td:eq(5) span').text(producto.stock_actual || '0');
        fila.find('td:eq(6) span').text(producto.seriales || '');
        
        // Formatear y actualizar precio
        const precioFormateado = parseFloat(producto.precio || 0).toFixed(2);
        fila.find('.precio').text(precioFormateado);
        
        // Actualizar botón de edición
        const botonEditar = fila.find('.btn-modificar');
        if (botonEditar.length) {
            // Actualizar atributos data-* para futuras ediciones
            const datosActualizar = {
                'data-nombre': producto.nombre_producto || '',
                'data-descripcion': producto.descripcion_producto || '',
                'data-modelo': producto.nombre_modelo || '',
                'data-marca': producto.nombre_marca || '',
                'data-stockactual': producto.stock_actual || '0',
                'data-stockmaximo': producto.stock_maximo || '0',
                'data-stockminimo': producto.stock_minimo || '0',
                'data-seriales': producto.seriales || '',
                'data-clausula': producto.clausula_garantia || '',
                'data-precio': precioFormateado,
                'data-categoria': producto.categoria_id || '',
                'data-tabla_categoria': producto.tabla_categoria || ''
            };

            // Aplicar los cambios a los atributos data-*
            Object.entries(datosActualizar).forEach(([key, value]) => {
                botonEditar.attr(key, value);
            });

            // Actualizar características dinámicas si existen
            if (producto.caracteristicas) {
                Object.entries(producto.caracteristicas).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        const dataKey = `data-${key.toLowerCase().replace(/_/g, '')}`;
                        botonEditar.attr(dataKey, value);
                    }
                });
            }
        }

        // Actualizar botón de detalles si existe
        const botonDetalle = fila.find('.btn-detalle');
        if (botonDetalle.length) {
            botonDetalle.attr({
                'data-nombredtl': producto.nombre_producto || '',
                'data-modelodtl': producto.nombre_modelo || '',
                'data-marcadtl': producto.nombre_marca || '',
                'data-descripciondtl': producto.descripcion_producto || '',
                'data-stockactualdtl': producto.stock_actual || '0',
                'data-stockmaximodtl': producto.stock_maximo || '0',
                'data-stockminimodtl': producto.stock_minimo || '0',
                'data-serialdtl': producto.seriales || '',
                'data-clausuladtl': producto.clausula_garantia || '',
                'data-categoriadtl': producto.nombre_categoria || '',
                'data-preciodtl': precioFormateado
            });
        }

        // Mostrar notificación de éxito
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Producto actualizado',
            showConfirmButton: false,
            timer: 1500
        });

    } catch (error) {
        console.error('Error al actualizar la fila del producto:', error);
        // Si hay un error, recargar la página para asegurar consistencia
        location.reload();
    }
}
// ...existing code...

    $('#btnIncluirProducto').on('click', function() {
        $('#incluirProductoForm')[0].reset();
        $('#registrarProductoModal').modal('show');
    });

    function soloTextoPermitido(e) {
    // Permite: letras, números, espacio, @, . y -
    const regex = /^[a-zA-Z0-9@\.\-\s]+$/;
    let valor = e.target.value;
    // Si el valor no cumple, elimina el último caracter ingresado
    if (!regex.test(valor)) {
        e.target.value = valor.replace(/[^a-zA-Z0-9@\.\-\s]/g, '');
    }
}
    $(document).on('click', '#registrarProductoModal .close', function() {
        $('#registrarProductoModal').modal('hide');
    });

$('#incluirProductoForm').on('submit', function(event) {
    event.preventDefault();
 
 

    // Validaciones antes del envío
    let errores = [];

    const nombre = $('#nombre_producto').val().trim();
    const descripcion = $('#descripcion_producto').val().trim();
    const modelo = $('#modelo').val();
    const stockActual = parseInt($('#Stock_Actual').val());
    const stockMinimo = parseInt($('#Stock_Minimo').val());
    const stockMaximo = parseInt($('#Stock_Maximo').val());
    const categoria = $('#Categoria').val();
    const seriales = $('#Seriales').val().trim();
    const precioInput = $('#Precio').val().trim().replace(',', '.');
    const precio = Number(precioInput);
    const precioRegex = /^\d+(\.\d{0,2})?$/;

    // Validación de texto usando regexTexto global
    let caracteristicasInvalidas = [];
    $('#caracteristicasCategoria input[type="number"]').each(function() {
        if (parseFloat($(this).val()) < 0) {
            caracteristicasInvalidas.push($(this).attr('name'));
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    if (caracteristicasInvalidas.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error en características',
            text: 'Las características numéricas no pueden tener valores negativos.'
        });
        return;
    }

    if (!regexTexto.test(nombre)) {
        errores.push("El nombre del producto solo puede contener letras, números y espacios.");
    }
    if (descripcion && !regexTexto.test(descripcion)) {
        errores.push("La descripción solo puede contener letras, números y espacios.");
    }
    if (nombre.length < 3) {
        errores.push("El nombre del producto debe tener al menos 3 caracteres.");
    }

    if (!modelo) {
        errores.push("Debe seleccionar un modelo.");
    }

    if (isNaN(stockActual) || stockActual <= 0) {
        errores.push("El Stock Actual debe ser mayor a 0.");
    }

    if (isNaN(stockMinimo) || stockMinimo <= 0) {
        errores.push("El Stock Mínimo debe ser mayor a 0.");
    }

    if (isNaN(stockMaximo) || stockMaximo <= 0) {
        errores.push("El Stock Máximo debe ser mayor a 0.");
    }

    if (!isNaN(stockMinimo) && !isNaN(stockMaximo) && stockMinimo >= stockMaximo) {
        errores.push("El Stock Mínimo debe ser menor al Stock Máximo.");
    }

    if (isNaN(stockActual) || stockActual < 0) {
    errores.push("El Stock Actual debe ser mayor o igual a 0.");
}

    if (!categoria) {
        errores.push("Debe seleccionar una categoría.");
    }

    if (seriales.length === 0) {
        errores.push("Debe ingresar el código serial.");
    }
    let caracteristicasTextoInvalidas = [];
$('#caracteristicasCategoria input[type="text"]').each(function() {
    if (!regexTexto.test($(this).val())) {
        caracteristicasTextoInvalidas.push($(this).attr('name'));
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
});
if (caracteristicasTextoInvalidas.length > 0) {
    Swal.fire({
        icon: 'error',
        title: 'Error en características',
        text: 'Las características de texto solo pueden contener letras, números, espacios, @, punto y guion.'
    });
    return;
}

    if (!precioRegex.test(precioInput)) {
    errores.push("El precio debe ser un número válido con hasta 2 decimales.");
} else if (precio <= 0) {
    errores.push("El precio debe ser mayor a 0.");
}

$('#Precio').val($('#Precio').val().replace(',', '.'));

    // VALIDACIONES ADICIONALES POR CATEGORÍA
 

    if (errores.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Errores en el formulario',
            html: errores.join("<br>"),
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    // Si pasa validación, continuar con el envío AJAX
    const formData = new FormData(this);
    let datos = {};
    for (let [key, value] of formData.entries()) {
        datos[key] = value;
    }

    $.ajax({
        url: '',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        cache: false,
        success: function(response) {
            try {
                var data = (typeof response === 'string') ? JSON.parse(response) : response;
                if (data && data.status === 'success') {
                    Swal.fire({
                        title: 'Éxito',
                        text: 'Producto ingresado exitosamente',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    console.error('Error al registrar producto:', data && data.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: (data && data.message) || 'No se pudo registrar el producto'
                    });
                }
            } catch (e) {
                console.error('Error al parsear respuesta:', e, response);
                // Fallback: intentar extraer JSON embebido en texto
                try {
                    var txt = String(response).trim();
                    var first = txt.indexOf('{');
                    var last = txt.lastIndexOf('}');
                    if (first !== -1 && last !== -1 && last > first) {
                        var sub = txt.substring(first, last + 1);
                        var data2 = JSON.parse(sub);
                        if (data2 && data2.status === 'success') {
                            Swal.fire({
                                title: 'Éxito',
                                text: 'Producto ingresado exitosamente',
                                icon: 'success',
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                location.reload();
                            });
                            return;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: (data2 && data2.message) || 'No se pudo registrar el producto'
                        });
                        return;
                    }
                } catch (ignore) {}
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Respuesta del servidor no válida'
                });
            }
        },
error: function(xhr, status, error) {
    console.error("Error AJAX:", status, error, xhr.responseText);
}
    });
});

/**
 * Actualiza una fila en la tabla de productos con los nuevos datos
 * @param {Object} producto - Objeto con los datos actualizados del producto
 */
function actualizarFilaEnTabla(producto) {
    const fila = $(`tr[data-id="${producto.id_producto}"]`);
    if (!fila.length) {
        console.warn('No se encontró la fila del producto a actualizar');
        location.reload(); // Recargar como último recurso
        return;
    }

    try {
        // Actualizar datos visibles en la fila
        fila.find('.campo-nombres').first().text(producto.nombre_producto || '');
        fila.find('td:eq(3) span').text(producto.nombre_modelo || '');
        fila.find('td:eq(4) span').text(producto.nombre_marca || '');
        fila.find('td:eq(5) span').text(producto.stock_actual || '0');
        fila.find('td:eq(6) span').text(producto.seriales || '');
        
        // Formatear y actualizar precio
        const precioFormateado = parseFloat(producto.precio || 0).toFixed(2);
        fila.find('.precio').text(precioFormateado);
        
        // Actualizar botón de edición
        const botonEditar = fila.find('.btn-modificar');
        if (botonEditar.length) {
            // Actualizar atributos data-* para futuras ediciones
            const datosActualizar = {
                'data-nombre': producto.nombre_producto || '',
                'data-descripcion': producto.descripcion_producto || '',
                'data-modelo': producto.nombre_modelo || '',
                'data-marca': producto.nombre_marca || '',
                'data-stockactual': producto.stock_actual || '0',
                'data-stockmaximo': producto.stock_maximo || '0',
                'data-stockminimo': producto.stock_minimo || '0',
                'data-seriales': producto.seriales || '',
                'data-clausula': producto.clausula_garantia || '',
                'data-precio': precioFormateado,
                'data-categoria': producto.categoria_id || '',
                'data-tabla_categoria': producto.tabla_categoria || ''
            };

            // Aplicar los cambios a los atributos data-*
            Object.entries(datosActualizar).forEach(([key, value]) => {
                botonEditar.attr(key, value);
            });

            // Actualizar características dinámicas si existen
            if (producto.caracteristicas) {
                Object.entries(producto.caracteristicas).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        const dataKey = `data-${key.toLowerCase().replace(/_/g, '')}`;
                        botonEditar.attr(dataKey, value);
                    }
                });
            }
        }

        // Actualizar botón de detalles si existe
        const botonDetalle = fila.find('.btn-detalle');
        if (botonDetalle.length) {
            botonDetalle.attr({
                'data-nombredtl': producto.nombre_producto || '',
                'data-modelodtl': producto.nombre_modelo || '',
                'data-marcadtl': producto.nombre_marca || '',
                'data-descripciondtl': producto.descripcion_producto || '',
                'data-stockactualdtl': producto.stock_actual || '0',
                'data-stockmaximodtl': producto.stock_maximo || '0',
                'data-stockminimodtl': producto.stock_minimo || '0',
                'data-serialdtl': producto.seriales || '',
                'data-clausuladtl': producto.clausula_garantia || '',
                'data-categoriadtl': producto.nombre_categoria || '',
                'data-preciodtl': precioFormateado
            });
        }

        // Mostrar notificación de éxito
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Producto actualizado',
            showConfirmButton: false,
            timer: 1500
        });

    } catch (error) {
        console.error('Error al actualizar la fila del producto:', error);
        // Si hay un error, recargar la página para asegurar consistencia
        location.reload();
    }
}

document.getElementById('imagen').addEventListener('change', function (event) {
  const input = event.target;
  const preview = document.getElementById('imagenPreview');

  if (input.files && input.files[0]) {
    const reader = new FileReader();

    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };

    reader.readAsDataURL(input.files[0]);
  } else {
    preview.src = '#';
    preview.style.display = 'none';
  }
});

document.querySelectorAll('.btn-modificar').forEach(btn => {
  btn.addEventListener('click', function () {
    // ...otros campos...
    const imagen = this.dataset.imagen; // ya es la ruta completa
const preview = document.getElementById('modificarImagenPreview');
preview.src = imagen;
preview.style.display = 'block';

    // Limpiar input file
    document.getElementById('modificarImagen').value = '';
    // Limpiar preview si cambia la imagen
    document.getElementById('modificarImagen').onchange = function (event) {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          preview.src = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
      } else {
        preview.src = rutaImagen;
      }
    };
    // ...resto del código...
    $('#modificarProductoModal').modal('show');
  });
});

$('#Precio').on('input', function() {
    let precioInput = $(this).val().trim().replace(',', '.');
    const precioRegex = /^\d+(\.\d{0,2})?$/;
    if (!precioRegex.test(precioInput) && precioInput !== "") {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
});
    // Cerrar modal de modificación
    $(document).on('click', '#modificarProductoModal .close', function() {
        $('#modificarProductoModal').modal('hide');
    });
    
    // Delegación para el despliegue de opciones (modificar/eliminar)
    $('#tablaConsultas').on('click', '.vertical', function(e) {
        e.stopPropagation(); // Prevenir cierre inmediato

        // Cerrar todos los menús primero
        $('.desplegable').not($(this).next('.desplegable')).hide();

        // Alternar el menú actual
        const menuActual = $(this).next('.desplegable');
        menuActual.toggle();
    });

    // Cerrar el menú si se hace clic fuera
    $(document).on('click', function() {
        $('.desplegable').hide();
    });
});

/**
 * Actualiza una fila en la tabla de productos con los nuevos datos
 * @param {Object} producto - Objeto con los datos actualizados del producto
 */
function actualizarFilaEnTabla(producto) {
    const fila = $(`tr[data-id="${producto.id_producto}"]`);
    if (!fila.length) {
        console.warn('No se encontró la fila del producto a actualizar');
        location.reload(); // Recargar como último recurso
        return;
    }

    try {
        // Actualizar datos visibles en la fila
        fila.find('.campo-nombres').first().text(producto.nombre_producto || '');
        fila.find('td:eq(3) span').text(producto.nombre_modelo || '');
        fila.find('td:eq(4) span').text(producto.nombre_marca || '');
        fila.find('td:eq(5) span').text(producto.stock_actual || '0');
        fila.find('td:eq(6) span').text(producto.seriales || '');
        
        // Formatear y actualizar precio
        const precioFormateado = parseFloat(producto.precio || 0).toFixed(2);
        fila.find('.precio').text(precioFormateado);
        
        // Actualizar botón de edición
        const botonEditar = fila.find('.btn-modificar');
        if (botonEditar.length) {
            // Actualizar atributos data-* para futuras ediciones
            const datosActualizar = {
                'data-nombre': producto.nombre_producto || '',
                'data-descripcion': producto.descripcion_producto || '',
                'data-modelo': producto.nombre_modelo || '',
                'data-marca': producto.nombre_marca || '',
                'data-stockactual': producto.stock_actual || '0',
                'data-stockmaximo': producto.stock_maximo || '0',
                'data-stockminimo': producto.stock_minimo || '0',
                'data-seriales': producto.seriales || '',
                'data-clausula': producto.clausula_garantia || '',
                'data-precio': precioFormateado,
                'data-categoria': producto.categoria_id || '',
                'data-tabla_categoria': producto.tabla_categoria || ''
            };

            // Aplicar los cambios a los atributos data-*
            Object.entries(datosActualizar).forEach(([key, value]) => {
                botonEditar.attr(key, value);
            });

            // Actualizar características dinámicas si existen
            if (producto.caracteristicas) {
                Object.entries(producto.caracteristicas).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        const dataKey = `data-${key.toLowerCase().replace(/_/g, '')}`;
                        botonEditar.attr(dataKey, value);
                    }
                });
            }
        }

        // Actualizar botón de detalles si existe
        const botonDetalle = fila.find('.btn-detalle');
        if (botonDetalle.length) {
            botonDetalle.attr({
                'data-nombredtl': producto.nombre_producto || '',
                'data-modelodtl': producto.nombre_modelo || '',
                'data-marcadtl': producto.nombre_marca || '',
                'data-descripciondtl': producto.descripcion_producto || '',
                'data-stockactualdtl': producto.stock_actual || '0',
                'data-stockmaximodtl': producto.stock_maximo || '0',
                'data-stockminimodtl': producto.stock_minimo || '0',
                'data-serialdtl': producto.seriales || '',
                'data-clausuladtl': producto.clausula_garantia || '',
                'data-categoriadtl': producto.nombre_categoria || '',
                'data-preciodtl': precioFormateado
            });
        }

        // Mostrar notificación de éxito
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Producto actualizado',
            showConfirmButton: false,
            timer: 1500
        });

    } catch (error) {
        console.error('Error al actualizar la fila del producto:', error);
        // Si hay un error, recargar la página para asegurar consistencia
        location.reload();
    }
}


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
function cambiarEstatus(idUsuario) {
    const span = $(`span[onclick*="cambiarEstatus(${idUsuario}"]`);
    const estatusActual = span.text().trim().toLowerCase();
    const nuevoEstatus = estatusActual === 'habilitado' ? 'inhabilitado' : 'habilitado';
    
    // Feedback visual inmediato
    span.addClass('cambiando');
    
    $.ajax({
        url: '',
        type: 'POST',
        dataType: 'json',
        data: {
            accion: 'cambiar_estatus',
            id_producto: idUsuario,
            nuevo_estatus: nuevoEstatus
        },
        success: function(data) {
            span.removeClass('cambiando');
            
            if (data.status === 'success') {
                span.text(nuevoEstatus);
                span.removeClass('habilitado inhabilitado').addClass(nuevoEstatus);
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Estatus actualizado!',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                // Revertir visualmente
                span.text(estatusActual);
                span.removeClass('habilitado inhabilitado').addClass(estatusActual);
                Swal.fire('Error', data.message || 'Error al cambiar el estatus', 'error');
            }
        },
        error: function(xhr, status, error) {
            span.removeClass('cambiando');
            // Revertir visualmente
            span.text(estatusActual);
            span.removeClass('habilitado inhabilitado').addClass(estatusActual);
            Swal.fire('Error', 'Error en la conexión', 'error');
        }
    });
}
function muestraMensaje(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: mensaje
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalDetallesProducto');
    const cerrar = document.getElementById('cerrarModalDetalles');

    document.querySelectorAll('.btn-detalle').forEach(btn => {
        btn.addEventListener('click', function () {
            const imagen = this.dataset.imagendtl;
            const contenedorImagen = document.getElementById('detalle-imagen');
            contenedorImagen.innerHTML = imagen 
                ? `<img src="${imagen}" alt="Imagen del Producto" style="width: 150px; height: 150px;">`
                : 'Sin imagen';
            document.getElementById('detalle-id').textContent = this.dataset.iddtl;
            document.getElementById('detalle-nombre').textContent = this.dataset.nombredtl;
            document.getElementById('detalle-modelo').textContent = this.dataset.modelodtl;
            document.getElementById('detalle-marca').textContent = this.dataset.marcadtl;
            document.getElementById('detalle-descripcion').textContent = this.dataset.descripciondtl;
            document.getElementById('detalle-stockactual').textContent = this.dataset.stockactualdtl;
            document.getElementById('detalle-stockmaximo').textContent = this.dataset.stockmaximodtl;
            document.getElementById('detalle-stockminimo').textContent = this.dataset.stockminimodtl;
            document.getElementById('detalle-serial').textContent = this.dataset.serialdtl;
            document.getElementById('detalle-clausula').textContent = this.dataset.clausuladtl;
            document.getElementById('detalle-categoria').textContent = this.dataset.categoriadtl;
            document.getElementById('detalle-precio').textContent = this.dataset.preciodtl;
            document.getElementById('detalle-estatus').textContent = this.dataset.estatusdtl;

            // Mostrar modal con clase animada
            modal.classList.add('mostrar');
        });
    });

    // Cierre al hacer clic en el botón "X"
    cerrar.addEventListener('click', () => {
        modal.classList.remove('mostrar');
    });

    // Cierre al hacer clic fuera del contenido del modal
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('mostrar');
        }
    });
});

/**
 * Actualiza una fila en la tabla de productos con los nuevos datos
 * @param {Object} producto - Objeto con los datos actualizados del producto
 */
function actualizarFilaEnTabla(producto) {
    const fila = $(`tr[data-id="${producto.id_producto}"]`);
    if (!fila.length) {
        console.warn('No se encontró la fila del producto a actualizar');
        location.reload(); // Recargar como último recurso
        return;
    }

    try {
        // Actualizar datos visibles en la fila
        fila.find('.campo-nombres').first().text(producto.nombre_producto || '');
        fila.find('td:eq(3) span').text(producto.nombre_modelo || '');
        fila.find('td:eq(4) span').text(producto.nombre_marca || '');
        fila.find('td:eq(5) span').text(producto.stock_actual || '0');
        fila.find('td:eq(6) span').text(producto.seriales || '');
        
        // Formatear y actualizar precio
        const precioFormateado = parseFloat(producto.precio || 0).toFixed(2);
        fila.find('.precio').text(precioFormateado);
        
        // Actualizar botón de edición
        const botonEditar = fila.find('.btn-modificar');
        if (botonEditar.length) {
            // Actualizar atributos data-* para futuras ediciones
            const datosActualizar = {
                'data-nombre': producto.nombre_producto || '',
                'data-descripcion': producto.descripcion_producto || '',
                'data-modelo': producto.nombre_modelo || '',
                'data-marca': producto.nombre_marca || '',
                'data-stockactual': producto.stock_actual || '0',
                'data-stockmaximo': producto.stock_maximo || '0',
                'data-stockminimo': producto.stock_minimo || '0',
                'data-seriales': producto.seriales || '',
                'data-clausula': producto.clausula_garantia || '',
                'data-precio': precioFormateado,
                'data-categoria': producto.categoria_id || '',
                'data-tabla_categoria': producto.tabla_categoria || ''
            };

            // Aplicar los cambios a los atributos data-*
            Object.entries(datosActualizar).forEach(([key, value]) => {
                botonEditar.attr(key, value);
            });

            // Actualizar características dinámicas si existen
            if (producto.caracteristicas) {
                Object.entries(producto.caracteristicas).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        const dataKey = `data-${key.toLowerCase().replace(/_/g, '')}`;
                        botonEditar.attr(dataKey, value);
                    }
                });
            }
        }

        // Actualizar botón de detalles si existe
        const botonDetalle = fila.find('.btn-detalle');
        if (botonDetalle.length) {
            botonDetalle.attr({
                'data-nombredtl': producto.nombre_producto || '',
                'data-modelodtl': producto.nombre_modelo || '',
                'data-marcadtl': producto.nombre_marca || '',
                'data-descripciondtl': producto.descripcion_producto || '',
                'data-stockactualdtl': producto.stock_actual || '0',
                'data-stockmaximodtl': producto.stock_maximo || '0',
                'data-stockminimodtl': producto.stock_minimo || '0',
                'data-serialdtl': producto.seriales || '',
                'data-clausuladtl': producto.clausula_garantia || '',
                'data-categoriadtl': producto.nombre_categoria || '',
                'data-preciodtl': precioFormateado
            });
        }

        // Mostrar notificación de éxito
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Producto actualizado',
            showConfirmButton: false,
            timer: 1500
        });

    } catch (error) {
        console.error('Error al actualizar la fila del producto:', error);
        // Si hay un error, recargar la página para asegurar consistencia
        location.reload();
    }
}