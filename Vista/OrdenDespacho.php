<?php $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 14;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <title>Gestionar Orden de Despacho</title>
</head>

<body class="fondo" style=" height: 100vh; background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'newnavbar.php'; ?>

<!-- Modal para registrar orden de despacho -->
<div class="modal fade modal-registrar" id="registrarOrdenModal" tabindex="-1" role="dialog"
aria-labelledby="registrarOrdenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="ingresarOrdenDespacho" method="POST" novalidate>
                <div class="modal-header">
                    <h5 class="titulo-form" id="registrarOrdenModalLabel">Incluir Orden de Despacho</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="ingresar">
                    <div class="envolver-form">
                        <label for="correlativo">Correlativo</label>
                        <input type="text" class="control-form" id="correlativo" name="correlativo" placeholder="0123456789" maxlength="10">
                        <span class="span-value" id="scorrelativo"></span>
                    </div>

                    <div class="envolver-form">
                        <label for="factura">Orden de compra</label>
                        <select name="factura" id="factura" class="form-select">
                            <option value="" disabled selected>Seleccionar orden de compra</option>
                            <?php foreach ($facturas as $factura): ?>
                                <option value="<?php echo htmlspecialchars($factura['id_factura']); ?>">
                                    <?php echo htmlspecialchars('Orden de compra #'.$factura['id_factura'].' Cliente: '.$factura['nombre'].' Fecha '.$factura['fecha']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="span-value" id="sfactura"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="boton-form" type="submit">Registrar</button>
                    <button class="boton-reset" type="reset">Limpiar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="contenedor-tabla">

    <div class="tabla-header">
        <div class="ghost"></div>

        <h3>Lista de Orden <br>
        de Despacho</h3>
        
        <div class="ghost"></div>
    </div>

    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>N° de Orden <br> de despacho</th>
                <th>Código de orden <br> de compra</th>
                <th>Cliente</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($ordendespacho as $orden): ?>
            <tr data-id="<?php echo $orden['id_orden_despachos']; ?>">
                <td data-order="<?= $orden['fecha_despacho'] ?>">
                    <span class="campo-numeros">
                        <?= date('d/m/Y', strtotime($orden['fecha_despacho'])) ?>
                    </span>
                </td>
                <td>
                    <span class="campo-numeros">
                        <?php echo htmlspecialchars($orden['id_orden_despachos']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-numeros">
                        <?php echo htmlspecialchars($orden['id_factura']); ?>
                    </span>
                </td>
                <td>
                    <span class="campo-nombres">
                        <?= htmlspecialchars($orden['cliente']) ?>
                    </span>
                </td>
                <td>
                    <span class="campo-rango">
                        <?php echo htmlspecialchars($orden['estado']); ?>
                    </span>
                </td>
                <td>
                    <ul>
                        <button
                            class="btn-detalle"
                            title="Ver Detalles"
                            data-id="<?= htmlspecialchars($orden['id_factura']) ?>"
                            data-productos='<?= json_encode($orden['productos'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'
                            data-cliente="<?= htmlspecialchars($orden['cliente']) ?>"
                            data-cedula="<?= htmlspecialchars($orden['cedula']) ?>"
                            data-fecha="<?= htmlspecialchars($orden['fecha_despacho']) ?>">
                            <img src="img/eye.svg">
                        </button>
                        <?php if ($orden['estado'] !== 'Entregada'): ?>
                            <button
                                class="btn-marcar"
                                title="Marcar como Entregada">
                                <img src="img/check.svg">
                            </button>
                        <?php endif; ?>
                        <form method="post" name="DescargarOrdenDespacho">
                            <button class="btn-descargar" name="DescargarOrdenDespacho" type="submit"
                                title="Descargar Orden de Despacho" value="<?php echo $orden['id_orden_despachos']; ?>">
                                <img src="img/download.svg">
                            </button>
                        </form>
                        <button class="btn-anular"
                            title="Anular Orden de Despacho"
                            data-id-orden="<?= htmlspecialchars($orden['id_orden_despachos']) ?>">
                            <img src="img/circle-x.svg">
                        </button>
                    </ul>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="modalDetallesOrden" class="modal-detalles" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="titulo-form" id="modalDetallesLabel">Detalles de la Orden</h5>
                <button type="button" class="close" id="cerrarModalDetallesOrden">&times;</button>
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
                
                <h6 class="subtitle">Productos</h6>
                <div class="table-responsive">
                    <table class="tablaConsultas">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
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
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="public/bootstrap/js/sidebar.js"></script>
<script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/js/jquery-3.7.1.min.js"></script>
<script src="public/js/jquery.dataTables.min.js"></script>
<script src="public/js/dataTables.bootstrap5.min.js"></script>
<script src="public/js/datatable.js"></script>
<script src="javascript/sweetalert2.all.min.js"></script>
<script src="javascript/usuario.js"></script>
<script src="javascript/validaciones.js"></script>
<script>
    class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, utf8_decode('MULTISERVICIOS CASA LAI, C.A.'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('CARRERA 32 ENTRE CALLES 32 Y 33 Nº 32-42 BARQUISIMETO ESTADO LARA'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('04245483493, 04123661369, 04245483493, 04123661369.'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('SERVICIO TÉCNICO A IMPRESORAS GARANTIZADO'), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }
}

// Crear PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Datos del cliente
if (!empty($orden) && isset($orden[0])) {
    $ordenDespacho = $orden[0]; // Datos generales de la orden

    $pdf->Cell(50, 5, utf8_decode('CÓDIGO DE ORDEN DE DESPACHO: ' . $ordenDespacho['id_orden_despachos']), 0, 1);
    $pdf->Cell(50, 5, utf8_decode('NOMBRE: ' . ($ordenDespacho['cliente'] ?? '')), 0, 1);
    $pdf->Cell(50, 5, utf8_decode('C.I.: V' . ($ordenDespacho['cedula'] ?? '')), 0, 1);
    // Si tienes dirección y teléfono, agrégalos aquí
    // $pdf->Cell(50, 5, utf8_decode('DIRECCIÓN: ' . ($ordenDespacho['direccion'] ?? '')), 0, 1);
    // $pdf->Cell(50, 5, utf8_decode('TELÉFONO: ' . ($ordenDespacho['telefono'] ?? '')), 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(50, 5, utf8_decode('FECHA DOCUMENTO: ' . ($ordenDespacho["fecha_despacho"] ?? '')), 0, 1);
    $pdf->Ln(5);
}

// Encabezado tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(130, 7, utf8_decode('DESCRIPCIÓN'), 1);
$pdf->Cell(20, 7, utf8_decode('CANT.'), 1, 0, 'C');
$pdf->Cell(20, 7, utf8_decode('PRECIO'), 1, 0, 'C');
$pdf->Cell(20, 7, utf8_decode('TOTAL'), 1, 0, 'C');
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$total_documento = 0;

// Recorrer productos de la orden
if (!empty($orden) && isset($orden[0]['productos'])) {
    foreach ($orden[0]['productos'] as $item) {
        $descripcion = $item['producto'] . ' ' . $item['modelo'] . ' ' . $item['marca'];
        $cantidad = $item['cantidad'];
        $precio_unitario = $item['precio_unitario'];
        $subtotal = $item['subtotal'];

        $pdf->Cell(130, 7, utf8_decode($descripcion), 1);
        $pdf->Cell(20, 7, utf8_decode($cantidad), 1, 0, 'C');
        $pdf->Cell(20, 7, utf8_decode(number_format($precio_unitario, 2) . ' BS'), 1, 0, 'C');
        $pdf->Cell(20, 7, utf8_decode(number_format($subtotal, 2) . ' BS'), 1, 0, 'C');
        $pdf->Ln();

        $total_documento += $subtotal;
    }
}

// Totales
$pdf->Ln(5);
// Si tienes descuento y otros datos, agrégalos aquí
$descuento = 0; // Puedes obtenerlo de la orden si existe
$iva = ($total_documento - $descuento) * 0.16;
$total_final = ($total_documento - $descuento) + $iva;

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(130, 7, utf8_decode('SUB-TOTAL'), 1);
$pdf->Cell(60, 7, utf8_decode(number_format($total_documento, 2) . ' BS'), 1);
$pdf->Ln();
$pdf->Cell(130, 7, utf8_decode('DESCUENTO'), 1);
$pdf->Cell(60, 7, utf8_decode(number_format($descuento, 2) . ' BS'), 1);
$pdf->Ln();
$pdf->Cell(130, 7, utf8_decode('DELIVERY'), 1);
$pdf->Cell(60, 7, utf8_decode('0.00 BS'), 1);
$pdf->Ln();
$pdf->Cell(130, 7, utf8_decode('I.V.A 16%'), 1);
$pdf->Cell(60, 7, utf8_decode(number_format($iva, 2) . ' BS'), 1);
$pdf->Ln();
$pdf->Cell(130, 7, utf8_decode('TOTAL DOCUMENTO'), 1);
$pdf->Cell(60, 7, utf8_decode(number_format($total_final, 2) . ' BS'), 1);
$pdf->Ln(10);

// Limpiar el buffer
ob_end_clean();

// Nombre del archivo
$nombre_archivo = ($ordenDespacho['cliente'] ?? 'cliente') . '_' . ($ordenDespacho['cedula'] ?? '') . '_orden_' . ($ordenDespacho['id_orden_despachos'] ?? '') . '_' . date('Y-m-d', strtotime($ordenDespacho['fecha_despacho'] ?? date('Y-m-d'))) . '.pdf';

// Descargar el archivo
$pdf->Output('D', utf8_decode($nombre_archivo));
exit;
</script>
<script>
window.facturasDisponibles = <?php
echo json_encode(array_map(function($factura) {
    return [
        'id_factura' => $factura['id_factura'],
        'factura' => 'Orden de compra #'.$factura['id_factura'].' Cliente: '.$factura['nombre'].' Fecha '.$factura['fecha']
    ];
}, $facturas));
?>;
</script>
<script src="javascript/ordendespacho.js"></script>

<script>
$(document).ready(function() {
    $('#tablaConsultas').DataTable({
        language: {
            url: 'public/js/es-ES.json'
        },
        columnDefs: [
            {
                targets: 4, // Columna del estado
                render: function(data, type, row) {
                    if (type === 'sort') {
                        // "Por Entregar" se ordena primero (0), "Entregada" después (1)
                        return data === 'Por Entregar' ? 0 : 1;
                    }
                    return data;
                }
            }
        ],
        order: [
            [4, 'asc'], // Primero Por Entregar, luego Entregada
            [0, 'asc']  // Dentro del estado, fecha más vieja primero
        ]
    });
});
</script>

<!-- Botón para abrir el modal (puedes colocarlo donde prefieras) -->
<script>
$(document).ready(function() {
    $('#btnIncluirOrden').on('click', function() {
        $('#registrarOrdenModal').modal('show');
    });
});
</script>
    <button 
        class="btn-grafica"
        title="Visualizar Reportes"
        onclick="window.location.href='?pagina=reporteVentas'">
        <img src="img/grafic.png" alt="Reportes" width="30" height="30">
    
    </button>
</body>


</html>

<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>