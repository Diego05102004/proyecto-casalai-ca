/**
 * recepcion-new.js - Script unificado para la gestión de Recepciones
 */

// ==========================================
// 1. SISTEMA DE PROTECCIÓN DE SELECTS
// ==========================================
function protegerSelects(selectIds, interval = 1000) {
    const originales = {};

    selectIds.forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;

        originales[id] = Array.from(select.options).map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));
    });

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
            }
        });
    }, interval);
}

// ==========================================
// 2. FUNCIONES AUXILIARES Y DE UTILIDAD
// ==========================================
function mensajes(icono, titulo, mensaje) {
    Swal.fire({
        icon: icono,
        title: titulo,
        text: mensaje,
        showConfirmButton: true,
        confirmButtonText: 'Aceptar',
    });
}

function validarkeypress(er, e) {
    let key = e.keyCode;
    let tecla = String.fromCharCode(key);
    let a = er.test(tecla);

    if (!a) {
        e.preventDefault();
    }
}

function validarkeyup(er, etiqueta, etiquetamensaje, mensaje) {
    let a = er.test(etiqueta.val());

    if (a) {
        etiquetamensaje.text("");
        etiqueta.removeClass("is-invalid").addClass("is-valid");
        return 1;
    } else {
        etiquetamensaje.text(mensaje);
        etiqueta.removeClass("is-valid").addClass("is-invalid");
        return 0;
    }
}

function space(str) {
    if (!str) return '';
    return str.replace(/\s{2,}/g, ' ');
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    const partes = fechaStr.split('-');
    if (partes.length === 3) {
        return `${partes[2].padStart(2, '0')}/${partes[1].padStart(2, '0')}/${partes[0]}`;
    }
    return fechaStr;
}

// ==========================================
// 3. VALIDACIONES DE FORMULARIO
// ==========================================
function validarEnvioRecepcion() {
    let correlativoInput = $("#correlativo");
    let correlativoVal = space(correlativoInput.val()).trim();
    correlativoInput.val(correlativoVal);

    let esValido = true;

    if (validarkeyup(/^[0-9]{6}$/, correlativoInput,$("#scorrelativo"), "*El N° de factura debe tener exactamente 6 dígitos*") == 0) {
        esValido = false;
    }

    let proveedorVal = $("#proveedor").val();
    if (proveedorVal) {
        $("#proveedor").removeClass("is-invalid").addClass("is-valid");
        $("#sproveedor").text("");
    } else {
        $("#proveedor").removeClass("is-invalid").addClass("is-invalid");
        $("#sproveedor").text("*Debe seleccionar un proveedor*");
        esValido = false;
    }

    if (!esValido) {
        mensajes('error', 'Campos incompletos', 'Por favor complete los campos obligatorios (*)');
    }

    return esValido;
}

function verificaproductos() {
    var filasModal = $("#productosList .producto-row").length;
    if (filasModal > 0) {
        let valido = true;
        $("#productosList .producto-row").each(function() {
            let prodSel = $(this).find('.producto-select').val();
            let cant = $(this).find('input[name="cantidad[]"]').val();
            if (!prodSel || !cant || Number(cant) <= 0) {
                valido = false;
            }
        });
        if (!valido) {
            mensajes('error', 'Verifique los productos', 'Debe seleccionar un producto válido y asignar una cantidad mayor a 0');
            return false;
        }
        return true;
    } else {
        mensajes('error', 'Verifique los productos', 'Debe seleccionar algún producto');
        return false;
    }
}

// ==========================================
// 4. INTEGRACIÓN SEGURA CON EL MICROSERVICIO (LAZY INITIALIZATION)
// ==========================================
let asistenteRecepcionIA = null;
let urlPreviewFacturaRecepcion = null;

function obtenerAsistenteIA() {
    if (!asistenteRecepcionIA && typeof AsistenteRecepcionIA !== 'undefined') {
        asistenteRecepcionIA = new AsistenteRecepcionIA({
            apiUrl: 'http://127.0.0.1:8000',
            debug: true,
            selectores: { alertas: '#estadoIARecepcion' }
        });
    }
    return asistenteRecepcionIA;
}

