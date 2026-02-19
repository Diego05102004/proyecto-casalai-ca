<!-- Modal de Ayuda para Modelos -->
<div class="modal-overlay" id="modalAyuda">
    <div class="modal-container">
        <div class="modal-header">
            <h2 class="modal-title">Ayuda de Modelos</h2>
            <button class="modal-close" id="closeModal">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        
        <div class="modal-content">
            <!-- Slide Principal: Gestión de Modelos -->
            <div class="slide active" data-tarjeta="principal">
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z" fill="currentColor"/>
                            </svg>
                            Gestión de Modelos
                        </h3>
                    </div>
                    <div class="card-body">
                        <p>Administre los modelos de productos para especificar versiones y variantes.</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Información gestionable:</h4>
                                <ul>
                                    <li>Nombre del modelo</li>
                                    <li>Marca asociada</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h4>Operaciones disponibles:</h4>
                                <ul>
                                    <li><strong>Registrar</strong>: Nuevo modelo</li>
                                    <li><strong>Consultar</strong>: Ver lista completa</li>
                                    <li><strong>Modificar</strong>: Actualizar datos</li>
                                    <li><strong>Eliminar</strong>: Remover modelo</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Nota:</strong> Los modelos especifican versiones y variantes dentro de cada marca.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 1: Registrar Modelo -->
            <div class="slide" data-tarjeta="registrar">
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                <line x1="12" y1="8" x2="12" y2="16" stroke="currentColor" stroke-width="2"/>
                                <line x1="8" y1="12" x2="16" y2="12" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Registrar Modelo
                        </h3>
                    </div>
                    <div class="card-body">
                        <h4>Pasos para Registrar Nuevo Modelo</h4>
                        
                        <div class="row">
                            <div class="col-md-7">
                                <ol>
                                    <li><strong>Paso 1:</strong> Haga clic en el botón <strong>"+"</strong> (color verde) para nuevo modelo.</li>
                                    <li><strong>Paso 2:</strong> Seleccione la <strong>marca</strong> asociada.</li>
                                    <li><strong>Paso 3:</strong> Ingrese el <strong>nombre</strong> del modelo.</li>
                                    <li><strong>Paso 4:</strong> Haga clic en <strong>"Registrar"</strong> para confirmar.</li>
                                </ol>
                            </div>
                            <div class="col-md-5">
                                <div class="alert alert-light border">
                                    <i class="bi bi-patch-plus text-success me-2"></i>
                                    <strong>Nuevo Modelo:</strong><br> Botón "+" verde
                                </div>
                                <div class="alert alert-light border mt-2">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Botón Limpiar</strong><br> Resetea el formulario
                                </div>
                                <div class="alert alert-info border mt-2">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Nombre del Modelo:</strong><br> (único en el sistema)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 2: Modificar Modelo -->
            <div class="slide" data-tarjeta="modificar">
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" stroke="currentColor" stroke-width="2"/>
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Modificar Modelo
                        </h3>
                    </div>
                    <div class="card-body">
                        <h4>Pasos para Modificar Modelo</h4>
                        
                        <div class="row">
                            <div class="col-md-7">
                                <ol>
                                    <li><strong>Paso 1:</strong> Localice el modelo en la tabla.</li>
                                    <li><strong>Paso 2:</strong> Haga clic en el ícono del <strong>lápiz</strong> 📝 en la columna "Acciones".</li>
                                    <li><strong>Paso 3:</strong> Edite los campos necesarios.</li>
                                    <li><strong>Paso 4:</strong> Haga clic en <strong>"Modificar"</strong> para confirmar cambios.</li>
                                </ol>
                            </div>
                            <div class="col-md-5">
                                <div class="alert alert-light border">
                                    <i class="bi bi-pencil text-info me-2"></i>
                                    <strong>Modificar:</strong> Ícono lápiz
                                </div>
                                <div class="alert alert-info border mt-2">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Tip:</strong> Los cambios se reflejan inmediatamente
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide 3: Eliminar Modelo -->
            <div class="slide" data-tarjeta="eliminar">
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <polyline points="3,6 5,6 21,6" stroke="currentColor" stroke-width="2"/>
                                <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Eliminar Modelo
                        </h3>
                    </div>
                    <div class="card-body">
                        <h4>Pasos para Eliminar Modelo</h4>
                        
                        <div class="row">
                            <div class="col-md-7">
                                <ol>
                                    <li><strong>Paso 1:</strong> Encuentre el modelo que desea eliminar.</li>
                                    <li><strong>Paso 2:</strong> Haga clic en el ícono de la <strong>X</strong> ❌ en "Acciones".</li>
                                    <li><strong>Paso 3:</strong> Confirme la eliminación en el mensaje de advertencia.</li>
                                </ol>
                            </div>
                            <div class="col-md-5">
                                <div class="alert alert-light border">
                                    <i class="bi bi-trash text-danger me-2"></i>
                                    <strong>Eliminar:</strong> Ícono X rojo
                                </div>
                                <div class="alert alert-danger border mt-2">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <strong>¡Cuidado!</strong><br> Esta acción no se puede deshacer
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación -->
        <div class="modal-ayuda-navigation">
            <div class="nav-indicators">
                <button class="nav-dot active" data-slide="0" title="Gestión de Modelos"></button>
                <button class="nav-dot" data-slide="1" title="Registrar"></button>
                <button class="nav-dot" data-slide="2" title="Modificar"></button>
                <button class="nav-dot" data-slide="3" title="Eliminar"></button>
            </div>
            <div class="nav-buttons">
                <button class="nav-btn-prev" id="prevSlide">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <polyline points="15,18 9,12 15,6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
                <button class="nav-btn-next" id="nextSlide">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <polyline points="9,18 15,12 9,6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>