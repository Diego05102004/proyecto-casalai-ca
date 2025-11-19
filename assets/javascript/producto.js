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

// Helper functions for validation
function validarkeypress(er, e) {
    const key = e.key;
    const keyCode = e.keyCode || e.which;
    const tecla = String.fromCharCode(keyCode);
    const specials = [8, 37, 39, 46]; // Backspace, left arrow, right arrow, delete
    
    if (specials.includes(keyCode)) return true;
    
    const regex = new RegExp(er);
    if (!regex.test(tecla)) {
        e.preventDefault();
        return false;
    }
    return true;
}

function space(str) {
    return str.replace(/\s+/g, ' ').trim();
}

function soloTextoPermitido(e) {
    // Si es el campo de cláusula de garantía, permitir espacios sin restricciones
    if (e.target.id === 'Clausula_garantia') {
        return true; // No aplicar restricciones de caracteres
    }
    
    // Para otros campos: permite letras, números, espacio, @, . y -
    const regex = /^[a-zA-Z0-9@\.\-\s]+$/;
    let valor = e.target.value;
    // Si el valor no cumple, elimina el último caracter ingresado
    if (!regex.test(valor)) {
        e.target.value = valor.replace(/[^a-zA-Z0-9@\.\-\s]/g, '');
    }
}

// Agregar después de la función soloTextoPermitido

const regexNombreProducto = /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{3,20}$/;
const regexDescripcionProducto = /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,\-\s\b]{2,50}$/;
const regexSerialesProducto = /^[a-zA-Z0-9-]{1,20}$/;
const regexPrecioProducto = /^\d+(\.\d{1,2})?$/;
const regexCaracteristicasTexto = /^[a-zA-Z0-9@\.\-\sÁÉÍÓÚáéíóúñÑ]+$/;

const productoFormConfigs = {
    registrar: {
        tipo: 'registrar',
        formSelector: '#incluirProductoForm',
        campos: {
            nombre: { selector: '#nombre_producto', span: '#snombre_producto', label: 'nombre del producto' },
            descripcion: { selector: '#descripcion_producto', span: '#sdescripcion_producto', label: 'descripción del producto' },
            modelo: { selector: '#modelo', span: '#smodelo', label: 'modelo' },
            imagen: { selector: '#imagen', span: '#simagen', label: 'imagen del producto' },
            stockActual: { selector: '#Stock_Actual', span: '#sStock_Actual', label: 'stock actual' },
            stockMaximo: { selector: '#Stock_Maximo', span: '#sStock_Maximo', label: 'stock máximo' },
            stockMinimo: { selector: '#Stock_Minimo', span: '#sStock_Minimo', label: 'stock mínimo' },
            clausula: { selector: '#Clausula_garantia', span: '#sClausula_garantia', label: 'cláusula de garantía' },
            categoria: { selector: '#Categoria', span: '#sCategoria', label: 'categoría' },
            seriales: { selector: '#Seriales', span: '#sSeriales', label: 'seriales' },
            precio: { selector: '#Precio', span: '#sPrecio', label: 'precio' }
        },
        caracteristicasContenedor: '#caracteristicasCategoria',
        tablaCategoriaHidden: '#tabla_categoria'
    },
    modificar: {
        tipo: 'modificar',
        formSelector: '#modificarProductoForm',
        campos: {
            nombre: { selector: '#modificarNombreProducto', span: '#smodificarNombreProducto', label: 'nombre del producto' },
            descripcion: { selector: '#modificarDescripcionProducto', span: '#smodificarDescripcionProducto', label: 'descripción del producto' },
            modelo: { selector: '#modificarModelo', span: '#smodificarModelo', label: 'modelo' },
            imagen: { selector: '#modificarImagen', span: '#smodificarImagen', label: 'imagen del producto' },
            stockActual: { selector: '#modificarStockActual', span: '#smodificarStockActual', label: 'stock actual' },
            stockMaximo: { selector: '#modificarStockMaximo', span: '#smodificarStockMaximo', label: 'stock máximo' },
            stockMinimo: { selector: '#modificarStockMinimo', span: '#smodificarStockMinimo', label: 'stock mínimo' },
            clausula: { selector: '#modificarClausulaGarantia', span: '#smodificarClausulaGarantia', label: 'cláusula de garantía' },
            categoria: { selector: '#modificarCategoria', span: '#smodificarCategoria', label: 'categoría' },
            seriales: { selector: '#modificarSeriales', span: '#smodificarSeriales', label: 'seriales' },
            precio: { selector: '#modificarPrecio', span: '#smodificarPrecio', label: 'precio' }
        },
        caracteristicasContenedor: '#caracteristicasCategoriaModificar',
        tablaCategoriaHidden: '#modificar_tabla_categoria'
    }
};

