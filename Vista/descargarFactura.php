<?php
ob_start(); // Iniciar buffer de salida
require('assets/public/fpdf/fpdf.php');

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, mb_convert_encoding('MULTISERVICIOS CASA LAI, C.A.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, mb_convert_encoding('CARRERA 32 ENTRE CALLES 32 Y 33 Nº 32-42 BARQUISIMETO ESTADO LARA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('04245483493, 04123661369, 04245483493, 04123661369.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 5, mb_convert_encoding('SERVICIO TÉCNICO A IMPRESORAS GARANTIZADO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 5, mb_convert_encoding('No se garantiza la disponibilidad de los productos una vez pasados los días después de ser creada la orden de compra', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $this->Cell(0, 10, mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') . $this->PageNo(), 0, 0, 'C');
    }
}

// Crear PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Datos del cliente
if (!empty($res)) {
    $cliente = $res[0]; // Los datos del cliente se repiten por fila

    $pdf->Cell(50, 5, mb_convert_encoding('CÓDIGO DE ORDEN DE COMPRA: ' . $cliente['id_factura'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('NOMBRE: ' . $cliente['nombre'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('C.I.: V' . $cliente['cedula'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('DIRECCIÓN: ' . $cliente['direccion'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Cell(50, 5, mb_convert_encoding('TELÉFONO: ' . $cliente['telefono'], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(5);
    $pdf->Cell(50, 5, mb_convert_encoding('FECHA DOCUMENTO: ' . $cliente["fecha"], 'ISO-8859-1', 'UTF-8'), 0, 1);
    $pdf->Ln(5);
}
/*
// Encabezado tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(130, 7, mb_convert_encoding('DESCRIPCIÓN', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20, 7, mb_convert_encoding('CANT.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(20, 7, mb_convert_encoding('PRECIO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(20, 7, mb_convert_encoding('TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Ln();
*/
$pdf->SetFont('Arial', '', 10);
$total_documento = 0;

// Calcular anchos dinámicos para precio y total
$ancho_precio_max = 25; // ancho mínimo
$ancho_total_max = 25; // ancho mínimo

foreach ($res as $item) {
    $precio_unitario = $item['precio_convertido'];
    $subtotal = $item['cantidad'] * $precio_unitario;
    
    $texto_precio = number_format($precio_unitario, 2) . ' BS';
    $texto_total = number_format($subtotal, 2) . ' BS';
    
    $ancho_precio = $pdf->GetStringWidth($texto_precio) + 10; // +10 para padding
    $ancho_total = $pdf->GetStringWidth($texto_total) + 10;
    
    if ($ancho_precio > $ancho_precio_max) {
        $ancho_precio_max = $ancho_precio;
    }
    if ($ancho_total > $ancho_total_max) {
        $ancho_total_max = $ancho_total;
    }
}

// Ajustar ancho de descripción (total 190 - cantidad(20) - precio - total)
$ancho_descripcion = 190 - 20 - $ancho_precio_max - $ancho_total_max;

// Dibujar encabezado con anchos calculados
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('DESCRIPCIÓN', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20, 7, mb_convert_encoding('CANT.', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell($ancho_precio_max, 7, mb_convert_encoding('PRECIO', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell($ancho_total_max, 7, mb_convert_encoding('TOTAL', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);

foreach ($res as $item) {
    $descripcion = $item['producto'] . ' ' . $item['nombre_modelo'] . ' ' . $item['nombre_marca'];
    $cantidad = $item['cantidad'];
    $precio_unitario = $item['precio_convertido'];
    $subtotal = $cantidad * $precio_unitario;

    $pdf->Cell($ancho_descripcion, 7, mb_convert_encoding($descripcion, 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell(20, 7, mb_convert_encoding($cantidad, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($ancho_precio_max, 7, mb_convert_encoding(number_format($precio_unitario, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($ancho_total_max, 7, mb_convert_encoding(number_format($subtotal, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Ln();

    $total_documento += $subtotal;
}

// Totales
$pdf->Ln(5);
$descuento = floatval($cliente['descuento']);
$iva = ($total_documento - $descuento) * 0.16;
$total_final = ($total_documento - $descuento) + $iva;

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('SUB-TOTAL', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20 + $ancho_precio_max + $ancho_total_max, 7, mb_convert_encoding(number_format($total_documento, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln();
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('DESCUENTO', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20 + $ancho_precio_max + $ancho_total_max, 7, mb_convert_encoding(number_format($descuento, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln();
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('DELIVERY', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20 + $ancho_precio_max + $ancho_total_max, 7, mb_convert_encoding('0.00 BS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln();
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('I.V.A 16%', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20 + $ancho_precio_max + $ancho_total_max, 7, mb_convert_encoding(number_format($iva, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln();
$pdf->Cell($ancho_descripcion, 7, mb_convert_encoding('TOTAL DOCUMENTO', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Cell(20 + $ancho_precio_max + $ancho_total_max, 7, mb_convert_encoding(number_format($total_final, 2) . ' BS', 'ISO-8859-1', 'UTF-8'), 1);
$pdf->Ln(10);

// Limpiar el buffer
ob_end_clean();

// Nombre del archivo
$nombre_archivo = $cliente['nombre'] . '_' . $cliente['cedula'] . '_factura_' . $cliente['id_factura'] . '_' . date('Y-m-d', strtotime($cliente['fecha'])) . '.pdf';

// Descargar el archivo
$pdf->Output('D', mb_convert_encoding($nombre_archivo, 'ISO-8859-1', 'UTF-8'));
exit;
?>
