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

// Función para marcar productos agregados
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
            $(this).removeClass('tr-seleccionado'); // Remover selección verde si ya está agregado
        } else {
            $(this).removeClass('agregado');
            $(this).find('.btn-agregar-prod').prop('disabled', false).text('Agregar');
        }
    });
}

$(document).ready(function () {

    var $tabla = $('#tablaConsultas');
    if ($tabla.length) {
        var tablaRecepcion;
        if (!$.fn.DataTable.isDataTable('#tablaConsultas')) {
            tablaRecepcion = $tabla.DataTable({
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
                    if ($filter.find('#btnIncluirRecepcion').length) return;

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
                        id: 'btnIncluirRecepcion',
                        'class': 'btn-incluir',
                        type: 'button',
                        title: 'Incluir Recepción'
                    }).append($('<img>', { src: 'assets/img/plus.svg' }));

                    $btnWrapper.append($btn);
                    $filter.append($btnWrapper);
                }
            });
        } else {
            tablaRecepcion = $tabla.DataTable();
        }
    }
    
    protegerSelects(['proveedor']);

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", "Atención", $("#mensajes").html());
    }

    $("#correlativo").on("keypress", function(e){
        validarkeypress(/^[0-9]*$/, e);
        let correlativo = document.getElementById("correlativo");
        correlativo.value = space(correlativo.value);
    });
    $("#correlativo").on("keyup", function(){
        validarkeyup(
            /^[0-9]{6}$/,
            $(this),
            $("#scorrelativo"),
            "*Formato válido: 012345*"
        );
    });

    $("#proveedor").on("change", function() {
        if ($(this).val()) {
            $(this).removeClass("is-invalid").addClass("is-valid");
            $("#sproveedor").text("");
        } else {
            $(this).removeClass("is-valid").addClass("is-invalid");
            $("#sproveedor").text("*Debe seleccionar un proveedor*");
        }
    });

    function validarEnvioRecepcion(){
        let correlativo = document.getElementById("correlativo");
        correlativo.value = space(correlativo.value).trim();

        if(validarkeyup(
            /^[0-9]{4,10}$/,
            $("#correlativo"),
            $("#scorrelativo"),
            "*El N° de factura debe tener de 6 dígitos*"
        )==0){
            mensajes('error', 'Verifique el N° de factura', 'Le faltan dígitos al N° de factura');
            return false;
        }

        if($("#proveedor").val()) {
            $("#proveedor").removeClass("is-invalid").addClass("is-valid");
            $("#sproveedor").text("");
        } else {
            $("#proveedor").removeClass("is-valid").addClass("is-invalid");
            $("#sproveedor").text("*Debe seleccionar un proveedor*");
            mensajes('error', 'Verifique el proveedor', 'El campo esta vacio');
            return false;
        }

         return true;
    }

    function agregarFilaRecepcion(recepcion) {
        const nuevaFila = [
            `<span class="campo-numeros">${recepcion.fecha ? formatearFecha(recepcion.fecha) : ''}</span>`,
            `<span class="campo-numeros">${recepcion.correlativo}</span>`,
            `<span class="campo-nombres">${recepcion.nombre_proveedor}</span>`,
            `<span class="campo-numeros">${Number(recepcion.costo_inversion).toLocaleString('es-VE', {minimumFractionDigits:2})}</span>`,
            `<ul>
                <button class="btn-detalle"
                    title="Detallar"
                    data-id_recepcion="${recepcion.id_recepcion}"
                    data-fecha="${recepcion.fecha}"
                    data-correlativo="${recepcion.correlativo}"
                    data-proveedor="${recepcion.nombre_proveedor}"
                    data-costo_inversion="${recepcion.costo_inversion}">
                    <img src="assets/img/eye.svg">
                </button>
                <button class="btn-anular"
                    title="Anular Recepción"
                    data-correlativo="${recepcion.correlativo}">
                    <img src="assets/img/circle-x.svg">
                </button>
            </ul>`
        ];
        const tabla = $('#tablaConsultas').DataTable();
        const rowIdx = tabla.row.add(nuevaFila).draw(false).index();
        $(tabla.row(rowIdx).node()).attr('data-id', recepcion.correlativo);
        tabla.page('last').draw('page');
    }

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

    function resetRecepcion() {
        $("#correlativo").val("");
        $("#scorrelativo").text("");
        $("#proveedor").val("");
        $("#proveedor").removeClass("is-valid is-invalid");
        $("#proveedor").prop('checked', false);
        }

    $('#ingresarRecepcion').on('reset', function() {
        setTimeout(function() {
            $("#proveedor").val("");
            $("#proveedor").removeClass("is-valid is-invalid");
            }, 0);
    });

    $(document).on('click', '#btnIncluirRecepcion', function() {
        $('#ingresarRecepcion')[0].reset();
        $('#scorrelativo').text('');
        $("#proveedor").removeClass("is-valid is-invalid");
        $("#proveedor").prop('checked', false);
        // Mostrar paso inicial al abrir el modal
        mostrarPasoInicial();
        $('#registrarRecepcionModal').modal('show');
    });

    $(document).on('click', '#registrarRecepcionModal .close', function() {
        $('#registrarRecepcionModal').modal('hide');
    });

    $(document).on('click', '#modalp .close-2', function() {
        // Limpiar selección verde al cerrar el modal
        $('#listadop tr').removeClass('tr-seleccionado');
        $('#modalp').modal('hide');
    });

    // Limpiar selección cuando se cierra el modal haciendo clic fuera
    $(document).on('hidden.bs.modal', '#modalp', function() {
        $('#listadop tr').removeClass('tr-seleccionado');
    });

    $('#ingresarRecepcion').on('submit', function(e) {
        e.preventDefault();

        if (validarEnvioRecepcion() && verificaproductos()) {
            var datos = new FormData(this);
            datos.append("accion", "registrar");
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: respuesta.message || 'Recepción registrada correctamente',
                        showConfirmButton: true,
                    });
                    
                    // Limpiar el formulario
                    resetRecepcion();
                    
                    // Limpiar la tabla de productos del modal
                    const tablaRecepcion = document.getElementById('recepcion1');
                    if (tablaRecepcion) {
                        tablaRecepcion.innerHTML = '';
                    }
                    
                    // Agregar la nueva recepción a la tabla
                    if(respuesta.recepcion){
                        agregarFilaRecepcion(respuesta.recepcion);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || 'No se pudo registrar la recepción',
                        showConfirmButton: true
                    });
                }
            });
        }
    });

    $(document).on('click', '#registrarOrdenModal .close', function() {
        $('#registrarOrdenModal').modal('hide');
    });

    //function para saber si selecciono algun productos
    function verificaproductos() {
        var existe = false;
        if ($("#recepcion1 tr").length > 0) {
            existe = true;
        } else {
            mensajes('error', 'Verifique los productos', 'Debe seleccionar algun producto');
        }
        return existe;
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
                try {
                    respuesta = JSON.parse(respuesta);
                } catch (e) {
                    console.error("Error al parsear JSON:", e, respuesta);
                    Swal.fire('Error', 'Respuesta no válida del servidor.', 'error');
                    return;
                }
            }
            if (callback) callback(respuesta);
        },
        error: function (xhr, status, error) {
            console.error("Detalles del error AJAX:", {
                estado: status,
                error: error,
                codigo: xhr.status,
                textoEstado: xhr.statusText,
                respuestaServidor: xhr.responseText
            });

            Swal.fire({
                title: 'Error en la solicitud AJAX',
                html: `
                    <b>Código:</b> ${xhr.status} <br>
                    <b>Estado:</b> ${xhr.statusText} <br>
                    <b>Error:</b> ${error} <br>
                    <b>Respuesta:</b> <pre>${xhr.responseText}</pre>
                `,
                icon: 'error',
                width: 600
            });
        }
    });
}

