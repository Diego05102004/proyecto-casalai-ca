/**
 * Cliente JavaScript para el Microservicio IA Recepción
 * Proporciona la interfaz de comunicación entre el frontend PHP y la API Python
 */

class IARecepcionClient {
    constructor(config = {}) {
        // Configuración base
        this.config = {
            apiUrl: config.apiUrl || 'http://localhost:8000',
            timeout: config.timeout || 30000, // 30 segundos
            retries: config.retries || 2,
            ...config
        };
        
        // Estado del cliente
        this.estado = {
            conectado: false,
            procesando: false,
            ultimaVerificacion: null,
            errores: []
        };
        
        // Bind métodos
        this.procesarFactura = this.procesarFactura.bind(this);
        this.verificarCoherencia = this.verificarCoherencia.bind(this);
        this.procesarYVerificar = this.procesarYVerificar.bind(this);
        
        // Verificar conexión al iniciar
        this.verificarConexion();
    }
    
    /**
     * Verifica si el microservicio está disponible
     */
    async verificarConexion() {
        try {
            const response = await this._request('GET', '/health');
            this.estado.conectado = response.estado === 'saludable';
            
            if (this.estado.conectado) {
                console.log('Microservicio IA Recepción conectado exitosamente');
            } else {
                console.warn('Microservicio en estado degradado');
            }
            
            return this.estado.conectado;
        } catch (error) {
            this.estado.conectado = false;
            console.error('Error conectando con el microservicio:', error);
            return false;
        }
    }
    
