<?php $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 21;

if ((isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) || $_SESSION['nombre_rol'] == 'SuperUsuario') { ?>

<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <style>
        .preview-comprobante {
            margin-top: 10px;
            text-align: center;
            display: none;
        }
        .preview-comprobante img {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 3px;
            padding: 3px;
        }
        /* ...otros estilos... */
        #modalp .modal-content {
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            background: #f8fafc;
        }
        #modalp .modal-header {
            background: #e0e4eaff;
            color: #fff;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        #modalp .modal-header .close-2 {
            color: #fff;
            opacity: 1;
            font-size: 1.5rem;
        }
        #modalp .tablaConsultas {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        #modalp .tablaConsultas th {
            background: #e9ecef;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        #modalp .tablaConsultas td, #modalp .tablaConsultas th {
            padding: 10px 8px;
            text-align: center;
            vertical-align: middle;
        }
        #modalp .tablaConsultas tr.agregado {
            background: #d1e7dd !important;
            color: #155724;
            font-weight: bold;
            cursor: not-allowed;
        }
        #modalp .tablaConsultas tr:hover:not(.agregado) {
            background: #f1f3f5;
            cursor: pointer;
        }
        #modalp .btn-agregar-prod {
            background: #198754;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 1rem;
            transition: background 0.2s;
        }
        #modalp .btn-agregar-prod:disabled {
            background: #adb5bd;
            color: #fff;
            cursor: not-allowed;
        }
    </style>
    <title>Gestionar Ventas Presenciales</title>
</head>

