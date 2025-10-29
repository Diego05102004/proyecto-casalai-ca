$(document).ready(function () {

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", 4000, "Atención", $("#mensajes").html());
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

    $("#proveedor").on("change blur", function() {
        validarkeyup(
            /^.+$/,
            $(this),
            $("#sproveedor"),
            "*Debe seleccionar un proveedor*"
        );
    });

    $("#tamanocompra").on("change blur", function() {
        validarkeyup(
            /^.+$/,
            $(this),
            $("#stamanocompra"),
            "*Debe seleccionar un tamaño de compra*"
        );
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

        if($("#proveedor").val() === null || $("#proveedor").val() === "") {
            $("#sproveedor").text("*Debe seleccionar un proveedor*");
            mensajes('error', 'Verifique el proveedor', 'El campo esta vacio');
            return false;
        } else {
            $("#sproveedor").text("");
        }

        if($("#tamanocompra").val() === null || $("#tamanocompra").val() === "") {
            $("#stamanocompra").text("*Debe seleccionar un tamaño de compra*");
            mensajes('error', 'Verifique el tamaño de compra', 'El campo esta vacio');
            return false;
        } else {
            $("#stamanocompra").text("");
        }
        return true;
    }

    function agregarFilaRecepcion(recepcion) {
        const nuevaFila = [
            `<span class="campo-numeros">${recepcion.fecha ? formatearFecha(recepcion.fecha) : ''}</span>`,
            `<span class="campo-numeros">${recepcion.correlativo}</span>`,
            `<span class="campo-nombres">${recepcion.nombre_proveedor}</span>`,
            `<span class="campo-nombres">${recepcion.tamanocompra}</span>`,
            `<span class="campo-numeros">${Number(recepcion.costo_inversion).toLocaleString('es-VE', {minimumFractionDigits:2})}</span>`,
            `<ul>
                <button class="btn-detalle"
                    title="Detallar"
                    data-id_recepcion="${recepcion.id_recepcion}"
                    data-fecha="${recepcion.fecha}"
                    data-correlativo="${recepcion.correlativo}"
                    data-proveedor="${recepcion.nombre_proveedor}"
                    data-costo_inversion="${recepcion.costo_inversion}">
                    <img src="img/eye.svg">
                </button>
                <button class="btn-anular"
                    title="Anular Recepción"
                    data-correlativo="${recepcion.correlativo}">
                    <img src="img/circle-x.svg">
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
        $('#correlativo').val('');
        $('#proveedor').val('');
        $('#tamanocompra').val('');
        $('#scorrelativo').text('');
        $('#sproveedor').text('');
        $('#stamanocompra').text('');
    }

    $('#btnIncluirRecepcion').on('click', function() {
        $('#ingresarRecepcion')[0].reset();
        $('#scorrelativo').text('');
        $('#sproveedor').text('');
        $('#stamanocompra').text('');
        $('#registrarRecepcionModal').modal('show');
    });

    $(document).on('click', '#registrarRecepcionModal .close', function() {
        $('#registrarRecepcionModal').modal('hide');
    });

    $(document).on('click', '#modalp .close-2', function() {
        $('#modalp').modal('hide');
    });

    $('#ingresarRecepcion').on('submit', function(e) {
        e.preventDefault();

        if (validarEnvioRecepcion() && verificaproductos()) {
            var datos = new FormData(this);
            datos.append("accion", "registrar");
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: respuesta.message || 'Recepción registrada correctamente'
                    });
                    if(respuesta.status === "success" && respuesta.recepcion){
                        console.log(respuesta.recepcion);
                        agregarFilaRecepcion(respuesta.recepcion);
                        resetRecepcion();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || 'No se pudo registrar la recepción'
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

//funcion para colocar los productos
function colocaproducto(linea){
    var id = $(linea).find("td:eq(0)").text();
    var encontro = false;
    
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
}
//fin de funcion modifica subtotal

//funcion para eliminar linea de detalle de ventas
function borrarp(boton){
    $(boton).closest('tr').remove();
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