function establecerEstadoIARecepcion(mensaje, estado = 'info') {
    const estadoEl = document.getElementById('estadoIARecepcion');
    if (!estadoEl) return;
    estadoEl.textContent = mensaje;
    estadoEl.dataset.state = estado;
    estadoEl.hidden = !mensaje;
}

function activarContingenciaIARecepcion(detalle = '') {
    establecerEstadoIARecepcion(
        `El servicio de lectura automática por IA no está disponible temporalmente. Se ha habilitado el formulario manual.${detalle ? ` Detalle: ${detalle}` : ''}`,
        'error'
    );
}

function normalizarTextoRecepcion(valor) {
    return String(valor || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase().replace(/[^a-z0-9]/g, '');
}

function buscarOpcionCatalogo(select, texto) {
    const buscado = normalizarTextoRecepcion(texto);
    if (!buscado) return null;
    const opciones = Array.from(select.options).filter(opcion => opcion.value);
    const exactas = opciones.filter(opcion => normalizarTextoRecepcion(opcion.textContent) === buscado);
    if (exactas.length === 1) return exactas[0];
    const parciales = opciones.filter(opcion => {
        const nombre = normalizarTextoRecepcion(opcion.textContent);
        return nombre.includes(buscado) || buscado.includes(nombre);
    });
    return parciales.length === 1 ? parciales[0] : null;
}

function rellenarRecepcionDesdeFactura(factura) {
    const correlativo = document.getElementById('correlativo');
    if (factura.numero_factura) correlativo.value = factura.numero_factura;

    const proveedor = document.getElementById('proveedor');
    const proveedorEncontrado = buscarOpcionCatalogo(proveedor, factura.nombre_proveedor);
    if (proveedorEncontrado) proveedor.value = proveedorEncontrado.value;

    const productosDetectados = Array.isArray(factura.productos) ? factura.productos : [];
    if (productosDetectados.length) {
        const productosList = document.getElementById('productosList');
        while (productosList.querySelectorAll('.producto-row').length > 1) {
            productosList.lastElementChild.remove();
        }
        productosList.querySelector('.producto-row').querySelectorAll('select, input').forEach(campo => {
            if (campo.classList.contains('subtotal-display')) campo.value = '$0.00';
            else campo.value = '';
        });

        productosDetectados.forEach((producto, indice) => {
            const fila = indice === 0
                ? productosList.querySelector('.producto-row')
                : addProductoRow();
            const selector = fila.querySelector('.producto-select');
            const opcion = buscarOpcionCatalogo(selector, producto.nombre);
            if (opcion) {
                selector.value = opcion.value;
                seleccionarProducto(selector);
            }
            fila.querySelector('input[name="cantidad[]"]').value = producto.cantidad > 0 ? producto.cantidad : 1;
            fila.querySelector('input[name="costo[]"]').value = Number(producto.costo_unitario || 0).toFixed(2);
            calcularSubtotal(fila.querySelector('input[name="costo[]"]'));
        });
    }

    const inputIva = document.getElementById('porcentajeIvaRecepcion');
    if (inputIva) inputIva.value = Number(factura.porcentaje_iva || 0).toFixed(2);
    calcularTotal();
    actualizarContadorProductos();
    establecerEstadoIARecepcion(`Análisis completado. Factura: ${factura.numero_factura || 'no detectada'}.`);
}

function limpiarFacturaIARecepcion() {
    const aiClient = obtenerAsistenteIA();
    if (aiClient) aiClient.limpiarCache();
    if (urlPreviewFacturaRecepcion) URL.revokeObjectURL(urlPreviewFacturaRecepcion);
    urlPreviewFacturaRecepcion = null;
    
    const previewFac = document.getElementById('previewFacturaRecepcion');
    const prevImg = document.getElementById('previewImagenRecepcion');
    const prevPdf = document.getElementById('previewPdfRecepcion');
    const resIA = document.getElementById('resultadoIARecepcion');

    if (previewFac) previewFac.hidden = true;
    if (prevImg) { prevImg.hidden = true; prevImg.removeAttribute('src'); }
    if (prevPdf) { prevPdf.hidden = true; prevPdf.removeAttribute('src'); }
    if (resIA) { resIA.textContent = ''; resIA.hidden = true; }
    establecerEstadoIARecepcion('');
}

async function analizarFacturaRecepcion(archivo) {
    const aiClient = obtenerAsistenteIA();
    if (!aiClient) {
        activarContingenciaIARecepcion('Librería del microservicio no disponible.');
        return;
    }
    establecerEstadoIARecepcion('Analizando factura con OCR...');
    const resultado = await aiClient.extraerDesdeImagen(archivo);
    if (!resultado.exito) {
        if (resultado.contingencia) activarContingenciaIARecepcion(resultado.error);
        else establecerEstadoIARecepcion(`No se pudo analizar la factura: ${resultado.error || 'error'}`, 'error');
        return;
    }
    rellenarRecepcionDesdeFactura(resultado.data);
}

// ==========================================
// 5. GESTIÓN DE MODALES Y PRODUCTOS
// ==========================================
function openModal(type) {
    const modal = document.getElementById('recepcionModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('recepcionForm');
    
    if (type === 'registrar') {
        if (modalTitle) modalTitle.textContent = 'Nueva Recepción';
        if (form) form.reset();
        limpiarFacturaIARecepcion();
        const filas = document.querySelectorAll('#productosList .producto-row');
        filas.forEach((fila, indice) => { if (indice > 0) fila.remove(); });
        actualizarContadorProductos();
        calcularTotal();
    }
    if (modal) {
        modal.style.display = 'block';
        modal.style.animation = 'fadeIn 0.3s ease';
    }
}

function closeModal() {
    const modal = document.getElementById('recepcionModal');
    if (!modal) return;
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function addProductoRow() {
    const productosList = document.getElementById('productosList');
    if (!productosList) return null;
    const firstRow = productosList.querySelector('.producto-row');
    if (!firstRow) return null;

    const newRow = firstRow.cloneNode(true);
    newRow.style.animation = 'slideDown 0.3s ease';
    newRow.querySelector('.producto-select').value = '';
    newRow.querySelector('.producto-codigo').value = '';
    newRow.querySelector('.producto-marca').value = '';
    newRow.querySelector('.producto-modelo').value = '';
    newRow.querySelector('.producto-serial').value = '';
    newRow.querySelector('input[name="cantidad[]"]').value = '';
    newRow.querySelector('input[name="costo[]"]').value = '';
    newRow.querySelector('.subtotal-display').value = '$0.00';
    productosList.appendChild(newRow);
    actualizarContadorProductos();
    return newRow;
}

function seleccionarProducto(select) {
    const opcion = select.selectedOptions[0];
    const fila = select.closest('.producto-row');
    if (!fila) return;
    fila.querySelector('.producto-codigo').value = opcion?.dataset.codigo || '';
    fila.querySelector('.producto-marca').value = opcion?.dataset.marca || '';
    fila.querySelector('.producto-modelo').value = opcion?.dataset.modelo || '';
    fila.querySelector('.producto-serial').value = opcion?.dataset.serial || '';
    calcularSubtotal(select);
}

function calcularSubtotal(input) {
    const row = input.closest('.producto-row');
    if (!row) return;
    const cantidad = parseFloat(row.querySelector('input[name="cantidad[]"]').value) || 0;
    const costo = parseFloat(row.querySelector('input[name="costo[]"]').value) || 0;
    const subtotal = cantidad * costo;
    const display = row.querySelector('.subtotal-display');
    if (display) display.value = '$' + subtotal.toFixed(2);
    calcularTotal();
}

function calcularTotal() {
    const filas = document.querySelectorAll('#productosList .producto-row');
    const subtotal = Array.from(filas).reduce((suma, fila) => {
        const cantidad = Number(fila.querySelector('input[name="cantidad[]"]')?.value) || 0;
        const costo = Number(fila.querySelector('input[name="costo[]"]')?.value) || 0;
        return suma + cantidad * costo;
    }, 0);

    const porcentajeIva = Number(document.getElementById('porcentajeIvaRecepcion')?.value) || 0;
    const montoIva = Math.round((subtotal * (porcentajeIva / 100) + Number.EPSILON) * 100) / 100;

    if (document.getElementById('subtotalRecepcion')) document.getElementById('subtotalRecepcion').textContent = '$' + subtotal.toFixed(2);
    if (document.getElementById('montoIvaRecepcion')) document.getElementById('montoIvaRecepcion').textContent = '$' + montoIva.toFixed(2);
    if (document.getElementById('totalValue')) document.getElementById('totalValue').textContent = '$' + (subtotal + montoIva).toFixed(2);
}

function actualizarContadorProductos() {
    const count = document.querySelectorAll('.producto-row').length;
    const counterEl = document.querySelector('.product-count');
    if (counterEl) counterEl.textContent = count + ' producto' + (count !== 1 ? 's' : '');
}

function removeProductoRow(button) {
    const row = button.closest('.producto-row');
    if (document.querySelectorAll('.producto-row').length > 1) {
        row.style.animation = 'slideUp 0.3s ease';
        setTimeout(() => {
            row.remove();
            actualizarContadorProductos();
            calcularTotal();
        }, 300);
    } else {
        alert('Debe haber al menos un producto');
    }
}

// ==========================================
// 6. ACCIONES: GUARDAR, DETALLAR, ANULAR
// ==========================================
async function saveRecepcion(event) {
    if (event) event.preventDefault();

    const form = document.getElementById('recepcionForm');
    if (!validarEnvioRecepcion()) return;
    if (!verificaproductos()) return;

    const botonGuardar = document.querySelector('#recepcionModal .modal-footer .btn-save');
    if (botonGuardar) botonGuardar.disabled = true;
    
    try {
        const archivo = document.getElementById('fotoFacturaRecepcion')?.files[0];
        let modoContingencia = false;
        let iaVerificada = false;
        const aiClient = obtenerAsistenteIA();

        if (archivo) {
            if (!aiClient) {
                modoContingencia = true;
            } else if (!aiClient.getFacturaId()) {
                const extraccion = await aiClient.extraerDesdeImagen(archivo);
                if (!extraccion.exito) {
                    if (!extraccion.contingencia) throw new Error(extraccion.error || 'No se pudo analizar la factura.');
                    modoContingencia = true;
                } else {
                    rellenarRecepcionDesdeFactura(extraccion.data);
                }
            }

            if (!modoContingencia && aiClient) {
                establecerEstadoIARecepcion('Verificando los datos antes del registro...');
                const datosFormulario = {
                    numero_factura: document.getElementById('correlativo').value,
                    nombre_proveedor: document.querySelector('#proveedor option:checked').textContent,
                    productos: Array.from(document.querySelectorAll('#productosList .producto-row')).map(fila => ({
                        nombre: fila.querySelector('.producto-select option:checked').textContent,
                        modelo: fila.querySelector('.producto-modelo').value,
                        marca: fila.querySelector('.producto-marca').value,
                        serial: fila.querySelector('.producto-serial').value,
                        cantidad: Number(fila.querySelector('input[name="cantidad[]"]').value),
                        costo: Number(fila.querySelector('input[name="costo[]"]').value)
                    }))
                };
                const verificacion = await aiClient.verificarCoherencia(
                    aiClient.getFacturaId(), datosFormulario
                );
                if (verificacion.error) {
                    if (!verificacion.contingencia) throw new Error(verificacion.error);
                    modoContingencia = true;
                } else if (!verificacion.exito) {
                    const discrepancias = verificacion.discrepancias || [];
                    const detalle = discrepancias.map(item =>
                        `${item.campo}: factura "${item.valor_factura}" / formulario "${item.valor_formulario}"`
                    ).join('\n');
                    if (discrepancias.some(item => item.severidad === 'CRITICA')) {
                        establecerEstadoIARecepcion('Registro bloqueado: corrige las discrepancias críticas con la factura.', 'error');
                        alert(`La verificación encontró diferencias críticas. Corrige los datos antes de guardar.\n\n${detalle}`);
                        if (botonGuardar) botonGuardar.disabled = false;
                        return;
                    }
                    if (!confirm(`Se encontraron diferencias con la factura:\n\n${detalle}\n\n¿Deseas guardar de todos modos?`)) {
                        if (botonGuardar) botonGuardar.disabled = false;
                        return;
                    }
                    iaVerificada = true;
                } else {
                    iaVerificada = true;
                }
            }

            if (modoContingencia) {
                activarContingenciaIARecepcion();
                if (!confirm('El servicio de lectura automática por IA no está disponible temporalmente. Se ha habilitado el formulario manual. La factura se conservará al registrar. ¿Deseas continuar con el registro manual?')) {
                    if (botonGuardar) botonGuardar.disabled = false;
                    return;
                }
            }
        }

        const formData = new FormData(form);
        formData.delete('foto_factura');
        formData.append('accion', 'registrar');
        if (archivo) formData.append('factura_contingencia', archivo, archivo.name);
        if (modoContingencia) formData.append('modo_contingencia', 'true');
        if (iaVerificada) formData.append('ia_verificada', 'true');

        const response = await fetch('?pagina=recepcion', { method: 'POST', body: formData });
        const data = await response.json();

        if (data && data.status === 'success') {
            document.getElementById('recepcionModal').style.display = 'none';
            
            if ($.fn.DataTable.isDataTable('table.table-info')) {
                const tabla = $('table.table-info').DataTable();
                
                const rec = data.recepcion || {};
                const rawFecha = rec.fecha || rec.fecha_recepcion || new Date().toISOString().split('T')[0];
                const fechaRaw = typeof rawFecha === 'object' ? (rawFecha.date || String(rawFecha)) : String(rawFecha);
                const nuevaFecha = formatearFecha(fechaRaw);
                const correlativoNum = String(rec.correlativo || document.getElementById('correlativo').value || '');
                const proveedorNombre = String(rec.nombre_proveedor || $('#proveedor option:checked').text() || '');
                const totalMontoStr = document.getElementById('totalValue')?.textContent || '$0.00';
                
                const $nuevaFila =$(`<tr>
                    <td data-order="${fechaRaw}">${nuevaFecha}</td>
                    <td>${correlativoNum}</td>
                    <td>${proveedorNombre}</td>
                    <td>${totalMontoStr}</td>
                    <td class="action-buttons">
                        <button class="btn-action btn-detallar" title="Ver Detalles" data-correlativo="${correlativoNum}" data-proveedor="${proveedorNombre}" data-fecha="${fechaRaw}" onclick="viewRecepcion(this)"><img src="assets/img/eye.svg"></button>
                        <button class="btn-action btn-anular" title="Anular Recepción" onclick="anularRecepcion('${correlativoNum}')"><img src="assets/img/circle-x.svg"></button>
                    </td>
                </tr>`);

                tabla.row.add($nuevaFila).draw(false);
            }

            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: data.message || 'Recepción registrada correctamente.',
                showConfirmButton: true,
            });
        } else {
            await Swal.fire({
                icon: 'error',
                title: 'Error al registrar recepción',
                text: data.message || 'Error desconocido',
                showConfirmButton: true,
            });
        }
    } catch (error) {
        console.error('Error al guardar recepción:', error);
        establecerEstadoIARecepcion(error.message || 'Error al guardar recepción.', 'error');
        await Swal.fire({
            icon: 'error',
            title: 'Error al registrar recepción',
            text: error.message || 'Por favor, intente nuevamente.',
            showConfirmButton: true
        });
    } finally {
        if (botonGuardar) botonGuardar.disabled = false;
    }
}

function viewRecepcion(boton) {
    const datosFila = boton instanceof HTMLElement ? boton.dataset : {};
    const correlativo = datosFila.correlativo || boton;
    const proveedorFila = datosFila.proveedor || '';
    const fechaFila = datosFila.fecha || '';

    function actualizarInformacionGeneral(recepcion = {}) {
        if (document.getElementById('viewCorrelativo')) document.getElementById('viewCorrelativo').textContent = recepcion.correlativo || correlativo || 'N/A';
        if (document.getElementById('viewProveedor')) document.getElementById('viewProveedor').textContent = proveedorFila || recepcion.nombre_proveedor || 'No disponible';
        if (document.getElementById('viewFecha')) document.getElementById('viewFecha').textContent = fechaFila || recepcion.fecha || 'No disponible';
    }

    actualizarInformacionGeneral();

    fetch('?pagina=recepcion', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'accion=obtener_detalle_recepcion&correlativo=' + encodeURIComponent(correlativo)
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.status === 'success') {
            const recepcion = data.recepcion;
            actualizarInformacionGeneral(recepcion);
            
            const productosTable = document.getElementById('viewProductos');
            if (productosTable) {
                productosTable.innerHTML = '';
                const productosRecepcion = recepcion.productos || data.productos || [];
                const subtotalProductos = productosRecepcion.reduce((suma, prod) => suma + (Number(prod.cantidad) || 0) * (Number(prod.costo) || 0), 0);
                
                productosRecepcion.forEach(prod => {
                    const cantidad = Number(prod.cantidad) || 0;
                    const costo = Number(prod.costo) || 0;
                    productosTable.innerHTML += `
                        <tr>
                            <td>${prod.producto || prod.nombre_producto || 'N/A'}</td>
                            <td>${cantidad}</td>
                            <td>$${costo.toFixed(2)}</td>
                            <td>$${(cantidad * costo).toFixed(2)}</td>
                        </tr>
                    `;
                });

                if (document.getElementById('viewSubtotal')) document.getElementById('viewSubtotal').textContent = '$' + subtotalProductos.toFixed(2);
                if (document.getElementById('viewTotal')) document.getElementById('viewTotal').textContent = '$' + (recepcion.total_factura ? Number(recepcion.total_factura).toFixed(2) : subtotalProductos.toFixed(2));
            }
        }
        const modal = document.getElementById('viewModal');
        if (modal) { modal.style.display = 'block'; modal.style.animation = 'fadeIn 0.3s ease'; }
    })
    .catch(() => {
        const modal = document.getElementById('viewModal');
        if (modal) { modal.style.display = 'block'; modal.style.animation = 'fadeIn 0.3s ease'; }
    });
}