    /**
     * Procesa una imagen de factura y extrae información
     * @param {File} archivoImagen - Archivo de imagen de la factura
     * @param {Function} onProgress - Callback para progreso (opcional)
     * @returns {Promise<Object>} Resultado del procesamiento
     */
    async procesarFactura(archivoImagen, onProgress = null) {
        if (!archivoImagen) {
            throw new Error('Se requiere un archivo de imagen');
        }
        
        if (this.estado.procesando) {
            throw new Error('Ya hay un procesamiento en curso');
        }
        
        this.estado.procesando = true;
        
        try {
            // Validar archivo
            this._validarArchivoImagen(archivoImagen);
            
            // Preparar FormData
            const formData = new FormData();
            formData.append('archivo', archivoImagen);
            
            // Notificar inicio
            if (onProgress) onProgress({ estado: 'iniciando', progreso: 0 });
            
            // Realizar solicitud
            const response = await this._request('POST', '/procesar-factura', formData, {
                onUploadProgress: (progressEvent) => {
                    if (onProgress) {
                        const procentaje = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );
                        onProgress({ 
                            estado: 'subiendo', 
                            progreso: porcentaje,
                            cargado: progressEvent.loaded,
                            total: progressEvent.total
                        });
                    }
                }
            });
            
            // Notificar completado
            if (onProgress) onProgress({ estado: 'completado', progreso: 100 });
            
            // Guardar estado
            this.estado.ultimaVerificacion = {
                tipo: 'procesamiento',
                timestamp: new Date().toISOString(),
                resultado: response
            };
            
            return response;
            
        } catch (error) {
            // Agregar error al estado
            this.estado.errores.push({
                timestamp: new Date().toISOString(),
                tipo: 'procesamiento',
                error: error.message
            });
            
            throw error;
        } finally {
            this.estado.procesando = false;
        }
    }
    
    /**
     * Verifica la coherencia entre datos de factura y formulario
     * @param {Object} datosFormulario - Datos del formulario
     * @param {Object} datosFactura - Datos extraídos de la factura (opcional)
     * @returns {Promise<Object>} Resultado de verificación
     */
    async verificarCoherencia(datosFormulario, datosFactura = {}) {
        if (!datosFormulario) {
            throw new Error('Se requieren los datos del formulario');
        }
        
        try {
            // Preparar payload
            const payload = {
                numero_factura: datosFactura.numero_factura || '',
                nombre_proveedor: datosFactura.nombre_proveedor || '',
                productos_factura: datosFactura.productos || [],
                datos_formulario: {
                    numero_factura: datosFormulario.numero_factura || '',
                    nombre_proveedor: datosFormulario.nombre_proveedor || '',
                    productos: datosFormulario.productos || []
                }
            };
            
            // Realizar verificación
            const response = await this._request('POST', '/verificar-coherencia', payload);
            
            // Guardar estado
            this.estado.ultimaVerificacion = {
                tipo: 'verificacion',
                timestamp: new Date().toISOString(),
                resultado: response
            };
            
            return response;
            
        } catch (error) {
            this.estado.errores.push({
                timestamp: new Date().toISOString(),
                tipo: 'verificacion',
                error: error.message
            });
            
            throw error;
        }
    }
    
    /**
     * Procesa factura y verifica coherencia en una sola operación
     * @param {File} archivoImagen - Archivo de imagen
     * @param {Object} datosFormulario - Datos del formulario
     * @param {Function} onProgress - Callback para progreso
     * @returns {Promise<Object>} Resultado completo
     */
    async procesarYVerificar(archivoImagen, datosFormulario, onProgress = null) {
        if (!archivoImagen || !datosFormulario) {
            throw new Error('Se requieren tanto la imagen como los datos del formulario');
        }
        
        this.estado.procesando = true;
        
        try {
            // Validar archivo
            this._validarArchivoImagen(archivoImagen);
            
            // Preparar FormData
            const formData = new FormData();
            formData.append('archivo', archivoImagen);
            formData.append('datos_formulario', JSON.stringify(datosFormulario));
            
            // Notificar inicio
            if (onProgress) onProgress({ estado: 'iniciando', progreso: 0 });
            
            // Realizar solicitud combinada
            const response = await this._request('POST', '/procesar-y-verificar', formData, {
                onUploadProgress: (progressEvent) => {
                    if (onProgress) {
                        const porcentaje = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );
                        onProgress({ 
                            estado: 'procesando', 
                            progreso: porcentaje
                        });
                    }
                }
            });
            
            // Notificar completado
            if (onProgress) onProgress({ estado: 'completado', progreso: 100 });
            
            // Guardar estado
            this.estado.ultimaVerificacion = {
                tipo: 'integral',
                timestamp: new Date().toISOString(),
                resultado: response
            };
            
            return response;
            
        } catch (error) {
            this.estado.errores.push({
                timestamp: new Date().toISOString(),
                tipo: 'integral',
                error: error.message
            });
            
            throw error;
        } finally {
            this.estado.procesando = false;
        }
    }
    
    /**
     * Obtiene el estado actual del cliente
     */
    getEstado() {
        return {
            ...this.estado,
            config: this.config
        };
    }
    
    /**
     * Limpia el historial de errores
     */
    limpiarErrores() {
        this.estado.errores = [];
    }
    
    /**
     * Método privado para realizar solicitudes HTTP
     */
    async _request(method, endpoint, data = null, options = {}) {
        const url = `${this.config.apiUrl}${endpoint}`;
        
        // Configuración por defecto
        const config = {
            method,
            timeout: this.config.timeout,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            ...options
        };
        
        // Manejar datos según el tipo
        if (data) {
            if (data instanceof FormData) {
                config.body = data;
                delete config.headers['Content-Type']; // Deja que el navegador lo establezca
            } else {
                config.body = JSON.stringify(data);
            }
        }
        
        // Intentar con reintentos
        let lastError;
        for (let intento = 0; intento <= this.config.retries; intento++) {
            try {
                const response = await fetch(url, config);
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
                }
                
                return await response.json();
                
            } catch (error) {
                lastError = error;
                
                if (intento < this.config.retries) {
                    // Esperar antes de reintentar (exponential backoff)
                    await new Promise(resolve => 
                        setTimeout(resolve, Math.pow(2, intento) * 1000)
                    );
                    continue;
                }
                
                throw lastError;
            }
        }
    }
    
    /**
     * Valida que el archivo sea una imagen válida
     */
    _validarArchivoImagen(archivo) {
        // Tipos permitidos
        const tiposPermitidos = [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/bmp',
            'image/tiff'
        ];
        
        if (!tiposPermitidos.includes(archivo.type)) {
            throw new Error(`Tipo de archivo no soportado: ${archivo.type}. Use JPG, PNG o BMP.`);
        }
        
        // Tamaño máximo (10MB)
        const tamanoMaximo = 10 * 1024 * 1024;
        if (archivo.size > tamanoMaximo) {
            throw new Error(`El archivo es demasiado grande. Máximo 10MB.`);
        }
        
        return true;
    }
}

