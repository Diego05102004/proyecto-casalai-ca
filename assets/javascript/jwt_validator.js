/**
 * Validador de JWT en tiempo real con sistema de extensión de sesión
 * 
 * Este script verifica periódicamente si el token JWT sigue siendo válido.
 * Si el token expira o es inválido, destruye la sesión y redirige al login.
 * Incluye sistema de extensión de sesión con modal y animación.
 */

(function() {
    'use strict';
    
    // Configuración
    const CONFIG = {
        verifyInterval: 5000, // Verificar cada 5 segundos
        verifyEndpoint: 'api/verify_token.php',
        extendEndpoint: 'api/extend_token.php',
        extensionThreshold: 20, // Mostrar modal 20 segundos antes de expirar
        extensionTime: 300, // Extender sesión por 5 minutos (300 segundos)
        maxExtensions: 3, // Máximo de extensiones permitidas
        extensionCookieName: 'session_extensions' // Nombre de la cookie para rastrear extensiones
    };
    
    let verifyTimer = null;
    let countdownTimer = null;
    let isRedirecting = false;
    let isModalShown = false;
    
    console.log('[JWT] Script jwt_validator.js cargado');
    console.log('[JWT] Configuración:', CONFIG);
    
    /**
     * Obtiene el número de extensiones de sesión usadas
     */
    function getExtensionCount() {
        const cookieValue = document.cookie
            .split('; ')
            .find(row => row.startsWith(CONFIG.extensionCookieName + '='));
        return cookieValue ? parseInt(cookieValue.split('=')[1]) : 0;
    }
    
    /**
     * Incrementa el contador de extensiones de sesión
     */
    function incrementExtensionCount() {
        const currentCount = getExtensionCount();
        const newCount = currentCount + 1;
        const expires = new Date();
        expires.setDate(expires.getDate() + 1); // Expira en 24 horas
        document.cookie = `${CONFIG.extensionCookieName}=${newCount}; expires=${expires.toUTCString()}; path=/`;
        return newCount;
    }
    
    /**
     * Reinicia el contador de extensiones de sesión
     */
    function resetExtensionCount() {
        document.cookie = `${CONFIG.extensionCookieName}=0; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/`;
    }
    
    /**
     * Verifica el token JWT
     */
    function verifyToken() {
        if (isRedirecting || isModalShown) {
            console.log('[JWT] Verificación omitida: isRedirecting=', isRedirecting, ', isModalShown=', isModalShown);
            return;
        }
        
        console.log('[JWT] Verificando token...');
        fetch(CONFIG.verifyEndpoint, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Token inválido o expirado');
            }
            return response.json();
        })
        .then(data => {
            console.log('[JWT] Respuesta de verificación:', data);
            
            if (!data.valid) {
                // Token inválido o expirado
                console.log('[JWT] Token inválido o expirado');
                handleTokenExpired();
            } else {
                console.log('[JWT] Token válido. expires_in:', data.expires_in, 'threshold:', CONFIG.extensionThreshold);
                
                if (data.expires_in && data.expires_in <= CONFIG.extensionThreshold) {
                    // Token está por expirar (20 segundos o menos)
                    console.log('[JWT] Token por expirar, mostrando modal...');
                    if (!isModalShown) {
                        showExtensionModal(data.expires_in);
                    }
                }
            }
        })
        .catch(error => {
            console.error('[JWT] Error al verificar token:', error);
            // Si hay error, asumimos que el token es inválido
            handleTokenExpired();
        });
    }
    
    /**
     * Muestra el modal de extensión de sesión
     */
    function showExtensionModal(secondsLeft) {
        console.log('[JWT] showExtensionModal llamado con secondsLeft:', secondsLeft);
        isModalShown = true;
        
        const extensionCount = getExtensionCount();
        const remainingExtensions = CONFIG.maxExtensions - extensionCount;
        
        console.log('[JWT] Extension count:', extensionCount, 'Remaining:', remainingExtensions);
        
        // Si no hay extensiones disponibles, no mostrar modal
        if (remainingExtensions <= 0) {
            console.log('[JWT] No hay extensiones disponibles, redirigiendo al login');
            handleTokenExpired();
            return;
        }
        
        console.log('[JWT] Creando modal HTML...');
        
        // Crear el modal
        const modal = document.createElement('div');
        modal.id = 'session-extension-modal';
        modal.innerHTML = `
            <div class="modal-overlay">
                <div class="modal-content">
                    <div class="modal-icon">
                        <svg class="countdown-circle" viewBox="0 0 100 100">
                            <circle class="countdown-bg" cx="50" cy="50" r="45" fill="none" stroke="#e0e0e0" stroke-width="8"/>
                            <circle class="countdown-progress" cx="50" cy="50" r="45" fill="none" stroke="#ff6b6b" stroke-width="8" 
                                    stroke-dasharray="283" stroke-dashoffset="0" stroke-linecap="round"
                                    transform="rotate(-90 50 50)"/>
                            <text class="countdown-text" x="50" y="55" text-anchor="middle" font-size="24" font-weight="bold" fill="#333">${secondsLeft}</text>
                        </svg>
                    </div>
                    <h2 class="modal-title">Tu sesión está por expirar</h2>
                    <p class="modal-message">¿Deseas extender tu sesión por 5 minutos adicionales?</p>
                    <p class="modal-remaining">Extensiones disponibles: <strong>${remainingExtensions}</strong> de ${CONFIG.maxExtensions}</p>
                    <div class="modal-buttons">
                        <button class="btn-extend" id="btn-extend-session">Extender Sesión</button>
                        <button class="btn-logout" id="btn-logout-session">Cerrar Sesión</button>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar estilos CSS
        const styles = document.createElement('style');
        styles.textContent = `
            #session-extension-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            .modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.3s ease-in;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .modal-content {
                background: white;
                border-radius: 16px;
                padding: 40px;
                max-width: 450px;
                width: 90%;
                text-align: center;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                animation: slideIn 0.3s ease-out;
            }
            
            @keyframes slideIn {
                from { 
                    transform: translateY(-50px);
                    opacity: 0;
                }
                to { 
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            .modal-icon {
                margin-bottom: 24px;
            }
            
            .countdown-circle {
                width: 120px;
                height: 120px;
            }
            
            .countdown-bg {
                stroke: #e0e0e0;
            }
            
            .countdown-progress {
                stroke: #ff6b6b;
                transition: stroke-dashoffset 1s linear;
            }
            
            .countdown-text {
                font-size: 28px;
                font-weight: bold;
                fill: #333;
            }
            
            .modal-title {
                margin: 0 0 16px 0;
                color: #333;
                font-size: 24px;
                font-weight: 600;
            }
            
            .modal-message {
                margin: 0 0 12px 0;
                color: #666;
                font-size: 16px;
                line-height: 1.5;
            }
            
            .modal-remaining {
                margin: 0 0 24px 0;
                color: #888;
                font-size: 14px;
            }
            
            .modal-buttons {
                display: flex;
                gap: 12px;
                justify-content: center;
            }
            
            .btn-extend, .btn-logout {
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .btn-extend {
                background: #4CAF50;
                color: white;
            }
            
            .btn-extend:hover {
                background: #45a049;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
            }
            
            .btn-logout {
                background: #f44336;
                color: white;
            }
            
            .btn-logout:hover {
                background: #da190b;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
            }
        `;
        
        document.head.appendChild(styles);
        document.body.appendChild(modal);
        
        console.log('[JWT] Modal agregado al DOM');
        
        // Iniciar animación de cuenta regresiva
        startCountdownAnimation(secondsLeft);
        
        // Agregar event listeners
        document.getElementById('btn-extend-session').addEventListener('click', extendSession);
        document.getElementById('btn-logout-session').addEventListener('click', handleTokenExpired);
    }
    
    /**
     * Inicia la animación de cuenta regresiva
     */
    function startCountdownAnimation(secondsLeft) {
        const progressCircle = document.querySelector('.countdown-progress');
        const countdownText = document.querySelector('.countdown-text');
        const circumference = 2 * Math.PI * 45; // 283
        
        let remaining = secondsLeft;
        
        countdownTimer = setInterval(() => {
            remaining--;
            
            if (remaining <= 0) {
                clearInterval(countdownTimer);
                hideExtensionModal();
                handleTokenExpired();
                return;
            }
            
            // Actualizar texto
            countdownText.textContent = remaining;
            
            // Actualizar círculo de progreso
            const offset = circumference * (1 - remaining / secondsLeft);
            progressCircle.style.strokeDashoffset = offset;
            
            // Cambiar color cuando queda poco tiempo
            if (remaining <= 5) {
                progressCircle.style.stroke = '#f44336';
            } else if (remaining <= 10) {
                progressCircle.style.stroke = '#ff9800';
            }
        }, 1000);
    }
    
    /**
     * Oculta el modal de extensión de sesión
     */
    function hideExtensionModal() {
        const modal = document.getElementById('session-extension-modal');
        if (modal) {
            modal.remove();
        }
        
        const styles = document.querySelector('style[data-modal-extension]');
        if (styles) {
            styles.remove();
        }
        
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
        
        isModalShown = false;
    }
    
    /**
     * Extiende la sesión del usuario
     */
    function extendSession() {
        const extensionCount = getExtensionCount();
        
        if (extensionCount >= CONFIG.maxExtensions) {
            alert('Has alcanzado el máximo de extensiones de sesión permitidas.');
            hideExtensionModal();
            handleTokenExpired();
            return;
        }
        
        // Deshabilitar botón para evitar múltiples clics
        const btnExtend = document.getElementById('btn-extend-session');
        if (btnExtend) {
            btnExtend.disabled = true;
            btnExtend.textContent = 'Extendiendo...';
        }
        
        fetch(CONFIG.extendEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al extender sesión');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Incrementar contador de extensiones
                incrementExtensionCount();
                
                // Ocultar modal
                hideExtensionModal();
                
                // Mostrar mensaje de éxito
                showSuccessMessage('Sesión extendida exitosamente por 5 minutos');
                
                // Reiniciar verificación
                console.log('Sesión extendida, continuando verificación');
            } else {
                throw new Error(data.message || 'Error al extender sesión');
            }
        })
        .catch(error => {
            console.error('Error al extender sesión:', error);
            alert('No se pudo extender la sesión. Por favor, inicia sesión nuevamente.');
            hideExtensionModal();
            handleTokenExpired();
        });
    }
    
    /**
     * Muestra un mensaje de éxito temporal
     */
    function showSuccessMessage(message) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 10001;
            animation: slideInRight 0.3s ease-out;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
        `;
        toast.textContent = message;
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { 
                    transform: translateX(100%);
                    opacity: 0;
                }
                to { 
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        
        document.head.appendChild(style);
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    /**
     * Maneja la expiración del token
     */
    function handleTokenExpired() {
        if (isRedirecting) {
            return;
        }
        
        isRedirecting = true;
        
        // Detener verificaciones
        if (verifyTimer) {
            clearInterval(verifyTimer);
        }
        
        // Ocultar modal si está visible
        hideExtensionModal();
        
        // Reiniciar contador de extensiones
        resetExtensionCount();
        
        // Mostrar mensaje al usuario
        alert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
        
        // Redirigir al login
        window.location.href = '?pagina=login';
    }
    
    /**
     * Inicia la verificación periódica
     */
    function startVerification() {
        // Verificar inmediatamente al cargar
        verifyToken();
        
        // Configurar verificación periódica
        verifyTimer = setInterval(verifyToken, CONFIG.verifyInterval);
        
        console.log('Validación JWT en tiempo real iniciada (intervalo:', CONFIG.verifyInterval / 1000, 'segundos)');
    }
    
    /**
     * Detiene la verificación periódica
     */
    function stopVerification() {
        if (verifyTimer) {
            clearInterval(verifyTimer);
            verifyTimer = null;
            console.log('Validación JWT en tiempo real detenida');
        }
    }
    
    // Iniciar verificación cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startVerification);
    } else {
        startVerification();
    }
    
    // Exponer funciones globalmente si es necesario
    window.JWTValidator = {
        start: startVerification,
        stop: stopVerification,
        verify: verifyToken
    };
    
})();