<?php include 'newnavbar.php'; ?>
<div id="tasa" hidden><?php echo $data['monitors']['bcv']['price'];?></div>
<body class="fondo" style=" height: 100vh; background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<!-- ...código anterior... -->
<div class="modal fade modal-registrar" id="registrarCompraFisicaModal" tabindex="-1" role="dialog" 
aria-labelledby="registrarCompraFisicaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="f" method="POST" enctype="multipart/form-data" onsubmit="return validarFormularioCompra()">
                <div class="modal-header">
                    <h5 class="titulo-form" id="registrarCompraFisicaModalLabel">Incluir Venta Presencial</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="registrar">
                    
                    <div class="envolver-form">
                        <label for="buscarCliente">Cliente (Buscar por nombre o cédula)</label>
                        <div class="input-group">
                            <input type="text" id="buscarCliente" placeholder="Escriba para buscar..." class="control-form" maxlength="50" autocomplete="off">
                            <button type="button" id="btnNuevoCliente" title="Registrar nuevo cliente">
                                <i class="fas fa-plus"></i> Nuevo
                            </button>
                        </div>
                        
                        <!-- Este será nuestro dropdown personalizado -->
                        <div id="clientesDropdown" class="custom-dropdown" style="display: none;">
                            <!-- Los resultados se cargarán aquí dinámicamente -->
                        </div>
                        
                        <!-- Campo oculto para almacenar el ID del cliente seleccionado -->
                        <input type="hidden" name="cliente" id="cliente_id">
                        
                        <!-- Mostrar información del cliente seleccionado -->
                        <div id="clienteSeleccionado" class="cliente-info" style="display: none; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <strong id="clienteNombre"></strong>
                            <br>
                            <small id="clienteCedula"></small>
                            <button type="button" id="btnCambiarCliente" class="btn-cambiar-cliente" title="Seleccionar otro cliente">
                                <svg viewBox="0 0 24 24" width="14" height="14">
                                    <path d="M19 8l-4 4h3c0 3.31-2.69 6-6 6-1.01 0-1.97-.25-2.8-.7l-1.46 1.46C8.97 19.54 10.43 20 12 20c4.42 0 8-3.58 8-8h3l-4-4zM6 12c0-3.31 2.69-6 6-6 1.01 0 1.97.25 2.8.7l1.46-1.46C15.03 4.46 13.57 4 12 4c-4.42 0-8 3.58-8 8H1l4 4 4-4H6z"/>
                                </svg>
                                Cambiar
                            </button>
                        </div>
                    </div>
                            
                    <div class="envolver-form">
                        <input class="" type="text" id="codigoproducto" name="codigoproducto" style="display:none"/>
                        <input class="" type="text" id="idproducto" name="idproducto" style="display:none"/>
                        <button type="button" class="boton-form" id="listado" name="listado">Lista de Productos</button>
                    </div>
                
                    <div class="row">
                        <div class="col">
                            <hr />
                        </div>
                    </div>
                
                    <div class="table-responsive card shadow">
                        <table class="tabla" id="tablarecepcion">
                            <thead class="">
                                <tr>
                                    <th>Acción</th>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>modelo</th>
                                    <th>Marca</th>
                                    <th>Precio en $</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody class="" id="recepcion1">
                            </tbody>
                        </table>
                    </div>
                    <hr>
                    <!-- Monto total y cambio -->

                    <!-- PAGOS DINÁMICOS -->
                    <div id="pagos-container">
                        <!-- Aquí se agregarán los bloques de pago dinámicamente -->
                    </div>
                    <div class="grupo-form">
                        <div class="grupo-interno">
                            <button type="button" id="agregarPago" class="btn btn-secondary" style="margin-top: 25px;">Agregar otro pago</button>
                        </div>
                        <div class="grupo-interno">
                            <label><strong>TOTAL DE LA COMPRA EN BS:</strong></label>
                            <input class="control-form" id="totalCompra" name="totalCompra" readonly style="cursor: default; font-weight: bold; font-size: 1.2rem;">
                        </div>
                    </div>
                </div>
                <div class="grupo-form" style="margin-bottom: 5px;">
                    <div class="grupo-interno">
                        <label for="monto_total" class="form-label">Monto total Cancelado</label>
                        <input type="text" id="monto_total" name="monto_total" class="form-control" value="0.00" style="cursor: default;" readonly>
                    </div>
                    <div class="grupo-interno">
                        <label for="monto_faltante" class="form-label">Monto Faltante</label>
                        <input type="text" id="monto_faltante" name="monto_faltante" class="form-control" value="0.00" style="cursor: default;" readonly>
                    </div>
                    <div class="grupo-interno">
                        <label for="cambio_efectivo" class="form-label">Cambio</label>
                        <input type="text" id="cambio_efectivo" name="cambio_efectivo" class="form-control" value="0.00" style="cursor: default;" readonly>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="boton-form" id="registrar" name="registrar">Registrar</button>
                    <button class="boton-reset" type="reset">Limpiar</button>
                </div>
            </form>
            <div class="modal fade" tabindex="-1" role="dialog" id="modalp">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="titulo-form">Listado de productos</h5>
                        <button type="button" class="close-2" data-bs-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                    </div>
                    <div class="modal-body">
                        <table class="tablaConsultas">
                        <thead class="text-center">
                            <tr>
                            <th style="display:none">Id</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>modelo</th>
                            <th>Marca</th>
                            <th>Precio</th>
                            </tr>
                        </thead>
                        <tbody class="text-center" id="listadop">
                        </tbody>
                        </table>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="contenedor-tabla">
    <div class="tabla-header">
        <div class="ghost"></div>
        <h3>Ventas Presenciales</h3>
        <div class="space-btn-incluir">
            <button id="btnIncluirDespacho" class="btn-incluir" title="Incluir Compra Física">
                <img src="img/plus.svg">
            </button>
        </div>
    </div>
    
    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>CLIENTE / CÉDULA</th>
                <th>MONTO TOTAL</th>
                <th>ACCIÓN</th>
            </tr>
        </thead>
<?php 
foreach ($compras as &$compra) {
    // Convertir productos y pagos a arrays
    $compra['productos'] = !empty($compra['productos'])
        ? json_decode('[' . $compra['productos'] . ']', true)
        : [];

    $compra['pagos'] = !empty($compra['pagos'])
        ? json_decode('[' . $compra['pagos'] . ']', true)
        : [];

    // 🔹 AGRUPAR PRODUCTOS REPETIDOS
    $agrupadosProductos = [];
    foreach ($compra['productos'] as $producto) {
        $key = $producto['id_producto'] . '-' . $producto['precio'];
        if (!isset($agrupadosProductos[$key])) {
            $agrupadosProductos[$key] = $producto;
        } 
    }
    $compra['productos'] = array_values($agrupadosProductos);

    // 🔹 AGRUPAR PAGOS REPETIDOS
    $agrupadosPagos = [];
    foreach ($compra['pagos'] as $pago) {
        // Usamos referencia + cuenta + monto como clave única
        $keyPago = $pago['referencia'] . '-' . $pago['cuenta'] . '-' . $pago['monto'];
        if (!isset($agrupadosPagos[$keyPago])) {
            $agrupadosPagos[$keyPago] = $pago;
        } 
    }
    $compra['pagos'] = array_values($agrupadosPagos);
}
unset($compra);