/**
 * Utilidades para integración con formularios PHP
 */
class IARecepcionFormHelper {
    constructor(iaClient, formSelector) {
        this.iaClient = iaClient;
        this.form = document.querySelector(formSelector);
        
        if (!this.form) {
            throw new Error(`No se encontró el formulario con selector: ${formSelector}`);
        }
        
        this.inicializar();
    }
    
    inicializar() {
        // Agregar campos para la IA
        this._agregarCamposIA();
        
        // Configurar eventos
        this._configurarEventos();
        
        // Agregar botones de acción
        this._agregarBotonesAccion();
    }
    
    _agregarCamposIA() {
        // Campo para carga de imagen
        const imagenContainer = document.createElement('div');
        imagenContainer.className = 'ia-imagen-container';
        imagenContainer.innerHTML = `
            <div class="form-group">
                <label for="ia_factura_imagen"> Fotografía de la Factura (Requerido para verificación IA)</label>
                <input type="file" id="ia_factura_imagen" name="ia_factura_imagen" 
                       accept="image/*" class="form-control" required>
                <small class="form-text text-muted">
                    Cargue una fotografía clara de la factura para verificación automática
                </small>
                <div id="ia_imagen_preview" class="mt-2"></div>
            </div>
        `;
        
        // Insertar antes del primer campo del formulario
        this.form.insertBefore(imagenContainer, this.form.firstChild);
        
        // Contenedor para resultados de IA
        const resultadosContainer = document.createElement('div');
        resultadosContainer.id = 'ia_resultados_container';
        resultadosContainer.className = 'ia-resultados-container';
        resultadosContainer.style.display = 'none';
        
        this.form.appendChild(resultadosContainer);
    }
    