// =================================================================
// CÓDIGO DE INTEGRACIÓN IA - MICROSERVICIO RECEPCIÓN
// =================================================================

// Variables globales para la IA
let iaClient = null;
let iaDatosFactura = null;

// Funciones para manejar el flujo de 3 pasos
function mostrarPasoInicial() {
    $('#paso_inicial_importar').show();
    $('#paso1_carga_factura').hide();
    $('#paso2_formulario_recepcion').hide();
    $('#ia_resumen_factura').hide();
    iaDatosFactura = null;
}

function mostrarPaso1() {
    $('#paso_inicial_importar').hide();
    $('#paso1_carga_factura').show();
    $('#paso2_formulario_recepcion').hide();
    $('#ia_resumen_factura').hide();
    iaDatosFactura = null;
}

function mostrarPaso2() {
    $('#paso_inicial_importar').hide();
    $('#paso1_carga_factura').hide();
    $('#paso2_formulario_recepcion').show();
    $('#ia_resumen_factura').show();
}

// Inicializar cliente IA
function inicializarIA() {
    try {
        // Verificar que la clase IARecepcionClient esté disponible
        if (typeof IARecepcionClient === 'undefined') {
            console.error('ERROR: La clase IARecepcionClient no está definida. El script ia-recepcion.js no se cargó correctamente.');
            actualizarEstadoIA(false);
            return;
        }
        
        iaClient = new IARecepcionClient({
            apiUrl: 'http://localhost:8000',
            timeout: 30000
        });
        
        // Verificar conexión
        if (iaClient && typeof iaClient.verificarConexion === 'function') {
            iaClient.verificarConexion().then(conectado => {
                actualizarEstadoIA(conectado);
            }).catch(error => {
                console.error('Error verificando conexión IA:', error);
                actualizarEstadoIA(false);
            });
        } else {
            console.error('El método verificarConexion no está disponible en el cliente IA');
            actualizarEstadoIA(false);
        }
        
        console.log('Cliente IA inicializado exitosamente');
    } catch (error) {
        console.error('Error inicializando cliente IA:', error);
        actualizarEstadoIA(false);
    }
}

