<?php if ($_SESSION) {?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <title>Reportes de Inventario</title>
</head>

<body class="fondo" style="height: auto; min-height: 100vh; background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'newnavbar.php'; ?>

<!-- SOLO REPORTE DE RECEPCIÓN -->
<div class="report-container">
    <div class="report-header">
        <h2 class="titulo-form">Reportes de Recepción</h2>
        <p class="texto-p">Seleccione y genere los reportes que desea visualizar</p>
    </div>

    <!-- Selector de Reportes -->
    <div class="report-selector">
        <label for="selectReporteRecepcion" class="form-label"><strong>Seleccionar Reporte:</strong></label>
        <select id="selectReporteRecepcion" class="form-select">
            <option value="todos">Todos los Reportes</option>
            <option value="proveedores">Recepciones por Proveedor</option>
            <option value="productos">Productos más Recibidos</option>
            <option value="mensual">Recepciones Mensuales</option>
        </select>
    </div>

    <!-- Parámetros -->
    <div class="parameters-container">
        <h2 class="titulo-form">Parámetros del Reporte</h2>
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <label for="fechaInicioRecepcion" class="form-label">Fecha inicio:</label>
                <input type="date" id="fechaInicioRecepcion" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="fechaFinRecepcion" class="form-label">Fecha fin:</label>
                <input type="date" id="fechaFinRecepcion" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="tipoGraficaRecepcion" class="form-label">Tipo de gráfica:</label>
                <select id="tipoGraficaRecepcion" class="form-select">
                    <option value="bar">Barras</option>
                    <option value="pie">Pastel</option>
                    <option value="line">Líneas</option>
                    <option value="doughnut">Donas</option>
                    <option value="polarArea">Área Polar</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button id="generarReporteRecepcionBtn" class="btn btn-primary w-100">Generar Reporte</button>
            </div>
        </div>
    </div>

    <!-- Mensaje de error -->
    <div id="errorMessageRecepcion" class="error-message">
        No se pudieron cargar los datos. Verifique la conexión con el servidor.
    </div>

    <!-- Reporte 1: Cantidad de recepciones por proveedor -->
    <div class="report-section" id="reporteProveedores">
        <h2 class="titulo-form">Recepciones por Proveedor</h2>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoProveedores" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaProveedores"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-success btn-download" onclick="descargarPDFRecepcion('reporteProveedores','Reporte_Recepciones_Proveedores.pdf')">
                📥 Descargar PDF
            </button>
            <button class="btn btn-warning btn-download" onclick="descargarImagenRecepcion('graficoProveedores','Grafico_Recepciones_Proveedores.png')">
                🖼️ Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 2: Productos más recibidos -->
    <div class="report-section" id="reporteProductos">
        <h2 class="titulo-form">Productos más Recibidos</h2>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoProductos" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaProductos"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-success btn-download" onclick="descargarPDFRecepcion('reporteProductos','Reporte_Recepciones_Productos.pdf')">
                📥 Descargar PDF
            </button>
            <button class="btn btn-warning btn-download" onclick="descargarImagenRecepcion('graficoProductos','Grafico_Recepciones_Productos.png')">
                🖼️ Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 3: Recepciones mensuales -->
    <div class="report-section" id="reporteMensualRecepcion">
        <h2 class="titulo-form"> Recepciones Mensuales</h2>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoMensualRecepcion" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaMensualRecepcion"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-success btn-download" onclick="descargarPDFRecepcion('reporteMensualRecepcion','Reporte_Recepciones_Mensual.pdf')">
                📥 Descargar PDF
            </button>
            <button class="btn btn-warning btn-download" onclick="descargarImagenRecepcion('graficoMensualRecepcion','Grafico_Recepciones_Mensual.png')">
                🖼️ Descargar Gráfico
            </button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    // ============================
    // SECCIÓN DE RECEPCIÓN
    // ============================
    const recepcionesProveedor = <?php echo json_encode($RecepcionesProveedor) ?>;
    const productosRecibidos = <?php echo json_encode($ProductorRecibidos) ?>;
    const recepcionMensual = <?php echo json_encode($RecepcionMensual)?>;
    let graficoProveedores = null;
    let graficoProductos = null;
    let graficoMensualRecepcion = null;

    function filtrarPorFechas(datos, inicio, fin) {
        const inicioDate = inicio ? new Date(inicio + 'T00:00:00') : null;
        const finDate = fin ? new Date(fin + 'T23:59:59') : null;

        return datos.filter(d => {
            const fechaDato =
                d.fecha ||
                d.fecha_recepcion ||
                d.fechaRecepcion ||
                d.created_at ||
                d.mes ||
                d.fecha_registro ||
                null;

            if (!fechaDato) return true;

            const fechaConvertida = new Date(fechaDato);

            if (isNaN(fechaConvertida)) return true;
            if (inicioDate && fechaConvertida < inicioDate) return false;
            if (finDate && fechaConvertida > finDate) return false;

            return true;
        });
    }
    
    function generarColores(n) {
        return Array.from({length: n}, (_, i) => {
            const hue = (360 / n) * i;
            return `hsl(${hue}, 70%, 60%)`;
        });
    }

function renderReporteRecepcion(datos, labelKey, valueKey, canvasId, tablaId, titulo, tipoGrafica) {
    if (!datos || datos.length === 0) {
        document.getElementById(tablaId).innerHTML = `
            <div class="alert alert-warning text-center">
                📭 No hay datos disponibles para el período seleccionado
            </div>`;
        return;
    }

    // **CORRECCIÓN: Mapear los campos correctamente**
    const labels = datos.map(d => {
        return d[labelKey] || d.label || d.nombre_proveedor || d.nombre_producto || d.mes || 'Sin nombre';
    });
    
    const data = datos.map(d => {
        const valor = d[valueKey] || d.value || d.total || d.cantidad || 0;
        return parseInt(valor) || 0;
    });

    const total = data.reduce((a, b) => a + b, 0);
    const colores = generarColores(labels.length);

    // **CORRECCIÓN: Header dinámico para la tabla**
    const headerLabel = getHeaderLabelRecepcion(labelKey);
    
    let tablaHtml = `
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>${headerLabel}</th>
                        <th>Cantidad</th>
                        <th>Porcentaje (%)</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    labels.forEach((nombre, i) => {
        const pct = total > 0 ? ((data[i] / total) * 100).toFixed(2) : 0;
        tablaHtml += `
            <tr>
                <td>${nombre}</td>
                <td>${data[i].toLocaleString()}</td>
                <td>${pct}%</td>
            </tr>
        `;
    });
    
    tablaHtml += `
                </tbody>
                <tfoot class="table-active">
                    <tr>
                        <th>Total</th>
                        <th>${total.toLocaleString()}</th>
                        <th>100%</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;
    
    document.getElementById(tablaId).innerHTML = tablaHtml;

    const ctx = document.getElementById(canvasId).getContext('2d');
    
    // Destruir gráfico anterior si existe
    if (canvasId === 'graficoProveedores' && graficoProveedores) {
        graficoProveedores.destroy();
    } else if (canvasId === 'graficoProductos' && graficoProductos) {
        graficoProductos.destroy();
    } else if (canvasId === 'graficoMensualRecepcion' && graficoMensualRecepcion) {
        graficoMensualRecepcion.destroy();
    }
    
    const newChart = new Chart(ctx, {
        type: tipoGrafica,
        data: {
            labels: labels,
            datasets: [{
                label: titulo,
                data: data,
                backgroundColor: colores,
                borderColor: tipoGrafica === 'line' ? colores.map(c => c.replace('hsl', 'hsla').replace(')', ', 1)')) : colores,
                borderWidth: tipoGrafica === 'line' ? 3 : 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: tipoGrafica !== 'line',
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: titulo,
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.parsed;
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Asignar el nuevo gráfico a la variable correspondiente
    if (canvasId === 'graficoProveedores') {
        graficoProveedores = newChart;
    } else if (canvasId === 'graficoProductos') {
        graficoProductos = newChart;
    } else if (canvasId === 'graficoMensualRecepcion') {
        graficoMensualRecepcion = newChart;
    }
}

// **FUNCIÓN AUXILIAR PARA HEADERS DE TABLA**
function getHeaderLabelRecepcion(labelKey) {
    const headers = {
        'nombre_proveedor': 'Proveedor',
        'nombre_producto': 'Producto', 
        'mes': 'Mes',
        'label': 'Descripción'
    };
    return headers[labelKey] || 'Item';
}
    function toggleReportesRecepcion() {
        const seleccion = document.getElementById('selectReporteRecepcion').value;
        if (seleccion === 'todos') {
            document.getElementById('reporteProveedores').style.display = 'block';
            document.getElementById('reporteProductos').style.display = 'block';
            document.getElementById('reporteMensualRecepcion').style.display = 'block';
        } else if (seleccion === 'proveedores') {
            document.getElementById('reporteProveedores').style.display = 'block';
            document.getElementById('reporteProductos').style.display = 'none';
            document.getElementById('reporteMensualRecepcion').style.display = 'none';
        } else if (seleccion === 'productos') {
            document.getElementById('reporteProveedores').style.display = 'none';
            document.getElementById('reporteProductos').style.display = 'block';
            document.getElementById('reporteMensualRecepcion').style.display = 'none';
        } else if (seleccion === 'mensual') {
            document.getElementById('reporteProveedores').style.display = 'none';
            document.getElementById('reporteProductos').style.display = 'none';
            document.getElementById('reporteMensualRecepcion').style.display = 'block';
        }
    }

    function generarReportesRecepcion() {
        const tipoGrafica = document.getElementById('tipoGraficaRecepcion').value;
        const inicio = document.getElementById('fechaInicioRecepcion').value;
        const fin = document.getElementById('fechaFinRecepcion').value;

        const recepcionesProveedorFiltrado = filtrarPorFechas(
            recepcionesProveedor.filter(r => !r.estatus || r.estatus.toLowerCase() !== 'anulada'),
            inicio,
            fin
        );

        const productosRecibidosFiltrado = filtrarPorFechas(
            productosRecibidos.filter(r => !r.estatus || r.estatus.toLowerCase() !== 'anulada'),
            inicio,
            fin
        );

        const recepcionMensualFiltrado = filtrarPorFechas(
            recepcionMensual.filter(r => !r.estatus || r.estatus.toLowerCase() !== 'anulada'),
            inicio,
            fin
        );
        try {
            renderReporteRecepcion(recepcionesProveedorFiltrado, "nombre_proveedor", "total", "graficoProveedores", "tablaProveedores", "Recepciones por Proveedor", tipoGrafica);
            renderReporteRecepcion(productosRecibidosFiltrado, "nombre_producto", "total", "graficoProductos", "tablaProductos", "Productos más Recibidos", tipoGrafica);
            renderReporteRecepcion(recepcionMensualFiltrado, "mes", "total", "graficoMensualRecepcion", "tablaMensualRecepcion", "Recepciones Mensuales", tipoGrafica);
            document.getElementById('errorMessageRecepcion').style.display = 'none';
        } catch (error) {
            console.error("Error al generar reportes de recepción:", error);
            document.getElementById('errorMessageRecepcion').style.display = 'block';
        }
    }

    // Descargar PDF para RECEPCIÓN con gráfica arriba y tabla abajo
    function descargarPDFRecepcion(contenedorId, nombreArchivo) {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            
            const reporte = document.getElementById(contenedorId);
            const titulo = reporte.querySelector('h3').textContent;
            
            // Agregar título al PDF
            doc.setFontSize(18);
            doc.setTextColor(40);
            doc.text(titulo, 20, 20);
            
            // Obtener el canvas del gráfico
            let canvasId;
            if (contenedorId === 'reporteProveedores') {
                canvasId = 'graficoProveedores';
            } else if (contenedorId === 'reporteProductos') {
                canvasId = 'graficoProductos';
            } else if (contenedorId === 'reporteMensualRecepcion') {
                canvasId = 'graficoMensualRecepcion';
            }
            
            const canvas = document.getElementById(canvasId);
            if (canvas) {
                // Convertir canvas a imagen
                const imgData = canvas.toDataURL('image/png');
                
                // Dimensiones para la imagen en el PDF
                const pageWidth = doc.internal.pageSize.getWidth();
                const imgWidth = pageWidth - 40; // 20mm de margen a cada lado
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                // Agregar imagen del gráfico al PDF
                doc.addImage(imgData, 'PNG', 20, 30, imgWidth, imgHeight);
                
                // Posición Y después del gráfico
                let currentY = 30 + imgHeight + 10;
                
                // Verificar si hay espacio suficiente para la tabla
                if (currentY + 100 > doc.internal.pageSize.getHeight()) {
                    doc.addPage();
                    currentY = 20;
                }
                
                // Agregar tabla como imagen
                const tablaElement = reporte.querySelector('.chart-table');
                if (tablaElement) {
                    html2canvas(tablaElement, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff'
                    }).then(tablaCanvas => {
                        const tablaImgData = tablaCanvas.toDataURL('image/png');
                        const tablaImgWidth = pageWidth - 40;
                        const tablaImgHeight = (tablaCanvas.height * tablaImgWidth) / tablaCanvas.width;
                        
                        // Verificar si necesita nueva página
                        if (currentY + tablaImgHeight > doc.internal.pageSize.getHeight()) {
                            doc.addPage();
                            currentY = 20;
                        }
                        
                        doc.addImage(tablaImgData, 'PNG', 20, currentY, tablaImgWidth, tablaImgHeight);
                        doc.save(nombreArchivo);
                    });
                } else {
                    doc.save(nombreArchivo);
                }
            } else {
                alert('No se pudo encontrar el gráfico para generar el PDF.');
            }
            
        } catch (error) {
            console.error("Error al generar PDF de recepción:", error);
            alert("Error al generar el PDF. Asegúrese de que todas las librerías estén cargadas correctamente.");
        }
    }

    function descargarImagenRecepcion(canvasId, nombreArchivo) {
        try {
            const canvas = document.getElementById(canvasId);
            const link = document.createElement('a');
            link.download = nombreArchivo;
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (error) {
            console.error("Error al descargar imagen de recepción:", error);
            alert("Error al descargar la imagen del gráfico.");
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const hoy = new Date();
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        document.getElementById('fechaInicioRecepcion').valueAsDate = primerDiaMes;
        document.getElementById('fechaFinRecepcion').valueAsDate = hoy;
        generarReportesRecepcion();
        document.getElementById('generarReporteRecepcionBtn').addEventListener('click', generarReportesRecepcion);
        document.getElementById('selectReporteRecepcion').addEventListener('change', toggleReportesRecepcion);
        toggleReportesRecepcion();
    });
</script>

<script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/js/jquery-3.7.1.min.js"></script>
<script src="public/js/jquery.dataTables.min.js"></script>
<script src="public/js/dataTables.bootstrap5.min.js"></script>
<script src="public/js/datatable.js"></script>
</body>
</html>
<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>