    _configurarEventos() {
        // Preview de imagen
        const inputImagen = document.getElementById('ia_factura_imagen');
        inputImagen.addEventListener('change', (e) => {
            this._mostrarPreviewImagen(e.target.files[0]);
        });
        
        // Validación antes de envío
        this.form.addEventListener('submit', (e) => {
            if (!this._validarAntesDeEnviar()) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    _agregarBotonesAccion() {
        const botonContainer = document.createElement('div');
        botonContainer.className = 'ia-botones-container mt-3';
        botonContainer.innerHTML = `
            <button type="button" id="ia_btn_verificar" class="btn btn-info">
                <i class="fas fa-robot"></i> Verificar con IA
            </button>
            <button type="button" id="ia_btn_limpiar" class="btn btn-secondary ml-2">
                <i class="fas fa-eraser"></i> Limpiar
            </button>
        `;
        
        this.form.appendChild(botonContainer);
        
        // Eventos de botones
        document.getElementById('ia_btn_verificar').addEventListener('click', () => {
            this._verificarConIA();
        });
        
        document.getElementById('ia_btn_limpiar').addEventListener('click', () => {
            this._limpiarResultados();
        });
    }
    
    _mostrarPreviewImagen(archivo) {
        const preview = document.getElementById('ia_imagen_preview');
        
        if (!archivo) {
            preview.innerHTML = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" 
                     style="max-width: 300px; max-height: 200px; border: 1px solid #ddd;" 
                     class="img-thumbnail">
                <p class="mt-1 mb-0"><small>${archivo.name} (${this._formatFileSize(archivo.size)})</small></p>
            `;
        };
        reader.readAsDataURL(archivo);
    }
    
    async _verificarConIA() {
        const inputImagen = document.getElementById('ia_factura_imagen');
        const archivo = inputImagen.files[0];
        
        if (!archivo) {
            this._mostrarError('Por favor, seleccione una imagen de la factura');
            return;
        }
        
        try {
            // Mostrar indicador de progreso
            this._mostrarProgreso('Procesando factura con IA...');
            
            // Obtener datos del formulario
            const datosFormulario = this._obtenerDatosFormulario();
            
            // Procesar y verificar
            const resultado = await this.iaClient.procesarYVerificar(
                archivo, 
                datosFormulario,
                (progreso) => this._actualizarProgreso(progreso)
            );
            
            // Mostrar resultados
            this._mostrarResultados(resultado);
            
        } catch (error) {
            this._mostrarError(`Error en verificación IA: ${error.message}`);
        }
    }
    
    _obtenerDatosFormulario() {
        // Este método debe adaptarse según los campos específicos del formulario
        return {
            numero_factura: document.querySelector('[name*="factura"]')?.value || '',
            nombre_proveedor: document.querySelector('[name*="proveedor"]')?.value || '',
            productos: this._obtenerProductosFormulario()
        };
    }
    
    _obtenerProductosFormulario() {
        // Implementar según la estructura de productos en el formulario
        // Esto es un ejemplo genérico
        const productos = [];
        
        // Buscar filas de productos (adaptar según el HTML real)
        const filasProductos = document.querySelectorAll('.producto-row');
        
        filasProductos.forEach((fila, index) => {
            productos.push({
                nombre: fila.querySelector('[name*="nombre"]')?.value || '',
                modelo: fila.querySelector('[name*="modelo"]')?.value || '',
                marca: fila.querySelector('[name*="marca"]')?.value || '',
                serial: fila.querySelector('[name*="serial"]')?.value || '',
                costo: parseFloat(fila.querySelector('[name*="costo"]')?.value || 0),
                cantidad: parseInt(fila.querySelector('[name*="cantidad"]')?.value || 0)
            });
        });
        
        return productos;
    }
    
    _mostrarResultados(resultado) {
        const container = document.getElementById('ia_resultados_container');
        
        if (!resultado.exito) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <strong>Error:</strong> ${resultado.mensaje}
                </div>
            `;
            container.style.display = 'block';
            return;
        }
        
        const procesamiento = resultado.procesamiento;
        const verificacion = resultado.verificacion;
        const accion = resultado.accion_recomendada;
        
        // Determinar clase de alerta según acción
        let alertClass = 'alert-success';
        let icono = 'fa-check-circle';
        
        if (accion === 'bloquear') {
            alertClass = 'alert-danger';
            icono = 'fa-exclamation-triangle';
        } else if (accion === 'requiere_revision') {
            alertClass = 'alert-warning';
            icono = 'fa-exclamation-circle';
        } else if (accion === 'advertencia') {
            alertClass = 'alert-info';
            icono = 'fa-info-circle';
        }
        
        container.innerHTML = `
            <div class="alert ${alertClass}">
                <h5><i class="fas ${icono}"></i> Resultado de Verificación IA</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Procesamiento de Factura</h6>
                        <p><strong>Confianza:</strong> ${(procesamiento.confianza * 100).toFixed(1)}%</p>
                        <p><strong>N° Factura:</strong> ${procesamiento.datos_factura.numero_factura || 'No detectado'}</p>
                        <p><strong>Proveedor:</strong> ${procesamiento.datos_factura.nombre_proveedor || 'No detectado'}</p>
                        <p><strong>Productos detectados:</strong> ${procesamiento.datos_factura.productos.length}</p>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Verificación de Coherencia</h6>
                        <p><strong>Estado:</strong> ${verificacion.es_coherente ? 'Coherente' : 'Inconsistencias detectadas'}</p>
                        <p><strong>Confianza verificación:</strong> ${(verificacion.confianza_verificacion * 100).toFixed(1)}%</p>
                        <p><strong>Discrepancias:</strong> ${verificacion.discrepancias.length}</p>
                    </div>
                </div>
                
                ${verificacion.discrepancias.length > 0 ? this._generarHtmlDiscrepancias(verificacion.discrepancias) : ''}
                
                <div class="mt-3">
                    <strong>Acción recomendada:</strong> 
                    <span class="badge badge-${this._getBadgeClass(accion)}">${this._getAccionTexto(accion)}</span>
                </div>
            </div>
        `;
        
        container.style.display = 'block';
    }
    
    _generarHtmlDiscrepancias(discrepancias) {
        let html = '<h6 class="mt-3">Discrepancias Detectadas:</h6><ul class="list-unstyled">';
        
        discrepancias.forEach((disc, index) => {
            const severidadClass = disc.severidad === 'alta' ? 'danger' : 
                                  disc.severidad === 'media' ? 'warning' : 'info';
            
            html += `
                <li class="mb-2">
                    <span class="badge badge-${severidadClass}">${disc.severidad.toUpperCase()}</span>
                    <strong>${disc.campo}:</strong> 
                    "${disc.valor_factura}" vs "${disc.valor_formulario}"
                </li>
            `;
        });
        
        html += '</ul>';
        return html;
    }
    
    _getBadgeClass(accion) {
        switch (accion) {
            case 'aprobar': return 'success';
            case 'bloquear': return 'danger';
            case 'requiere_revision': return 'warning';
            case 'advertencia': return 'info';
            default: return 'secondary';
        }
    }
    
    _getAccionTexto(accion) {
        switch (accion) {
            case 'aprobar': return 'Aprobar Registro';
            case 'bloquear': return 'Bloquear Registro';
            case 'requiere_revision': return 'Requiere Revisión Manual';
            case 'advertencia': return 'Advertencia - Verificar';
            default: return 'Desconocido';
        }
    }
    
    _mostrarProgreso(mensaje) {
        const container = document.getElementById('ia_resultados_container');
        container.innerHTML = `
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm mr-2" role="status">
                        <span class="sr-only">Procesando...</span>
                    </div>
                    ${mensaje}
                </div>
                <div class="progress mt-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" id="ia_progress_bar"></div>
                </div>
            </div>
        `;
        container.style.display = 'block';
    }
    
    _actualizarProgreso(progreso) {
        const progressBar = document.getElementById('ia_progress_bar');
        if (progressBar && progreso.progreso) {
            progressBar.style.width = `${progreso.progreso}%`;
        }
    }
    
    _mostrarError(mensaje) {
        const container = document.getElementById('ia_resultados_container');
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> ${mensaje}
            </div>
        `;
        container.style.display = 'block';
    }
    
    _limpiarResultados() {
        const container = document.getElementById('ia_resultados_container');
        container.style.display = 'none';
        container.innerHTML = '';
        
        // Limpiar imagen
        document.getElementById('ia_factura_imagen').value = '';
        document.getElementById('ia_imagen_preview').innerHTML = '';
    }
    
    _validarAntesDeEnviar() {
        // Verificar si hay resultados de IA y si bloquean el envío
        if (this.estado.ultimaVerificacion) {
            const resultado = this.estado.ultimaVerificacion.resultado;
            
            if (resultado.accion_recomendada === 'bloquear') {
                this._mostrarError('No se puede enviar el formulario. La IA ha detectado discrepancias críticas que deben corregirse.');
                return false;
            }
        }
        
        // Verificar que se haya cargado una imagen
        const inputImagen = document.getElementById('ia_factura_imagen');
        if (!inputImagen.files[0]) {
            this._mostrarError('Por favor, cargue una imagen de la factura antes de enviar.');
            return false;
        }
        
        return true;
    }
    
    _formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Exportar para uso global
window.IARecepcionClient = IARecepcionClient;
window.IARecepcionFormHelper = IARecepcionFormHelper;
