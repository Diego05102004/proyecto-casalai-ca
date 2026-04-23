/**
 * ============================================================================
 * ASISTENTE IA - MÓDULO RECEPCIÓN
 * Fase 1: Verificación Asistida (El Guardián)
 * 
 * Este módulo JavaScript gestiona la integración entre el sistema PHP
 * y el microservicio de IA para auditoría de recepción de mercancía.
 * ============================================================================
 */

class AsistenteRecepcionIA {
    constructor(config = {}) {
        this.apiUrl = config.apiUrl || 'http://localhost:8000';
        this.facturaIdActual = null;
        this.datosExtraidos = null;
        this.modoDebug = config.debug || false;
        
        this.selectores = {
            inputImagen: config.selectores?.inputImagen || '#input-foto-factura',
            previewImagen: config.selectores?.previewImagen || '#preview-factura',
            btnExtraer: config.selectores?.btnExtraer || '#btn-extraer-factura',
            btnVerificar: config.selectores?.btnVerificar || '#btn-verificar-datos',
            panelResultados: config.selectores?.panelResultados || '#panel-resultados-ia',
            alertasContainer: config.selectores?.alertas || '#alertas-recepcion',
            formulario: config.selectores?.formulario || '#form-recepcion'
        };
        
        this.callbacks = {
            onExtraccionExitosa: config.onExtraccionExitosa || (() => {}),
            onVerificacionExitosa: config.onVerificacionExitosa || (() => {}),
            onDiscrepancias: config.onDiscrepancias || (() => {}),
            onError: config.onError || console.error
        };
        
        this._inicializar();
    }

    _inicializar() {
        this._log('Asistente IA inicializado - Fase 1: El Guardián');
        this._verificarConexionAPI();
    }

    _log(mensaje, tipo = 'info') {
        const prefijo = '[AsistenteIA]';
        if (this.modoDebug || tipo === 'error') {
            console[tipo === 'error' ? 'error' : tipo === 'warn' ? 'warn' : 'log'](prefijo, mensaje);
        }
    }

    async _verificarConexionAPI() {
        try {
            const response = await fetch(`${this.apiUrl}/health`, { method: 'GET', headers: { 'Accept': 'application/json' } });
            if (response.ok) {
                const data = await response.json();
                this._log(`API conectada: ${data.fase || 'OK'}`);
                return true;
            }
        } catch (e) {
            this._log('API no disponible. Verifique que el microservicio esté ejecutándose.', 'warn');
        }
        return false;
    }