function closeViewModal() {
    const modal = document.getElementById('viewModal');
    if (!modal) return;
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

function anularRecepcion(correlativo) {
    const anularCorr = document.getElementById('anularCorrelativo');
    if (anularCorr) anularCorr.textContent = correlativo;
    
    const modal = document.getElementById('anularModal');
    if (modal) {
        modal.style.display = 'block';
        modal.style.animation = 'fadeIn 0.3s ease';
    }
}

function closeAnularModal() {
    const modal = document.getElementById('anularModal');
    if (!modal) return;
    modal.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => { modal.style.display = 'none'; }, 300);
}

async function confirmarAnulacion() {
    const correlativo = document.getElementById('anularCorrelativo')?.textContent;
    if (!correlativo) return;

    const formData = new FormData();
    formData.append('accion', 'anular');
    formData.append('correlativo', correlativo);
    
    const botonConfirmar = document.querySelector('#anularModal .btn-danger');
    if (botonConfirmar) botonConfirmar.disabled = true;

    try {
        const response = await fetch('?pagina=recepcion', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data && data.status === 'success') {
            document.getElementById('anularModal').style.display = 'none';
            
            if ($.fn.DataTable.isDataTable('table.table-info')) {
                const tabla = $('table.table-info').DataTable();
                tabla.rows().every(function() {
                    let $row =$(this.node());
                    let rowCorrelativo = $row.find('td:eq(1)').text().trim();
                    if (rowCorrelativo === correlativo) {
                        tabla.row(this).remove();
                    }
                });
                tabla.draw(false);
            }

            await Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: data.message || 'La recepción se anuló correctamente.',
                confirmButtonText: 'OK'
            });
        } else {
            await Swal.fire({
                icon: 'error',
                title: 'Error al anular recepción',
                text: data.message || 'Error desconocido',
                confirmButtonText: 'Entendido'
            });
        }
    } catch (error) {
        console.error('Error al anular recepción:', error);
        await Swal.fire({
            icon: 'error',
            title: 'Error al anular recepción',
            text: error.message || 'Por favor, intente nuevamente.',
            confirmButtonText: 'Entendido'
        });
    } finally {
        if (botonConfirmar) botonConfirmar.disabled = false;
    }
}

