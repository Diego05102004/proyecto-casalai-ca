<div class="modal-ayuda-overlay" id="modalAyuda">
    <div class="modal-ayuda-container">
        <div class="modal-ayuda-header">
            <h2 class="modal-ayuda-title">AYUDA</h2>
            <button class="modal-ayuda-close" id="cerrarModalAyuda">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <div class="modal-ayuda-content">
            <!-- Contenido principal -->
            <div class="ayuda-seccion" id="ayudaPrincipal">
                <div class="ayuda-header">
                    <div class="ayuda-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <polyline points="9,22 9,12 15,12 15,22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 8V6a4 4 0 0 0-8 0v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 2v.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Ventas Presenciales</h3>
                </div>
                
                <p class="ayuda-descripcion">Consulte sus ventas presenciales y realice los pagos o cancelaciones correspondientes.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Ventas presenciales realizadas</li>
                            <li>Fecha de la venta</li>
                            <li>Cliente</li>
                            <li>Costo de la compra</li>
                            <li>Método de pago</li>
                            <li>Comprobante de pago</li>
                            <li>Productos vendidos</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Registrar:</strong> Nueva venta presencial</li>
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Detallar:</strong> Información detallada de la venta presencial</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta Registrar -->
                <div class="tarjeta-ayuda tarjeta-registrar" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Registrar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite registrar una nueva venta presencial con diferentes métodos de pago. Soporta pago móvil, transferencia, efectivo y Zelle.</p>
                        
                        <h6>Pasos Generales:</h6>
                        <ul>
                            <li>Busque el Cliente en el sistema</li>
                            <li>Haga clic en "Lista de Productos" y seleccione los productos</li>
                            <li>Configure el costo por unidad y cantidad</li>
                        </ul>
                        
                        <h6>Métodos de Pago:</h6>
                        
                        <h6>1. Pago Móvil o Transferencia:</h6>
                        <ul>
                            <li>Seleccione el banco emisor</li>
                            <li>Ingrese el N° de referencia del pago</li>
                            <li>Agregue la imagen del comprobante de pago (JPG/PNG)</li>
                            <li>Ingrese el monto pagado</li>
                            <li>Haga clic en "Registrar" para confirmar</li>
                        </ul>
                        
                        <h6>2. Efectivo:</h6>
                        <ul>
                            <li>Ingrese el monto pagado</li>
                            <li>Haga clic en "Registrar" para confirmar</li>
                        </ul>
                        
                        <h6>3. Zelle:</h6>
                        <ul>
                            <li>Seleccione el banco emisor</li>
                            <li>Ingrese el nombre del propietario de la cuenta Zelle</li>
                            <li>Ingrese el monto pagado</li>
                            <li>Ingrese el N° de referencia del pago</li>
                            <li>Agregue la imagen del comprobante de pago (JPG/PNG)</li>
                            <li>Haga clic en "Registrar" para confirmar</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <strong>Nota:</strong> Si el cliente no está registrado, haga clic en "Nuevo" (verde) para registrarlo primero.
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta Detallar -->
                <div class="tarjeta-ayuda tarjeta-detallar" data-tarjeta="detallar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Detallar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite visualizar toda la información completa de una venta presencial específica.</p>
                        <ul>
                            <li>Ubique la venta en la lista de ventas presenciales</li>
                            <li>Haga clic en el ícono del ojo (👁) en la columna "Acciones"</li>
                            <li>Revise todos los detalles de la venta:</li>
                            <li>• Información del cliente</li>
                            <li>• Fecha y hora de la venta</li>
                            <li>• Productos vendidos con cantidades y precios</li>
                            <li>• Método de pago utilizado</li>
                            <li>• Comprobante de pago (si aplica)</li>
                            <li>• Monto total de la transacción</li>
                            <li>Puede cerrar la vista cuando termine</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-ayuda-navigation">
            <button class="nav-btn nav-btn-prev" id="btnNavPrev" disabled>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            
            <div class="nav-indicators">
                <span class="nav-dot nav-dot-active" data-slide="0"></span>
                <span class="nav-dot" data-slide="1"></span>
                <span class="nav-dot" data-slide="2"></span>
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>