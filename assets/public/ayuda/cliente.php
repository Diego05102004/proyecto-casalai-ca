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
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h3 class="ayuda-titulo">Gestión de Clientes</h3>
                </div>
                
                <p class="ayuda-descripcion">Administre la base de datos de clientes del sistema.</p>
                
                <div class="ayuda-grid">
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Información gestionable:</h4>
                        <ul class="ayuda-lista">
                            <li>Nombre completo</li>
                            <li>Cédula</li>
                            <li>N° de teléfono</li>
                            <li>Dirección (Estado/Ciudad/Calle o Avenida)</li>
                            <li>Correo electrónico</li>
                            <li>Historial de compras</li>
                        </ul>
                    </div>
                    
                    <div class="ayuda-columna">
                        <h4 class="ayuda-subtitulo">Operaciones disponibles:</h4>
                        <ul class="ayuda-lista">
                            <li><strong>Registrar:</strong> Nuevo cliente</li>
                            <li><strong>Consultar:</strong> Ver lista completa</li>
                            <li><strong>Modificar:</strong> Actualizar datos</li>
                            <li><strong>Eliminar:</strong> Remover cliente</li>
                            <li><strong>Reporte:</strong> Estadísticas de compras</li>
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
                        <p>Permite agregar un nuevo cliente al sistema. Debe completar todos los campos obligatorios como nombre, cédula, teléfono, dirección y correo.</p>
                        <ul>
                            <li>Haga clic en el botón "+" (verde) en la esquina superior derecha</li>
                            <li>Complete todos los campos obligatorios marcados con *</li>
                            <li>Ingrese el nombre completo (solo letras, mínimo 2 caracteres)</li>
                            <li>Ingrese la cédula (formato: 1.234.567 o 12.345.678)</li>
                            <li>Ingrese el N° de teléfono (formato: 0400-000-0000)</li>
                            <li>Ingrese una dirección completa (Estado/Ciudad/Calle)</li>
                            <li>Ingrese un correo electrónico (gmail, outlook, yahoo, icloud)</li>
                            <li>Haga clic en "Registrar" para guardar</li>
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
                        <p>Permite actualizar los datos de un cliente existente en el sistema.</p>
                        <ul>
                            <li>Localice al cliente que desea modificar en la tabla</li>
                            <li>Haga clic en el ícono del lápiz (📝) en "Acciones"</li>
                            <li>Edite los campos necesarios (nombre, teléfono, dirección, etc.)</li>
                            <li>Haga clic en "Modificar" para confirmar los cambios</li>
                            <li>Los cambios se reflejarán inmediatamente en el sistema</li>
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
                        <p>Permite remover permanentemente un cliente del sistema.</p>
                        <ul>
                            <li>Encuentre al cliente que desea eliminar en la lista</li>
                            <li>Haga clic en el ícono de la X (❌) en "Acciones"</li>
                            <li>Confirme la eliminación en el mensaje de advertencia</li>
                            <li>El cliente será eliminado permanentemente del sistema</li>
                            <li><strong>¡Cuidado!</strong> Esta acción no se puede deshacer</li>
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
                        <p>Permite generar reportes estadísticos de los clientes y sus compras.</p>
                        <ul>
                            <li>Haga clic en el botón de gráfica (azul) en la esquina superior derecha</li>
                            <li>Consulte la sección "Top 10 Clientes por Productos Comprados"</li>
                            <li>Visualice el gráfico de barras con las estadísticas</li>
                            <li>Revise la tabla detallada con información completa</li>
                            <li>El sistema genera automáticamente el reporte en PDF</li>
                            <li>Puede descargar el archivo para análisis externos</li>
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
            </div>
            
            <button class="nav-btn nav-btn-next" id="btnNavNext">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>