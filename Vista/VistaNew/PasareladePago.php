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
        error_log("Error al generar JWT en PasareladePago: " . $e->getMessage());
    }
}

// Variables para los componentes reutilizables
$pagina_actual = 'PasareladePago';
$titulo_pagina = 'Pasarela de Pagos';

// Iniciar el buffer de contenido
ob_start();
?>

            <!-- Pasarela de Pagos -->
            <div class="pasarela-section">
                <div class="section-header">
                    <h2><i class="fas fa-credit-card"></i> Registrar Pagos Online</h2>
                    <div class="section-actions">
                        <button class="btn-back" onclick="window.location.href='?pagina=gestionarfactura'">
                            <span class="btn-icon"><i class="fas fa-arrow-left"></i></span>
                            Volver a Pedidos
                        </button>
                    </div>
                </div>

                <!-- Alerta de Borrador -->
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Borrador:</strong> El pago aún no ha sido enviado para validación.
                </div>

                <!-- Formulario de Pagos -->
                <div class="pagos-container">
                    <div class="pagos-layout">
                        <!-- Datos Bancarios -->
                        <div class="datos-bancarios-section">
                            <h3 class="section-title"><i class="fas fa-university"></i> Datos Bancarios</h3>
                            <div class="cuentas-list" id="cuentas-list">
                                <?php if (!empty($listadocuentas)): ?>
                                    <?php foreach ($listadocuentas as $cuenta): ?>
                                        <div class="cuenta-card" data-cuenta="<?php echo htmlspecialchars($cuenta['id_cuenta'] ?? ''); ?>">
                                            <div class="cuenta-header">
                                                <span class="cuenta-banco"><?php echo htmlspecialchars($cuenta['nombre_banco'] ?? ''); ?></span>
                                                <span class="cuenta-tipo"><?php echo htmlspecialchars($cuenta['tipo_cuenta'] ?? ''); ?></span>
                                            </div>
                                            <div class="cuenta-info">
                                                <div class="cuenta-numero">
                                                    <i class="fas fa-hashtag"></i>
                                                    <?php echo htmlspecialchars($cuenta['numero_cuenta'] ?? ''); ?>
                                                </div>
                                                <div class="cuenta-rif">
                                                    <i class="fas fa-id-card"></i>
                                                    <?php echo htmlspecialchars($cuenta['rif'] ?? ''); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="no-cuentas">No hay cuentas bancarias registradas</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Formulario de Pagos -->
                        <div class="form-pagos-section">
                            <form id="formularioPago" method="POST" enctype="multipart/form-data" novalidate>
                                <input type="hidden" name="accion" value="ingresar">
                                <input type="hidden" name="id_factura" value="<?php echo $idFactura ?? ''; ?>">
                                
                                <h3 class="section-title"><i class="fas fa-file-invoice-dollar"></i> Información del Pago</h3>
                                
                                <!-- Monto Total -->
                                <div class="monto-section">
                                    <label for="monto_total">Monto Total a Pagar</label>
                                    <input type="text" class="monto-input" id="monto_total" name="monto_total" 
                                           value="<?php echo number_format($monto ?? 0, 2); ?> Bs" readonly>
                                </div>

                                <!-- Contenedor de Pagos -->
                                <div class="pagos-horizontal" id="pagos-container">
                                    <!-- Los bloques de pago se agregarán aquí dinámicamente -->
                                </div>

                                <!-- Resumen de Pagos -->
                                <div id="resumen-pagos" style="display: none;">
                                    <h4 class="resumen-title">Resumen de Pagos</h4>
                                    <div class="resumen-grid">
                                        <div class="resumen-card">
                                            <div class="resumen-label">Total Registrado</div>
                                            <div id="total-registrado" class="resumen-value success">0.00 Bs</div>
                                        </div>
                                        <div class="resumen-card">
                                            <div class="resumen-label">Restante por Pagar</div>
                                            <div id="restante-pagar" class="resumen-value danger">
                                                <?php echo number_format($monto ?? 0, 2); ?> Bs
                                            </div>
                                        </div>
                                    </div>
                                    <p id="mensaje-validacion" class="error-message"></p>
                                </div>

                                <!-- Botones -->
                                <div class="buttons-section">
                                    <button type="button" class="btn-pago btn-secondary" id="agregar-pago">
                                        <i class="fas fa-plus"></i> Agregar Método de Pago
                                    </button>
                                    <button type="submit" class="btn-pago btn-primary" id="btn-registrar" disabled>
                                        <i class="fas fa-check"></i> Registrar Pagos
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                /* Estilos de Pasarela de Pagos */
                .pasarela-section {
                    margin-top: 30px;
                }

                .pagos-container {
                    background: white;
                    border-radius: 16px;
                    padding: 30px;
                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                    border: 1px solid rgba(33, 150, 243, 0.1);
                }

                .pagos-layout {
                    display: grid;
                    grid-template-columns: 1fr 2fr;
                    gap: 30px;
                }

                @media (max-width: 992px) {
                    .pagos-layout {
                        grid-template-columns: 1fr;
                    }
                }

                .section-title {
                    font-size: 1.2rem;
                    font-weight: 700;
                    color: #2d3748;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .section-title i {
                    color: #2196F3;
                }

                .alert {
                    padding: 15px 20px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .alert-warning {
                    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
                    border: 1px solid #ffc107;
                    color: #856404;
                }

                .alert i {
                    font-size: 1.2rem;
                }

                /* Cuentas Bancarias */
                .cuentas-list {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 15px;
                }

                .cuenta-card {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border: 1px solid #dee2e6;
                    border-radius: 12px;
                    padding: 15px;
                    transition: all 0.3s ease;
                }

                .cuenta-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }

                .cuenta-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                }

                .cuenta-banco {
                    font-weight: 700;
                    color: #2d3748;
                }

                .cuenta-tipo {
                    font-size: 0.8rem;
                    background: #2196F3;
                    color: white;
                    padding: 4px 8px;
                    border-radius: 20px;
                }

                .cuenta-info {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }

                .cuenta-numero,
                .cuenta-rif {
                    font-size: 0.9rem;
                    color: #495057;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .no-cuentas {
                    text-align: center;
                    color: #718096;
                    padding: 20px;
                    font-style: italic;
                }

                /* Monto */
                .monto-section {
                    margin-bottom: 20px;
                }

                .monto-section label {
                    display: block;
                    font-weight: 600;
                    color: #2d3748;
                    margin-bottom: 8px;
                }

                .monto-input {
                    width: 100%;
                    padding: 15px;
                    border: 2px solid #e9ecef;
                    border-radius: 10px;
                    font-size: 1.2rem;
                    font-weight: 700;
                    background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
                    color: #155724;
                    text-align: center;
                }

                /* Contenedor de Pagos */
                .pagos-horizontal {
                    display: flex;
                    gap: 15px;
                    overflow-x: auto;
                    padding: 10px 0;
                    min-height: 150px;
                }

                .bloque-pago {
                    flex: 1;
                    min-width: 300px;
                    background: white;
                    border: 2px solid #2196F3;
                    border-radius: 12px;
                    padding: 20px;
                    position: relative;
                }

                .btn-quitar-pago {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: #dc3545;
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 28px;
                    height: 28px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    line-height: 1;
                }

                .btn-quitar-pago:hover {
                    background: #c82333;
                }

                .campos-pago {
                    display: none;
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 2px dashed #dee2e6;
                }

                .campos-pago.show {
                    display: block;
                }

                .envolver-form {
                    margin-bottom: 15px;
                }

                .envolver-form label {
                    display: block;
                    font-weight: 600;
                    color: #495057;
                    margin-bottom: 5px;
                    font-size: 0.9rem;
                }

                .control-form {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ced4da;
                    border-radius: 6px;
                    font-size: 0.9rem;
                }

                /* Resumen */
                .resumen-title {
                    font-size: 1rem;
                    color: #495057;
                    margin-bottom: 15px;
                }

                .resumen-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                }

                .resumen-card {
                    background: white;
                    padding: 15px;
                    border-radius: 10px;
                    border: 1px solid #dee2e6;
                }

                .resumen-label {
                    font-size: 0.85rem;
                    color: #6c757d;
                }

                .resumen-value {
                    font-size: 1.2rem;
                    font-weight: 700;
                }

                .resumen-value.success {
                    color: #28a745;
                }

                .resumen-value.danger {
                    color: #dc3545;
                }

                .error-message {
                    text-align: center;
                    color: #dc3545;
                    font-size: 0.9rem;
                    margin: 10px 0 0 0;
                }

                /* Botones */
                .buttons-section {
                    display: flex;
                    justify-content: center;
                    gap: 15px;
                    margin-top: 20px;
                }

                .btn-pago {
                    padding: 12px 25px;
                    border: none;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: 0.95rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: all 0.3s ease;
                }

                .btn-pago:disabled {
                    opacity: 0.5;
                    cursor: not-allowed;
                }

                .btn-primary {
                    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                    color: white;
                }

                .btn-primary:hover:not(:disabled) {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(33, 150, 243, 0.3);
                }

                .btn-secondary {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                    color: white;
                }

                .btn-secondary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(79, 172, 254, 0.3);
                }

                .btn-back {
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
                    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
                    color: white;
                }

                .btn-back:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .pagos-container {
                        padding: 20px;
                    }

                    .pagos-horizontal {
                        flex-direction: column;
                    }

                    .bloque-pago {
                        min-width: 100%;
                    }

                    .buttons-section {
                        flex-direction: column;
                    }

                    .btn-pago {
                        width: 100%;
                        justify-content: center;
                    }
                }
            </style>

            <script>
                // Funcionalidad para agregar pagos
                $(document).ready(function() {
                    let contadorPagos = 0;
                    const montoTotal = parseFloat('<?php echo number_format($monto ?? 0, 2, '.', ''); ?>');
                    let totalRegistrado = 0;

                    $('#agregar-pago').on('click', function() {
                        contadorPagos++;
                        
                        const bloquePago = `
                            <div class="bloque-pago" id="pago-${contadorPagos}">
                                <button type="button" class="btn-quitar-pago" onclick="quitarPago(${contadorPagos})">&times;</button>
                                <h4 style="margin: 0 0 15px 0; color: #2196F3;">Pago #${contadorPagos}</h4>
                                <div class="campos-pago show">
                                    <div class="envolver-form">
                                        <label>Seleccionar Cuenta</label>
                                        <select class="control-form cuenta-pago" name="pagos[${contadorPagos}][cuenta]">
                                            <?php if (!empty($listadocuentas)): ?>
                                                <?php foreach ($listadocuentas as $cuenta): ?>
                                                    <option value="<?php echo $cuenta['id_cuenta']; ?>">
                                                        <?php echo htmlspecialchars($cuenta['nombre_banco'] . ' - ' . $cuenta['numero_cuenta']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="envolver-form">
                                        <label>Método de Pago</label>
                                        <select class="control-form metodo-pago" name="pagos[${contadorPagos}][tipo]">
                                            <option value="">Seleccione método</option>
                                            <option value="transferencia">Transferencia</option>
                                            <option value="pago_movil">Pago Móvil</option>
                                        </select>
                                    </div>
                                    <div class="envolver-form">
                                        <label>Referencia</label>
                                        <input type="text" class="control-form referencia-pago" name="pagos[${contadorPagos}][referencia]" maxlength="12" placeholder="Número de referencia">
                                    </div>
                                    <div class="envolver-form">
                                        <label>Monto</label>
                                        <input type="number" class="control-form monto-pago" name="pagos[${contadorPagos}][monto]" step="0.01" min="0" placeholder="0.00">
                                    </div>
                                    <div class="envolver-form">
                                        <label>Comprobante</label>
                                        <input type="file" class="control-form comprobante-pago" name="pagos[${contadorPagos}][comprobante]" accept="image/*">
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        $('#pagos-container').append(bloquePago);
                        $('#resumen-pagos').show();
                    });

                    // Actualizar resumen al cambiar montos
                    $(document).on('change', '.monto-pago', function() {
                        actualizarResumen();
                    });

                    function actualizarResumen() {
                        totalRegistrado = 0;
                        $('.monto-pago').each(function() {
                            totalRegistrado += parseFloat($(this).val()) || 0;
                        });
                        
                        $('#total-registrado').text(totalRegistrado.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' Bs');
                        
                        const restante = montoTotal - totalRegistrado;
                        $('#restante-pagar').text(restante.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' Bs');
                        
                        if (restante <= 0) {
                            $('#restante-pagar').removeClass('danger').addClass('success');
                            $('#btn-registrar').prop('disabled', false);
                        } else {
                            $('#restante-pagar').removeClass('success').addClass('danger');
                            $('#btn-registrar').prop('disabled', true);
                        }
                    }

                    window.quitarPago = function(id) {
                        $('#pago-' + id).remove();
                        actualizarResumen();
                        
                        if ($('#pagos-container .bloque-pago').length === 0) {
                            $('#resumen-pagos').hide();
                        }
                    };
                });
            </script>

<?php
$contenido_pagina = ob_get_clean();

// Incluir la estructura base del dashboard
require_once __DIR__ . '/dashboard_base.php';
?>