    /**
     * PASO 1: Captura y extracción de factura
     * Sube imagen al microservicio y obtiene datos extraídos
     */
    async extraerDesdeImagen(archivoImagen) {
        this._log('Iniciando extracción OCR...');
        
        const formData = new FormData();
        formData.append('imagen', archivoImagen);
        
        try {
            const response = await fetch(`${this.apiUrl}/fase1/extraer`, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.detail || 'Error en extracción');
            }
            
            const resultado = await response.json();
            
            if (resultado.exito) {
                this.facturaIdActual = resultado.factura_id;
                this.datosExtraidos = resultado;
                
                this._mostrarPreviewExtraccion(resultado);
                this.callbacks.onExtraccionExitosa(resultado);
                
                this._log(`Extracción exitosa. ID: ${this.facturaIdActual}`);
                return { exito: true, data: resultado };
            } else {
                throw new Error('Extracción no exitosa');
            }
            
        } catch (error) {
            this._log(`Error extracción: ${error.message}`, 'error');
            this._mostrarError(`Error al procesar factura: ${error.message}`);
            this.callbacks.onError(error);
            return { exito: false, error: error.message };
        }
    }

    /**
     * PASO 2: Verificación de coherencia
     * Compara datos del formulario con la factura extraída
     * @param {string} facturaId - ID de la factura (opcional, usa el actual si no se proporciona)
     * @param {Object} datosFormulario - Datos del formulario a verificar
     */
    async verificarCoherencia(facturaId, datosFormulario) {
        // Permitir llamada con o sin facturaId explícito
        let idFactura = facturaId;
        let datos = datosFormulario;
        
        if (typeof facturaId === 'object' && datosFormulario === undefined) {
            // Solo se pasaron los datos, usar facturaId actual
            datos = facturaId;
            idFactura = this.facturaIdActual;
        }
        
        if (!idFactura && !this.facturaIdActual) {
            this._mostrarError('Primero debe cargar y extraer la factura');
            return { exito: false, error: 'Sin factura en cache' };
        }
        
        const facturaIdFinal = idFactura || this.facturaIdActual;
        
        this._log('Verificando coherencia de datos...');
        
        const payload = {
            factura_id: facturaIdFinal,
            datos_formulario: datos
        };
        
        try {
            const response = await fetch(`${this.apiUrl}/fase1/verificar`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.detail || 'Error en verificación');
            }
            
            const resultado = await response.json();
            
            if (resultado.exito) {
                this._mostrarVerificacionExitosa(resultado);
                this.callbacks.onVerificacionExitosa(resultado);
            } else {
                this._mostrarDiscrepancias(resultado);
                this.callbacks.onDiscrepancias(resultado);
            }
            
            return resultado;
            
        } catch (error) {
            this._log(`Error verificación: ${error.message}`, 'error');
            this._mostrarError(`Error al verificar: ${error.message}`);
            return { exito: false, error: error.message };
        }
    }

    /**
     * Comparación directa (extracción + verificación en un paso)
     */
    async compararDirecto(archivoImagen, datosFormulario) {
        this._log('Iniciando comparación directa...');
        
        const formData = new FormData();
        formData.append('imagen', archivoImagen);
        formData.append('datos_json', JSON.stringify(datosFormulario));
        
        try {
            const response = await fetch(`${this.apiUrl}/fase1/comparar-directo`, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.detail || 'Error en comparación');
            }
            
            const resultado = await response.json();
            
            if (resultado.exito) {
                this._mostrarVerificacionExitosa(resultado);
                this.callbacks.onVerificacionExitosa(resultado);
            } else {
                this._mostrarDiscrepancias(resultado);
                this.callbacks.onDiscrepancias(resultado);
            }
            
            return resultado;
            
        } catch (error) {
            this._log(`Error comparación: ${error.message}`, 'error');
            this._mostrarError(`Error: ${error.message}`);
            return { exito: false, error: error.message };
        }
    }

    /**
     * UTILIDADES UI
     */
    _mostrarPreviewExtraccion(datos) {
        const panel = document.querySelector(this.selectores.panelResultados);
        if (!panel) return;
        
        const nivelConfianza = datos.confianza_promedio >= 0.85 ? 'alta' : 
                               datos.confianza_promedio >= 0.60 ? 'media' : 'baja';
        const colorConfianza = { alta: 'success', media: 'warning', baja: 'danger' }[nivelConfianza];
        
        let htmlProductos = '';
        if (datos.productos && datos.productos.length > 0) {
            htmlProductos = datos.productos.map((p, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td>${p.nombre || 'N/A'}</td>
                    <td>${p.modelo || '-'}</td>
                    <td>${p.marca || '-'}</td>
                    <td>${p.serial || '-'}</td>
                    <td>${p.cantidad}</td>
                    <td>${p.costo_unitario ? '$' + p.costo_unitario.toFixed(2) : '-'}</td>
                    <td>
                        <span class="badge bg-${p.confianza >= 0.7 ? 'success' : p.confianza >= 0.4 ? 'warning' : 'danger'}">
                            ${(p.confianza * 100).toFixed(0)}%
                        </span>
                    </td>
                </tr>
            `).join('');
        }
        
        panel.innerHTML = `
            <div class="alert alert-${colorConfianza} alert-dismissible fade show" role="alert">
                <h5><i class="fas fa-file-invoice"></i> Factura Extraída</h5>
                <p class="mb-1"><strong>N° Factura:</strong> ${datos.numero_factura || 'No detectado'}</p>
                <p class="mb-1"><strong>Proveedor:</strong> ${datos.nombre_proveedor || 'No detectado'}</p>
                <p class="mb-1"><strong>Confianza:</strong> 
                    <span class="badge bg-${colorConfianza}">${(datos.confianza_promedio * 100).toFixed(1)}%</span>
                </p>
                <hr>
                <p class="mb-0"><strong>ID Cache:</strong> <code>${datos.factura_id}</code></p>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            ${datos.productos.length > 0 ? `
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark"><tr><th>#</th><th>Producto</th><th>Modelo</th><th>Marca</th><th>Serial</th><th>Cant</th><th>Costo</th><th>Conf</th></tr></thead>
                    <tbody>${htmlProductos}</tbody>
                </table>
            </div>` : ''}
        `;
        panel.classList.remove('d-none');
    }

    _mostrarVerificacionExitosa(resultado) {
        const panel = document.querySelector(this.selectores.panelResultados);
        if (!panel) return;
        
        panel.innerHTML = `
            <div class="alert alert-success" role="alert">
                <h5><i class="fas fa-check-circle"></i> Verificación Exitosa</h5>
                <p>${resultado.mensaje}</p>
                <p class="mb-0"><small>Hash: <code>${resultado.hash_verificacion}</code></small></p>
            </div>
        `;
        
        this._mostrarAlerta('success', 'Los datos coinciden con la factura. Puede proceder con el registro.');
    }

    _mostrarDiscrepancias(resultado) {
        const panel = document.querySelector(this.selectores.panelResultados);
        if (!panel) return;
        
        const hayCriticas = resultado.discrepancias.some(d => d.severidad === 'CRITICA');
        const colorAlerta = hayCriticas ? 'danger' : 'warning';
        const icono = hayCriticas ? 'fa-exclamation-triangle' : 'fa-exclamation-circle';
        
        const filasDiscrepancias = resultado.discrepancias.map(d => {
            const colorSeveridad = { CRITICA: 'danger', ALTA: 'warning', MEDIA: 'info', BAJA: 'secondary' }[d.severidad] || 'secondary';
            return `
                <tr class="table-${colorSeveridad}">
                    <td><span class="badge bg-${colorSeveridad}">${d.severidad}</span></td>
                    <td><strong>${d.campo}</strong></td>
                    <td class="text-decoration-line-through text-muted">${d.valor_formulario}</td>
                    <td class="fw-bold">${d.valor_factura}</td>
                    <td><small>${d.mensaje}</small></td>
                </tr>
            `;
        }).join('');
        
        panel.innerHTML = `
            <div class="alert alert-${colorAlerta}" role="alert">
                <h5><i class="fas ${icono}"></i> Discrepancias Detectadas</h5>
                <p>${resultado.mensaje}</p>
                <p class="mb-0"><strong>Acción:</strong> ${hayCriticas ? 'Registro BLOQUEADO. Corrija los datos antes de continuar.' : 'Revise las diferencias antes de proceder.'}</p>
            </div>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-dark"><tr><th>Severidad</th><th>Campo</th><th>Valor Formulario</th><th>Valor Factura</th><th>Observación</th></tr></thead>
                    <tbody>${filasDiscrepancias}</tbody>
                </table>
            </div>
            <pre class="bg-light p-2 small" style="max-height: 200px; overflow-y: auto;">${resultado.reporte_detallado}</pre>
        `;
        
        this._mostrarAlerta(colorAlerta, hayCriticas ? 
            '⚠️ Registro BLOQUEADO: Hay discrepancias críticas con la factura.' : 
            '⚠️ Diferencias detectadas. Revise antes de registrar.'
        );
    }

    _mostrarError(mensaje) {
        this._mostrarAlerta('danger', mensaje);
    }

    _mostrarAlerta(tipo, mensaje) {
        const container = document.querySelector(this.selectores.alertasContainer);
        if (!container) {
            alert(mensaje);
            return;
        }
        
        const alertId = 'alert-ia-' + Date.now();
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) alert.remove();
        }, 10000);
    }

    /**
     * UTILIDADES PÚBLICAS
     */
    obtenerDatosExtraidos() {
        return this.datosExtraidos;
    }

    getFacturaId() {
        return this.facturaIdActual;
    }

    limpiarCache() {
        this.facturaIdActual = null;
        this.datosExtraidos = null;
        this._log('Cache limpiado');
    }

    setApiUrl(url) {
        this.apiUrl = url;
        this._verificarConexionAPI();
    }
}

// Exportar para uso global o módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AsistenteRecepcionIA;
} else {
    window.AsistenteRecepcionIA = AsistenteRecepcionIA;
}