function obtenerCampoConfig(campoConfig) {
    return {
        $campo: $(campoConfig.selector),
        $span: campoConfig.span ? $(campoConfig.span) : $()
    };
}

function marcarCampoValido($campo, $span) {
    $campo.removeClass('is-invalid').addClass('is-valid');
    if ($span.length) $span.text('');
}

function marcarCampoInvalido($campo, $span, mensaje) {
    $campo.removeClass('is-valid').addClass('is-invalid');
    if ($span.length) $span.text(mensaje);
}

function validarNombreProductoCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim();
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*El nombre del producto es obligatorio*');
        error = 'El nombre del producto es obligatorio';
    } else if (!regexNombreProducto.test(valor)) {
        marcarCampoInvalido($campo, $span, '*Solo letras, de 3 a 20 caracteres*');
        error = 'El nombre del producto solo puede contener letras y espacios (3-20 caracteres)';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarDescripcionProductoCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim();
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*La descripción es obligatoria*');
        error = 'La descripción del producto es obligatoria';
    } else if (!regexDescripcionProducto.test(valor)) {
        marcarCampoInvalido($campo, $span, '*Máximo 50 caracteres*');
        error = 'La descripción debe tener entre 2 y 50 caracteres';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarModeloCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val();
    let error = null;

    if (!valor) {
        marcarCampoInvalido($campo, $span, '*Debe seleccionar un modelo*');
        error = 'Debe seleccionar un modelo';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarImagenCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const tieneArchivo = ($campo[0] && $campo[0].files && $campo[0].files.length > 0) || ($campo.val() && $campo.val().trim() !== '');
    let error = null;

    if (!tieneArchivo) {
        marcarCampoInvalido($campo, $span, '*Debe seleccionar una imagen*');
        error = 'Debe seleccionar una imagen';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarNumeroNoNegativo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim();
    const numero = parseInt(valor, 10);

    if (valor === '' || isNaN(numero) || numero < 0) {
        marcarCampoInvalido($campo, $span, '*Ingrese un valor válido (0 o mayor)*');
        return `El ${campoConfig.label} debe ser un número válido (0 o mayor)`;
    }

    marcarCampoValido($campo, $span);
    return { numero, error: null };
}

function validarStockCampo(campoConfig, extra = {}) {
    const resultado = validarNumeroNoNegativo(campoConfig);

    if (typeof resultado === 'string') {
        return resultado;
    }

    if (extra.compare && typeof extra.compare === 'function') {
        const compareResult = extra.compare(resultado.numero);
        if (compareResult) {
            const { $campo, $span } = obtenerCampoConfig(campoConfig);
            marcarCampoInvalido($campo, $span, compareResult.spanMensaje || '*Valor inválido*');
            return compareResult.errorMensaje || `El ${campoConfig.label} es inválido`;
        }
    }

    return null;
}

function validarClausulaCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val();
    const longitud = valor.trim().length;
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*La cláusula de garantía es obligatoria*');
        error = 'La cláusula de garantía es obligatoria';
    } else if (longitud < 10 || longitud > 200) {
        marcarCampoInvalido($campo, $span, '*La cláusula debe tener entre 10 y 200 caracteres*');
        error = 'La cláusula de garantía debe tener entre 10 y 200 caracteres';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarCategoriaCampo(campoConfig, hiddenSelector) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val();
    let error = null;

    if (!valor) {
        marcarCampoInvalido($campo, $span, '*Debe seleccionar una categoría*');
        error = 'Debe seleccionar una categoría';
    } else {
        marcarCampoValido($campo, $span);
        if (hiddenSelector) {
            $(hiddenSelector).val(valor);
        }
    }

    return error;
}

function validarSerialesCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim();
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*El campo de seriales es obligatorio*');
        error = 'El campo de seriales es obligatorio';
    } else if (!regexSerialesProducto.test(valor)) {
        marcarCampoInvalido($campo, $span, '*Solo letras, números y guiones*');
        error = 'Los seriales solo pueden contener letras, números y guiones (máx. 20 caracteres)';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarPrecioCampo(campoConfig) {
    const { $campo, $span } = obtenerCampoConfig(campoConfig);
    const valor = $campo.val().trim().replace(',', '.');
    const numero = parseFloat(valor);
    let error = null;

    if (valor === '') {
        marcarCampoInvalido($campo, $span, '*El precio es obligatorio*');
        error = 'El precio es obligatorio';
    } else if (isNaN(numero) || !regexPrecioProducto.test(valor) || numero <= 0) {
        marcarCampoInvalido($campo, $span, '*Ingrese un precio válido (ej: 10.99)*');
        error = 'Ingrese un precio válido (ej: 10.99)';
    } else {
        marcarCampoValido($campo, $span);
    }

    return error;
}

function validarCaracteristicasProducto(config, errores = null) {
    const contenedor = $(config.caracteristicasContenedor);
    if (!contenedor.length) return;

    let numerosInvalidos = false;
    let textosInvalidos = false;

    contenedor.find('input[type="number"]').each(function() {
        const $input = $(this);
        const valor = $input.val().trim();
        const numero = parseFloat(valor);
        const $span = $('#s' + $input.attr('id'));

        if (valor === '' || isNaN(numero) || numero < 0) {
            $input.removeClass('is-valid').addClass('is-invalid');
            if ($span.length) $span.text('*Ingrese un valor válido (0 o mayor)*');
            numerosInvalidos = true;
        } else {
            $input.removeClass('is-invalid').addClass('is-valid');
            if ($span.length) $span.text('');
        }
    });

    contenedor.find('input[type="text"]').each(function() {
        const $input = $(this);
        const valor = $input.val().trim();
        const $span = $('#s' + $input.attr('id'));

        if (valor === '') {
            $input.removeClass('is-valid').addClass('is-invalid');
            if ($span.length) $span.text('*Este campo es obligatorio*');
            textosInvalidos = true;
        } else if (!regexCaracteristicasTexto.test(valor)) {
            $input.removeClass('is-valid').addClass('is-invalid');
            if ($span.length) $span.text('*Solo letras, números, espacios, @, punto y guion*');
            textosInvalidos = true;
        } else {
            $input.removeClass('is-invalid').addClass('is-valid');
            if ($span.length) $span.text('');
        }
    });

    if (errores) {
        if (numerosInvalidos && !errores.includes('Hay campos de características con valores numéricos inválidos')) {
            errores.push('Hay campos de características con valores numéricos inválidos');
        }
        if (textosInvalidos && !errores.includes('Los campos de características de texto solo permiten letras, números, espacios, @, punto y guion')) {
            errores.push('Los campos de características de texto solo permiten letras, números, espacios, @, punto y guion');
        }
    }
}

function validarFormularioProducto(config) {
    if (!config) {
        return { valido: true, errores: [] };
    }

    const errores = [];
    const campos = config.campos;

    if (campos.nombre) {
        const error = validarNombreProductoCampo(campos.nombre);
        if (error) errores.push(error);
    }

    if (campos.descripcion) {
        const error = validarDescripcionProductoCampo(campos.descripcion);
        if (error) errores.push(error);
    }

    if (campos.modelo) {
        const error = validarModeloCampo(campos.modelo);
        if (error) errores.push(error);
    }

    if (campos.imagen) {
        const error = validarImagenCampo(campos.imagen);
        if (error) errores.push(error);
    }

    if (campos.stockActual) {
        const error = validarStockCampo(campos.stockActual);
        if (error) errores.push(error);
    }

    if (campos.stockMinimo) {
        const error = validarStockCampo(campos.stockMinimo);
        if (error) errores.push(error);
    }

    if (campos.stockMaximo) {
        const error = validarStockCampo(campos.stockMaximo, {
            compare: (valorActual) => {
                const minimo = parseInt($(campos.stockMinimo.selector).val(), 10);
                if (!isNaN(minimo) && valorActual <= minimo) {
                    return {
                        spanMensaje: '*Debe ser mayor al stock mínimo*',
                        errorMensaje: 'El stock máximo debe ser mayor al stock mínimo'
                    };
                }
                return null;
            }
        });
        if (error) errores.push(error);
    }

    if (campos.clausula) {
        const error = validarClausulaCampo(campos.clausula);
        if (error) errores.push(error);
    }

    if (campos.categoria) {
        const error = validarCategoriaCampo(campos.categoria, config.tablaCategoriaHidden);
        if (error) errores.push(error);
    }

    if (campos.seriales) {
        const error = validarSerialesCampo(campos.seriales);
        if (error) errores.push(error);
    }

    if (campos.precio) {
        const error = validarPrecioCampo(campos.precio);
        if (error) errores.push(error);
    }

    if (config.caracteristicasContenedor) {
        validarCaracteristicasProducto(config, errores);
    }

    return {
        valido: errores.length === 0,
        errores
    };
}

function configurarValidacionesTiempoRealProducto() {
    Object.values(productoFormConfigs).forEach((config) => {
        if (!config || !$(config.formSelector).length) return;
        const campos = config.campos;

        if (campos.nombre) {
            $(campos.nombre.selector).on('input blur', () => validarNombreProductoCampo(campos.nombre));
        }

        if (campos.descripcion) {
            $(campos.descripcion.selector).on('input blur', () => validarDescripcionProductoCampo(campos.descripcion));
        }

        if (campos.modelo) {
            $(campos.modelo.selector).on('change blur', () => validarModeloCampo(campos.modelo));
        }

        if (campos.imagen) {
            $(campos.imagen.selector).on('change blur', () => validarImagenCampo(campos.imagen));
        }

        if (campos.stockActual) {
            $(campos.stockActual.selector).on('input blur', () => validarStockCampo(campos.stockActual));
        }

        if (campos.stockMinimo) {
            $(campos.stockMinimo.selector).on('input blur', () => {
                validarStockCampo(campos.stockMinimo);
                if (campos.stockMaximo) {
                    validarStockCampo(campos.stockMaximo, {
                        compare: (valorActual) => {
                            const minimo = parseInt($(campos.stockMinimo.selector).val(), 10);
                            if (!isNaN(minimo) && valorActual <= minimo) {
                                return {
                                    spanMensaje: '*Debe ser mayor al stock mínimo*',
                                    errorMensaje: 'El stock máximo debe ser mayor al stock mínimo'
                                };
                            }
                            return null;
                        }
                    });
                }
            });
        }

        if (campos.stockMaximo) {
            $(campos.stockMaximo.selector).on('input blur', () => {
                validarStockCampo(campos.stockMaximo, {
                    compare: (valorActual) => {
                        const minimo = parseInt($(campos.stockMinimo.selector).val(), 10);
                        if (!isNaN(minimo) && valorActual <= minimo) {
                            return {
                                spanMensaje: '*Debe ser mayor al stock mínimo*',
                                errorMensaje: 'El stock máximo debe ser mayor al stock mínimo'
                            };
                        }
                        return null;
                    }
                });
            });
        }

        if (campos.clausula) {
            $(campos.clausula.selector).on('input blur', () => validarClausulaCampo(campos.clausula));
        }

        if (campos.categoria) {
            $(campos.categoria.selector).on('change blur', () => validarCategoriaCampo(campos.categoria, config.tablaCategoriaHidden));
        }

        if (campos.seriales) {
            $(campos.seriales.selector).on('input blur', () => validarSerialesCampo(campos.seriales));
        }

        if (campos.precio) {
            $(campos.precio.selector).on('input blur', () => validarPrecioCampo(campos.precio));
        }

        if (config.caracteristicasContenedor) {
            $(document).on('input blur', `${config.caracteristicasContenedor} input`, () => validarCaracteristicasProducto(config));
        }
    });
}

function limpiarValidacionesFormulario(config) {
    if (!config) return;
    Object.values(config.campos).forEach((campo) => {
        const { $campo, $span } = obtenerCampoConfig(campo);
        $campo.removeClass('is-valid is-invalid');
        if ($span.length) $span.text('');
    });

    if (config.caracteristicasContenedor) {
        const $contenedor = $(config.caracteristicasContenedor);
        $contenedor.find('input').removeClass('is-valid is-invalid');
        $contenedor.find('.span-value').text('');
    }
}

$(document).ready(function () {
    var $tabla = $('#tablaConsultas');
    if ($tabla.length) {
        var tablaProductos;
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaProductos = $tabla.DataTable({
                language: {
                    url: 'assets/public/js/es-ES.json'
                },
                scrollX: true,
                scrollCollapse: true,
                order: [[0, 'desc']],
                initComplete: function () {
                    var api = this.api();
                    var $wrapper = $(api.table().container());

                    api.columns.adjust();

                    var $filter = $wrapper.find('.dataTables_filter');
                    if (!$filter.length) return;
                    // Evitar duplicar el botón si ya existe
                    if ($filter.find('#btnIncluirProducto').length) return;

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
                        id: 'btnIncluirProducto',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Producto'
                    }).append($('<img>', { src: 'assets/img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
            tablaProductos = $tabla.DataTable();
        }
    }

    protegerSelects(['marca', 'categoria', 'modificar_marca', 'modificar_categoria']);

    const regexTexto = /^[a-zA-Z0-9@\.\-\sÁÉÍÓÚáéíóúñÑ]+$/;
    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", 4000, "Atención", $("#mensajes").html());
    }

    configurarValidacionesTiempoRealProducto();

    // Warranty clause validation - sin trim() para permitir espacios
    $("#Clausula_garantia").on("keyup blur", function() {
        const value = $(this).val(); // Eliminado trim() para preservar espacios
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sClausula_garantia").text('*La cláusula de garantía es obligatoria*');
        } else if (value.trim().length < 10 || value.trim().length > 200) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sClausula_garantia").text('*La cláusula debe tener entre 10 y 200 caracteres (sin contar espacios al inicio/fin)*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sClausula_garantia").text('');
        }
    });

    // Stock fields validation
    $("#Stock_Actual, #Stock_Minimo, #Stock_Maximo").on("keyup blur", function() {
        const value = $(this).val().trim();
        const id = $(this).attr('id');
        const spanId = 's' + id;
        
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#" + spanId).text('*Campo obligatorio*');
        } else if (!/^\d+$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#" + spanId).text('*Solo números enteros*');
        } else {
            const numValue = parseInt(value);
            if (numValue < 0) {
                $(this).removeClass('is-valid').addClass('is-invalid');
                $("#" + spanId).text('*El valor no puede ser negativo*');
            } else {
                $(this).removeClass('is-invalid').addClass('is-valid');
                $("#" + spanId).text('');
            }
        }
    });

    // Price field validation
    $("#Precio").on("keyup blur", function() {
        const value = $(this).val().trim().replace(',', '.');
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sPrecio").text('*El precio es obligatorio*');
        } else if (!/^\d+(\.\d{1,2})?$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sPrecio").text('*Formato inválido (ej: 10.99)*');
        } else if (parseFloat(value) <= 0) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sPrecio").text('*El precio debe ser mayor a 0*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sPrecio").text('');
        }
    });

    // Category validation on change
    $("#Categoria").on("change", function() {
        const value = $(this).val();
        if (!value) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sCategoria").text('*Debe seleccionar una categoría*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sCategoria").text('');
        }
    });

    // Serial number validation
    $("#Seriales").on("keyup blur", function() {
        const value = $(this).val().trim();
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sSeriales").text('*Campo obligatorio*');
        } else if (!/^[a-zA-Z0-9-]+$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sSeriales").text('*Solo letras, números y guiones*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sSeriales").text('');
        }
    });

    // Product name validation
    $("#nombre_producto").on("keypress", function(e){
        return validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]*$/, e);
    });

    $("#nombre_producto").on("keyup", function(){
        const value = $(this).val().trim();
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#snombre_producto").text('*El nombre del producto es obligatorio*');
        } else if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ\s\b]{3,20}$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#snombre_producto").text('*Solo letras, de 3 a 20 caracteres*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#snombre_producto").text('');
        }
    });

    // Model validation
    $("#modelo").on("change blur", function() {
        const value = $(this).val();
        if (!value) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#smodelo").text('*Debe seleccionar un modelo*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#smodelo").text('');
        }
    });

    // Image validation
    $("#imagen").on("change blur", function() {
        const value = $(this).val();
        if (!value) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#simagen").text('*Debe seleccionar una imagen*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#simagen").text('');
        }
    });

    // Description validation
    $("#descripcion_producto").on("keypress", function(e){
        return validarkeypress(/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]*$/, e);
    });

    $("#descripcion_producto").on("keyup", function(){
        const value = $(this).val().trim();
        if (value === '') {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sdescripcion_producto").text('*La descripción es obligatoria*');
        } else if (!/^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,-\s\b]{2,50}$/.test(value)) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $("#sdescripcion_producto").text('*Máximo 50 caracteres*');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $("#sdescripcion_producto").text('');
        }
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
    });

    $("#Clausula_garantia").on("keyup", function(){
        validarkeyup(
            /^[a-zA-ZÁÉÍÓÚñÑáéíóúüÜ0-9,\-\s]{2,50}$/,
            $(this),
            $("#smodificarClausulaGarantia"),
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
        validarkeypress(/^[a-zA-Z0-9\b]*$/, e); 
    });

    $("#Seriales").on("keyup", function () {
        validarkeyup(
            /^[A-Z0-9]{1,20}$/,
            $(this),
            $("#sSeriales"),
            "*El formato solo permite letras y números*"
        );
    });
    $("#Seriales").on("input", function () {
        const limpio = this.value
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '');
        this.value = limpio;
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

    // Abrir modal de registro (botón Incluir Producto dentro del DataTable)
    $(document).on('click', '#btnIncluirProducto', function() {
        const config = productoFormConfigs.registrar;
        if (config) {
            $('#incluirProductoForm')[0].reset();
            
            validarFormularioProducto(config);
            limpiarValidacionesFormulario(config);
        }
        $('#registrarProductoModal').modal('show');
    });

    $(document).on('click', '#registrarProductoModal .close, #registrarProductoModal [data-dismiss="modal"]', function() {
        $('#registrarProductoModal').modal('hide');
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
    
    const config = productoFormConfigs.modificar;
    const resultado = validarFormularioProducto(config);

    if (!resultado.valido) {
        const $primerError = $(`${config.formSelector} .is-invalid`).first();
        if ($primerError.length) {
            $('html, body').animate({ scrollTop: $primerError.offset().top - 120 }, 500);
            $primerError.focus();
        }

        Swal.fire({
            icon: 'error',
            title: 'Error en el formulario',
            html: resultado.errores.join('<br>'),
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    $(config.tablaCategoriaHidden).val($(config.campos.categoria.selector).val());

    Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Preparar datos del formulario
    var formData = new FormData(this);
    formData.append('accion', 'modificar');


    // Enviar petición AJAX
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
$('#registrarProductoModal').on('submit', function(e) {
    e.preventDefault();
    
    const config = productoFormConfigs.registrar;
    const resultado = validarFormularioProducto(config);

    if (!resultado.valido) {
        const $primerError = $(`${config.formSelector} .is-invalid`).first();
        if ($primerError.length) {
            $('html, body').animate({ scrollTop: $primerError.offset().top - 120 }, 500);
            $primerError.focus();
        }

        Swal.fire({
            icon: 'error',
            title: 'Error en el formulario',
            html: resultado.errores.join('<br>'),
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    $(config.tablaCategoriaHidden).val($(config.campos.categoria.selector).val());

    Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Preparar datos del formulario
    var formData = new FormData(this);
    formData.append('accion', 'registrar');


    // Enviar petición AJAX
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
            timer: 2000
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
            timer: 2000
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
                    timer: 2000
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
            timer: 2000
        });

    } catch (error) {
        console.error('Error al actualizar la fila del producto:', error);
        // Si hay un error, recargar la página para asegurar consistencia
        location.reload();
    }
}