?>

<tbody>
<?php if (!empty($compras)): ?>
    <?php foreach ($compras as $compra): ?>
        <?php
            $montoTotal = 0;
            foreach ($compra['productos'] as $prod) {
                $montoTotal += $prod['precio'] * $prod['cantidad'];
            }
        ?>
        <tr>
            <td>
                <span class="campo-numeros">
                    <?= date('d/m/Y', strtotime($compra['fecha_factura'])) ?>
                </span>
            </td>
            <td>
                <span class="campo-nombres">
                    <?= htmlspecialchars($compra['nombre_cliente']) ?>
                </span>
                <span class="campo-numeros">
                    (<?= htmlspecialchars($compra['cedula']) ?>)
                </span>
            </td>
            <td>
                <span class="campo-numeros">
                    <?= number_format($montoTotal, 2) ?>
                </span>
            </td>
            <td>
                <ul>
                    <button
                        class="btn-detalle"
                        title="Ver Detalles"
                        data-id="<?= htmlspecialchars($compra['id_factura']) ?>"
                        data-productos='<?= json_encode($compra['productos'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
                        data-pagos='<?= json_encode($compra['pagos'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
                        data-cliente="<?= htmlspecialchars($compra['nombre_cliente']) ?>"
                        data-cedula="<?= htmlspecialchars($compra['cedula']) ?>"
                        data-telefono="<?= htmlspecialchars($compra['telefono'] ?? '') ?>"  
                        data-correo="<?= htmlspecialchars($compra['correo'] ?? '') ?>"
                        data-fecha="<?= htmlspecialchars($compra['fecha_factura']) ?>">
                        <img src="img/eye.svg">
                    </button>
                </ul>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="4" class="text-center">No hay registros de compras</td></tr>
<?php endif; ?>
</tbody>
    </table>
</div>

