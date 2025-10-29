<?php if ($_SESSION) {?>

  <!DOCTYPE html>
  <html lang="es">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reporte de Productos</title>
    <?php include 'header.php'; ?>
    <style>
      .foto-producto {
        max-width: 80px;
        max-height: 80px;
        border-radius: 6px;
        border: 1px solid #ccc;
        padding: 5px;
        object-fit: contain;
        background: #fff;
      }
      #reporteMasVendidos, #reporteStock, #reporteRotacion {
    min-height: 420px;
    max-width: 100%;
}
#reporteMasVendidos, #reporteStock, #reporteRotacion {
    min-height: 420px;
    max-width: 200%;
}
canvas {
    max-width: 100% !important;
    height: 400px !important;
    max-height: 400px !important;
    display: block;
    
}
    </style>
    
  </head>

  <body class="fondo"
    style=" height: 100vh; background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <?php include 'newnavbar.php'; ?>

  <div class="container mt-4">
    <!-- Formulario de parámetros para el reporte -->
    <div class="reporte-container" style="max-width: 1000px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 0 15px rgba(0,0,0,0.1);">
        <div class="reporte-parametros" style="margin-bottom: 30px; text-align:center;">
            <div class="form-inline" style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
                <label for="tipoReporte">Tipo de reporte:</label>
                <select id="tipoReporte" class="form-select" style="width:220px;">
                    <option value="mas_vendidos">Top Productos Más Vendidos</option>
                    <option value="stock">Stock Alto vs Bajo</option>
                    <option value="rotacion">Rotación de Productos</option>
                </select>

                <label for="tipoGrafica">Tipo de gráfica:</label>
                <select id="tipoGrafica" class="form-select" style="width:200px;">
                    <option value="bar">Barras</option>
                    <option value="pie">Pastel</option>
                    <option value="line">Líneas</option>
                    <option value="doughnut">Rosca</option>
                    <option value="polarArea">Área Polar</option>
                </select>

                <!-- Parámetros dinámicos -->
                <div id="parametrosExtra" style="display:flex; gap:10px; flex-wrap: wrap; align-items:center;">
                    <!-- Aquí se insertan dinámicamente los inputs según el reporte -->
                </div>

                <button id="generarReporteBtn" class="btn btn-primary">Generar</button>
                <button id="descargarPDF" class="btn btn-success">Descargar PDF</button>
            </div>
        </div>

        <!-- Contenedor del Reporte -->
        <h3 id="tituloReporte" style="text-align: center; margin-bottom: 20px; color:#1f66df;">Reporte de Productos</h3>
        
        <!-- Secciones de reportes -->
        <div id="reporteMasVendidos">
            <h5>Top Productos Más Vendidos</h5>
            <canvas id="chartMasVendidos" height="400"></canvas>
            <div id="reporteMasVendidosDetalle"></div>
        </div>
        
        <div id="reporteStock" style="display:none;">
            <h5>Stock Alto vs Bajo</h5>
            <canvas id="chartStock" height="400"></canvas>
  <div id="reporteStockDetalle"></div>
        </div>
        
        <div id="reporteRotacion" style="display:none;">
            <h5>Rotación de Productos (días promedio)</h5>
            <canvas id="chartRotacion" height="400"></canvas>
            <div id="reporteRotacionDetalle"></div> 
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="javascript/sweetalert2.all.min.js"></script>
<script src="javascript/validaciones.js"></script>
<script src="public/js/chart.js"></script>
<script src="public/js/html2canvas.min.js"></script>
<script src="public/js/jspdf.umd.min.js"></script>
<script src="public/js/jquery-3.7.1.min.js"></script>
<script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/js/jquery.dataTables.min.js"></script>
<script src="public/js/dataTables.bootstrap5.min.js"></script>
<script src="public/js/datatable.js"></script>
<script src="javascript/producto.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
    // Datos desde PHP en arrays
    const masVendidos = <?php echo json_encode($masVendidos); ?>;
    const stockProductos = <?php echo json_encode($stockProductos); ?>;
    const rotacion = <?php echo json_encode($rotacion); ?>;
    const categorias = <?php echo json_encode($categorias); ?>; // <- array de categorías desde PHP

    // Variables para gráficos
    let chartVendidos = null;
    let chartStock = null;
    let chartRotacion = null;

    // --- Función para mostrar parámetros dinámicos ---
    function mostrarParametrosExtra(tipoReporte) {
        const contenedor = document.getElementById('parametrosExtra');
        contenedor.innerHTML = ""; // limpiar

        if (tipoReporte === "mas_vendidos") {
            contenedor.innerHTML = `
                <label for="topN">Top N:</label>
                <input type="number" id="topN" value="10" min="1" class="form-control" style="width:100px;">
            `;
        } else if (tipoReporte === "stock") {
            let options = categorias.map(cat => `<option value="${cat.id_categoria}">${cat.nombre_categoria}</option>`).join('');
            contenedor.innerHTML = `
                <label for="categoriaStock">Categoría:</label>
                <select id="categoriaStock" class="form-select" style="width:200px;">
                    <option value="todas">Todas</option>
                    ${options}
                </select>
            `;
        }
    }

    // Función para mostrar un reporte específico
    function mostrarReporte(tipoReporte, tipoGrafica) {
        document.getElementById('reporteMasVendidos').style.display = 'none';
        document.getElementById('reporteStock').style.display = 'none';
        document.getElementById('reporteRotacion').style.display = 'none';
        
        if (tipoReporte === 'mas_vendidos') {
            document.getElementById('reporteMasVendidos').style.display = 'block';
            document.getElementById('tituloReporte').textContent = 'Top Productos Más Vendidos';
        } else if (tipoReporte === 'stock') {
            document.getElementById('reporteStock').style.display = 'block';
            document.getElementById('tituloReporte').textContent = 'Stock Alto vs Bajo';
        } else if (tipoReporte === 'rotacion') {
            document.getElementById('reporteRotacion').style.display = 'block';
            document.getElementById('tituloReporte').textContent = 'Rotación de Productos (días promedio)';
        }

        generarGrafico(tipoReporte, tipoGrafica);
    }

    // Función para generar gráficos
