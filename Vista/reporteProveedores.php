<?php if ($_SESSION) {?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <title>Reportes de Proveedores</title>
    
    <style>
        :root {
            --primary-color: #1f66df;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #ffc107;
            --light-bg: #f8f9fa;
            --border-radius: 12px;
            --box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        
        .report-container {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            padding: 32px 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .report-header {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 30px;
            border-bottom: 2px solid var(--light-bg);
            padding-bottom: 15px;
        }
        
        .parameters-container {
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--light-bg);
            border-radius: var(--border-radius);
        }
        
        .report-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
        }
        
        .report-section:hover {
            box-shadow: var(--box-shadow);
        }
        
        .chart-container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            gap: 20px;
        }
        
        .chart-canvas {
            flex: 1;
            min-width: 300px;
            text-align: center;
        }
        
        .chart-table {
            flex: 2;
            min-width: 400px;
        }
        
        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 10px 5px;
            padding: 10px 20px;
        }
        
        .download-buttons {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .error-message {
            color: #dc3545;
            text-align: center;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .form-inline {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: center;
        }
        
        .form-inline .form-control,
        .form-inline .form-select {
            width: auto;
            min-width: 160px;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
            margin: 30px 0;
        }
        
        .stock-negativo {
            color: #dc3545;
            font-weight: bold;
        }
        
        .stock-bajo {
            color: #ffc107;
            font-weight: bold;
        }
        
        .stock-ok {
            color: #198754;
            font-weight: bold;
        }
        
        /* Estilos para PDF */
        .pdf-layout {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .pdf-chart {
            width: 100%;
            text-align: center;
        }
        
        .pdf-table {
            width: 100%;
        }
    </style>
</head>

<body class="fondo" style="height: auto; min-height: 100vh; background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'newnavbar.php'; ?>

<div class="report-container">
    <div class="report-header">
        <h2 class="titulo-form">Reportes de Proveedores</h2>
        <p class="texto-p">Analice el desempeño y suministro de proveedores</p>
    </div>

    <!-- Parámetros del Reporte -->
    <div class="parameters-container">
        <h5>Parámetros del Reporte</h5>
        <div class="form-inline">
            <div class="form-group">
                <label for="tipoGrafica">Tipo de gráfica:</label>
                <select id="tipoGrafica" class="form-select">
                    <option value="bar">Barras</option>
                    <option value="pie">Pastel</option>
                    <option value="line">Líneas</option>
                    <option value="doughnut">Donas</option>
                    <option value="polarArea">Área Polar</option>
                </select>
            </div>
            <div class="form-group">
                <label for="selectReporte">Reporte:</label>
                <select id="selectReporte" class="form-select">
                    <option value="todos">Todos los Reportes</option>
                    <option value="reporteSuministro">Suministro por Proveedor</option>
                    <option value="reporteRanking">Ranking de Proveedores</option>
                    <option value="reporteComparacion">Comparación Mensual</option>
                    <option value="reporteDependencia">Dependencia de Proveedores</option>
                </select>
            </div>
            <div class="form-group">
                <button id="generarReporteBtn" class="btn btn-primary">Generar Reporte</button>
            </div>
        </div>
    </div>

    <!-- Spinner de carga -->
    <div id="loadingSpinner" class="loading-spinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
        <p>Generando reportes...</p>
    </div>

    <!-- Mensaje de error -->
    <div id="errorMessage" class="error-message">
        No se pudieron cargar los datos. Verifique la conexión con el servidor.
    </div>

    <!-- Reporte 1: Suministro por Proveedor -->
    <div class="report-section" id="reporteSuministro">
        <h3 class="titulo-form">Productos Suministrados por Proveedor</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoSuministro" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaSuministro"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-success btn-download" onclick="descargarPDFProveedores('reporteSuministro', 'Reporte_Suministro_Proveedores.pdf')">
                📥 Descargar PDF
            </button>
            <button class="btn btn-warning btn-download" onclick="descargarImagenProveedores('graficoSuministro', 'Grafico_Suministro_Proveedores.png')">
                🖼️ Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 2: Ranking de Proveedores -->
    <div class="report-section" id="reporteRanking">
        <h3 class="titulo-form">Ranking de Proveedores</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoRanking" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaRanking"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-success btn-download" onclick="descargarPDFProveedores('reporteRanking', 'Reporte_Ranking_Proveedores.pdf')">
                📥 Descargar PDF
            </button>
            <button class="btn btn-warning btn-download" onclick="descargarImagenProveedores('graficoRanking', 'Grafico_Ranking_Proveedores.png')">
                🖼️ Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 3: Comparación Mensual -->
    <div class="report-section" id="reporteComparacion">
        <h3 class="titulo-form">Comparación Mensual de Suministros</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoComparacion" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaComparacion"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-success btn-download" onclick="descargarPDFProveedores('reporteComparacion', 'Reporte_Comparacion_Proveedores.pdf')">
                📥 Descargar PDF
            </button>
            <button class="btn btn-warning btn-download" onclick="descargarImagenProveedores('graficoComparacion', 'Grafico_Comparacion_Proveedores.png')">
                🖼️ Descargar Gráfico
            </button>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Reporte 4: Dependencia de Proveedores -->
    <div class="report-section" id="reporteDependencia">
        <h3 class="titulo-form">Dependencia de Proveedores</h3>
        <div class="chart-container">
            <div class="chart-canvas">
                <canvas id="graficoDependencia" width="400" height="400"></canvas>
            </div>
            <div class="chart-table">
                <div id="tablaDependencia"></div>
            </div>
        </div>
        <div class="download-buttons">
            <button class="btn btn-success btn-download" onclick="descargarPDFProveedores('reporteDependencia', 'Reporte_Dependencia_Proveedores.pdf')">
                📥 Descargar PDF
            </button>
            <button class="btn btn-warning btn-download" onclick="descargarImagenProveedores('graficoDependencia', 'Grafico_Dependencia_Proveedores.png')">
                🖼️ Descargar Gráfico
            </button>
        </div>
    </div>
</div>

<!-- Scripts -->
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    // Datos parametrizados desde PHP
    let reporteSuministro = <?php echo json_encode($reporteSuministroProveedores ?? []); ?>;
    let reporteRanking = <?php echo json_encode($reporteRankingProveedores ?? []); ?>;
    let reporteComparacion = <?php echo json_encode($reporteComparacion ?? []); ?>;
    let reporteDependencia = <?php echo json_encode($reporteDependencia ?? []); ?>;

    // Variables globales de gráficos
    let graficoSuministro = null, graficoRanking = null, graficoComparacion = null, graficoDependencia = null;

    console.log('Datos cargados:', {
        suministro: reporteSuministro,
        ranking: reporteRanking,
        comparacion: reporteComparacion,
        dependencia: reporteDependencia
    });

    // Función para asegurar que el gráfico esté renderizado
    function asegurarRenderizadoGrafico(canvasId, callback) {
        const canvas = document.getElementById(canvasId);
        const chart = Chart.getChart(canvasId);
        
        if (chart) {
            // Forzar actualización del gráfico
            chart.update('none');
            
            // Pequeño delay para asegurar el renderizado
            setTimeout(() => {
                callback();
            }, 100);
        } else {
            callback();
        }
    }

    // Generador de colores
    function generarColores(n) {
        return Array.from({length: n}, (_, i) => {
            const hue = (360 / n) * i;
            return `hsl(${hue}, 70%, 60%)`;
        });
    }

    // Función render general mejorada
    function renderReporte(datos, tipoReporte, canvasId, tablaId, titulo, tipoGrafica, esHorizontal = false) {
        console.log(`Renderizando ${tipoReporte} en ${canvasId}`, datos);
        
        const tablaElement = document.getElementById(tablaId);
        const canvasElement = document.getElementById(canvasId);
        
        if (!tablaElement || !canvasElement) {
            console.error(`Elemento no encontrado: ${tablaId} o ${canvasId}`);
            return;
        }

        if (!datos || datos.length === 0) {
            tablaElement.innerHTML = `<div class="alert alert-warning text-center">📭 No hay datos disponibles</div>`;
            // Limpiar canvas
            const ctx = canvasElement.getContext('2d');
            ctx.clearRect(0, 0, canvasElement.width, canvasElement.height);
            return;
        }

        let labels = [];
        let data = [];
        let total = 0;
        let tablaHtml = '';

        try {
            switch(tipoReporte) {
                case 'suministro':
                    labels = datos.map(d => d.nombre_proveedor || 'Sin nombre');
                    data = datos.map(d => parseInt(d.cantidad) || 0);
                    total = data.reduce((a,b) => a+b, 0);
                    tablaHtml = crearTablaSuministro(datos,total);
                    break;
                case 'ranking':
                    datos = datos.sort((a,b) => (b.total || 0) - (a.total || 0));
                    labels = datos.map(d => d.nombre_proveedor || 'Sin nombre');
                    data = datos.map(d => parseFloat(d.total) || 0);
                    total = data.reduce((a,b) => a+b,0);
                    tablaHtml = crearTablaRanking(datos,total);
                    break;
                case 'comparacion':
                    labels = datos.map(p => p.nombre_proveedor || 'Sin nombre');
                    data = datos.map(p => parseFloat(p.precio_promedio) || 0);
                    titulo = `Comparación: ${datos.nombre_producto || 'Producto'}`;
                    tablaHtml = crearTablaComparacion(datos);
                    break;
                case 'dependencia':
                    labels = datos.map(d => d.nombre_proveedor || 'Sin nombre');
                    data = datos.map(d => parseFloat(d.dependencia_porcentaje) || 0);
                    total = 100;
                    tablaHtml = crearTablaDependencia(datos);
                    break;
            }

            tablaElement.innerHTML = tablaHtml;
            const ctx = canvasElement.getContext('2d');

            // Destruir gráfico previo
            switch(canvasId) {
                case 'graficoSuministro':
                    if (graficoSuministro) graficoSuministro.destroy();
                    break;
                case 'graficoRanking':
                    if (graficoRanking) graficoRanking.destroy();
                    break;
                case 'graficoComparacion':
                    if (graficoComparacion) graficoComparacion.destroy();
                    break;
                case 'graficoDependencia':
                    if (graficoDependencia) graficoDependencia.destroy();
                    break;
            }

            const colores = generarColores(labels.length);

            let options = {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { 
                        display: !['line', 'bar'].includes(tipoGrafica), 
                        position: 'bottom' 
                    },
                    title: { 
                        display: true, 
                        text: titulo,
                        font: { size: 16 }
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
            };
            
            if (esHorizontal) options.indexAxis = 'y';

            // Crear nuevo gráfico
            const nuevoGrafico = new Chart(ctx, {
                type: tipoGrafica,
                data: { 
                    labels: labels, 
                    datasets: [{ 
                        label: titulo, 
                        data: data, 
                        backgroundColor: colores,
                        borderColor: tipoGrafica === 'line' ? colores : undefined,
                        borderWidth: tipoGrafica === 'line' ? 2 : 1
                    }] 
                },
                options: options
            });

            // Asignar a la variable global correcta
            switch(canvasId) {
                case 'graficoSuministro': graficoSuministro = nuevoGrafico; break;
                case 'graficoRanking': graficoRanking = nuevoGrafico; break;
                case 'graficoComparacion': graficoComparacion = nuevoGrafico; break;
                case 'graficoDependencia': graficoDependencia = nuevoGrafico; break;
            }

        } catch (error) {
            console.error(`Error renderizando ${tipoReporte}:`, error);
            tablaElement.innerHTML = `<div class="alert alert-danger text-center">❌ Error al generar el reporte</div>`;
        }
    }

    // Funciones de tablas
    function crearTablaSuministro(datos,total) {
        let html = `<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="table-dark"><tr><th>Proveedor</th><th>Cantidad</th><th>%</th></tr></thead><tbody>`;
        datos.forEach(d => {
            let pct = total > 0 ? ((parseInt(d.cantidad)||0)/total*100).toFixed(2) : 0;
            html += `<tr><td>${d.nombre_proveedor || 'N/A'}</td><td>${d.cantidad || 0}</td><td>${pct}%</td></tr>`;
        });
        html += `<tfoot class="table-active"><tr><th>Total</th><th>${total}</th><th>100%</th></tr></tfoot>`;
        return html+`</tbody></table></div>`;
    }

    function crearTablaRanking(datos,total) {
        let html = `<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="table-dark"><tr><th>Proveedor</th><th>Total Compras</th><th>%</th></tr></thead><tbody>`;
        datos.forEach(d => {
            let pct = total > 0 ? ((parseFloat(d.total)||0)/total*100).toFixed(2) : 0;
            html += `<tr><td>${d.nombre_proveedor || 'N/A'}</td><td>$${(parseFloat(d.total)||0).toFixed(2)}</td><td>${pct}%</td></tr>`;
        });
        html += `<tfoot class="table-active"><tr><th>Total</th><th>$${total.toFixed(2)}</th><th>100%</th></tr></tfoot>`;
        return html+`</tbody></table></div>`;
    }

    function crearTablaComparacion(datos) {
        if (!datos || datos.length === 0) return '<div class="alert alert-info">No hay datos de comparación</div>';
        let html = `<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="table-dark"><tr><th>Producto</th><th>Proveedor</th><th>Precio Promedio</th><th>Registros</th></tr></thead><tbody>`;
        datos.forEach(p => {
            html += `<tr><td>${p.nombre_producto || 'Producto'}</td><td>${p.nombre_proveedor || 'N/A'}</td><td>$${(parseFloat(p.precio_promedio)||0).toFixed(2)}</td><td>${p.cantidad || 0}</td></tr>`;
        });
        return html+`</tbody></table></div>`;
    }

    function crearTablaDependencia(datos) {
        let html = `<div class="table-responsive"><table class="table table-bordered table-striped"><thead class="table-dark"><tr><th>Proveedor</th><th>Monto</th><th>% Dependencia</th></tr></thead><tbody>`;
        datos.forEach(d=>{
            html+=`<tr><td>${d.nombre_proveedor || 'N/A'}</td><td>$${d.monto_total_pagado || '0.00'}</td><td>${d.dependencia_porcentaje || '0.00'}%</td></tr>`;
        });
        return html+`</tbody></table></div>`;
    }

    // Selector de reportes
    function toggleReportes() {
        const seleccion = document.getElementById('selectReporte').value;
        const reportes = ['reporteSuministro','reporteRanking','reporteComparacion','reporteDependencia'];
        
        reportes.forEach(r => {
            const elemento = document.getElementById(r);
            if (elemento) {
                elemento.style.display = (seleccion === 'todos' || seleccion === r) ? 'block' : 'none';
                // Ocultar también los divisores
                const divisores = document.querySelectorAll('.divider');
                divisores.forEach(divisor => {
                    const prevSection = divisor.previousElementSibling;
                    if (prevSection && prevSection.id && prevSection.id === r) {
                        divisor.style.display = (seleccion === 'todos' || seleccion === r) ? 'block' : 'none';
                    }
                });
            }
        });
    }

    // Función para mostrar/ocultar loading
    function toggleLoading(mostrar) {
        document.getElementById('loadingSpinner').style.display = mostrar ? 'block' : 'none';
    }

    // Generar reportes mejorado
    function generarReportes() {
        console.log('Generando reportes...');
        const tipoGrafica = document.getElementById('tipoGrafica').value;
        
        toggleLoading(true);
        toggleReportes();
        
        // Pequeño delay para asegurar que el DOM se actualizó
        setTimeout(() => {
            try {
                if (document.getElementById('reporteSuministro').style.display === 'block') {
                    renderReporte(reporteSuministro, 'suministro', 'graficoSuministro', 'tablaSuministro', 'Suministro por Proveedor', tipoGrafica, true);
                }
                if (document.getElementById('reporteRanking').style.display === 'block') {
                    renderReporte(reporteRanking, 'ranking', 'graficoRanking', 'tablaRanking', 'Ranking de Proveedores', tipoGrafica);
                }
                if (document.getElementById('reporteComparacion').style.display === 'block') {
                    renderReporte(reporteComparacion, 'comparacion', 'graficoComparacion', 'tablaComparacion', 'Comparación Mensual', tipoGrafica);
                }
                if (document.getElementById('reporteDependencia').style.display === 'block') {
                    renderReporte(reporteDependencia, 'dependencia', 'graficoDependencia', 'tablaDependencia', 'Dependencia de Proveedores', 'doughnut');
                }
                
                document.getElementById('errorMessage').style.display = 'none';
            } catch (error) {
                console.error("Error al generar reportes:", error);
                document.getElementById('errorMessage').style.display = 'block';
            } finally {
                toggleLoading(false);
            }
        }, 100);
    }

    // Descargar PDF para PROVEEDORES con gráfica arriba y tabla abajo
    function descargarPDFProveedores(contenedorId, nombreArchivo) {
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
            if (contenedorId === 'reporteSuministro') {
                canvasId = 'graficoSuministro';
            } else if (contenedorId === 'reporteRanking') {
                canvasId = 'graficoRanking';
            } else if (contenedorId === 'reporteComparacion') {
                canvasId = 'graficoComparacion';
            } else if (contenedorId === 'reporteDependencia') {
                canvasId = 'graficoDependencia';
            }
            
            asegurarRenderizadoGrafico(canvasId, () => {
                const canvas = document.getElementById(canvasId);
                if (canvas) {
                    // Convertir canvas a imagen
                    const imgData = canvas.toDataURL('image/png');
                    
                    // Dimensiones para la imagen en el PDF
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const imgWidth = pageWidth - 40;
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
            });
            
        } catch (error) {
            console.error("Error al generar PDF de proveedores:", error);
            alert("Error al generar el PDF. Asegúrese de que todas las librerías estén cargadas correctamente.");
        }
    }

    // Descargar imagen del gráfico para PROVEEDORES
    function descargarImagenProveedores(canvasId, nombreArchivo) {
        try {
            asegurarRenderizadoGrafico(canvasId, () => {
                const canvas = document.getElementById(canvasId);
                const link = document.createElement('a');
                link.download = nombreArchivo;
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        } catch (error) {
            console.error("Error al descargar imagen de proveedores:", error);
            alert("Error al descargar la imagen del gráfico.");
        }
    }

    // Inicialización mejorada
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, inicializando reportes...');
        
        // Generar reportes iniciales
        setTimeout(() => {
            generarReportes();
            
            // Agregar event listeners
            const generarBtn = document.getElementById('generarReporteBtn');
            const selectReporte = document.getElementById('selectReporte');
            
            if (generarBtn) {
                generarBtn.addEventListener('click', generarReportes);
            }
            
            if (selectReporte) {
                selectReporte.addEventListener('change', generarReportes);
            }
        }, 500);
    });
</script>

<!-- Scripts locales -->
<script src="javascript/proveedor.js"></script>
<script src="public/js/jquery-3.7.1.min.js"></script>
<script src="public/js/jquery.dataTables.min.js"></script>
<script src="public/js/dataTables.bootstrap5.min.js"></script>
<script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tablaConsultas').DataTable({
            language: { url: 'public/js/es-ES.json' },
            pageLength: 10,
            order: [[0, 'asc']]
        });
    });
</script>

</body>
</html>
<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}