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
                            <path d="M3 21h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 21V7l8-4v18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M19 21V11l-6-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 9v.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 12v.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Recepción de Productos</h3>
                </div>
                
                <p class="ayuda-descripcion">Registre la entrada de nuevos productos al inventario desde proveedores.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>N° de factura de compra</li>
                            <li>Proveedor del producto</li>
                            <li>Productos recibidos</li>
                            <li>Cantidad y costo unitario</li>
                            <li>Fecha de recepción</li>
                            <li>Descripción de la recepción</li>
                            <li>Estado de la recepción</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Registrar:</strong> Nueva recepción</li>
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Detallar:</strong> Ver información completa</li>
                            <li><strong>Anular:</strong> Remover recepción</li>
                            <li><strong>Reporte:</strong> Generar reportes</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Contenido de tarjetas (inicialmente oculto) -->
            <div class="ayuda-tarjetas" id="ayudaTarjetas" style="display: none;">
                <!-- Tarjeta: Registrar -->
                <div class="ayuda-tarjeta" data-tarjeta="registrar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono tarjeta-registrar">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="tarjeta-titulo">Registrar Recepción</h3>
                    </div>
                    <div class="tarjeta-contenido">
                        <p><strong>Pasos para Registrar Nueva Recepción:</strong></p>
                        <ol>
                            <li class="mb-2">Haga clic en el botón <strong>"Nueva Recepción"</strong> en la parte superior derecha.</li>
                            <li class="mb-2">Ingrese el <strong>N° de la factura</strong>.</li>
                            <li class="mb-2">Seleccione el <strong>proveedor</strong>.</li>
                            <li class="mb-2">Seleccione el <strong>tamaño de la compra</strong>.</li>
                            <li class="mb-2">Haga clic en <strong>"Lista de Productos"</strong> y seleccione los productos recibidos, costo por unidad y cantidad.</li>
                            <li class="mb-2">Haga clic en <strong>"Registrar"</strong>.</li>
                        </ol>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Información importante:</strong>
                            <ul class="mb-0 mt-2">
                                <li>El N° de Factura debe ser único en el sistema</li>
                                <li>El costo unitario es requerido para cada producto</li>
                                <li>Puede agregar y remover productos antes de confirmar</li>
                                <li>El botón "Limpiar" resetea el formulario</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta: Detallar -->
                <div class="ayuda-tarjeta" data-tarjeta="detallar">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono tarjeta-detallar">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                        </div>
                        <h3 class="tarjeta-titulo">Detallar Recepción</h3>
                    </div>
                    <div class="tarjeta-contenido">
                        <p><strong>Pasos para Detallar Recepción:</strong></p>
                        <ol>
                            <li class="mb-2">Haga clic en el botón ícono del <strong>ojo</strong> <i class="bi bi-eye text-warning me-2"></i> en la columna "Acciones" para ver la información completa de la recepción.</li>
                        </ol>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-eye me-2"></i>
                            <strong>Detallar:</strong> Ícono ojo en la columna de acciones
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta: Anular -->
                <div class="ayuda-tarjeta" data-tarjeta="anular">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono tarjeta-anular">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="tarjeta-titulo">Anular Recepción</h3>
                    </div>
                    <div class="tarjeta-contenido">
                        <p><strong>Pasos para Anular Recepción:</strong></p>
                        <ol>
                            <li class="mb-2">Encuentre la recepción que desea anular.</li>
                            <li class="mb-2">Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                            <li class="mb-2">Confirme la anulación en el mensaje de advertencia.</li>
                        </ol>
                        
                        <div class="alert alert-danger mt-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>¡Cuidado!</strong> Esta acción no se puede deshacer
                        </div>
                        
                        <div class="alert alert-light border mt-2">
                            <i class="bi bi-trash text-danger me-2"></i>
                            <strong>Anular:</strong> Ícono X rojo en acciones
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta: Reporte -->
                <div class="ayuda-tarjeta" data-tarjeta="reporte">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono tarjeta-reporte">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h3 class="tarjeta-titulo">Generar Reportes</h3>
                    </div>
                    <div class="tarjeta-contenido">
                        <p><strong>Pasos para Generar Reportes de Recepciones:</strong></p>
                        <ol>
                            <li class="mb-2">Haga clic en el botón de la <strong>gráfica</strong> (color azul) en la esquina superior derecha.</li>
                            <li class="mb-2">Elije el tipo de reporte: (Todos los Reportes, Recepciones por Proveedor, Productos más Recibidos, Recepciones Mensuales).</li>
                            <li class="mb-2">Ingrese las fechas: (Inicio y Fin).</li>
                            <li class="mb-2">Elije el tipo de gráfica: (Barras, Pastel, Líneas, Rosca o Área Polar).</li>
                            <li class="mb-2">Haga clic en <strong>"Generar Reporte"</strong> para visualizar.</li>
                        </ol>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Tipos de reportes disponibles:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Recepciones por Proveedor</li>
                                <li>Productos más Recibidos</li>
                                <li>Recepciones Mensuales</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navegación -->
        <div class="modal-ayuda-nav">
            <button class="nav-btn" id="btnNavPrev">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            
            <div class="nav-dots">
                <span class="nav-dot nav-dot-active" data-slide="0"></span>
                <span class="nav-dot" data-slide="1"></span>
                <span class="nav-dot" data-slide="2"></span>
                <span class="nav-dot" data-slide="3"></span>
                <span class="nav-dot" data-slide="4"></span>
            </div>
            
            <button class="nav-btn" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>