<div id="modalDetallesVenta" class="modal-detalles" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="titulo-form" id="modalDetallesLabel">Detalles de la Venta</h5>
                <button type="button" class="close" id="cerrarModalDetallesVenta">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group fila-dato">
                    <label>Fecha de la compra:</label>
                    <p id="detalleFecha"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Cliente/Cédula:</label>
                    <p id="detalleCliente"></p> <p id="detalleCedula"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Teléfono:</label>
                    <p id="detalleTelefono"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Correo:</label>
                    <p id="detalleCorreo"></p>
                </div>
                
                <h6 class="subtitle">Productos</h6>
                <div class="table-responsive">
                    <table class="tablaConsultas">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Modelo</th>
                                <th>Marca</th>
                                <th>Serial</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="detalleProductos"></tbody>
                    </table>
                </div>
                <h6 class="subtitle">Pagos</h6>
                <div class="table-responsive">
                    <table class="tablaConsultas">
                        <thead>
                            <tr>
                                <th>Comprobante</th>
                                <th>Tipo de Compra</th>
                                <th>Referencia</th>
                                <th>Monto</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody id="detallePagos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalDetallesVenta(datosVenta) {
    try {
        // === Datos del cliente ===
        document.getElementById("detalleFecha").textContent = datosVenta.fecha || '';
        document.getElementById("detalleCliente").textContent = datosVenta.cliente || '';
        document.getElementById("detalleCedula").textContent = datosVenta.cedula || '';
        document.getElementById("detalleTelefono").textContent = datosVenta.telefono || '';
        document.getElementById("detalleCorreo").textContent = datosVenta.correo || '';

        // === Tabla de productos ===
        const tbodyProductos = document.getElementById("detalleProductos");
        tbodyProductos.innerHTML = "";
        let totalGeneral = 0;
        if (Array.isArray(datosVenta.productos)) {
            datosVenta.productos.forEach(prod => {
                const total = (parseFloat(prod.precio) * parseFloat(prod.cantidad)).toFixed(2);
                totalGeneral += parseFloat(total);
                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td><span class="campo-numeros">${prod.codigo || ''}</span></td>
                    <td><span class="campo-nombres">${prod.nombre || ''}</span></td>
                    <td><span class="campo-nombres">${prod.modelo || ''}</span></td>
                    <td><span class="campo-nombres">${prod.marca || ''}</span></td>
                    <td><span class="campo-numeros">${prod.serial || ''}</span></td>
                    <td><span class="campo-numeros">${prod.cantidad || ''}</span></td>
                    <td><span class="campo-numeros">${prod.precio || ''}</span></td>
                    <td><span class="campo-tex-num">${total}</span></td>
                `;
                tbodyProductos.appendChild(fila);
            });
        }

        // === Tabla de pagos ===
        const tbodyPagos = document.getElementById("detallePagos");
        tbodyPagos.innerHTML = "";
        if (Array.isArray(datosVenta.pagos)) {
            datosVenta.pagos.forEach(pago => {
                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td>
                        <img src="${ pago.comprobante}" alt="comprobante" style="width:60px; height:auto; border-radius:5px; cursor:pointer;"
                        onclick="verComprobanteAmpliado('${pago.comprobante}')">
                    </td>
                    <td><span class="campo-nombres">${pago.tipo || ''}</span></td>
                    <td><span class="campo-numeros">${pago.referencia || ''}</span></td>
                    <td><span class="campo-numeros">${pago.monto || ''}</span></td>
                    <td><span class="campo-rango">${pago.estatus || ''}</span></td>
                `;
                tbodyPagos.appendChild(fila);
            });
        }

        // === Mostrar modal personalizado ===
        document.getElementById('modalDetallesVenta').classList.add('mostrar');
        document.body.classList.add('modal-open'); // Si tienes estilos para fondo gris

    } catch (error) {
        console.error("Error al abrir modal:", error);
    }
}

// Mostrar comprobante ampliado
function verComprobanteAmpliado(src) {
    const ventana = window.open("", "Comprobante", "width=600,height=600");
    ventana.document.write(`<img src="${src}" style="width:100%; height:auto;">`);
}

// Evento para abrir el modal de detalles
$(document).on('click', '.btn-detalle', function() {
    const datosVenta = {
        id: $(this).data('id'),
        productos: $(this).data('productos') || [],
        tipo: $(this).data('tipo') || '',
        pagos: $(this).data('pagos') || [],
        cliente: $(this).data('cliente') || '',
        cedula: $(this).data('cedula') || '',
        telefono: $(this).data('telefono') || '',
        correo: $(this).data('correo') || '',
        fecha: $(this).data('fecha') || ''
    };
    abrirModalDetallesVenta(datosVenta);
});

// Evento para cerrar el modal
$('#cerrarModalDetallesVenta').on('click', function () {
    $('#modalDetallesVenta').removeClass('mostrar');
    document.body.classList.remove('modal-open');
});

// Cierre al hacer clic fuera del contenido del modal
$(window).on('click', function (e) {
    const modal = document.getElementById('modalDetallesVenta');
    if (e.target === modal) {
        $(modal).removeClass('mostrar');
        document.body.classList.remove('modal-open');
    }
});
</script>

<!-- Scripts para gráfica y PDF -->

	<script>
const productosDisponibles = <?= json_encode(array_map(function($prod) {
    return [
        'id_producto' => $prod['id_producto'],
        'nombre_producto' => $prod['nombre_producto'],
        'precio' => $prod['precio'],
        ];
}, $productos)) ?>;

