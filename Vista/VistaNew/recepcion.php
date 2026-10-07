<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión (sistema tradicional)
if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

// Verificar y/o generar token JWT (sistema JWT)
require_once __DIR__ . '/../../Modelo/Config/Auth.php';
use Usuario\ProyectoCasalaiCa\Config\Auth;

if (!Auth::validateToken() && isset($_SESSION['id_usuario']) && isset($_SESSION['nombre_rol'])) {
    try {
        $token = Auth::generateToken($_SESSION['id_usuario'], $_SESSION['nombre_rol']);
        Auth::setTokenCookie($token);
    } catch (Exception $e) {
        error_log("Error al generar JWT en recepcion: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'recepcion';
$titulo_pagina = 'Gestionar de Recepción';

// Iniciar el buffer de contenido
ob_start();
?>

<link rel="stylesheet" href="assets/styles/tablas_section_styles.css">
<link rel="stylesheet" href="assets/styles/modal-anular.css">

<!-- Recepciones Section -->
<div class="table-section">
    <div class="section-header">
        <h2>Lista de Recepciones</h2>
        <div class="section-actions">
            <button class="btn-table-superior btn-ayuda" title="Visualizar Ayuda">
                <img src="assets/img/info-ayuda.svg">
            </button>
            <button class="btn-table-superior btn-incluir" title="Incluir Recepción" onclick="openModal('registrar')">
                <img src="assets/img/plus.svg">
            </button>
        </div>
    </div>

    <!-- Tabla de Recepciones -->
    <div class="table-container">
        <table class="table-info">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Correlativo</th>
                    <th>Proveedor</th>
                    <th>Monto Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recepciones)): ?>
                    <?php foreach ($recepciones as $recepcion): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($recepcion['fecha'] ?? 'now')); ?></td>
                            <td><?php echo htmlspecialchars($recepcion['correlativo'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($recepcion['nombre_proveedor'] ?? 'N/A'); ?></td>
                            <td>
                                $<?php echo number_format($recepcion['total_factura'] ?? $recepcion['costo_inversion'] ?? 0, 2, ',', '.'); ?>
                                <?php if (($recepcion['total_factura'] ?? null) === null): ?>
                                    <small title="El IVA de esta recepción no fué registrada">IVA no registrado</small>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <button class="btn-action btn-detallar" 
                                    title="Ver Detalles"
                                    data-correlativo="<?php echo htmlspecialchars((string)($recepcion['correlativo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-proveedor="<?php echo htmlspecialchars((string)($recepcion['nombre_proveedor'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-fecha="<?php echo htmlspecialchars((string)($recepcion['fecha'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-estado="<?php echo htmlspecialchars((string)($recepcion['estado'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    onclick="viewRecepcion(this)">
                                    <img src="assets/img/eye.svg">
                                </button>
                                <?php if (isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] === 'SuperUsuario'): ?>
                                    <button class="btn-action btn-anular" 
                                        title="Anular Recepción"
                                        onclick="anularRecepcion('<?php echo htmlspecialchars($recepcion['correlativo'] ?? ''); ?>')">
                                        <img src="assets/img/circle-x.svg">
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 3rem; color: #2196F3; margin-bottom: 15px;"></i>
                            <p style="color: #718096; margin: 0;">No hay recepciones registradas</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

            <!-- Modal para Registrar Recepción -->
            <div id="recepcionModal" class="modal">
                <div class="modal-content modal-large">
                    <div class="modal-header">
                        <div class="modal-header-content">
                            <div class="modal-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="modal-title-content">
                                <h2 id="modalTitle">Nueva Recepción</h2>
                                <p>Complete los datos para registrar una nueva recepción de productos</p>
                            </div>
                        </div>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="recepcionForm" enctype="multipart/form-data">
                            <!-- Información General -->
                            <div class="form-section">
                                <div class="section-title">
                                    <i class="fas fa-info-circle"></i>
                                    <h3>Información General</h3>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="proveedor">
                                            <i class="fas fa-building"></i>
                                            Proveedor*
                                        </label>
                                        <select id="proveedor" name="proveedor" required>
                                            <option value="">Seleccione un proveedor</option>
                                            <?php if (!empty($proveedores)): ?>
                                                <?php foreach ($proveedores as $proveedor): ?>
                                                    <option value="<?php echo $proveedor['id_proveedor'] ?? ''; ?>">
                                                        <?php echo htmlspecialchars($proveedor['nombre_proveedor'] ?? 'Sin nombre'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="">No hay proveedores disponibles</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="correlativo">
                                            <i class="fas fa-file-invoice"></i>
                                            Número de Factura*
                                        </label>
                                        <input type="text" id="correlativo" name="correlativo" required 
                                               placeholder="Ej: FAC-001" class="input-with-icon">
                                        <i class="fas fa-hashtag input-icon"></i>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="fotoFacturaRecepcion">
                                        <i class="fas fa-robot"></i>
                                        Factura para análisis asistido
                                    </label>
                                    <input type="file" id="fotoFacturaRecepcion" name="foto_factura" accept="image/*,.pdf">
                                    <small>Adjunte una imagen o PDF (máximo 5 MB). El análisis rellenará los datos detectados.</small>
                                </div>
                                <div id="previewFacturaRecepcion" class="recepcion-ia-preview" hidden>
                                    <img id="previewImagenRecepcion" alt="Vista previa de la factura" hidden>
                                    <iframe id="previewPdfRecepcion" title="Vista previa de la factura PDF" hidden></iframe>
                                </div>
                                <div id="estadoIARecepcion" class="recepcion-ia-status" role="status" aria-live="polite" hidden></div>
                                <div id="resultadoIARecepcion" class="recepcion-ia-result" hidden></div>
                            </div>
                            
                            <!-- Productos de la Recepción -->
                            <div class="form-section productos-section">
                                <div class="section-title">
                                    <i class="fas fa-boxes"></i>
                                    <h3>Productos de la Recepción</h3>
                                    <span class="product-count">0 productos</span>
                                </div>
                                <div id="productosList">
                                    <div class="producto-row">
                                        <div class="form-group">
                                            <label>Código</label>
                                            <input type="text" class="producto-codigo" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-box"></i>
                                                Nombre del producto*
                                            </label>
                                            <select name="producto[]" class="producto-select" required onchange="seleccionarProducto(this)">
                                                <option value="">Seleccione un producto</option>
                                                <?php if (!empty($productos)): ?>
                                                    <?php foreach ($productos as $producto): ?>
                                                        <option value="<?php echo htmlspecialchars((string)($producto['id_producto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-codigo="<?php echo htmlspecialchars((string)($producto['id_producto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-marca="<?php echo htmlspecialchars($producto['nombre_marca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-modelo="<?php echo htmlspecialchars($producto['nombre_modelo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-serial="<?php echo htmlspecialchars($producto['serial'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                            <?php echo htmlspecialchars($producto['nombre_producto'] ?? 'Sin nombre', ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option value="">No hay productos disponibles</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Marca</label>
                                            <input type="text" class="producto-marca" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Modelo</label>
                                            <input type="text" class="producto-modelo" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Serial</label>
                                            <input type="text" class="producto-serial" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-dollar-sign"></i>
                                                Costo Unitario*
                                            </label>
                                            <input type="number" name="costo[]" required placeholder="0.00" step="0.01" min="0" class="input-with-icon" oninput="calcularSubtotal(this)">
                                            <i class="fas fa-dollar-sign input-icon"></i>
                                        </div>
                                        <div class="form-group">
                                            <label>
                                                <i class="fas fa-cubes"></i>
                                                Cantidad*
                                            </label>
                                            <input type="number" name="cantidad[]" required placeholder="0" min="1" class="input-with-icon" oninput="calcularSubtotal(this)">
                                            <i class="fas fa-hashtag input-icon"></i>
                                        </div>
                                        <div class="form-group subtotal-group">
                                            <label>
                                                <i class="fas fa-calculator"></i>
                                                Subtotal
                                            </label>
                                            <input type="text" class="subtotal-display" readonly value="$0.00">
                                        </div>
                                        <button type="button" class="btn-remove-row" onclick="removeProductoRow(this)" title="Eliminar producto">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="productos-footer">
                                    <button type="button" class="btn-add-row" onclick="addProductoRow()">
                                        <i class="fas fa-plus"></i> Agregar Producto
                                    </button>
                                    <div class="total-section recepcion-resumen-factura">
                                        <div class="resumen-factura-item">
                                            <span class="total-label">Subtotal:</span>
                                            <strong class="total-value" id="subtotalRecepcion">$0.00</strong>
                                        </div>
                                        <div class="resumen-factura-item resumen-iva-tasa">
                                            <label for="porcentajeIvaRecepcion">IVA (%)</label>
                                            <input type="number" id="porcentajeIvaRecepcion" name="porcentaje_iva" value="0" min="0" max="100" step="0.01" oninput="calcularTotal()">
                                        </div>
                                        <div class="resumen-factura-item">
                                            <span class="total-label">Monto IVA:</span>
                                            <strong class="total-value" id="montoIvaRecepcion">$0.00</strong>
                                        </div>
                                        <div class="resumen-factura-item resumen-factura-total">
                                            <span class="total-label">Total factura:</span>
                                            <strong class="total-value" id="totalValue">$0.00</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn-cancel" onclick="closeModal()">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <button class="btn-save" onclick="saveRecepcion()">
                                    <i class="fas fa-check"></i> Guardar Recepción
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<!-- Modal para Ver Detalles de Recepción -->
<div id="viewModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <div class="modal-header-content">
                <div class="modal-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Detalles de Recepción</h2>
                    <p>Información completa de la recepción seleccionada</p>
                </div>
            </div>
            <span class="close-modal" onclick="closeViewModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="recepcion-detalles">
                <!-- Información General -->
                <div class="detalle-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        <h3>Información General</h3>
                    </div>
                    <div class="detalle-grid">
                        <div class="detalle-item">
                            <label>Correlativo:</label>
                            <span id="viewCorrelativo">REC-001</span>
                        </div>
                        <div class="detalle-item">
                            <label>Proveedor:</label>
                            <span id="viewProveedor">TechCorp S.A.</span>
                        </div>
                        <div class="detalle-item">
                            <label>Fecha:</label>
                            <span id="viewFecha">2026-09-29</span>
                        </div>
                        <div class="detalle-item">
                            <label>Estado:</label>
                            <span class="status-badge completed" id="viewEstado">Completada</span>
                        </div>
                    </div>
                </div>

                <!-- Productos -->
                <div class="detalle-section">
                    <div class="section-title">
                        <i class="fas fa-boxes"></i>
                        <h3>Productos Recibidos</h3>
                    </div>
                    <div class="productos-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Costo Unitario</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="viewProductos">
                                <tr>
                                    <td>iPhone 15 Pro Max</td>
                                    <td>10</td>
                                    <td>$1,199.00</td>
                                    <td>$11,990.00</td>
                                </tr>
                                <tr>
                                    <td>MacBook Air M3</td>
                                    <td>5</td>
                                    <td>$1,099.00</td>
                                    <td>$5,495.00</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3"><strong>Subtotal:</strong></td>
                                    <td><strong id="viewSubtotal">$17,485.00</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="3"><strong>IVA (<span id="viewIvaPorcentaje">0%</span>):</strong></td>
                                    <td><strong id="viewMontoIva">No registrado</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="3"><strong>Total factura:</strong></td>
                                    <td><strong id="viewTotal">$17,485.00</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Anular Recepción -->
<div id="anularModal" class="modal">
    <div class="modal-content">
        <div class="modal-header warning">
            <div class="modal-header-content">
                <div class="modal-icon warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="modal-title-content">
                    <h2>Anular Recepción</h2>
                    <p>Confirmación de anulación de recepción</p>
                </div>
            </div>
            <span class="close-modal" onclick="closeAnularModal()">&times;</span>
        </div>
        <div class="modal-body-anular">
            <div class="anular-content">
                <div class="anular-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <h3>¿Está seguro de anular esta recepción?</h3>
                <p>Esta acción no se puede deshacer y afectará el inventario del sistema.</p>
                
                <div class="anular-info">
                    <div class="info-row">
                        <label>Correlativo:</label>
                        <span id="anularCorrelativo"></span>
                    </div>
                </div>
                <div class="modal-footer-anular">
                    <button class="btm-confirmar btn-cancel" onclick="closeAnularModal()">
                        Cancelar
                    </button>
                    <button class="btm-confirmar btn-danger" onclick="confirmarAnulacion()">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

            <style>
                /* Estilos específicos de Recepciones */
                .recepciones-section {
                    margin-top: 30px;
                }

                /* Override modal size for recepcion */
                #recepcionModal .modal-content.modal-large {
                    max-width: 95%;
                    width: 1300px;
                    max-height: 90vh;
                    overflow-y: auto;
                }

                #recepcionModal .modal-body {
                    max-height: 70vh;
                    overflow-y: auto;
                }

                #viewModal .modal-content.modal-large {
                    max-height: 90vh;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                }

                #viewModal .modal-header,
                #viewModal .modal-footer {
                    flex-shrink: 0;
                }

                #viewModal .modal-body {
                    min-height: 0;
                    overflow-y: auto;
                    overscroll-behavior: contain;
                }

                #recepcionModal .recepcion-ia-preview {
                    margin: 12px 0 18px;
                }

                #recepcionModal [hidden] {
                    display: none !important;
                }

                #recepcionModal .recepcion-ia-preview img,
                #recepcionModal .recepcion-ia-preview iframe {
                    display: block;
                    width: 100%;
                    max-height: 360px;
                    border: 1px solid rgba(33, 150, 243, 0.2);
                    border-radius: 8px;
                    background: white;
                }

                #recepcionModal .recepcion-ia-preview img {
                    width: auto;
                    max-width: 100%;
                    height: auto;
                }

                #recepcionModal .recepcion-ia-preview iframe {
                    height: 360px;
                }

                #recepcionModal .recepcion-ia-status,
                #recepcionModal .recepcion-ia-result {
                    margin-top: 12px;
                    padding: 12px 16px;
                    border: 1px solid rgba(33, 150, 243, 0.2);
                    border-radius: 8px;
                    background: rgba(33, 150, 243, 0.05);
                    color: #2d3748;
                    white-space: pre-line;
                }

                #recepcionModal .recepcion-ia-status[data-state="error"] {
                    border-color: rgba(220, 53, 69, 0.3);
                    background: rgba(220, 53, 69, 0.08);
                }

                .status-badge {
                    padding: 6px 14px;
                    border-radius: 20px;
                    font-size: 0.75rem;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .status-badge.completed {
                    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
                    color: white;
                }

                .status-badge.pending {
                    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
                    color: white;
                }

                .status-badge.cancelled {
                    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
                    color: white;
                }

                /* Productos de Recepción */
                .form-section {
                    background: rgba(33, 150, 243, 0.03);
                    border-radius: 12px;
                    padding: 25px;
                    margin-bottom: 20px;
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .section-title {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 2px solid rgba(33, 150, 243, 0.1);
                }

                .section-title i {
                    font-size: 1.3rem;
                    color: #2196F3;
                }

                .section-title h3 {
                    font-size: 1.1rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin: 0;
                }

                .section-title .product-count {
                    margin-left: auto;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                    padding: 4px 12px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    font-weight: 600;
                }

                .productos-section {
                    background: linear-gradient(135deg, rgba(33, 150, 243, 0.05) 0%, rgba(25, 118, 210, 0.05) 100%);
                }

                #productosList {
                    overflow-x: auto;
                    padding-bottom: 8px;
                }

                .productos-recepcion h3 {
                    font-size: 1.1rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin-bottom: 15px;
                }

                .producto-row {
                    display: grid;
                    grid-template-columns: minmax(190px, 1.8fr) minmax(75px, 0.55fr) repeat(3, minmax(95px, 0.9fr)) minmax(125px, 1.1fr) minmax(90px, 0.8fr) minmax(95px, 0.9fr) auto;
                    min-width: 1080px;
                    gap: 12px;
                    margin-bottom: 15px;
                    align-items: start;
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .subtotal-group {
                    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
                    border-radius: 8px;
                    padding: 10px;
                    display: flex;
                    flex-direction: column;
                    align-items: flex-start;
                }

                .subtotal-group label {
                    color: white;
                    font-size: 0.7rem;
                    font-weight: 600;
                    margin-bottom: 5px;
                }

                .subtotal-display {
                    background: white;
                    border: none;
                    color: #2d3748;
                    font-weight: 700;
                    text-align: right;
                    font-size: 0.9rem;
                    padding: 4px 8px;
                    border-radius: 4px;
                    width: 100%;
                }

                .productos-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 2px solid rgba(33, 150, 243, 0.1);
                }

                .total-section {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    padding: 12px 20px;
                    border-radius: 10px;
                    color: white;
                }

                .total-label {
                    font-size: 0.9rem;
                    font-weight: 600;
                }

                .total-value {
                    font-size: 1.3rem;
                    font-weight: 800;
                }

                .recepcion-resumen-factura {
                    display: grid;
                    grid-template-columns: repeat(4, minmax(130px, 1fr));
                    align-items: center;
                    flex: 1;
                }

                .resumen-factura-item {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                    min-width: 0;
                }

                .resumen-factura-item .total-label,
                .resumen-factura-item label {
                    color: white;
                    font-size: 0.8rem;
                    font-weight: 600;
                }

                .resumen-factura-item .total-value {
                    font-size: 1rem;
                }

                .resumen-iva-tasa input {
                    width: 100%;
                    min-width: 0;
                    padding: 5px 8px;
                    border: 1px solid rgba(255, 255, 255, 0.7);
                    border-radius: 5px;
                }

                .resumen-factura-total {
                    border-left: 1px solid rgba(255, 255, 255, 0.35);
                    padding-left: 14px;
                }

                .input-with-icon {
                    position: relative;
                }

                .input-icon {
                    position: absolute;
                    right: 15px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #2196F3;
                    pointer-events: none;
                }

                .input-with-icon input,
                .input-with-icon select {
                    padding-right: 40px;
                }

                /* Mejoras en labels */
                .form-group label {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-weight: 600;
                    color: #2d3748;
                    margin-bottom: 8px;
                }

                .form-group label i {
                    color: #2196F3;
                    font-size: 0.9rem;
                }

                /* Modal Header Mejorado */
                .modal-header-content {
                    display: flex;
                    align-items: center;
                    gap: 15px;
                }

                .modal-icon {
                    width: 50px;
                    height: 50px;
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .modal-icon i {
                    font-size: 1.5rem;
                    color: white;
                }

                .modal-title-content h2 {
                    font-size: 1.4rem;
                    font-weight: 700;
                    color: white;
                    margin: 0 0 5px 0;
                }

                .modal-title-content p {
                    font-size: 0.85rem;
                    color: rgba(255, 255, 255, 0.8);
                    margin: 0;
                }

                .btn-save {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-save:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
                }

                /* Modal de Detalles */
                .recepcion-detalles {
                    display: flex;
                    flex-direction: column;
                    gap: 25px;
                }

                .detalle-section {
                    background: rgba(33, 150, 243, 0.03);
                    border-radius: 12px;
                    padding: 20px;
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .detalle-grid {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 15px;
                }

                .detalle-item {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }

                .detalle-item label {
                    font-size: 0.85rem;
                    font-weight: 600;
                    color: #718096;
                }

                .detalle-item span {
                    font-size: 1rem;
                    font-weight: 700;
                    color: #2d3748;
                }

                .productos-table {
                    overflow-x: auto;
                }

                .productos-table table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .productos-table thead {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .productos-table th {
                    padding: 12px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 0.85rem;
                }

                .productos-table td {
                    padding: 12px;
                    border-bottom: 1px solid #e9ecef;
                }

                .productos-table tfoot {
                    background: rgba(33, 150, 243, 0.05);
                }

                .productos-table tfoot td {
                    font-weight: 700;
                    color: #2196F3;
                }

                .observaciones-text {
                    background: white;
                    padding: 15px;
                    border-radius: 8px;
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .observaciones-text p {
                    margin: 0;
                    color: #2d3748;
                    line-height: 1.6;
                }

                /* Botones de sección */
                .btn-add-recepcion, .btn-filter {
                    padding: 12px 20px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-add-recepcion {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-filter {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-report {
                    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
                    color: white;
                }

                .btn-add-recepcion:hover, .btn-filter:hover, .btn-report:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .productos-footer {
                        align-items: stretch;
                        flex-direction: column;
                        gap: 12px;
                    }

                    .recepcion-resumen-factura {
                        grid-template-columns: repeat(2, minmax(120px, 1fr));
                    }

                    .resumen-factura-total {
                        border-left: 0;
                        padding-left: 0;
                    }

                    .producto-row {
                        grid-template-columns: minmax(190px, 1.8fr) minmax(75px, 0.55fr) repeat(3, minmax(95px, 0.9fr)) minmax(125px, 1.1fr) minmax(90px, 0.8fr) minmax(95px, 0.9fr) auto;
                    }
                }

                @media (max-width: 1200px) {
                    .producto-row {
                        grid-template-columns: minmax(190px, 1.8fr) minmax(75px, 0.55fr) repeat(3, minmax(95px, 0.9fr)) minmax(125px, 1.1fr) minmax(90px, 0.8fr) minmax(95px, 0.9fr) auto;
                        gap: 10px;
                    }
                }
            </style>

            <script src="microservicio/javascript/asistente_recepcion.js"></script>
            <script>
                const asistenteRecepcionIA = typeof AsistenteRecepcionIA !== 'undefined'
                    ? new AsistenteRecepcionIA({
                        apiUrl: 'http://127.0.0.1:8000',
                        proxyUrl: '?pagina=recepcion',
                        selectores: { alertas: '#estadoIARecepcion' }
                    })
                    : null;
                let urlPreviewFacturaRecepcion = null;

                function establecerEstadoIARecepcion(mensaje, estado = 'info') {
                    const estadoEl = document.getElementById('estadoIARecepcion');
                    estadoEl.textContent = mensaje;
                    estadoEl.dataset.state = estado;
                    estadoEl.hidden = !mensaje;
                }

                function activarContingenciaIARecepcion(detalle = '') {
                    establecerEstadoIARecepcion(
                        `El servicio de lectura automática por IA no está disponible temporalmente. Se ha habilitado el formulario manual. La factura se conservará al registrar.${detalle ? ` Detalle: ${detalle}` : ''}`,
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

                    document.getElementById('porcentajeIvaRecepcion').value =
                        Number(factura.porcentaje_iva || 0).toFixed(2);
                    calcularTotal();

                    actualizarContadorProductos();
                    const camposPendientes = [];
                    if (factura.nombre_proveedor && !proveedorEncontrado) camposPendientes.push('proveedor');
                    if (productosDetectados.some(producto => !buscarOpcionCatalogo(document.querySelector('.producto-select'), producto.nombre))) {
                        camposPendientes.push('selección de productos del catálogo');
                    }
                    const nota = camposPendientes.length
                        ? ` Revisa manualmente: ${camposPendientes.join(' y ')}.`
                        : ' Revisa los datos antes de guardar.';
                    establecerEstadoIARecepcion(`Análisis completado. Factura: ${factura.numero_factura || 'no detectada'}.${nota}`);
                    document.getElementById('resultadoIARecepcion').textContent =
                        `Proveedor detectado: ${factura.nombre_proveedor || 'no detectado'}\n` +
                        `Productos detectados: ${productosDetectados.length}\n` +
                        `Subtotal: $${Number(factura.subtotal_factura || 0).toFixed(2)}\n` +
                        `IVA: ${Number(factura.porcentaje_iva || 0).toFixed(2)}% ($${Number(factura.monto_iva || 0).toFixed(2)})\n` +
                        `Total factura: $${Number(factura.total_factura || 0).toFixed(2)}\n` +
                        `Confianza OCR: ${(Number(factura.confianza_promedio || 0) * 100).toFixed(1)}%`;
                    document.getElementById('resultadoIARecepcion').hidden = false;
                }

                function limpiarFacturaIARecepcion() {
                    if (asistenteRecepcionIA) asistenteRecepcionIA.limpiarCache();
                    if (urlPreviewFacturaRecepcion) URL.revokeObjectURL(urlPreviewFacturaRecepcion);
                    urlPreviewFacturaRecepcion = null;
                    document.getElementById('previewFacturaRecepcion').hidden = true;
                    document.getElementById('previewImagenRecepcion').hidden = true;
                    document.getElementById('previewPdfRecepcion').hidden = true;
                    document.getElementById('previewImagenRecepcion').removeAttribute('src');
                    document.getElementById('previewPdfRecepcion').removeAttribute('src');
                    document.getElementById('resultadoIARecepcion').textContent = '';
                    document.getElementById('resultadoIARecepcion').hidden = true;
                    establecerEstadoIARecepcion('');
                }

                async function analizarFacturaRecepcion(archivo) {
                    if (!asistenteRecepcionIA) {
                        establecerEstadoIARecepcion('No se pudo cargar el cliente del microservicio.', 'error');
                        return;
                    }
                    establecerEstadoIARecepcion('Analizando factura con OCR...');
                    const resultado = await asistenteRecepcionIA.extraerDesdeImagen(archivo);
                    if (!resultado.exito) {
                        if (resultado.contingencia) activarContingenciaIARecepcion(resultado.error);
                        else establecerEstadoIARecepcion(`No se pudo analizar la factura: ${resultado.error || 'error del servicio'}`, 'error');
                        return;
                    }
                    rellenarRecepcionDesdeFactura(resultado.data);
                }

                // Función para abrir modal de recepción
                function openModal(type) {
                    const modal = document.getElementById('recepcionModal');
                    const modalTitle = document.getElementById('modalTitle');
                    const form = document.getElementById('recepcionForm');
                    
                    if (type === 'registrar') {
                        modalTitle.textContent = 'Nueva Recepción';
                        form.reset();
                        limpiarFacturaIARecepcion();
                        const filas = document.querySelectorAll('#productosList .producto-row');
                        filas.forEach((fila, indice) => { if (indice > 0) fila.remove(); });
                        actualizarContadorProductos();
                        calcularTotal();
                    }
                    
                    modal.style.display = 'block';
                    modal.style.animation = 'fadeIn 0.3s ease';
                }

                // Función para cerrar modal de recepción
                function closeModal() {
                    const modal = document.getElementById('recepcionModal');
                    modal.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                }

                // Función para abrir modal de búsqueda
                function openFilterModal() {
                    const modal = document.getElementById('searchModal');
                    modal.style.display = 'block';
                    modal.style.animation = 'fadeIn 0.3s ease';
                }

                // Función para cerrar modal de búsqueda
                function closeSearchModal() {
                    const modal = document.getElementById('searchModal');
                    modal.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                }

                // Función para agregar fila de producto
                function addProductoRow() {
                    const productosList = document.getElementById('productosList');
                    const newRow = productosList.querySelector('.producto-row').cloneNode(true);
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
                    fila.querySelector('.producto-codigo').value = opcion?.dataset.codigo || '';
                    fila.querySelector('.producto-marca').value = opcion?.dataset.marca || '';
                    fila.querySelector('.producto-modelo').value = opcion?.dataset.modelo || '';
                    fila.querySelector('.producto-serial').value = opcion?.dataset.serial || '';
                    calcularSubtotal(select);
                }

                // Función para calcular subtotal de una fila
                function calcularSubtotal(input) {
                    const row = input.closest('.producto-row');
                    const cantidad = parseFloat(row.querySelector('input[name="cantidad[]"]').value) || 0;
                    const costo = parseFloat(row.querySelector('input[name="costo[]"]').value) || 0;
                    const subtotal = cantidad * costo;
                    row.querySelector('.subtotal-display').value = '$' + subtotal.toFixed(2);
                    calcularTotal();
                }

                // Función para calcular total general
                function calcularTotal() {
                    const subtotal = Array.from(document.querySelectorAll('#productosList .producto-row'))
                        .reduce((suma, fila) => {
                            const cantidad = Number(fila.querySelector('input[name="cantidad[]"]').value) || 0;
                            const costo = Number(fila.querySelector('input[name="costo[]"]').value) || 0;
                            return suma + cantidad * costo;
                        }, 0);
                    const porcentajeIva = Number(document.getElementById('porcentajeIvaRecepcion')?.value) || 0;
                    const montoIva = Math.round((subtotal * (porcentajeIva / 100) + Number.EPSILON) * 100) / 100;
                    document.getElementById('subtotalRecepcion').textContent = '$' + subtotal.toFixed(2);
                    document.getElementById('montoIvaRecepcion').textContent = '$' + montoIva.toFixed(2);
                    document.getElementById('totalValue').textContent = '$' + (subtotal + montoIva).toFixed(2);
                }

                // Función para actualizar contador de productos
                function actualizarContadorProductos() {
                    const count = document.querySelectorAll('.producto-row').length;
                    document.querySelector('.product-count').textContent = count + ' producto' + (count !== 1 ? 's' : '');
                }

                // Función para eliminar fila de producto
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

                // Función para guardar recepción
                async function saveRecepcion() {
                    const form = document.getElementById('recepcionForm');
                    if (!form.reportValidity()) return;
                    const botonGuardar = document.querySelector('#recepcionModal .modal-footer .btn-save');
                    botonGuardar.disabled = true;
                    
                    try {
                        const archivo = document.getElementById('fotoFacturaRecepcion').files[0];
                        let modoContingencia = false;
                        let iaVerificada = false;
                        if (archivo) {
                            if (!asistenteRecepcionIA) {
                                modoContingencia = true;
                            } else if (!asistenteRecepcionIA.getFacturaId()) {
                                const extraccion = await asistenteRecepcionIA.extraerDesdeImagen(archivo);
                                if (!extraccion.exito) {
                                    if (!extraccion.contingencia) throw new Error(extraccion.error || 'No se pudo analizar la factura.');
                                    modoContingencia = true;
                                } else {
                                    rellenarRecepcionDesdeFactura(extraccion.data);
                                }
                            }

                            if (!modoContingencia) {
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
                                const verificacion = await asistenteRecepcionIA.verificarCoherencia(
                                    asistenteRecepcionIA.getFacturaId(), datosFormulario
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
                                        return;
                                    }
                                    if (!confirm(`Se encontraron diferencias con la factura:\n\n${detalle}\n\n¿Deseas guardar de todos modos?`)) return;
                                    iaVerificada = true;
                                } else {
                                    iaVerificada = true;
                                }
                            }

                            if (modoContingencia) {
                                activarContingenciaIARecepcion();
                                if (!confirm('El servicio de lectura automática por IA no está disponible temporalmente. Se ha habilitado el formulario manual. La factura se conservará al registrar. ¿Deseas continuar con el registro manual?')) return;
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
                            const mensaje = data.procesamiento_pendiente
                                ? 'Recepción registrada. La factura quedó resguardada y marcada para procesamiento posterior.'
                                : data.factura_resguardada
                                    ? 'Recepción registrada. La factura quedó resguardada de forma privada.'
                                    : 'Recepción registrada correctamente.';
                            alert(mensaje);
                            closeModal();
                            location.reload();
                        } else {
                            alert('Error al registrar recepción: ' + (data.message || 'Error desconocido'));
                        }
                    } catch (error) {
                        console.error('Error al guardar recepción:', error);
                        establecerEstadoIARecepcion(error.message || 'Error al guardar recepción.', 'error');
                        alert(error.message || 'Error al guardar recepción. Por favor, intente nuevamente.');
                    } finally {
                        botonGuardar.disabled = false;
                    }
                }

                // Función para ver detalles de recepción
                function viewRecepcion(boton) {
                    const datosFila = boton instanceof HTMLElement ? boton.dataset : {};
                    const correlativo = datosFila.correlativo || boton;
                    const proveedorFila = datosFila.proveedor || '';
                    const fechaFila = datosFila.fecha || '';
                    const estadoFila = datosFila.estado || '';

                    function actualizarInformacionGeneral(recepcion = {}) {
                        const proveedor = proveedorFila || recepcion.nombre_proveedor || recepcion.proveedor;
                        const fecha = fechaFila || recepcion.fecha || recepcion.fecha_recepcion;
                        const estado = estadoFila || recepcion.estado || recepcion.estatus;

                        document.getElementById('viewCorrelativo').textContent = recepcion.correlativo || correlativo || 'N/A';
                        document.getElementById('viewProveedor').textContent = proveedor || 'No disponible';
                        document.getElementById('viewFecha').textContent = fecha || 'No disponible';

                        const estadoElemento = document.getElementById('viewEstado');
                        const estadoNormalizado = String(estado || '').toLowerCase();
                        estadoElemento.textContent = estado ? estadoNormalizado.toUpperCase() : 'No disponible';
                        estadoElemento.className = 'status-badge ' + (
                            estadoNormalizado === 'habilitado' ? 'completed' :
                            estadoNormalizado === 'anulado' ? 'cancelled' : 'pending'
                        );
                    }

                    actualizarInformacionGeneral();

                    // Cargar datos reales desde el backend
                    fetch('?pagina=recepcion', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'accion=obtener_recepcion&correlativo=' + encodeURIComponent(correlativo)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status === 'success') {
                            const recepcion = data.recepcion;
                            actualizarInformacionGeneral(recepcion);
                            
                            // Cargar productos
                            const productosTable = document.getElementById('viewProductos');
                            productosTable.innerHTML = '';
                            const productosRecepcion = recepcion.productos || data.productos || [];
                            const subtotalProductos = productosRecepcion.reduce((suma, prod) =>
                                suma + (Number(prod.cantidad) || 0) * (Number(prod.costo) || 0), 0);
                            productosRecepcion.forEach(prod => {
                                    const cantidad = Number(prod.cantidad) || 0;
                                    const costo = Number(prod.costo) || 0;
                                    const subtotal = cantidad * costo;
                                    productosTable.innerHTML += `
                                        <tr>
                                            <td>${prod.producto || prod.nombre_producto || 'N/A'}</td>
                                            <td>${cantidad}</td>
                                            <td>$${costo.toFixed(2)}</td>
                                            <td>$${subtotal.toFixed(2)}</td>
                                        </tr>
                                    `;
                            });

                            const subtotalFactura = recepcion.subtotal_factura === null || recepcion.subtotal_factura === undefined
                                ? subtotalProductos
                                : Number(recepcion.subtotal_factura);
                            const ivaRegistrado = recepcion.porcentaje_iva !== null && recepcion.porcentaje_iva !== undefined &&
                                recepcion.monto_iva !== null && recepcion.monto_iva !== undefined;
                            document.getElementById('viewSubtotal').textContent = '$' + subtotalFactura.toFixed(2);
                            document.getElementById('viewIvaPorcentaje').textContent = ivaRegistrado
                                ? `${Number(recepcion.porcentaje_iva).toFixed(2)}%`
                                : 'No registrado';
                            document.getElementById('viewMontoIva').textContent = ivaRegistrado
                                ? '$' + Number(recepcion.monto_iva).toFixed(2)
                                : 'No registrado';
                            document.getElementById('viewTotal').textContent = recepcion.total_factura !== null && recepcion.total_factura !== undefined
                                ? '$' + Number(recepcion.total_factura).toFixed(2)
                                : 'No registrado';
                            
                            // Observaciones
                            document.getElementById('viewObservaciones').textContent = recepcion.observaciones || 'Sin observaciones';
                            
                            const modal = document.getElementById('viewModal');
                            modal.style.display = 'block';
                            modal.style.animation = 'fadeIn 0.3s ease';
                        } else {
                            actualizarInformacionGeneral();
                            const modal = document.getElementById('viewModal');
                            modal.style.display = 'block';
                            modal.style.animation = 'fadeIn 0.3s ease';
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar recepción:', error);
                        actualizarInformacionGeneral();
                        
                        const modal = document.getElementById('viewModal');
                        modal.style.display = 'block';
                        modal.style.animation = 'fadeIn 0.3s ease';
                    });
                }

                // Función para cerrar modal de detalles
                function closeViewModal() {
                    const modal = document.getElementById('viewModal');
                    modal.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                }

                // Función para abrir modal de anulación
                function anularRecepcion(correlativo) {
                    // Cargar datos reales desde el backend
                    fetch('?pagina=recepcion', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'accion=obtener_recepcion&correlativo=' + encodeURIComponent(correlativo)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status === 'success') {
                            const recepcion = data.recepcion;
                            document.getElementById('anularCorrelativo').textContent = recepcion.correlativo || 'N/A';
                            
                            const modal = document.getElementById('anularModal');
                            modal.style.display = 'block';
                            modal.style.animation = 'fadeIn 0.3s ease';
                        } else {
                            // Fallback a datos simulados
                            document.getElementById('anularCorrelativo').textContent = correlativo;
                            
                            const modal = document.getElementById('anularModal');
                            modal.style.display = 'block';
                            modal.style.animation = 'fadeIn 0.3s ease';
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar recepción:', error);
                        // Fallback a datos simulados
                        document.getElementById('anularCorrelativo').textContent = correlativo;
                        
                        const modal = document.getElementById('anularModal');
                        modal.style.display = 'block';
                        modal.style.animation = 'fadeIn 0.3s ease';
                    });
                }

                // Función para cerrar modal de anulación
                function closeAnularModal() {
                    const modal = document.getElementById('anularModal');
                    modal.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                }

                // Función para confirmar anulación
                function confirmarAnulacion() {
                    const motivo = document.getElementById('motivoAnulacion').value;
                    const correlativo = document.getElementById('anularCorrelativo').textContent;
                    
                    if (!motivo.trim()) {
                        alert('Por favor, ingrese el motivo de la anulación');
                        return;
                    }
                    
                    // Enviar anulación al backend
                    const formData = new FormData();
                    formData.append('accion', 'anular');
                    formData.append('correlativo', correlativo);
                    formData.append('motivo', motivo);
                    
                    fetch('?pagina=recepcion', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.status === 'success') {
                            alert('Recepción anulada correctamente');
                            closeAnularModal();
                            // Recargar la página para mostrar los cambios
                            location.reload();
                        } else {
                            alert('Error al anular recepción: ' + (data.message || 'Error desconocido'));
                        }
                    })
                    .catch(error => {
                        console.error('Error al anular recepción:', error);
                        alert('Error al anular recepción. Por favor, intente nuevamente.');
                    });
                }

                // Función para imprimir recepción
                function imprimirRecepcion() {
                    alert('Función para imprimir recepción (conectar con backend)');
                }

                // Función para buscar recepciones
                function searchRecepcion() {
                    const form = document.getElementById('searchForm');
                    const formData = new FormData(form);
                    
                    alert('Búsqueda de recepciones\n(conectar con backend para mostrar resultados)');
                    closeSearchModal();
                }

                // Event listeners para cerrar modales
                document.addEventListener('DOMContentLoaded', function() {
                    // Cerrar modal de recepción
                    const closeRecepcionModal = document.querySelector('#recepcionModal .close-modal');
                    if (closeRecepcionModal) {
                        closeRecepcionModal.addEventListener('click', closeModal);
                    }

                    // Cerrar modal de búsqueda
                    const closeSearchModalBtn = document.querySelector('#searchModal .close-modal');
                    if (closeSearchModalBtn) {
                        closeSearchModalBtn.addEventListener('click', closeSearchModal);
                    }

                    // Cerrar modales al hacer clic fuera
                    window.addEventListener('click', function(event) {
                        const recepcionModal = document.getElementById('recepcionModal');
                        const searchModal = document.getElementById('searchModal');
                        const viewModal = document.getElementById('viewModal');
                        const anularModal = document.getElementById('anularModal');
                        
                        if (event.target === recepcionModal) {
                            closeModal();
                        }
                        if (event.target === searchModal) {
                            closeSearchModal();
                        }
                        if (event.target === viewModal) {
                            closeViewModal();
                        }
                        if (event.target === anularModal) {
                            closeAnularModal();
                        }
                    });

                    // Animaciones para filas de productos
                    const productoRows = document.querySelectorAll('.producto-row');
                    productoRows.forEach((row, index) => {
                        row.style.animation = `slideDown 0.3s ease ${index * 0.1}s`;
                    });

                    // Inicializar contador de productos
                    actualizarContadorProductos();

                    const archivoFactura = document.getElementById('fotoFacturaRecepcion');
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
                        contenedorPreview.hidden = false;
                        if (archivo.type === 'application/pdf' || archivo.name.toLowerCase().endsWith('.pdf')) {
                            previewPdf.src = urlPreviewFacturaRecepcion;
                            previewPdf.hidden = false;
                        } else if (archivo.type.startsWith('image/')) {
                            previewImagen.src = urlPreviewFacturaRecepcion;
                            previewImagen.hidden = false;
                        } else {
                            this.value = '';
                            limpiarFacturaIARecepcion();
                            establecerEstadoIARecepcion('Selecciona una imagen o un archivo PDF.', 'error');
                            return;
                        }
                        await analizarFacturaRecepcion(archivo);
                    });
                });

                // Animaciones CSS
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    
                    @keyframes fadeOut {
                        from { opacity: 1; }
                        to { opacity: 0; }
                    }
                    
                    @keyframes slideDown {
                        from {
                            opacity: 0;
                            transform: translateY(-20px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                    
                    @keyframes slideUp {
                        from {
                            opacity: 1;
                            transform: translateY(0);
                        }
                        to {
                            opacity: 0;
                            transform: translateY(-20px);
                        }
                    }
                `;
                document.head.appendChild(style);
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>