// Función para generar tabla debajo del gráfico
function generarTablaDetalle(containerId, headers, rows) {
    const container = document.getElementById(containerId + "Detalle");
    let html = `
        <div class="table-responsive mt-3">
            <table class="table table-bordered table-striped">
                <thead><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead>
                <tbody>
    `;

    rows.forEach(r => {
        html += "<tr>";
        r.forEach(val => {
            html += `<td>${val}</td>`;
        });
        html += "</tr>";
    });

    html += "</tbody></table></div>";
    container.innerHTML = html;
}

// Función para generar gráficos + tablas
function generarGrafico(tipoReporte, tipoGrafica) {
    switch(tipoReporte) {
        case 'mas_vendidos':
            if (chartVendidos) chartVendidos.destroy();

            const topN = parseInt(document.getElementById("topN")?.value || 10);
            const dataTop = masVendidos.slice(0, topN);

            const labelsVendidos = dataTop.map(item => item.nombre_producto);
            const valuesVendidos = dataTop.map(item => item.total_vendido);
            
            chartVendidos = new Chart(document.getElementById("chartMasVendidos"), {
                type: tipoGrafica,
                data: {
                    labels: labelsVendidos,
                    datasets: [{
                        label: "Unidades Vendidas",
                        data: valuesVendidos,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // 🔹 Tabla debajo
            generarTablaDetalle("reporteMasVendidos",
                ["Producto", "Unidades Vendidas"],
                dataTop.map(item => [item.nombre_producto, item.total_vendido])
            );
            break;

        case 'stock':
            if (chartStock) chartStock.destroy();

            const categoriaSeleccionada = document.getElementById("categoriaStock")?.value || "todas";
            let dataStock = stockProductos;

            if (categoriaSeleccionada !== "todas") {
                dataStock = stockProductos.filter(item => item.id_categoria == categoriaSeleccionada);
            }

            const altoStock = dataStock.filter(item => item.categoria_stock === "Alto Stock").length;
            const bajoStock = dataStock.filter(item => item.categoria_stock === "Bajo Stock").length;
            
            chartStock = new Chart(document.getElementById("chartStock"), {
                type: tipoGrafica,
                data: {
                    labels: ["Alto Stock", "Bajo Stock"],
                    datasets: [{
                        data: [altoStock, bajoStock],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)'
                        ],
                        borderWidth: 1
                        
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // 🔹 Tabla debajo con categoría incluida
            generarTablaDetalle("reporteStock",
                ["Producto", "Stock Actual", "Stock Mínimo", "Estatus", "Categoría"],
                dataStock.map(item => {
                    const categoria = categorias.find(c => c.id_categoria == item.id_categoria);
                    return [
                        item.nombre_producto,
                        item.stock,
                        item.stock_minimo,
                        item.categoria_stock,
                        categoria ? categoria.nombre_categoria : "Sin categoría"
                    ];
                })
            );
            break;

        case 'rotacion':
            if (chartRotacion) chartRotacion.destroy();

            const labelsRotacion = rotacion.map(item => item.nombre_producto);
            const valuesRotacion = rotacion.map(item => item.dias_promedio);
            
            chartRotacion = new Chart(document.getElementById("chartRotacion"), {
                type: tipoGrafica,
                data: {
                    labels: labelsRotacion,
                    datasets: [{
                        label: "Días promedio en inventario",
                        data: valuesRotacion,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });

            // 🔹 Tabla debajo
            generarTablaDetalle("reporteRotacion",
                ["Producto", "Días en Inventario"],
                rotacion.map(item => [item.nombre_producto, item.dias_promedio])
            );
            break;
    }
}


    // Event Listeners
    document.getElementById('generarReporteBtn').addEventListener('click', function() {
        const tipoReporte = document.getElementById('tipoReporte').value;
        const tipoGrafica = document.getElementById('tipoGrafica').value;
        mostrarReporte(tipoReporte, tipoGrafica);
    });

    document.getElementById('tipoReporte').addEventListener('change', function() {
        mostrarParametrosExtra(this.value);
    });

    // Inicializar con el primer reporte
    window.onload = function() {
        const tipoReporte = document.getElementById('tipoReporte').value;
        const tipoGrafica = document.getElementById('tipoGrafica').value;
        mostrarParametrosExtra(tipoReporte);
        mostrarReporte(tipoReporte, tipoGrafica);
    };

    // Descargar PDF (placeholder)
    document.getElementById('descargarPDF').addEventListener('click', function() {
        alert('Función de descarga de PDF será implementada próximamente');
    });
</script>


    <script>
      const categoriasDinamicas = <?php echo json_encode($categoriasDinamicas); ?>;

      $('#Categoria').on('change', function () {
        const tabla = $(this).find(':selected').data('tabla');
        $('#tabla_categoria').val(tabla)
        const categoria = categoriasDinamicas.find(cat => cat.tabla === tabla);
        const contenedor = $('#caracteristicasCategoria');
        contenedor.empty();

        if (categoria) {
          categoria.caracteristicas.forEach(carac => {
            let input = '';
           if (carac.tipo === 'int' || carac.tipo === 'float') {
    if (carac.tipo === 'int') {
        input = `<input type="number" min="1" step="1" class="form-control" name="carac[${carac.nombre}]" placeholder="${carac.nombre}" required>`;
    } else {
        input = `<input type="number" min="0.01" step="0.01" class="form-control" name="carac[${carac.nombre}]" placeholder="${carac.nombre}" required>`;
    }
} else {
    input = `<input type="text" class="form-control" name="carac[${carac.nombre}]" maxlength="${carac.max}" placeholder="${carac.nombre}" oninput="soloTextoPermitido(event)"> required>`;
}
            contenedor.append(`<div class="mb-2"><label>${carac.nombre}</label>${input}</div>`);
          });
        }
      });

      $('#modificarCategoria').on('change', function () {
    const tabla = $(this).val();
    $('#modificar_tabla_categoria').val(tabla);
    const categoria = categoriasDinamicas.find(cat => cat.tabla === tabla);
    const contenedor = $('#caracteristicasCategoriaModificar');
    contenedor.empty();

    if (categoria) {
        categoria.caracteristicas.forEach(carac => {
            let input = '';
            if (carac.tipo === 'int' || carac.tipo === 'float') {
                input = `<input type="number" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" placeholder="${carac.nombre}" ${carac.tipo === 'int' ? 'step="1"' : 'step="0.01"'} required>`;
            } else {
                input = `<input type="text" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" maxlength="${carac.max}" placeholder="${carac.nombre}" required>`;
            }
            contenedor.append(`<div class="mb-2 col-md-6"><label>${carac.nombre}</label>${input}</div>`);
        });
    }
});
    </script>
    
    <script>

$(document).ready(function () {
    // Genera los campos dinámicos al cambiar la categoría en el modal de modificar
$('#modificarCategoria').on('change', function () {
    const tabla = $(this).val();
    $('#modificar_tabla_categoria').val(tabla);
    const categoria = categoriasDinamicas.find(cat => cat.tabla === tabla);
    const contenedor = $('#caracteristicasCategoriaModificar');
    contenedor.empty();

    if (categoria) {
categoria.caracteristicas.forEach(carac => {
    let input = '';
if (carac.tipo === 'int' || carac.tipo === 'float') {
    if (carac.tipo === 'int') {
        input = `<input type="number" min="1" step="1" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" placeholder="${carac.nombre}" required>`;
    } else {
        input = `<input type="number" min="0.01" step="0.01" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" placeholder="${carac.nombre}" required>`;
    }
} else {
    input = `<input type="text" class="form-control" name="carac[${carac.nombre}]" id="modificar_${carac.nombre}" maxlength="${carac.max}" placeholder="${carac.nombre}" required>`;
}
    contenedor.append(`<div class="mb-2 col-md-6"><label>${carac.nombre}</label>${input}</div>`);
});
    }
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
?>
