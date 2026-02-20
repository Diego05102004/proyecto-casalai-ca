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
                    <h3 class="ayuda-titulo">Gestión de Productos</h3>
                </div>
                
                <p class="ayuda-descripcion">Administre el catálogo completo de productos del inventario.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Nombre del producto</li>
                            <li>Modelo y marca</li>
                            <li>Imagen del producto</li>
                            <li>Descripción breve</li>
                            <li>Stock (Actual, Máximo y Mínimo)</li>
                            <li>Cláusula de garantía</li>
                            <li>Categoría y características específicas</li>
                            <li>Código serial</li>
                            <li>Precio de venta</li>
                            <li>Estatus (habilitado/inhabilitado)</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Registrar:</strong> Nuevo producto</li>
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Detallar:</strong> Ver información completa del producto</li>
                            <li><strong>Modificar:</strong> Actualizar datos</li>
                            <li><strong>Eliminar:</strong> Remover producto</li>
                            <li><strong>Estatus:</strong> Actualizar estatus (habilitado/inhabilitado)</li>
                            <li><strong>Reporte:</strong> Generar reportes</li>
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
                        <p>Permite agregar un nuevo producto al sistema. Debe completar todos los campos obligatorios como nombre, modelo/marca, imagen, descripción, stock y precio.</p>
                        <ul>
                            <li>Haga clic en el botón "+" (verde) en la esquina superior derecha</li>
                            <li>Complete todos los campos obligatorios marcados con *</li>
                            <li>Ingrese nombre, seleccione modelo/marca y cargue imagen</li>
                            <li>Complete descripción, stock (actual, máximo, mínimo)</li>
                            <li>Redacte cláusula de garantía y seleccione categoría</li>
                            <li>Ingrese código serial y precio de venta</li>
                            <li>Haga clic en "Registrar" para guardar</li>
                        </ul>
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
                        <p>Permite visualizar toda la información completa de un producto específico del inventario.</p>
                        <ul>
                            <li>Ubique el producto en la lista</li>
                            <li>Haga clic en el ícono del ojo (👁) en "Acciones"</li>
                            <li>Revise todos los datos del producto incluyendo imagen y stock</li>
                            <li>Puede cerrar la vista cuando termine</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Tarjeta Modificar -->
                <div class="tarjeta-ayuda tarjeta-modificar" data-tarjeta="modificar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Modificar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite actualizar los datos de un producto existente en el sistema.</p>
                        <ul>
                            <li>Localice el producto que desea modificar en la tabla</li>
                            <li>Haga clic en el ícono del lápiz (📝) en "Acciones"</li>
                            <li>Edite los campos necesarios (nombre, precio, stock, etc.)</li>
                            <li>Haga clic en "Modificar" para confirmar los cambios</li>
                            <li>Los cambios se reflejarán inmediatamente</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Tarjeta Eliminar -->
                <div class="tarjeta-ayuda tarjeta-eliminar" data-tarjeta="eliminar" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <polyline points="3,6 5,6 21,6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Eliminar</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite remover permanentemente un producto del sistema, incluyendo su imagen asociada.</p>
                        <ul>
                            <li>Encuentre el producto que desea eliminar</li>
                            <li>Haga clic en el ícono de la X (❌) en "Acciones"</li>
                            <li>Confirme la eliminación en el mensaje de advertencia</li>
                            <li>El producto y todos sus datos serán eliminados permanentemente</li>
                            <li><strong>¡Cuidado!</strong> Esta acción no se puede deshacer</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Tarjeta Cambiar Estatus -->
                <div class="tarjeta-ayuda tarjeta-estatus" data-tarjeta="estatus" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="11" width="18" height="10" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Cambiar Estatus</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite habilitar o inhabilitar un producto sin eliminarlo del sistema.</p>
                        <ul>
                            <li>Encuentre el producto en la lista</li>
                            <li>Haga clic directamente sobre su estatus actual (habilitado/inhabilitado)</li>
                            <li>El sistema alternará automáticamente el estado</li>
                            <li>El producto mantendrá sus datos pero cambiará su disponibilidad</li>
                            <li>El cambio es instantáneo y no requiere confirmación</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Tarjeta Generar Reporte -->
                <div class="tarjeta-ayuda tarjeta-reporte" data-tarjeta="reporte" style="display: none;">
                    <div class="tarjeta-header">
                        <div class="tarjeta-icono">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
                                <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2"/>
                                <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2"/>
                                <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2"/>
                                <polyline points="10,9 9,9 8,9" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h4 class="tarjeta-titulo">Generar Reporte</h4>
                    </div>
                    <div class="tarjeta-contenido">
                        <p>Permite generar reportes de los productos registrados, incluyendo los más vendidos y estadísticas de inventario.</p>
                        <ul>
                            <li>Haga clic en el botón de gráfica (azul) en la esquina superior derecha</li>
                            <li>Seleccione el tipo de reporte (Todos, Productos más vendidos, etc.)</li>
                            <li>Ingrese las fechas de inicio y fin</li>
                            <li>Elija el tipo de gráfica (Barras, Pastel, Líneas, etc.)</li>
                            <li>Haga clic en "Generar Reporte" para visualizar</li>
                            <li>El sistema mostrará el reporte en formato visual</li>
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
                <span class="nav-dot" data-slide="3"></span>
                <span class="nav-dot" data-slide="4"></span>
                <span class="nav-dot" data-slide="5"></span>
                <span class="nav-dot" data-slide="6"></span>
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>