// ==========================================
// 7. INICIALIZACIÓN DOCUMENT READY
// ==========================================
$(document).ready(function() {
    // Inicialización de la tabla
    var $tabla =$('table.table-info, table[data-datatable]');
    if ($tabla.length && !$.fn.DataTable.isDataTable($tabla)) {$tabla.DataTable({
            language: { url: 'assets/public/js/es-ES.json' },
            scrollX: true,
            scrollCollapse: true,
            order: [[0, 'desc']],
            initComplete: function () {
                var api = this.api();
                var $wrapper =$(api.table().container());
                api.columns.adjust();

                var $filter =$wrapper.find('.dataTables_filter');
                if ($filter.length) {
                    $filter.css({                         display: 'flex',                         'align-items': 'center',                         'justify-content': 'flex-end',                         gap: '10px'                     });$filter.find('label').css({ 'margin-bottom': '0' });
                }
            }
        });
    }

    protegerSelects(['proveedor']);

    $("#correlativo").on("keypress", function(e){
        validarkeypress(/^[0-9]*$/, e);
        let correlativo = document.getElementById("correlativo");
        correlativo.value = space(correlativo.value);
    });

    $("#correlativo").on("keyup", function(){
        validarkeyup(/^[0-9]{6}$/, $(this),$("#scorrelativo"), "*Formato válido: 6 dígitos numéricos*");
    });

    $("#proveedor").on("change", function() {
        if ($(this).val()) {$(this).removeClass("is-invalid").addClass("is-valid");
            $("#sproveedor").text("");
        } else {
            $(this).removeClass("is-valid").addClass("is-invalid");
            $("#sproveedor").text("*Debe seleccionar un proveedor*");
        }
    });

    const archivoFactura = document.getElementById('fotoFacturaRecepcion');
    if (archivoFactura) {
        archivoFactura.addEventListener('change', async function() {
            limpiarFacturaIARecepcion();
            const archivo = this.files[0];
            if (!archivo) return;
            if (archivo.size > 5 * 1024 * 1024) {
                this.value = '';
                establecerEstadoIARecepcion('El archivo debe ser menor a 5 MB.', 'error');
                return;
            }
            urlPreviewFacturaRecepcion = URL.createObjectURL(archivo);
            const contenedorPreview = document.getElementById('previewFacturaRecepcion');
            const previewImagen = document.getElementById('previewImagenRecepcion');
            const previewPdf = document.getElementById('previewPdfRecepcion');
            if (contenedorPreview) contenedorPreview.hidden = false;

            if (archivo.type === 'application/pdf' || archivo.name.toLowerCase().endsWith('.pdf')) {
                if (previewPdf) { previewPdf.src = urlPreviewFacturaRecepcion; previewPdf.hidden = false; }
            } else if (archivo.type.startsWith('image/')) {
                if (previewImagen) { previewImagen.src = urlPreviewFacturaRecepcion; previewImagen.hidden = false; }
            }
            await analizarFacturaRecepcion(archivo);
        });
    }

    window.addEventListener('click', function(event) {
        if (event.target === document.getElementById('recepcionModal')) closeModal();
        if (event.target === document.getElementById('viewModal')) closeViewModal();
        if (event.target === document.getElementById('anularModal')) closeAnularModal();
    });

    actualizarContadorProductos();
});