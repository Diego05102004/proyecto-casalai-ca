
<?php
ob_start(); // Iniciar buffer de salida
require('public/fpdf/fpdf.php');

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
?>