$(document).ready(function () {

    if($.trim($("#mensajes").text()) != ""){
        mensajes("warning", "Atención", $("#mensajes").html());
    }

    $("#correlativo").on("keypress",function(e){
        validarkeypress(/^[0-9]*$/,e);
        let correlativo = document.getElementById("correlativo");
        correlativo.value = space(correlativo.value);
    });
    
    $("#correlativo").on("keyup",function(){
        validarkeyup(
            /^[0-9]{4,10}$/,
            $(this),
            $("#scorrelativo"),
            "Se permite de 4 a 10 dígitos"
        );
    });

    let hoy = new Date();
    let yyyy = hoy.getFullYear();
    let mm = String(hoy.getMonth() + 1).padStart(2, '0');
    let dd = String(hoy.getDate()).padStart(2, '0');
    let fechaMax = `${yyyy}-${mm}-${dd}`;
    $("#fecha").attr("max", fechaMax);
    
    $("#fecha").on("change keyup", function() {
        let fechaInput = $(this).val();
        let hoy = new Date();
        let fechaIngresada = new Date(fechaInput);

        hoy.setHours(0,0,0,0);

        if (fechaInput === "") {
            $("#sfecha").text("Debe ingresar una fecha");
        } else if (fechaIngresada > hoy) {
            $("#sfecha").text("No se permite una fecha futura");
            $(this).addClass("input-error");
        } else {
            $("#sfecha").text("");
            $(this).removeClass("input-error");
        }
    });

    $("#factura").on("change blur", function() {
        validarkeyup(
            /^.+$/,
            $(this),
            $("#sfactura"),
            "Debe seleccionar una factura"
        );
    });

    function validarEnvioOrden(){
        let correlativo = document.getElementById("correlativo");
        correlativo.value = space(correlativo.value).trim();
        
        let fecha = $("#fecha").val();
        let hoy = new Date();
        let fechaIngresada = new Date(fecha);
        hoy.setHours(0,0,0,0);

        if(validarkeyup(
            /^[0-9]{4,10}$/,
            $("#correlativo"),
            $("#scorrelativo"),
            "*El correlativo debe tener de 4 a 10 dígitos*"
        )==0){
            mensajes('error', 'Verifique el correlativo', 'Le faltan dígitos al correlativo');
            return false;
        }
        else if(validarkeyup(
            /^.+$/,
            $("#fecha"),
            $("#sfecha"),
            "*Debe ingresar una fecha completa (día, mes y año)*"
        )==0){
            mensajes('error', 'Verifique la fecha', 'La fecha está vacía, incompleta o no es válida');
            return false;
        } else if (fechaIngresada > hoy) {
            $("#sfecha").text("*Solo se permite una fecha actual o una fecha anterior*");
            mensajes('error', 'Verifique la fecha', 'No se permiten fechas futuras');
            return false;
        } else {
            $("#sfecha").text("");
        }

        if($("#factura").val() === null || $("#factura").val() === "") {
            $("#sfactura").text("*Debe seleccionar una factura*");
            mensajes('error', 'Verifique la factura', 'El campo esta vacio');
            return false;
        } else {
            $("#sfactura").text("");
        }

        return true;
    }

    function agregarFilaOrden(orden) {
        const nuevaFila = [
            `<span class="campo-numeros">${orden.correlativo}</span>`,
            `<span class="campo-nombres">${orden.fecha_despacho}</span>`,
            `<span class="campo-numeros">${orden.id_factura}</span>`,
            `<ul>
                <button class="btn-anular"
                    title="Anular Orden de Despacho"
                    data-id="${orden.id_orden_despachos}">
                    <img src="img/circle-x.svg">
                </button>
            </ul>`
        ];
        const tabla = $('#tablaConsultas').DataTable();
        const rowIdx = tabla.row.add(nuevaFila).draw(false).index();
        $(tabla.row(rowIdx).node()).attr('data-id', orden.id_orden_despachos);
        tabla.page('last').draw('page');
    }

    function resetOrden() {
        $('#correlativo').val('');
        $('#fecha').val('');
        $('#factura').val('');
        $('#scorrelativo').text('');
        $('#sfecha').text('');
        $('#sfactura').text('');
    }

    $('#btnIncluirOrden').on('click', function() {
        $('#ingresarOrdenDespacho')[0].reset();
        $('#scorrelativo').text('');
        $('#sfecha').text('');
        $('#sfactura').text('');
        $('#registrarOrdenModal').modal('show');
    });

    $('#ingresarOrdenDespacho').on('submit', function(e) {
        e.preventDefault();

        if(validarEnvioOrden()){
            var datos = new FormData(this);
            datos.append("accion", "ingresar");
            enviarAjax(datos, function(respuesta){
                if(respuesta.status === "success" || respuesta.resultado === "success"){
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: respuesta.message || 'Orden de despacho registrada correctamente'
                    });
                    agregarFilaOrden(respuesta.orden);
                    resetOrden();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: respuesta.message || 'No se pudo registrar la orden de despacho'
                    });
                }
            });
        }
    });

    $(document).on('click', '#registrarOrdenModal .close', function() {
        $('#registrarOrdenModal').modal('hide');
    });

    /*$(document).on('click', '.btn-anular', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        eliminarOrdenDespacho(id);
    });
    
    function eliminarOrdenDespacho(id) {
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
                console.log("ID del despacho a eliminar: ", id); 
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('id', id);
                mostrarDatosFormData(datos);
                enviarAjax(datos, function (respuesta) {
                    if (respuesta.status === 'success') {
                        Swal.fire(
                            'Eliminado!',
                            'La orden de despacho ha sido Anulada correctamente.',
                            'success'
                        ).then(function() {
                            eliminarFilaOrden(id);
                        });
                    } else {
                        muestraMensaje(respuesta.message);
                    }
                });
            }
        });
    }

    function eliminarFilaOrden(id) {
        const tabla = $('#tablaConsultas').DataTable();
        tabla.row($(`tr[data-id="${id}"]`)).remove().draw(false);
    }*/

    $(document).on('click', '.btn-marcar', function () {
        const $boton = $(this);
        const $fila = $boton.closest('tr');
        const id = $fila.data('id');
        const estado_actual = $fila.find('.campo-rango').text().trim();

        $.ajax({
            url: '',
            type: 'POST',
            data: {
                accion: 'cambiar_estado_orden',
                id: id,
                estado_actual: estado_actual
            },
            success: function (resp) {
                let r = JSON.parse(resp);
                if (r.status === 'success') {
                    $fila.find('.campo-rango').text(r.nuevo_estado);
                    $boton.remove();
                    Swal.fire('Estado cambiado a "' + r.nuevo_estado + '"');

                    const tabla = $('#tablaConsultas').DataTable();
                    tabla.order([
                        [4, 'asc'],
                        [0, 'asc'] 
                    ]).draw();
                } else {
                    Swal.fire('Error', r.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo cambiar el estado', 'error');
            }
        });
    });

    function anularFilaOrdenDespacho(idOrden) {
        const tabla = $('#tablaConsultas').DataTable();
        const fila = $(`#tablaConsultas tbody tr[data-id="${idOrden}"]`).addClass('anulada');
        tabla.row(fila).remove().draw();
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

                var idOrden = $(this).data('id-orden');

                var datos = new FormData();
                datos.append('accion', 'anularOrden');
                datos.append('id_orden_despachos', idOrden);

                $.ajax({
                    url: '', // ruta correcta aquí
                    type: 'POST',
                    data: datos,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (respuesta) {
                        if (respuesta.status === 'success') {
                            Swal.fire(
                                'Anulada!',
                                'La orden de despacho ha sido anulada.',
                                'success'
                            );
                            anularFilaOrdenDespacho(idOrden);
                        } else {
                            Swal.fire('Error', respuesta.message || 'Error al anular la orden', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Error en la solicitud AJAX', 'error');
                    }
                });
            }
        });
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
    
    function muestraMensaje(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: mensaje
        });
    }

    function mostrarDatosFormData(formData) {
        console.log('Datos enviados en FormData:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
    }

    function enviarAjax(datos, callback) {
        console.log("Enviando datos AJAX: ", datos);
        $.ajax({
            url: '', 
            type: 'POST',
            contentType: false,
            data: datos,
            processData: false,
            cache: false,
            success: function (respuesta) {
                console.log("Respuesta del servidor: ", respuesta); 
                callback(JSON.parse(respuesta));
            },
            error: function () {
                console.error('Error en la solicitud AJAX');
                muestraMensaje('Error en la solicitud AJAX');
            }
        });
    }
});

