<?php $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 2;
if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'header.php'; ?>
    <title>Gestionar Recepcion</title>
</head>
<style>
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
    .tr-seleccionado {
        background-color: #d4edda !important;
        border-left: 4px solid #28a745 !important;
        transition: all 0.3s ease;
    }
    .tr-seleccionado:hover {
        background-color: #c3e6cb !important;
    }
    .agregado {
        background-color: #f8d7da !important;
        opacity: 0.6;
    }
</style>

<body class="fondo" style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">
<?php include 'NewNavBar.php'; ?>

<div class="modal fade modal-registrar" id="registrarRecepcionModal" tabindex="-1" role="dialog" aria-labelledby="registrarRecepcionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">

            <form id="ingresarRecepcion" method="POST" novalidate>
                <div class="modal-header">
                    <button type="button" class="btn-ayuda-modal" title="Ayuda para Incluir Recepción" data-contexto="registrar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="registrarRecepcionModalLabel">Incluir Recepción</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="registrar">
                    
                    <!-- Estado inicial del modal: Botón para importar factura -->
                    <div id="paso_inicial_importar" class="ia-section">
                        <div class="ia-header">
                            <h6><i class="fas fa-file-import"></i> Importar Factura</h6>
                            <small class="text-muted">Seleccione una fotografía de la factura para comenzar con el registro</small>
                        </div>
                        <div class="text-center">
                            <button type="button" id="ia_btn_importar_factura" class="btn btn-primary btn-lg">
                                <i class="fas fa-camera"></i> Importar Factura
                            </button>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Debe importar la factura antes de poder registrar la recepción
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Paso 1: Carga de Factura (oculto inicialmente) -->
                    <div id="paso1_carga_factura" class="ia-section" style="display: none;">
                        <div class="ia-header">
                            <h6><i class="fas fa-camera"></i> Cargar Factura</h6>
                            <small class="text-muted">Cargue una fotografía de la factura para continuar</small>
                        </div>
                        <div class="form-group">
                            <label for="ia_factura_imagen" class="required">
                                <i class="fas fa-file-image"></i> Seleccionar Factura <span class="text-danger">*</span>
                            </label>
                            <input type="file" id="ia_factura_imagen" name="ia_factura_imagen" 
                                   class="form-control" accept="image/*" required>
                            <small class="form-text text-muted">
                                Formatos permitidos: JPG, PNG, BMP. Tamaño máximo: 10MB
                            </small>
                            <div id="ia_imagen_preview" class="mt-2"></div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="ia-botones-container mb-3">
                            <button type="button" id="ia_btn_continuar" class="btn btn-primary btn-sm" disabled>
                                <i class="fas fa-arrow-right"></i> Continuar al Formulario
                            </button>
                            <button type="button" id="ia_btn_limpiar" class="btn btn-secondary btn-sm ml-2">
                                <i class="fas fa-eraser"></i> Limpiar
                            </button>
                            <button type="button" id="ia_btn_cancelar" class="btn btn-danger btn-sm ml-2">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <div class="ia-estado-indicator float-right">
                                <span id="ia_estado_conexion" class="badge badge-secondary">
                                    <i class="fas fa-circle"></i> Desconectado
                                </span>
                            </div>
                        </div>
                        
                        <!-- Mensaje de procesamiento -->
                        <div id="ia_procesando" class="alert alert-info" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Procesando factura con IA...
                        </div>
                        
                        <!-- Contenedor para resultados de IA (oculto inicialmente) -->
                        <div id="ia_resultados_container" class="ia-resultados-container" style="display: none;">
                            <!-- Los resultados se mostrarán aquí dinámicamente -->
                        </div>
                    </div>
                    
                    <!-- Paso 2: Formulario de Recepción (oculto inicialmente) -->
                    <div id="paso2_formulario_recepcion" style="display: none;">
                        <div class="ia-section">
                            <div class="ia-header">
                                <h6><i class="fas fa-clipboard-list"></i> Completar Datos de Recepción</h6>
                                <small class="text-muted">La IA verificará automáticamente los datos al registrar</small>
                            </div>
                            <div id="ia_resumen_factura" class="alert alert-success" style="display: none;">
                                <i class="fas fa-check-circle"></i> <strong>Factura procesada:</strong> <span id="ia_resumen_texto"></span>
                            </div>
                        </div>
                        
                        <hr class="ia-divider">
                    
                    <div class="grupo-form">
                        <div class="grupo-interno">
                            <label for="correlativo">N° de Factura del Proveedor</label>
                            <input type="text" placeholder="N° de Factura" class="control-form" maxlength="6" id="correlativo" name="correlativo" />
                            <span class="span-value" id="scorrelativo"></span>
                        </div>
                        <div class="grupo-interno">
                            <label for="proveedor">Proveedor</label>
                            <select class="form-select" name="proveedor" id="proveedor">
                                <option value="" hidden>Seleccione el Proveedor</option>
                                <?php
                                foreach ($proveedores as $proveedor) {
                                    echo "<option value='" . $proveedor['id_proveedor'] . "'>" . $proveedor['nombre_proveedor'] . "</option>";
                                } ?>
                            </select>
                            <span class="span-value" id="sproveedor"></span>
                        </div>
                    </div>
                    <div class="envolver-form">
                        <input class="" type="text" id="codigoproducto" name="codigoproducto" style="display:none"/>
                        <input class="" type="text" id="idproducto" name="idproducto" style="display:none"/>
                        <button type="button" class="btn-listado btn-primary" id="listado" name="listado">Lista de Productos</button>
                    </div>
                    <div class="row">
                        <div class="col"><hr /></div>
                    </div>
                    <div class="table-responsive card shadow">
                        <table class="tabla" id="tablarecepcion">
                            <thead>
                                <tr>
                                    <th>Acción</th>
                                    <th style="display:none">Cl</th>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>modelo</th>
                                    <th>Marca</th>
                                    <th>Serial</th>
                                    <th>Costo por C/U</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody id="recepcion1"></tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="boton-form btn-primary" type="submit">
                            <i class="fas fa-save"></i> Registrar Recepción
                        </button>
                        <button class="boton-reset btn-primary" type="reset">
                            <i class="fas fa-redo"></i> Limpiar Formulario
                        </button>
                        <button type="button" class="btn btn-secondary" id="ia_btn_volver">
                            <i class="fas fa-arrow-left"></i> Volver a Cargar Factura
                        </button>
                    </div>
                </div> <!-- Cierre del paso2_formulario_recepcion -->
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
                                        <th>Codigo</th>
                                        <th>Nombre</th>
                                        <th>modelo</th>
                                        <th>Marca</th>
                                        <th>Serial</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center" id="listadop"></tbody>
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
        <h3>Lista de Recepciones</h3>
        <div class="ghost"></div>
    </div>
    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>N° DE FACTURA <br> DEL PROVEEDOR</th>
                <th>PROVEEDOR</th>
                <th>COSTO DE INVERSIÓN</th>
                <th>ACCIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recepciones)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No se han registrado recepciones.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recepciones as $recepcion): ?>
                    <tr data-id="<?= htmlspecialchars($recepcion['correlativo']) ?>">
                        <td>
                            <span class="campo-numeros">
                                <?= date('d/m/Y', strtotime($recepcion['fecha'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-numeros">
                                <?= htmlspecialchars($recepcion['correlativo']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-nombres">
                                <?= htmlspecialchars($recepcion['nombre_proveedor']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="campo-numeros">
                                <?= number_format($recepcion['costo_inversion'], 2, ',', '.') ?>
                            </span>
                        </td>
                        <td>
                            <ul>
                                <button class="btn-detalle"
                                    title="Ver Detalles"
                                    data-id_recepcion="<?= htmlspecialchars($recepcion['id_recepcion']) ?>"
                                    data-fecha="<?= htmlspecialchars($recepcion['fecha']) ?>"
                                    data-correlativo="<?= htmlspecialchars($recepcion['correlativo']) ?>"
                                    data-proveedor="<?= htmlspecialchars($recepcion['nombre_proveedor']) ?>"
                                    data-costo_inversion="<?= htmlspecialchars($recepcion['costo_inversion']) ?>">
                                    <img src="assets/img/eye.svg">
                                </button>
                                <?php if ($_SESSION['nombre_rol'] == 'Administrador' || $_SESSION['nombre_rol'] == 'SuperUsuario'): ?>
                                <button class="btn-anular"
                                    title="Anular Recepción"
                                    data-correlativo="<?= htmlspecialchars($recepcion['correlativo']) ?>">
                                    <img src="assets/img/circle-x.svg">
                                </button>
                                <?php endif; ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="modalDetallesRecepcion" class="modal-detalles" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="titulo-form">Detalles de la Recepción</h5>
                <button type="button" class="close" id="cerrarModalDetallesRecepcion">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group fila-dato">
                    <label>Fecha:</label>
                    <p id="detalle-fecha"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>N° de Factura:</label>
                    <p id="detalle-correlativo"></p>
                </div>
                <div class="form-group fila-dato">
                    <label>Proveedor:</label>
                    <p id="detalle-proveedor"></p>
                </div>
                <hr>
                <div class="table-responsive">
                    <table class="tablaConsultas" id="tablaDetalleProductosRecepcion">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Modelo</th>
                                <th>Marca</th>
                                <th>Serial</th>
                                <th>Cantidad</th>
                                <th>Costo</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDetalleProductosRecepcion"></tbody>
                    </table>
                </div>
                <div style="text-align:right; margin-top:16px;">
                    <label style="font-weight:bold;">Costo Total de la Inversión:</label>
                    <span id="detalle-costo-inversion" style="font-size:18px; color:#1f66df;"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="report-container">
    <div class="report-header">
        <h2 class="titulo-form">Reportes de Recepción</h2>
        <p class="texto-p">Seleccione y genere los reportes que desea visualizar</p>
    </div>
    <div class="report-selector">
        <label for="selectReporteRecepcion" class="title-select">Seleccionar Reporte:</label><br>
        <select id="selectReporteRecepcion" class="selector-reporte">
            <option value="todos">Todos los Reportes</option>
            <option value="proveedores">Recepciones por Proveedor</option>
            <option value="productos">Productos más Recibidos</option>
            <option value="mensual">Recepciones Mensuales</option>
        </select>
    </div>
    <div class="parameters-container">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <label for="fechaInicioRecepcion" class="title-select">Fecha inicio:</label>
                <input type="date" id="fechaInicioRecepcion" class="selector-reporte">
            </div>
            <div class="col-md-3">
                <label for="fechaFinRecepcion" class="title-select">Fecha fin:</label><br>
                <input type="date" id="fechaFinRecepcion" class="selector-reporte">
            </div>
            <div class="col-md-3">
                <label for="tipoGraficaRecepcion" class="title-select">Tipo de gráfica:</label>
                <select id="tipoGraficaRecepcion" class="selector-reporte">
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
        <div class="row g-3 align-items-center mt-2" id="parametrosIndividualesRecep"></div>
    </div>
    <div id="errorMessageRecepcion" class="error-message">
        No se pudieron cargar los datos. Verifique la conexión con el servidor.
    </div>

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
        <button class="btn btn-primary btn-download" onclick="descargarPDFRecepcion('reporteProveedores','Reporte_Recepciones_Proveedores.pdf')">Descargar PDF</button>
        <button class="btn btn-primary btn-download" style="color: white;" onclick="descargarImagenRecepcion('graficoProveedores','Grafico_Recepciones_Proveedores.png')">Descargar Gráfico</button>
    </div>
</div>
<div class="divider"></div>
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
        <button class="btn btn-primary btn-download" onclick="descargarPDFRecepcion('reporteProductos','Reporte_Recepciones_Productos.pdf')">Descargar PDF</button>
        <button class="btn btn-primary btn-download" style="color: white;" onclick="descargarImagenRecepcion('graficoProductos','Grafico_Recepciones_Productos.png')">Descargar Gráfico</button>
    </div>
</div>
<div class="divider"></div>
<div class="report-section" id="reporteMensualRecepcion">
    <h2 class="titulo-form">Recepciones Mensuales</h2>
    <div class="chart-container">
        <div class="chart-canvas">
            <canvas id="graficoMensualRecepcion" width="400" height="400"></canvas>
        </div>
        <div class="chart-table">
            <div id="tablaMensualRecepcion"></div>
        </div>
    </div>
    <div class="download-buttons">
        <button class="btn btn-primary btn-download" onclick="descargarPDFRecepcion('reporteMensualRecepcion','Reporte_Recepciones_Mensual.pdf')">Descargar PDF</button>
        <button class="btn btn-primary btn-download" style="color: white;" onclick="descargarImagenRecepcion('graficoMensualRecepcion','Grafico_Recepciones_Mensual.png')">Descargar Gráfico</button>
    </div>
</div>

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
            const fechaDato = d.fecha || d.fecha_recepcion || d.created_at || null;
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

    function agruparPorLabel(rows) {
        const map = new Map();
        (rows || []).forEach(r => {
            const key = String(r.label ?? '').trim() || 'Sin nombre';
            const val = Number(r.value ?? 0) || 0;
            map.set(key, (map.get(key) || 0) + val);
        });
        return Array.from(map.entries()).map(([label, value]) => ({ label, value }));
    }

    function renderReporteRecepcion(datos, labelKey, valueKey, canvasId, tablaId, titulo, tipoGrafica) {
        // Destruir gráfico previo si no hay datos
        if (!datos || datos.length === 0) {
            if (canvasId === 'graficoProveedores' && graficoProveedores) {
                graficoProveedores.destroy(); graficoProveedores = null;
            } else if (canvasId === 'graficoProductos' && graficoProductos) {
                graficoProductos.destroy(); graficoProductos = null;
            } else if (canvasId === 'graficoMensualRecepcion' && graficoMensualRecepcion) {
                graficoMensualRecepcion.destroy(); graficoMensualRecepcion = null;
            }
            const tablaEl = document.getElementById(tablaId);
            if (tablaEl) {
                tablaEl.innerHTML = `<div class="alert alert-warning text-center">📭 No hay datos disponibles para el período seleccionado</div>`;
            }
            const canvas = document.getElementById(canvasId);
            if (canvas) { const ctx = canvas.getContext('2d'); ctx && ctx.clearRect(0,0,canvas.width,canvas.height); }
            return;
        }

        const labels = datos.map(d => (d[labelKey] ?? d.label ?? 'Sin nombre'));
        const data = datos.map(d => Number(d[valueKey] ?? d.value ?? 0) || 0);
        const total = data.reduce((a, b) => a + b, 0);
        const colores = generarColores(labels.length);
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
                    <tbody>`;

        labels.forEach((nombre, i) => {
            const pct = total > 0 ? ((data[i] / total) * 100).toFixed(2) : 0;
            tablaHtml += `<tr><td>${nombre}</td><td>${data[i].toLocaleString()}</td><td>${pct}%</td></tr>`;
        });

        tablaHtml += `</tbody>
                    <tfoot class="table-active">
                        <tr><th>Total</th><th>${total.toLocaleString()}</th><th>100%</th></tr>
                    </tfoot>
                </table>
            </div>`;

        document.getElementById(tablaId).innerHTML = tablaHtml;
        const ctx = document.getElementById(canvasId).getContext('2d');

        if (canvasId === 'graficoProveedores' && graficoProveedores) graficoProveedores.destroy();
        else if (canvasId === 'graficoProductos' && graficoProductos) graficoProductos.destroy();
        else if (canvasId === 'graficoMensualRecepcion' && graficoMensualRecepcion) graficoMensualRecepcion.destroy();

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
                    legend: { display: tipoGrafica !== 'line', position: 'bottom' },
                    title: { display: true, text: titulo, font: { size: 16, weight: 'bold' } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const raw = context.raw;
                                let value = 0;
                                if (typeof context.parsed === 'number') value = context.parsed;
                                else if (context.parsed && typeof context.parsed === 'object') value = Number((context.parsed.x ?? context.parsed.y ?? raw ?? 0));
                                else value = Number(raw ?? 0);
                                const nums = (context.dataset.data || []).map(d => {
                                    if (typeof d === 'number') return d;
                                    if (d && typeof d === 'object') return Number(d.x ?? d.y ?? 0);
                                    const n = Number(d ?? 0); return isNaN(n) ? 0 : n;
                                });
                                const total = nums.reduce((a,b)=> a + (isNaN(b)?0:b), 0);
                                const pct = total > 0 ? ` (${((value/total)*100).toFixed(1)}%)` : '';
                                return `${value.toLocaleString(undefined,{maximumFractionDigits:2})}${pct}`;
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
        'label': 'Descripción',
        'mes': 'Mes'
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
        if (graficoProductos) { graficoProductos.destroy(); graficoProductos = null; }
        document.getElementById('reporteMensualRecepcion').style.display = 'none';
        if (graficoMensualRecepcion) { graficoMensualRecepcion.destroy(); graficoMensualRecepcion = null; }
    } else if (seleccion === 'productos') {
        document.getElementById('reporteProveedores').style.display = 'none';
        if (graficoProveedores) { graficoProveedores.destroy(); graficoProveedores = null; }
        document.getElementById('reporteProductos').style.display = 'block';
        document.getElementById('reporteMensualRecepcion').style.display = 'none';
        if (graficoMensualRecepcion) { graficoMensualRecepcion.destroy(); graficoMensualRecepcion = null; }
    } else if (seleccion === 'mensual') {
        document.getElementById('reporteProveedores').style.display = 'none';
        if (graficoProveedores) { graficoProveedores.destroy(); graficoProveedores = null; }
        document.getElementById('reporteProductos').style.display = 'none';
        if (graficoProductos) { graficoProductos.destroy(); graficoProductos = null; }
        document.getElementById('reporteMensualRecepcion').style.display = 'block';
    }
    buildParametrosUIRecep();
}

// Distinct helper
function distinctRecep(arr, key){
    const s = new Map();
    (arr||[]).forEach(r=>{ if(r && r[key]!==undefined && r[key]!==null){ const raw=String(r[key]).trim(); const norm=raw.toLowerCase(); if(!s.has(norm)) s.set(norm, raw); }});
    return Array.from(s.values());
}

function buildParametrosUIRecep(){
    const cont = document.getElementById('parametrosIndividualesRecep');
    if(!cont) return;
    const tipo = document.getElementById('selectReporteRecepcion').value;
    let html = '';
    if (tipo === 'proveedores'){
        const provs = Array.from(new Set(distinctRecep(recepcionesProveedor,'label'))).sort();
        html += `
        <div class="col-md-3">
            <label>Proveedor</label>
            <select id="paramProvRecep" class="form-select">
                <option value="">Todos</option>
                ${provs.map(p=>`<option value="${p}">${p}</option>`).join('')}
            </select>
        </div>`;
    } else if (tipo === 'productos'){
        const prods = Array.from(new Set(distinctRecep(productosRecibidos,'label'))).sort();
        const provs = Array.from(new Set(distinctRecep(productosRecibidos,'proveedor'))).sort();
        html += `
        <div class="col-md-4">
            <label>Producto</label>
            <select id="paramProdRecep" class="form-select">
                <option value="">Todos</option>
                ${prods.map(p=>`<option value="${p}">${p}</option>`).join('')}
            </select>
        </div>
        <div class="col-md-3">
            <label>Proveedor</label>
            <select id="paramProvProdRecep" class="form-select">
                <option value="">Todos</option>
                ${provs.map(p=>`<option value="${p}">${p}</option>`).join('')}
            </select>
        </div>
        <div class="col-md-2">
            <label>Top</label>
            <select id="paramTopNRecep" class="form-select">
                <option value="0">Todos</option>
                <option value="10">Top 10</option>
                <option value="20">Top 20</option>
                <option value="50">Top 50</option>
            </select>
        </div>`;
    } else if (tipo === 'mensual'){
        const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        html += `
        <div class="col-md-3">
            <label>Mes</label>
            <select id="paramMesRecep" class="form-select">
                <option value="">Todos</option>
                ${meses.map((m,i)=>`<option value="${i+1}">${m}</option>`).join('')}
            </select>
        </div>
        <div class="col-md-2">
            <label>Año</label>
            <input id="paramAnioRecep" type="number" class="form-control" placeholder="Ej: 2025" />
        </div>`;
    }
    cont.innerHTML = html;
}

    function getParametrosSeleccionadosRecep(){
        return {
            proveedor: document.getElementById('paramProvRecep')?.value || '',
            producto: document.getElementById('paramProdRecep')?.value || '',
            proveedorProducto: document.getElementById('paramProvProdRecep')?.value || '',
            topN: parseInt(document.getElementById('paramTopNRecep')?.value || '0',10) || 0,
            mes: document.getElementById('paramMesRecep')?.value || '',
            anio: document.getElementById('paramAnioRecep')?.value || ''
        };
    }

    function aplicarMesAnioRecep(datos, params){
        if(!datos) return [];
        const m = params.mes ? parseInt(params.mes,10) : null;
        let y = params.anio ? parseInt(params.anio,10) : null;
        const currentYear = new Date().getFullYear();
        if(!(y>0) || y>currentYear) y = null;
        if(!m && !y) return datos;
        const mesesES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        return (datos||[]).filter(d=>{
            let date=null;
            const cruda=d.fecha||d.fecha_recepcion||d.created_at||null;
            if(cruda){ const tmp=new Date(cruda); if(!isNaN(tmp)) date=tmp; }
            let mesCampo=d.mes_num||d.mes||null; let anioCampo=d.anio||d.año||d.year||null;
            if(typeof mesCampo==='string'){ const idx=mesesES.indexOf(mesCampo.toLowerCase()); if(idx>=0) mesCampo=idx+1; }
            const mes = date ? (date.getMonth()+1) : (mesCampo? parseInt(mesCampo,10): null);
            const anio = date ? date.getFullYear() : (anioCampo? parseInt(anioCampo,10): null);
            if(m && mes!==m) return false; if(y && anio!==y) return false; return true;
        });
    }

    function generarReportesRecepcion() {
        const tipoGrafica = document.getElementById('tipoGraficaRecepcion').value;
        const inicio = document.getElementById('fechaInicioRecepcion').value;
        const fin = document.getElementById('fechaFinRecepcion').value;
        const seleccion = document.getElementById('selectReporteRecepcion').value;
        const recepcionesProveedorFiltrado = filtrarPorFechas(recepcionesProveedor, inicio, fin);
        const productosRecibidosFiltrado  = filtrarPorFechas(productosRecibidos, inicio, fin);
        const recepcionMensualFiltrado    = recepcionMensual.slice();
        try {
            const params = getParametrosSeleccionadosRecep();
            let provFinal = recepcionesProveedorFiltrado;
            let prodFinal = productosRecibidosFiltrado;
            let mensFinal = recepcionMensualFiltrado;
            if (seleccion === 'proveedores' && params.proveedor){
                const norm = params.proveedor.trim().toLowerCase();
                provFinal = provFinal.filter(r=> String(r.label||'').trim().toLowerCase()===norm);
            }
            if (seleccion === 'productos'){
                if (params.producto){
                    const normP = params.producto.trim().toLowerCase();
                    prodFinal = prodFinal.filter(r=> String(r.label||'').trim().toLowerCase()===normP);
                }
                if (params.proveedorProducto){
                    const normProv = params.proveedorProducto.trim().toLowerCase();
                    prodFinal = prodFinal.filter(r=> String(r.proveedor||'').trim().toLowerCase()===normProv);
                }
            }
            if (seleccion === 'mensual') mensFinal = aplicarMesAnioRecep(mensFinal, params);
            let provAgg = agruparPorLabel(provFinal).sort((a,b)=> b.value - a.value);
            let prodAgg = agruparPorLabel(prodFinal).sort((a,b)=> b.value - a.value);
            if (seleccion === 'productos' && params.topN>0) prodAgg = prodAgg.slice(0, params.topN);
            if (document.getElementById('reporteProveedores').style.display === 'block'){
                renderReporteRecepcion(provAgg, "label", "value", "graficoProveedores", "tablaProveedores", "Recepciones por Proveedor", tipoGrafica);
            }
            if (document.getElementById('reporteProductos').style.display === 'block'){
                renderReporteRecepcion(prodAgg, "label", "value", "graficoProductos", "tablaProductos", "Productos más Recibidos", tipoGrafica);
            }
            if (document.getElementById('reporteMensualRecepcion').style.display === 'block'){
                renderReporteRecepcion(mensFinal.map(r=>({label: r.label, value: Number(r.value||0)})), "label", "value", "graficoMensualRecepcion", "tablaMensualRecepcion", "Recepciones Mensuales", tipoGrafica);
            }
            document.getElementById('errorMessageRecepcion').style.display = 'none';
        } catch (error) {
            console.error("Error al generar reportes de recepción:", error);
            document.getElementById('errorMessageRecepcion').style.display = 'block';
        }
    }

    function descargarPDFRecepcion(contenedorId, nombreArchivo) {
        try {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
            const reporte = document.getElementById(contenedorId);
            const titulo = reporte.querySelector('h3')?.textContent || "Reporte de Recepción";
            doc.setFontSize(18);
            doc.setTextColor(40);
            doc.text(titulo, 20, 20);
            let canvasId = contenedorId === 'reporteProveedores' ? 'graficoProveedores' : (contenedorId === 'reporteProductos' ? 'graficoProductos' : 'graficoMensualRecepcion');
            const canvas = document.getElementById(canvasId);
            if (canvas) {
                const imgData = canvas.toDataURL('image/png');
                const pageWidth = doc.internal.pageSize.getWidth();
                const imgWidth = pageWidth - 40;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                doc.addImage(imgData, 'PNG', 20, 30, imgWidth, imgHeight);
                let currentY = 30 + imgHeight + 10;
                const tablaElement = reporte.querySelector('.chart-table');
                if (tablaElement) {
                    html2canvas(tablaElement, { scale: 2, useCORS: true, backgroundColor: '#ffffff' }).then(tablaCanvas => {
                        const tablaImgData = tablaCanvas.toDataURL('image/png');
                        const tablaImgWidth = pageWidth - 40;
                        const tablaImgHeight = (tablaCanvas.height * tablaImgWidth) / tablaCanvas.width;
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
            alert("Error al generar el PDF.");
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
        toggleReportesRecepcion();
        buildParametrosUIRecep();
        document.getElementById('generarReporteRecepcionBtn').addEventListener('click', generarReportesRecepcion);
        document.getElementById('selectReporteRecepcion').addEventListener('change', toggleReportesRecepcion);
        generarReportesRecepcion();
    });
</script>

<script>
const proveedoresDisponibles = <?= json_encode($proveedores) ?>;
const productosDisponibles = <?= json_encode($productos) ?>;

function crearBloqueProducto(productosDisponibles) {
    return `
        <div class="row mb-2 grupo-producto">
            <div class="col-md-5">
                <label>Producto</label>
                <select class="form-control" name="productos[]">
                    ${productosDisponibles.map(prod => `<option value="${prod.id_producto}">${prod.nombre_producto}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-3">
                <label>Cantidad</label>
                <input type="number" class="form-control" name="cantidades[]" value="1" min="1">
            </div>
            <div class="col-md-2">
                <label>Costo</label>
                <input type="number" class="form-control" name="costos[]" value="0" min="0" step="0.01">
                <input type="hidden" name="iddetalles[]" value="">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger btn-eliminar-producto">Eliminar Producto</button>
            </div>
        </div>`;
}

$(document).on('click', '#btnAgregarProducto', function () {
    $('#contenedorDetalles').append(crearBloqueProducto(productosDisponibles));
});

$(document).on('click', '.btn-eliminar-producto', function () {
    $(this).closest('.grupo-producto').remove();
});

function eliminarBackdrop() {
    const backdrops = document.getElementsByClassName('modal-backdrop');
    while (backdrops.length > 0) {
        backdrops[0].parentNode.removeChild(backdrops[0]);
    }
}

function eliminarBackdropSeguro() {
    setTimeout(() => {
        const modalesAbiertos = document.querySelectorAll('.modal.show');
        if (modalesAbiertos.length === 0) {
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.parentNode.removeChild(backdrop));
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
        }
    }, 50);
}

$(document).on('click', '.modal .close', function() {
    $(this).closest('.modal').modal('hide');
    eliminarBackdropSeguro();
});
</script>

<?php include 'footer.php'; ?>

<!-- Estilos CSS para componentes IA -->
<style>
.ia-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #dee2e6;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.ia-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #007bff;
}

.ia-header h6 {
    color: #007bff;
    margin: 0;
    font-weight: 600;
}

.ia-header .fas {
    margin-right: 8px;
}

.ia-section .required::after {
    content: ' *';
    color: #dc3545;
}

.ia-botones-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ia-estado-indicator .badge {
    font-size: 0.8em;
    padding: 5px 10px;
}

.ia-estado-indicator .badge-success {
    background-color: #28a745;
}

.ia-estado-indicator .badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.ia-estado-indicator .badge-danger {
    background-color: #dc3545;
}

.ia-divider {
    border: 0;
    height: 2px;
    background: linear-gradient(to right, transparent, #007bff, transparent);
    margin: 20px 0;
}

.ia-resultados-container {
    margin-top: 15px;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.ia-resultados-container .alert {
    margin-bottom: 0;
    border-radius: 8px;
}

.ia-resultados-container .badge {
    font-size: 0.9em;
    padding: 6px 12px;
}

.ia-imagen-preview img {
    border: 2px solid #007bff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ia-imagen-preview .mt-1 {
    margin-top: 5px;
    color: #6c757d;
    font-size: 0.9em;
}

.ia-resultados-container .progress {
    height: 8px;
    border-radius: 4px;
}

.ia-resultados-container .progress-bar {
    border-radius: 4px;
}

.ia-resultados-container .list-unstyled li {
    padding: 8px;
    margin: 4px 0;
    border-radius: 4px;
    background-color: #f8f9fa;
    border-left: 4px solid #007bff;
}

.ia-resultados-container .badge-danger {
    background-color: #dc3545;
}

.ia-resultados-container .badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.ia-resultados-container .badge-info {
    background-color: #17a2b8;
}

.ia-resultados-container .badge-success {
    background-color: #28a745;
}

@media (max-width: 768px) {
    .ia-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .ia-botones-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ia-estado-indicator {
        margin-top: 10px;
        text-align: center;
    }
}
</style>

<!-- Cliente JavaScript del Microservicio IA -->
<script src="assets/javascript/ia-recepcion.js"></script>
<script src="assets/javascript/recepcion.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>

    <button class="btn-grafica" title="Visualizar Reportes" onclick="window.location.href='?pagina=reporteInventario'">
        <img src="assets/img/grafic.png" alt="Reportes" width="30" height="30">
    </button>
    <button class="btn-ayuda" title="Visualizar Ayuda">
        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
    </button>
</body>
</html>
<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>