// Actualizar indicador de estado de IA
function actualizarEstadoIA(conectado) {
    const estadoSpan = document.getElementById('ia_estado_conexion');
    if (estadoSpan) {
        if (conectado) {
            estadoSpan.className = 'badge badge-success';
            estadoSpan.innerHTML = '<i class="fas fa-circle"></i> Conectado';
        } else {
            estadoSpan.className = 'badge badge-danger';
            estadoSpan.innerHTML = '<i class="fas fa-circle"></i> Desconectado';
        }
    }
}

function procesarFacturaAutomaticamente() {
    const inputImagen = document.getElementById('ia_factura_imagen');
    const archivo = inputImagen.files[0];
    
    if (!archivo) {
        return;
    }
    
    // Validar archivo
    const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'];
    if (!tiposPermitidos.includes(archivo.type)) {
        Swal.fire({
            icon: 'error',
            title: 'Archivo no válido',
            text: 'Por favor, seleccione una imagen en formato JPG, PNG o BMP.',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Validar tamaño
    if (archivo.size > 10 * 1024 * 1024) { // 10MB
        Swal.fire({
            icon: 'error',
            title: 'Archivo demasiado grande',
            text: 'El tamaño máximo permitido es de 10MB.',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Mostrar preview
    mostrarPreviewImagen(archivo);
    
    // Procesar con IA
    $('#ia_procesando').show();
    $('#ia_btn_continuar').prop('disabled', true);
    
    iaClient.procesarFactura(archivo, 'spa')
        .then(resultado => {
            iaDatosFactura = resultado;
            
            // Mostrar resumen
            $('#ia_resumen_texto').text(
                `N°: ${resultado.numero_factura} - ${resultado.productos.length} productos detectados`
            );
            
            // Habilitar botón continuar
            $('#ia_btn_continuar').prop('disabled', false);
            $('#ia_procesando').hide();
            
            // Mostrar resultados básicos
            mostrarResultadosBasicos(resultado);
            
            Swal.fire({
                icon: 'success',
                title: 'Factura Procesada',
                text: `Se detectaron ${resultado.productos.length} productos con ${Math.round(resultado.confianza_general * 100)}% de confianza.`,
                timer: 2000,
                showConfirmButton: false
            });
        })
        .catch(error => {
            console.error('Error procesando factura:', error);
            $('#ia_procesando').hide();
            $('#ia_btn_continuar').prop('disabled', false);
            
            Swal.fire({
                icon: 'error',
                title: 'Error al Procesar',
                text: 'No se pudo procesar la factura. Intente con una imagen más clara.',
                confirmButtonText: 'Entendido'
            });
        });
}

function mostrarPreviewImagen(archivo) {
    const reader = new FileReader();
    reader.onload = function(e) {
        $('#ia_imagen_preview').html(`
            <div class="ia-imagen-preview">
                <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 8px;">
                <div class="mt-1">
                    <small class="text-muted">${archivo.name} (${(archivo.size / 1024).toFixed(1)} KB)</small>
                </div>
            </div>
        `);
    };
    reader.readAsDataURL(archivo);
}

function mostrarResultadosBasicos(resultado) {
    const productosHtml = resultado.productos.map((producto, index) => `
        <div class="alert alert-light border-left-info mb-2">
            <strong>Producto ${index + 1}:</strong> ${producto.nombre}<br>
            <small>
                Modelo: ${producto.modelo} | 
                Marca: ${producto.marca} | 
                Costo: $${producto.costo.toFixed(2)} | 
                Cantidad: ${producto.cantidad}
            </small>
        </div>
    `).join('');
    
    $('#ia_resultados_container').html(`
        <div class="text-left">
            <h6><i class="fas fa-robot"></i> Información Detectada</h6>
            <div class="mb-2">
                <strong>N° Factura:</strong> ${resultado.numero_factura}<br>
                <strong>Proveedor:</strong> ${resultado.nombre_proveedor}<br>
                <strong>Confianza:</strong> ${Math.round(resultado.confianza_general * 100)}%
            </div>
            <strong>Productos Detectados:</strong>
            ${productosHtml}
        </div>
    `).show();
}

// Eventos del nuevo flujo
$(document).on('click', '#ia_btn_importar_factura', function() {
    console.log('Click en importar factura');
    mostrarPaso1();
});

$(document).on('change', '#ia_factura_imagen', function() {
    const archivo = this.files[0];
    if (archivo) {
        // Procesar automáticamente
        procesarFacturaAutomaticamente();
    } else {
        $('#ia_btn_continuar').prop('disabled', true);
        $('#ia_imagen_preview').empty();
        $('#ia_resultados_container').hide();
    }
});

$(document).on('click', '#ia_btn_continuar', function() {
    if (iaDatosFactura) {
        mostrarPaso2();
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Espere Procesamiento',
            text: 'La factura aún se está procesando. Por favor espere.',
            confirmButtonText: 'Entendido'
        });
    }
});

$(document).on('click', '#ia_btn_limpiar', function() {
    // Limpiar todo
    $('#ia_factura_imagen').val('');
    $('#ia_imagen_preview').empty();
    $('#ia_resultados_container').hide();
    $('#ia_procesando').hide();
    $('#ia_btn_continuar').prop('disabled', true);
    iaDatosFactura = null;
});

$(document).on('click', '#ia_btn_cancelar', function() {
    // Volver al paso inicial
    mostrarPasoInicial();
});

$(document).on('click', '#ia_btn_volver', function() {
    mostrarPaso1();
});

// Modificar el envío del formulario para incluir verificación IA
$(document).on('submit', '#ingresarRecepcion', function(e) {
    e.preventDefault();
    
    // Validación básica primero
    if (!validarEnvioRecepcion() || !verificaproductos()) {
        return;
    }
    
    // Verificar que se haya procesado la factura
    if (!iaDatosFactura) {
        Swal.fire({
            icon: 'error',
            title: 'Factura Requerida',
            text: 'Debe cargar y procesar una factura antes de registrar la recepción.',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    // Verificación automática con IA
    if (iaClient && iaDatosFactura) {
        // Preparar datos del formulario
        const datosFormulario = {
            correlativo: $('#correlativo').val(),
            proveedor: $('#proveedor').val(),
            productos: []
        };
        
        // Obtener productos del formulario
        $('#recepcion1 tr').each(function() {
            const idProducto = $(this).find('input[name="producto[]"]').val();
            const cantidad = $(this).find('input[name="cantidad[]"]').val();
            const costo = $(this).find('input[name="costos[]"]').val();
            
            if (idProducto) {
                datosFormulario.productos.push({
                    id_producto: idProducto,
                    cantidad: parseInt(cantidad),
                    costo: parseFloat(costo)
                });
            }
        });
        
        // Verificar coherencia con IA
        iaClient.verificarCoherencia(datosFormulario, iaDatosFactura)
            .then(resultadoVerificacion => {
                if (resultadoVerificacion.accion_recomendada === 'bloquear') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Verificación IA - Bloqueado',
                        html: `
                            <p>La inteligencia artificial ha detectado discrepancias críticas que impiden el registro.</p>
                            <div class="text-left">
                                <strong>Discrepancias encontradas:</strong><br>
                                ${resultadoVerificacion.discrepancias.map(d => 
                                    `&bull; ${d.campo}: "${d.valor_factura}" vs "${d.valor_formulario}"`
                                ).join('<br>')}
                            </div>
                            <p class="mt-3"><strong>Por favor, corrija los datos antes de continuar.</strong></p>
                        `,
                        showConfirmButton: true,
                        confirmButtonText: 'Entendido'
                    });
                    return;
                }
                
                // Si hay advertencias, mostrarlas pero permitir continuar
                if (resultadoVerificacion.accion_recomendada === 'requiere_revision' || 
                    resultadoVerificacion.accion_recomendada === 'advertencia') {
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Verificación IA - Advertencia',
                        html: `
                            <p>La inteligencia artificial ha detectado algunas discrepancias:</p>
                            <div class="text-left">
                                <strong>Discrepancias:</strong><br>
                                ${resultadoVerificacion.discrepancias.map(d => 
                                    `&bull; ${d.campo}: "${d.valor_factura}" vs "${d.valor_formulario}"`
                                ).join('<br>')}
                            </div>
                            <p class="mt-3"><strong>¿Desea continuar con el registro?</strong></p>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, continuar',
                        cancelButtonText: 'No, revisar datos'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            enviarFormularioRecepcion(resultadoVerificacion);
                        }
                    });
                    return;
                }
                
                // Si todo está bien, enviar directamente
                enviarFormularioRecepcion(resultadoVerificacion);
            })
            .catch(error => {
                console.error('Error en verificación IA:', error);
                // En caso de error con IA, preguntar si desea continuar
                Swal.fire({
                    icon: 'warning',
                    title: 'Error en Verificación IA',
                    text: 'No se pudo verificar con la IA. ¿Desea continuar sin verificación?',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        enviarFormularioRecepcion(null);
                    }
                });
            });
    } else {
        // Si no hay IA, enviar normal
        enviarFormularioRecepcion(null);
    }
});

// Función separada para enviar el formulario
function enviarFormularioRecepcion(resultadoVerificacion) {
    var datos = new FormData($('#ingresarRecepcion')[0]);
    datos.append("accion", "registrar");
    
    // Agregar datos de verificación IA si existen
    if (resultadoVerificacion) {
        datos.append("ia_verificacion", JSON.stringify(resultadoVerificacion));
    }
    
    enviarAjax(datos, function(respuesta){
        if(respuesta.status === "success" || respuesta.resultado === "success"){
            
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: respuesta.message || 'Recepción registrada correctamente',
                showConfirmButton: true,
            });
            
            // Limpiar el formulario
            resetRecepcion();
            
            // Limpiar la tabla de productos del modal
            const tablaRecepcion = document.getElementById('recepcion1');
            if (tablaRecepcion) {
                tablaRecepcion.innerHTML = '';
            }
            
            // Agregar la nueva recepción a la tabla
            if(respuesta.recepcion){
                agregarFilaRecepcion(respuesta.recepcion);
            }
            
            // Cerrar modal y volver al paso 1
            $('#registrarRecepcionModal').modal('hide');
            mostrarPasoInicial();
            
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: respuesta.message || 'No se pudo registrar la recepción',
                showConfirmButton: true
            });
        }
    });
}

// Inicializar IA cuando el documento esté listo
$(document).ready(function() {
    console.log('Recepción lista - Inicializando sistema IA...');
    inicializarIA();
});


    $(document).on('click', '.btn-anular', function (e) {
        e.preventDefault();
        Swal.fire({
            title: '¿Está seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, anularla!'
        }).then((result) => {
            if (result.isConfirmed) {
                var correlativo = $(this).data('correlativo');
                var datos = new FormData();
                datos.append('accion', 'anular');
                datos.append('correlativo', correlativo);

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
                                'Anulada!',
                                'La recepción ha sido anulada.',
                                'success'
                            );
                            anularFilaRecepcion(correlativo);
                        } else {
                            Swal.fire('Error', respuesta.message || 'Error al anular la recepción', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Error en la solicitud AJAX', 'error');
                    }
                });
            }
        });
    });

    function anularFilaRecepcion(correlativo) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${correlativo}"]`).addClass('anulada');
        tabla.row(fila).remove().draw();
    }

    function verificarPermisosEnTiempoRealRecepcion() {
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
                $('#btnIncluirRecepcion').show();
            } else {
                $('#btnIncluirRecepcion').hide();
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
        verificarPermisosEnTiempoRealRecepcion();
        setInterval(verificarPermisosEnTiempoRealRecepcion, 10000); // 10 segundos
    });

    function mensajes(icono, titulo, mensaje){
        Swal.fire({
            icon: icono,
            title: titulo,
            text: mensaje,
            showConfirmButton: true,
            confirmButtonText: 'Aceptar',
        });
    }

    function validarkeypress(er, e) {
        key = e.keyCode;
        tecla = String.fromCharCode(key);
        a = er.test(tecla);

        if (!a) {
            e.preventDefault();
        }
    }

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

    function space(str) {
        const regex = /\s{2,}/g;
        var str = str.replace(regex, ' ');
        return str;
    }
});

function muestraMensaje(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: mensaje
    });
}

carga_productos();    //boton para levantar modal de productos
    $("#listado").on("click",function(){
    $("#modalp").modal("show");
});

$("#codigoproducto").on("keyup",function(){
    var codigo = $(this).val();
    $(this).addClass('agregado')
    $("#listadop tr").each(function(){
        if(codigo == $(this).find("td:eq(1)").text()){
            colocaproducto($(this));
        }
    });
});	

function carga_productos(){
    var datos = new FormData();
    datos.append('accion','listado'); //le digo que me muestre un listado de aulas
    enviaAjax(datos);
}

function enviaAjax(datos) {
    fetch('', {
        method: 'POST',
        body: datos
    })
    .then(res => res.text())
    .then(respuesta => {
        try {
            let lee = JSON.parse(respuesta);
            console.log(lee);

            if (lee.resultado == 'listado') {
                document.querySelector('#listadop').innerHTML = lee.mensaje;

            } else if (lee.resultado === 'registrar') {
                muestraMensaje('success', 6000, 'REGISTRAR', lee.mensaje);
                borrar();
                if (lee.data) insertarFilaTabla(lee.data);

            } else if (lee.resultado === 'encontro') {
                muestraMensaje('warning', 6000, 'Atención', lee.mensaje);

            } else if (lee.resultado === 'error') {
                muestraMensaje('error', 6000, 'Error', lee.mensaje);
            }

        } catch (e) {
            console.error("Error en JSON: " + e.message);
        }
    })
    .catch(err => console.error("Error AJAX:", err));
}

function borrar(){
    $("#correlativo").val('');
    $("#proveedor").val("disabled");
    $("#recepcion1 tr").remove();
    $("#descripcion").val('');
}

//funcion para colocar los productos - MODIFICADA PARA APLICAR COLOR VERDE
function colocaproducto(linea){
    var id = $(linea).find("td:eq(0)").text();
    var encontro = false;
    
    // Aplicar estilo verde a la línea seleccionada
    $(linea).addClass('tr-seleccionado');
    
    // Remover el estilo después de 2 segundos (opcional)
    setTimeout(function() {
        $(linea).removeClass('tr-seleccionado');
    }, 2000);
    
    $("#recepcion1 tr").each(function(){
        if(id*1 == $(this).find("td:eq(1)").text()*1){
            encontro = true
            var t = $(this).find("td:eq(4)").children();
            t.val(t.val()*1+1);
            modificasubtotal(t);
        } 
    });
    
    if(!encontro){
        var l = `
            <tr>
            <td>
            <button type="button" class="btn-eliminar-pr" onclick="borrarp(this)">Eliminar</button>
            </td>
            <td style="display:none">
                <input type="text" name="producto[]" style="display:none"
                value="`+
                    $(linea).find("td:eq(0)").text()+
                `"/>`+	
                    $(linea).find("td:eq(0)").text()+
            `</td>
            <td>`+
                    $(linea).find("td:eq(1)").text()+
            `</td>
            <td>`+
                    $(linea).find("td:eq(2)").text()+
            `</td>
            <td>`+
                    $(linea).find("td:eq(3)").text()+
            `</td>
            <td>`+
                    $(linea).find("td:eq(4)").text()+
            `</td>
            <td>`+
                    $(linea).find("td:eq(5)").text()+
            `</td>
            <td>
                <input type="number" class="numerico" name="costo[]" min="0.01" step="0.01" value="1" required>
            </td>
            <td>
                <input type="number" class="numerico" name="cantidad[]" min="1" step="1" value="1" required>
            </td>
            </tr>`;
        $("#recepcion1").append(l);
    }
    
    // Actualizar el estado de productos agregados
    marcarProductosAgregados();
}
//fin de funcion modifica subtotal

//funcion para eliminar linea de detalle de ventas
function borrarp(boton){
    $(boton).closest('tr').remove();
    // Actualizar el estado de productos agregados después de eliminar
    marcarProductosAgregados();
}

const tabla = document.getElementById('tablaConsultas');
const modal = document.getElementById('modalDetallesRecepcion');
const cerrar = document.getElementById('cerrarModalDetallesRecepcion');

tabla.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-detalle');
    if (!btn) return; // Si no se hizo clic en un botón detalle, no hace nada

    // Cargar datos principales
    document.getElementById('detalle-fecha').textContent = btn.dataset.fecha;
    document.getElementById('detalle-correlativo').textContent = btn.dataset.correlativo;
    document.getElementById('detalle-proveedor').textContent = btn.dataset.proveedor;

    // Limpiar tabla y costo
    document.getElementById('tbodyDetalleProductosRecepcion').innerHTML =
        '<tr><td colspan="7">Cargando...</td></tr>';
    document.getElementById('detalle-costo-inversion').textContent = '';

    // Obtener productos de la recepción por AJAX
    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `accion=productos_recepcion&id_recepcion=${encodeURIComponent(btn.dataset.id_recepcion)}`
    })
    .then(response => response.json())
    .then(productos => {
        let html = '';
        let total = 0;
        if (productos.length) {
            productos.forEach(prod => {
                html += `<tr>
                    <td><span class="campo-numeros">${prod.codigo}</span></td>
                    <td><span class="campo-nombres">${prod.producto}</span></td>
                    <td><span class="campo-nombres">${prod.modelo}</span></td>
                    <td><span class="campo-nombres">${prod.marca}</span></td>
                    <td><span class="campo-numeros">${prod.serial}</span></td>
                    <td><span class="campo-numeros">${prod.cantidad}</span></td>
                    <td><span class="campo-tex-num">${parseFloat(prod.costo).toLocaleString('es-VE', { minimumFractionDigits: 2 })}</span></td>
                </tr>`;
                total += parseFloat(prod.costo) * parseFloat(prod.cantidad);
            });
        } else {
            html = '<tr><td colspan="7" style="text-align:center;">Sin productos asociados.</td></tr>';
        }
        document.getElementById('tbodyDetalleProductosRecepcion').innerHTML = html;
        document.getElementById('detalle-costo-inversion').textContent =
            total.toLocaleString('es-VE', { minimumFractionDigits: 2 });
    });

    // Mostrar modal
    modal.classList.add('mostrar');
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
        cargarYMostrarModalAyuda(); // Sin contexto específico
    });
    
    // Botón de ayuda dentro de modales
    $(document).on('click.ayuda', '.btn-ayuda-modal', function(e) {
        e.preventDefault();
        const contexto = $(this).data('contexto');
        console.log('Clic en botón de ayuda modal con contexto:', contexto);
        cargarYMostrarModalAyuda(contexto);
    });
});