const tablaOrden = document.getElementById('tablaConsultas');
const modalOrden = document.getElementById('modalDetallesOrden');
const cerrarOrden = document.getElementById('cerrarModalDetallesOrden');

tablaOrden.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-detalle');
    if (!btn) return;
    let productos = JSON.parse(btn.dataset.productos);

    // Cargar datos principales
    document.getElementById('detalleCliente').textContent = btn.dataset.cliente;
    document.getElementById('detalleCedula').textContent = btn.dataset.cedula;
    document.getElementById('detalleFecha').textContent = btn.dataset.fecha;

    // Productos
    let prodHtml = '';
    productos.forEach(p => {
        let total = (parseFloat(p.precio_unitario) * parseFloat(p.cantidad)).toFixed(2);
        prodHtml += `
            <tr>
                <td><span class="campo-numeros">${p.codigo}</span></td>
                <td><span class="campo-nombres">${p.producto}</span></td>
                <td><span class="campo-nombres">${p.modelo}</span></td>
                <td><span class="campo-nombres">${p.marca}</span></td>
                <td><span class="campo-numeros">${p.serial}</span></td>
                <td><span class="campo-numeros">${p.cantidad}</span></td>
                <td><span class="campo-numeros">${p.precio_unitario}</span></td>
                <td><span class="campo-tex-num">${total}</span></td>
            </tr>
        `;
    });
    document.getElementById('detalleProductos').innerHTML = prodHtml;

    // Mostrar modal
    modalOrden.classList.add('mostrar');
});
// Cierre al hacer clic en el botón "X"
cerrarOrden.addEventListener('click', () => {
    modalOrden.classList.remove('mostrar');
});

// Cierre al hacer clic fuera del contenido del modal
window.addEventListener('click', (e) => {
    if (e.target === modalOrden) {
        modalOrden.classList.remove('mostrar');
    }
});