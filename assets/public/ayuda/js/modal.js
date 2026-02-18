// Modal de Ayuda - Funcionalidad Principal
class ModalAyudaProveedor {
    constructor() {
        this.currentSlide = 0;
        this.totalSlides = 7; // Máximo de slides, se ajustará dinámicamente
        this.modal = null;
        this.overlay = null;
        this.ayudaPrincipal = null;
        this.ayudaTarjetas = null;
        this.tarjetas = [];
        this.btnPrev = null;
        this.btnNext = null;
        this.navDots = [];
        this.btnClose = null;
        this.animationDirection = 'right';
        
        this.init();
    }
    
    init() {
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupModal());
        } else {
            this.setupModal();
        }
    }
    
    setupModal() {
        // Obtener elementos del DOM
        this.modal = document.getElementById('modalAyuda');
        if (!this.modal) {
            console.error('No se encontró el modal de ayuda');
            return;
        }
        
        this.overlay = this.modal;
        this.ayudaPrincipal = document.getElementById('ayudaPrincipal');
        this.ayudaTarjetas = document.getElementById('ayudaTarjetas');
        this.btnPrev = document.getElementById('btnNavPrev');
        this.btnNext = document.getElementById('btnNavNext');
        this.btnClose = document.getElementById('cerrarModalAyuda');
        
        // Obtener todas las tarjetas de forma dinámica
        this.tarjetas = [
            null, // Slide 0 es la sección principal
            document.querySelector('[data-tarjeta="registrar"]'),
            document.querySelector('[data-tarjeta="detallar"]'),
            document.querySelector('[data-tarjeta="modificar"]'),
            document.querySelector('[data-tarjeta="eliminar"]'),
            document.querySelector('[data-tarjeta="estatus"]'),
            document.querySelector('[data-tarjeta="anular"]'),
            document.querySelector('[data-tarjeta="reporte"]')
        ];
        
        // Filtrar tarjetas nulas y calcular totalSlides dinámicamente
        this.tarjetas = this.tarjetas.filter(tarjeta => tarjeta !== null);
        this.totalSlides = this.tarjetas.length + 1; // +1 por la sección principal
        
        // Obtener indicadores de navegación
        this.navDots = document.querySelectorAll('.nav-dot');
        
        // Configurar event listeners
        this.setupEventListeners();
        
        // Inicializar estado
        this.updateSlide();
    }
    
    setupEventListeners() {
        // Botón cerrar
        if (this.btnClose) {
            this.btnClose.addEventListener('click', () => this.closeModal());
        }
        
        // Cerrar al hacer clic en el overlay
        if (this.overlay) {
            this.overlay.addEventListener('click', (e) => {
                if (e.target === this.overlay) {
                    this.closeModal();
                }
            });
        }
        
        // Navegación con botones
        if (this.btnPrev) {
            this.btnPrev.addEventListener('click', () => this.prevSlide());
        }
        
        if (this.btnNext) {
            this.btnNext.addEventListener('click', () => this.nextSlide());
        }
        
        // Navegación con indicadores
        this.navDots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToSlide(index));
        });
        
        // Navegación con teclado
        document.addEventListener('keydown', (e) => {
            if (!this.modal || !this.modal.classList.contains('active')) return;
            
            switch(e.key) {
                case 'Escape':
                    this.closeModal();
                    break;
                case 'ArrowLeft':
                    this.prevSlide();
                    break;
                case 'ArrowRight':
                    this.nextSlide();
                    break;
            }
        });
        
        // Navegación con gestos táctiles (para móviles)
        this.setupTouchGestures();
    }
    
    setupTouchGestures() {
        if (!this.modal) return;
        
        let touchStartX = 0;
        let touchEndX = 0;
        
        this.modal.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        this.modal.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe(touchStartX, touchEndX);
        }, { passive: true });
    }
    
    handleSwipe(startX, endX) {
        const swipeThreshold = 50;
        const diff = startX - endX;
        
        if (Math.abs(diff) < swipeThreshold) return;
        
        if (diff > 0) {
            // Swipe left - siguiente slide
            this.nextSlide();
        } else {
            // Swipe right - slide anterior
            this.prevSlide();
        }
    }
    
    openModal() {
        if (!this.modal) return;
        
        // Resetear al primer slide
        this.currentSlide = 0;
        this.updateSlide();
        
        // Mostrar modal
        this.modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Foco en el modal para accesibilidad
        this.modal.setAttribute('aria-hidden', 'false');
        this.modal.focus();
        
        // Emitir evento personalizado
        this.emitEvent('modal:opened');
    }
    
    closeModal() {
        if (!this.modal) return;
        
        this.modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // Accesibilidad
        this.modal.setAttribute('aria-hidden', 'true');
        
        // Emitir evento personalizado
        this.emitEvent('modal:closed');
    }
    
    prevSlide() {
        if (this.currentSlide > 0) {
            this.animationDirection = 'left';
            this.currentSlide--;
            this.updateSlide();
        }
    }
    
    nextSlide() {
        if (this.currentSlide < this.totalSlides - 1) {
            this.animationDirection = 'right';
            this.currentSlide++;
            this.updateSlide();
        }
    }
    
    goToSlide(slideIndex) {
        if (slideIndex >= 0 && slideIndex < this.totalSlides) {
            this.animationDirection = slideIndex > this.currentSlide ? 'right' : 'left';
            this.currentSlide = slideIndex;
            this.updateSlide();
        }
    }
    
    updateSlide() {
        // Actualizar visibilidad del contenido
        if (this.currentSlide === 0) {
            // Mostrar sección principal
            if (this.ayudaPrincipal) {
                this.ayudaPrincipal.style.display = 'block';
                this.ayudaPrincipal.classList.remove('slide-in-left', 'slide-in-right');
                void this.ayudaPrincipal.offsetWidth; // Forzar reflow
                this.ayudaPrincipal.classList.add(this.animationDirection === 'right' ? 'slide-in-right' : 'slide-in-left');
            }
            if (this.ayudaTarjetas) {
                this.ayudaTarjetas.style.display = 'none';
            }
        } else {
            // Mostrar tarjetas
            if (this.ayudaPrincipal) {
                this.ayudaPrincipal.style.display = 'none';
            }
            if (this.ayudaTarjetas) {
                this.ayudaTarjetas.style.display = 'block';
            }
            
            // Ocultar todas las tarjetas
            this.tarjetas.forEach((tarjeta, index) => {
                if (tarjeta) {
                    const slideIndex = index + 1; // +1 porque el array empieza con null
                    if (slideIndex === this.currentSlide) {
                        tarjeta.style.display = 'block';
                        tarjeta.classList.remove('slide-in-left', 'slide-in-right');
                        void tarjeta.offsetWidth; // Forzar reflow
                        tarjeta.classList.add(this.animationDirection === 'right' ? 'slide-in-right' : 'slide-in-left');
                    } else {
                        tarjeta.style.display = 'none';
                    }
                }
            });
        }
        
        // Actualizar botones de navegación
        this.updateNavigationButtons();
        
        // Actualizar indicadores
        this.updateIndicators();
        
        // Emitir evento de cambio
        this.emitEvent('slide:changed', { slide: this.currentSlide });
    }
    
    updateNavigationButtons() {
        if (this.btnPrev) {
            this.btnPrev.disabled = this.currentSlide === 0;
        }
        
        if (this.btnNext) {
            this.btnNext.disabled = this.currentSlide === this.totalSlides - 1;
        }
    }
    
    updateIndicators() {
        this.navDots.forEach((dot, index) => {
            if (index === this.currentSlide) {
                dot.classList.add('nav-dot-active');
            } else {
                dot.classList.remove('nav-dot-active');
            }
        });
    }
    
    emitEvent(eventName, detail = {}) {
        const event = new CustomEvent(eventName, {
            detail: {
                modal: this.modal,
                currentSlide: this.currentSlide,
                totalSlides: this.totalSlides,
                ...detail
            }
        });
        document.dispatchEvent(event);
    }
    
    // Métodos públicos
    isOpen() {
        return this.modal && this.modal.classList.contains('active');
    }
    
    getCurrentSlide() {
        return this.currentSlide;
    }
    
    getTotalSlides() {
        return this.totalSlides;
    }
}

// Inicializar el modal cuando se cargue el script
let modalAyudaProveedor = null;

// Función para inicializar el modal (se puede llamar desde otros scripts)
function inicializarModalAyudaProveedor() {
    if (!modalAyudaProveedor) {
        modalAyudaProveedor = new ModalAyudaProveedor();
    }
    return modalAyudaProveedor;
}

// Auto-inicialización
(function() {
    // Esperar a que jQuery esté disponible si es necesario
    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            inicializarModalAyudaProveedor();
        });
    } else {
        // Inicialización vanilla JS
        inicializarModalAyudaProveedor();
    }
})();

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.ModalAyudaProveedor = ModalAyudaProveedor;
    window.inicializarModalAyudaProveedor = inicializarModalAyudaProveedor;
    window.modalAyudaProveedor = modalAyudaProveedor;
}