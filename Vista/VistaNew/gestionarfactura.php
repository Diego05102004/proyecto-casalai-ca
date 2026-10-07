<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['name'])) {
    header('Location: ../..');
    exit();
}

$pagina_actual = 'gestionarfactura';
$titulo_pagina = 'Gestión de Pedidos';

$rolActual = $_SESSION['id_rol'] ?? null;
$idModulo = 13;
$permiteConsultar = !empty($permisosUsuario[$rolActual][$idModulo]['consultar']) && $permisosUsuario[$rolActual][$idModulo]['consultar'] === true;

if (!$permiteConsultar) {
    header('Location: ?pagina=acceso-denegado');
    exit();
}

$facturas = is_array($facturas ?? null) ? $facturas : [];
$totalFacturas = count($facturas);
$pendientes = 0;
$pagadas = 0;
$canceladas = 0;

foreach ($facturas as $factura) {
    $estado = strtolower(trim((string)($factura['estatus'] ?? '')));

    if (strpos($estado, 'cancel') !== false || strpos($estado, 'anul') !== false) {
        $canceladas++;
    } elseif ($estado === '' || strpos($estado, 'borrador') !== false || strpos($estado, 'proceso') !== false || strpos($estado, 'incompleto') !== false) {
        $pendientes++;
    } else {
        $pagadas++;
    }
}

$porcentajePendientes = $totalFacturas > 0 ? round(($pendientes / $totalFacturas) * 100) : 0;
$porcentajePagadas = $totalFacturas > 0 ? round(($pagadas / $totalFacturas) * 100) : 0;
$porcentajeCanceladas = $totalFacturas > 0 ? round(($canceladas / $totalFacturas) * 100) : 0;

ob_start();
?>

<div class="orders-section">
    <div class="section-header">
        <h2>Gestión de Pedidos</h2>
        <div class="section-actions">
            <button type="button" class="btn-add-order" onclick="Swal.fire({ icon: 'info', title: 'Pedido nuevo', text: 'La creación de pedidos se realiza desde la compra o el flujo de facturación disponible.' });">
                <span class="btn-icon"><i class="fas fa-plus"></i></span>
                Crear Pedido
            </button>
            <button type="button" class="btn-filter" onclick="Swal.fire({ icon: 'info', title: 'Filtro', text: 'Usa la búsqueda del listado para filtrar los pedidos actuales.' });">
                <span class="btn-icon"><i class="fas fa-search"></i></span>
                Filtrar
            </button>
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card sales" data-summary-card="total">
            <div class="card-icon"><i class="fas fa-box"></i></div>
            <div class="card-content">
                <h3>Total Pedidos</h3>
                <p class="card-value"><?php echo $totalFacturas; ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="100, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $totalFacturas > 0 ? 100 : 0; ?>%</span>
                </div>
            </div>
        </div>

        <div class="summary-card expenses" data-summary-card="pending">
            <div class="card-icon"><i class="fas fa-clock"></i></div>
            <div class="card-content">
                <h3>Pendientes</h3>
                <p class="card-value"><?php echo $pendientes; ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo $porcentajePendientes; ?>, 100" stroke="#ff6b6b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $porcentajePendientes; ?>%</span>
                </div>
            </div>
        </div>

        <div class="summary-card income" data-summary-card="completed">
            <div class="card-icon"><i class="fas fa-check-circle"></i></div>
            <div class="card-content">
                <h3>Completados</h3>
                <p class="card-value"><?php echo $pagadas; ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo $porcentajePagadas; ?>, 100" stroke="#2196F3" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $porcentajePagadas; ?>%</span>
                </div>
            </div>
        </div>

        <div class="summary-card cancelled-orders" data-summary-card="cancelled">
            <div class="card-icon"><i class="fas fa-ban"></i></div>
            <div class="card-content">
                <h3>Cancelados</h3>
                <p class="card-value"><?php echo $canceladas; ?></p>
                <div class="progress-circle">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" stroke-dasharray="<?php echo $porcentajeCanceladas; ?>, 100" stroke="#64748b" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <span class="percentage"><?php echo $porcentajeCanceladas; ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="orders-table-container">
        <div id="listado" aria-live="polite">
            <p class="orders-loading">Cargando pedidos...</p>
        </div>
    </div>