// Función para crear un nuevo bloque vacío de producto
function crearBloqueProducto(productosDisponibles) {
    return `
        <div class="row mb-2 grupo-producto">
            <div class="col-md-5">
                <label>Producto</label>
                <select class="form-control" name="productos[]">
                    ${productosDisponibles.map(prod => `
                        <option value="${prod.id_producto}">${prod.nombre}</option>
                    `).join('')}
                </select>
            </div>
            <div class="col-md-3">
                <label>Cantidad</label>
                <input type="number" class="form-control" name="cantidades[]" value="1" min="1">
            </div>
            <div class="col-md-2">

                <input type="hidden" name="iddetalles[]" value="">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-eliminar-producto">Eliminar Producto</button>
            </div>
        </div>
    `;
}

// Evento para agregar un nuevo producto al modal
$(document).on('click', '#btnAgregarProducto', function () {
    $('#contenedorDetalles').append(crearBloqueProducto(productosDisponibles));
});

// Evento para eliminar el bloque correspondiente
$(document).on('click', '.btn-eliminar-producto', function () {
    $(this).closest('.grupo-producto').remove();
});
</script>


    <button 
        class="btn-grafica"
        title="Visualizar Reportes"
        onclick="window.location.href='?pagina=reporteVentas'">
        <img src="img/grafic.png" alt="Reportes" width="30" height="30">
    
    </button>
</body>



<?php include 'footer.php'; ?>

<!-- jQuery primero -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="javascript/comprafisica.js"></script>
<script src="public/js/chart.js"></script>
<script src="public/js/html2canvas.min.js"></script>
<script src="public/js/jspdf.umd.min.js"></script>
<script src="public/js/jquery-3.7.1.min.js"></script>
<script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/js/jquery.dataTables.min.js"></script>
<script src="public/js/dataTables.bootstrap5.min.js"></script>
<script src="public/js/datatable.js"></script>
<script src="javascript/validaciones.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const cuentas = <?php echo json_encode($listadocuentas); ?>;
const todosMetodos = ['Pago Movil', 'Transferencia', 'Efectivo', 'Efectivo en $', 'Zelle'];
 // Lista de todos los métodos posibles

// Función para filtrar cuentas por método de pago
function filtrarCuentasPorMetodo(metodo) {
    return cuentas.filter(cuenta => {
        const metodosCuenta = cuenta.metodos ? cuenta.metodos.split(',') : [];
        return metodosCuenta.includes(metodo);
    });
}

// Función para crear un bloque de pago
function crearBloquePago(idx) {
    return `
    <div class="bloque-pago" style="border:1px solid #ccc; padding:15px; margin-bottom:15px; border-radius:8px; position:relative;">
        <button type="button" class="btn btn-danger btn-sm btn-quitar-pago" style="z-index: 9999;position:absolute;top:8px;right:8px;display:${idx==0?'none':'inline-block'};">Quitar</button>

        <!-- Primero seleccionar el tipo de pago -->
        <div class="envolver-form">
            <label for="tipo_${idx}">Tipo de pago</label>
            <select class="form-select tipo-pago" name="pagos[${idx}][tipo]" id="tipo_${idx}" required>
                <option value="" disabled selected>Seleccione</option>
                ${todosMetodos.map(m => `<option value="${m}">${m}</option>`).join('')}
            </select>
            <span class="span-value" id="stipopago"></span>
        </div>

        <!-- Campos específicos del pago -->
        <div class="campos-pago" id="campos_pago_${idx}">
            <!-- Aquí se insertan los campos según el tipo -->
        </div>
    </div>
    `;
}

