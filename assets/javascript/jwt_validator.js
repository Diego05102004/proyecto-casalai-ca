/**
 * Validador de JWT en tiempo real
 * 
 * Este script verifica periódicamente si el token JWT sigue siendo válido.
 * Si el token expira o es inválido, destruye la sesión y redirige al login.
 */

(function() {
    'use strict';
    
    // Configuración
    const CONFIG = {
        verifyInterval: 1000, // Verificar cada 1 segundo
        verifyEndpoint: 'api/verify_token.php',
        warningThreshold: 3 // Mostrar advertencia 5 minutos antes de expirar
    };
    
    let verifyTimer = null;
    let isRedirecting = false;
    
    /**
     * Verifica el token JWT
     */
    function verifyToken() {
        if (isRedirecting) {
            return;
        }
        
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
            if (!data.valid) {
                // Token inválido o expirado
                handleTokenExpired();
            } else if (data.expires_in && data.expires_in < CONFIG.warningThreshold) {
                // Token está por expirar (opcional: mostrar advertencia)
                console.warn('Token JWT expirará pronto:', data.expires_in, 'segundos');
            }
        })
        .catch(error => {
            console.error('Error al verificar token:', error);
            // Si hay error, asumimos que el token es inválido
            handleTokenExpired();
        });
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