</div>

<div id="orderDetailsModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>Detalles del Pedido</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="order-details-content">
                <div class="order-info-section">
                    <h3>Información del Pedido</h3>
                    <div class="detail-row"><span class="detail-label">Número:</span><span class="detail-value" id="detailNumber">-</span></div>
                    <div class="detail-row"><span class="detail-label">Cliente:</span><span class="detail-value" id="detailClient">-</span></div>
                    <div class="detail-row"><span class="detail-label">Fecha:</span><span class="detail-value" id="detailDate">-</span></div>
                    <div class="detail-row"><span class="detail-label">Estado:</span><span class="detail-value" id="detailStatus">-</span></div>
                    <div class="detail-row"><span class="detail-label">Total:</span><span class="detail-value" id="detailTotal">-</span></div>
                </div>
                <div class="order-products-section">
                    <h3>Productos del Pedido</h3>
                    <div class="products-list" id="detailProducts"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-close" onclick="closeDetailsModal()">Cerrar</button>
        </div>
    </div>
</div>

<style>
    .orders-section {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 20px;
    }
    .section-header h2 {
        margin: 0;
        color: #1f2937;
    }
    .section-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .btn-add-order, .btn-filter, .btn-close {
        border: none;
        border-radius: 8px;
        padding: 11px 16px;
        cursor: pointer;
        font-weight: 600;
    }
    .btn-add-order {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
    }
    .btn-filter {
        background: #f3f4f6;
        color: #1f2937;
    }
    .btn-action {
        border: none;
        border-radius: 8px;
        padding: 8px 10px;
        margin-right: 6px;
        cursor: pointer;
        color: white;
    }
    .btn-view { background: #2196F3; }
    .btn-delete { background: #dc3545; }
    .orders-table-container {
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
    }
    .orders-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
    }
    .orders-table th {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
        text-align: left;
        padding: 14px 12px;
        font-size: 0.9rem;
    }
    .orders-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #e5e7eb;
        color: #374151;
    }
    .orders-table tr:hover {
        background: #f9fafb;
    }
    .client-info-mini {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .client-avatar-mini {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .status {
        display: inline-block;
        border-radius: 999px;
        padding: 5px 12px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .status.completed { background: #d1fae5; color: #065f46; }
    .status.processing { background: #fef3c7; color: #92400e; }
    .status.pending { background: #fee2e2; color: #991b1b; }
    .status.cancelled { background: #e5e7eb; color: #374151; }
    .cancelled-orders .card-icon { background: linear-gradient(135deg, #64748b, #475569); }
    #listado .accordion { width: 100%; }
    #listado .accordion-item { border-bottom: 1px solid #e5e7eb; }
    #listado .accordion-header { margin: 0; }
    #listado .accordion-button {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px;
        border: 0;
        background: #f3f4f6;
        color: #1f2937;
        font: inherit;
        text-align: left;
        cursor: pointer;
    }
    #listado .accordion-button::after {
        width: 8px;
        height: 8px;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        content: '';
        transform: rotate(45deg);
        transition: transform 0.2s ease;
    }
    #listado .accordion-button[aria-expanded="true"]::after { transform: rotate(225deg); }
    #listado .accordion-button.bg-success { background: #198754; color: #fff; }
    #listado .accordion-button.bg-success.text-white { color: #fff; }
    #listado .accordion-button.bg-warning { background: #ffc107; color: #212529; }
    #listado .accordion-collapse { display: none; }
    #listado .accordion-collapse.show { display: block; }
    #listado .accordion-body { padding: 16px; }
    #listado .table-responsive { overflow-x: auto; }
    #listado table { width: 100%; border-collapse: collapse; }
    #listado th, #listado td { padding: 10px 12px; border: 1px solid #e5e7eb; text-align: left; }
    #listado .alert { margin: 12px 0; padding: 12px 14px; border-radius: 6px; }
    #listado .alert-warning { background: #fff3cd; color: #664d03; }
    #listado .alert-info { background: #cff4fc; color: #055160; }
    #listado .alert-success { background: #d1e7dd; color: #0f5132; }
    #listado .alert-danger { background: #f8d7da; color: #842029; }
    #listado .alert-secondary { background: #e2e3e5; color: #41464b; }
    #listado .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; }
    #listado .badge.bg-success { background: #198754; color: #fff; }
    #listado .badge.bg-warning { background: #ffc107; color: #212529; }
    #listado .btn { display: inline-block; margin: 4px 4px 4px 0; padding: 8px 12px; border: 0; border-radius: 6px; color: #fff; cursor: pointer; }
    #listado .btn-success { background: #198754; }
    #listado .btn-danger { background: #dc3545; }
    #listado .btn-primary { background: #0d6efd; }
    .orders-loading { margin: 0; padding: 30px; color: #666; text-align: center; }
    .summary-cards {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        margin-bottom: 20px;
    }
    .summary-card {
        background: #fff;
        border-radius: 12px;
        padding: 18px;
        display: flex;
        gap: 14px;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .card-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.3rem;
        background: linear-gradient(135deg, #4f46e5, #3b82f6);
    }
    .summary-card.expenses .card-icon { background: linear-gradient(135deg, #f59e0b, #ef4444); }
    .summary-card.income .card-icon { background: linear-gradient(135deg, #10b981, #34d399); }
    .card-content h3 {
        margin: 0 0 6px;
        font-size: 0.88rem;
        color: #6b7280;
    }
    .card-value {
        margin: 0;
        font-size: 1.7rem;
        font-weight: 800;
        color: #111827;
    }
    .progress-circle {
        margin-top: 10px;
        width: 52px;
        height: 52px;
        position: relative;
    }
    .circular-chart {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }
    .circle-bg {
        fill: none;
        stroke: #e5e7eb;
        stroke-width: 3;
    }
    .circle {
        fill: none;
        stroke-width: 3;
        stroke-linecap: round;
        transition: stroke-dasharray 0.3s ease;
    }
    .percentage {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: 700;
        color: #374151;
    }
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 2000;
    }
    .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 720px;
        width: 90%;
        margin: 6% auto;
        box-shadow: 0 10px 35px rgba(0,0,0,0.25);
    }
    .modal-header {
        background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        color: white;
        padding: 18px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 12px 12px 0 0;
    }
    .modal-header h2 {
        margin: 0;
    }
    .close-modal {
        font-size: 28px;
        cursor: pointer;
    }
    .modal-body {
        padding: 20px;
    }
    .modal-footer {
        padding: 15px 20px 20px;
        text-align: right;
    }
    .order-details-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .order-info-section, .order-products-section {
        background: #f9fafb;
        border-radius: 10px;
        padding: 16px;
    }
    .order-info-section h3, .order-products-section h3 {
        margin-top: 0;
        color: #111827;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        border-bottom: 1px solid #e5e7eb;
        padding: 8px 0;
    }
    .detail-value {
        color: #111827;
        font-weight: 600;
        text-align: right;
    }
    .products-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .product-item {
        background: white;
        border-radius: 8px;
        padding: 10px 12px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        border: 1px solid #e5e7eb;
    }
    .btn-close {
        background: #111827;
        color: white;
    }
    @media (max-width: 768px) {
        .order-details-content { grid-template-columns: 1fr; }
        .section-header { flex-direction: column; align-items: flex-start; }
    }
</style>

<script src="assets/public/js/jquery.min.js"></script>
<script src="assets/javascript/factura.js"></script>
<script>
    function closeDetailsModal() {
        const modal = document.getElementById('orderDetailsModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('orderDetailsModal');
        if (modal) {
            const closeButton = modal.querySelector('.close-modal');
            if (closeButton) {
                closeButton.addEventListener('click', closeDetailsModal);
            }
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeDetailsModal();
                }
            });
        }
    });

    document.addEventListener('click', function (event) {
        const button = event.target.closest('#listado .accordion-button[data-bs-target]');
        if (!button) {
            return;
        }

        const target = document.querySelector(button.dataset.bsTarget);
        if (!target) {
            return;
        }

        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
        button.classList.toggle('collapsed', expanded);
        target.classList.toggle('show', !expanded);
    });
</script>

<?php
$contenido_pagina = ob_get_clean();
require_once __DIR__ . '/dashboard_base.php';
?>