function camposPorTipo(tipo, idx) {
    let montoField = `
        <input type="text" class="control-form monto-input" 
       name="pagos[${idx}][monto]" id="monto_${idx}" required>
        <span class="span-value bolivares-conversion" id="bolivares_${idx}" style="font-weight:bold;color:#0d6efd;"></span>
    `;

    if (tipo === "Pago Movil") {
        return `
            <div class="envolver-form">
                <label for="cuenta_${idx}">Cuenta</label>
                <select class="form-select cuenta-pago" name="pagos[${idx}][cuenta]" id="cuenta_${idx}" required disabled>
                    <option value="" disabled selected>Seleccione</option>
                </select>
            </div>
            <div class="envolver-form">
                <label for="referencia_${idx}">Referencia</label>
                <input type="text" class="control-form" name="pagos[${idx}][referencia]" id="referencia_${idx}" maxlength="15" required>
            </div>
            <div class="envolver-form">
                <label for="comprobante_${idx}">Comprobante (imagen)</label>
                <input type="file" class="control-form comprobante-pago" name="pagos[${idx}][comprobante]" id="comprobante_${idx}" accept="image/*" required>
            </div>
            <div class="envolver-form">
                <label for="monto_${idx}">Monto Recibido (Bs)</label>
                ${montoField}
            </div>
        `;
    } 
    else if (tipo === "Transferencia") {
        return `
            <div class="envolver-form">
                <label for="cuenta_${idx}">Cuenta</label>
                <select class="form-select cuenta-pago" name="pagos[${idx}][cuenta]" id="cuenta_${idx}" required disabled>
                    <option value="" disabled selected>Seleccione</option>
                </select>
            </div>
            <div class="envolver-form">
                <label for="referencia_${idx}">Referencia</label>
                <input type="text" class="control-form" name="pagos[${idx}][referencia]" id="referencia_${idx}" maxlength="15" required>
            </div>
            <div class="envolver-form">
                <label for="comprobante_${idx}">Comprobante (imagen)</label>
                <input type="file" class="control-form comprobante-pago" name="pagos[${idx}][comprobante]" id="comprobante_${idx}" accept="image/*" required>
            </div>
            <div class="envolver-form">
                <label for="monto_${idx}">Monto Recibido (Bs)</label>
                ${montoField}
            </div>
        `;
    }
    else if (tipo === "Efectivo en $") {
        return `
            <div class="envolver-form">
                <label for="monto_${idx}">Monto Recibido ($)</label>
                <input type="text" class="control-form monto-input-dolar" 
                     name="pagos[${idx}][monto_dolar]" id="monto_${idx}" required>
                <span class="span-value bolivares-conversion" id="bolivares_${idx}" style="font-weight:bold;color:#0d6efd;"></span>
                <input type="hidden" name="pagos[${idx}][monto]" id="monto_bs_${idx}">
                <small class="text-muted">Se convertirá automáticamente a Bs con la tasa del día.</small>
                <input name="pagos[${idx}][comprobante]" id="comprobante_${idx}" hidden value="/comprobantes/dolar.png">
            </div>
            </div>
        `;
    }
    else if (tipo === "Zelle") {
        return `
                    <div class="envolver-form">
                <label for="cuenta_${idx}">Cuenta</label>
                <select class="form-select cuenta-pago" name="pagos[${idx}][cuenta]" id="cuenta_${idx}" required disabled>
                    <option value="" disabled selected>Seleccione</option>
                </select>
            </div>
            <div class="envolver-form">
                <label for="referencia_${idx}">Propietario del Zelle</label>
                <input type="text" class="control-form" name="pagos[${idx}][descripcion]" id="referencia_${idx}" placeholder="Nombre del Propietario" required>
            </div>
            <div class="envolver-form">
                <label for="monto_${idx}">Monto Recibido ($)</label>
                <input type="number" step="0.01" class="control-form monto-input-dolar" min="0" 
                    name="pagos[${idx}][monto_dolar]" id="monto_${idx}" required>
                <span class="span-value bolivares-conversion" id="bolivares_${idx}" style="font-weight:bold;color:#0d6efd;"></span>
                <input type="hidden" name="pagos[${idx}][monto]" id="monto_bs_${idx}">
                <small class="text-muted">Se convertirá automáticamente a Bs con la tasa del día.</small>
            </div>
            <div class="envolver-form">
                <label for="referencia_${idx}">Referencia</label>
                <input type="text" class="control-form" name="pagos[${idx}][referencia]" id="referencia_${idx}" maxlength="15" placeholder="Referencia del Zelle" required>
            </div>
            <div class="envolver-form">
                <label for="comprobante_${idx}">Comprobante (imagen)</label>
                <input type="file" class="control-form comprobante-pago" name="pagos[${idx}][comprobante]" id="comprobante_${idx}" accept="image/*" required>
            </div>
        `;
    }
    else if (tipo === "Efectivo") {
        return `
            <div class="envolver-form">
                <label for="monto_${idx}">Monto Recibido (Bs)</label>
                ${montoField}

            </div>
                       <div class="envolver-form">
    <label for="comprobante_${idx}">Comprobante (imagen)</label>

    <!-- Imagen por defecto -->
    <img src="uploads/comprobantes/bolivar.png" 
         alt="Comprobante por defecto" 
         id="preview_${idx}" 
         style="max-width:150px; display:block;">

    <!-- Input file oculto -->
    <input  
           hidden 
           class="control-form comprobante-pago" 
           name="pagos[${idx}][comprobante]" 
           id="comprobante_${idx}" 
           accept="image/*" 
           value="uploads/comprobantes/bolivar.png"
           required>
</div>

        `;
    }
}

