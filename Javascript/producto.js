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
    // 1. Datos generales
    $('#modificarIdProducto').val(this.dataset.id);
    $('#modificarNombreProducto').val(this.dataset.nombre);
    $('#modificarDescripcionProducto').val(this.dataset.descripcion);
    $('#modificarModelo').val(this.dataset.modelo);
    $('#modificarMarca').val(this.dataset.marca);
    $('#modificarStockActual').val(this.dataset.stockactual);
    $('#modificarStockMaximo').val(this.dataset.stockmaximo);
    $('#modificarStockMinimo').val(this.dataset.stockminimo);
    $('#modificarClausulaGarantia').val(this.dataset.clausula);
    $('#modificarSeriales').val(this.dataset.seriales);
    $('#modificarPrecio').val(this.dataset.precio);

    // 2. Categoría y tabla dinámica
    const tablaCategoria = this.dataset.tabla_categoria || this.dataset.categoria;
    $('#modificarCategoria').val(tablaCategoria).trigger('change');
    $('#modificar_tabla_categoria').val(tablaCategoria);

    // 3. Imagen
     const imagen = this.dataset.imagen;
    const preview = document.getElementById('modificarImagenPreview');
    preview.src = imagen;
    preview.style.display = 'block';

    // Espera a que los campos dinámicos se generen y luego coloca los valores
    setTimeout(() => {
        const categoriaObj = categoriasDinamicas.find(cat => cat.tabla === tablaCategoria);
        if (categoriaObj) {
            categoriaObj.caracteristicas.forEach(carac => {
                // camelCase para dataset
                const camelName = carac.nombre.replace(/_([a-z])/g, g => g[1].toUpperCase());
                const valor = this.dataset[camelName];
                if (valor !== undefined) {
                    $(`#modificar_${carac.nombre}`).val(valor);
                }
            });
        }
    }, 200);
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
        $('#modificar_tabla_categoria').val($('#modificarCategoria').val());
           let caracteristicasInvalidas = [];
    $('#caracteristicasCategoria input[type="number"]').each(function() {
        if (parseFloat($(this).val()) < 0) {
            caracteristicasInvalidas.push($(this).attr('name'));
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    let caracteristicasTextoInvalidas = [];
$('#caracteristicasCategoriaModificar input[type="text"]').each(function() {
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
    if (caracteristicasInvalidas.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error en características',
            text: 'Las características numéricas no pueden tener valores negativos.'
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
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                var res = response;
                if (typeof response === 'string') {
                    try {
                        res = JSON.parse(response);
                    } catch (e) {
                        try {
                            var txt = response.trim();
                            var first = txt.indexOf('{');
                            var last = txt.lastIndexOf('}');
                            if (first !== -1 && last !== -1 && last > first) {
                                res = JSON.parse(txt.substring(first, last + 1));
                            }
                        } catch (ignore) {}
                    }
                }
                if (res && res.status === 'success') {
                    $('#modificarProductoModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Modificado',
                        text: (res.message || res.mensaje || 'El producto se ha modificado correctamente')
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    muestraMensaje((res && (res.message || res.mensaje)) || 'Error al modificar el producto');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error al modificar el producto:', textStatus, errorThrown);
                muestraMensaje('Error al modificar el producto.');
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