// Obtener la tasa
const tasa = parseFloat(document.getElementById('tasa').textContent) || 0;

// Evento para actualizar la conversión a bolívares
$(document).on('input', '.monto-input-dolar', function () {
    const idx = $(this).attr('id').split('_')[1];
    const montoDolar = parseFloat($(this).val()) || 0;
    const montoBs = (montoDolar * tasa).toFixed(2);

    // Mostrar conversión
    $('#bolivares_' + idx).text('Equivalente: Bs. ' + montoBs);

    // Guardar el valor convertido en el input hidden
    $('#monto_bs_' + idx).val(montoBs);
});

// 🔹 Función para bloquear signos + y - en montos
function validarTeclaMonto(e) {
    // Códigos de teclas para + y -
    if (e.key === '+' || e.key === '-' || e.keyCode === 187 || e.keyCode === 189) {
        return false; // Bloquea la entrada
    }
    return true; // Permite las demás teclas
}


// Inicializar el formulario con un pago
let pagosCount = 0;
function agregarPagoBloque() {
    $('#pagos-container').append(crearBloquePago(pagosCount));
    pagosCount++;
}
agregarPagoBloque();

// Evento para agregar otro pago
$('#agregarPago').on('click', function() {
    agregarPagoBloque();
});

// Evento para quitar un bloque de pago
$(document).on('click', '.btn-quitar-pago', function() {
    $(this).closest('.bloque-pago').remove();
});

// Evento al cambiar el tipo de pago: actualizar cuentas disponibles y mostrar campos
// Evento al cambiar el tipo de pago: actualizar cuentas disponibles y mostrar campos
$(document).on('change', '.tipo-pago', function() {
    const idx = $(this).attr('id').split('_')[1];
    const tipoSeleccionado = $(this).val();

    // Mostrar campos específicos para este tipo de pago
    $(`#campos_pago_${idx}`).html(camposPorTipo(tipoSeleccionado, idx));

    // Buscar el select que acaba de insertarse
    const $cuentaSelect = $(`#cuenta_${idx}`);

    if (tipoSeleccionado && $cuentaSelect.length) {
        // Filtrar cuentas que tienen este método de pago
        const cuentasFiltradas = filtrarCuentasPorMetodo(tipoSeleccionado);

        // Actualizar el select de cuentas
        $cuentaSelect.empty().prop('disabled', false);
        $cuentaSelect.append('<option value="" disabled selected>Seleccione una cuenta</option>');

        cuentasFiltradas.forEach(cuenta => {
            $cuentaSelect.append(
                `<option value="${cuenta.id_cuenta}">
                    ${cuenta.nombre_banco} - ${cuenta.numero_cuenta}
                </option>`
            );
        });
    }
});



// Evento para mostrar la vista previa de la imagen seleccionada
$(document).on('change', '.comprobante-pago', function (e) {
    const input = e.target;
    const idx = input.id.split('_')[1];
    const file = input.files[0];

    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function (ev) {
            $(`#preview_${idx} img`).attr('src', ev.target.result);
            $(`#preview_${idx}`).show();
        };
        reader.readAsDataURL(file);
    } else {
        $(`#preview_${idx}`).hide();
        $(`#preview_${idx} img`).attr('src', '');
    }
});

</script>
<script>
$(document).ready(function() {
    $('#tablaConsultas').DataTable({
        language: { url: 'public/js/es-ES.json' },
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [3] } // Desactiva ordenación en "DETALLES"
        ]
    });
});
</script>

<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>