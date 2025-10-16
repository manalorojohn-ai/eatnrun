// Page Optimization Handler
class PageOptimizer {
    constructor() {
        this.eventSource = null;
        this.scrollTimeout = null;
        this.initialized = false;
        this.cleanup = this.cleanup.bind(this);
    }

    init() {
        if (this.initialized) return;
        
        // Add cleanup listeners
        window.addEventListener('beforeunload', this.cleanup);
        window.addEventListener('unload', this.cleanup);
        
        // Optimize scroll performance
        this.optimizeScroll();
        
        // Cache DOM elements
        this.cacheElements();
        
        // Initialize lazy loading
        this.initLazyLoading();
        
        this.initialized = true;
    }

    cleanup() {
        // Close EventSource connection
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        
        // Clear intervals and timeouts
        if (window.notificationInterval) {
            clearInterval(window.notificationInterval);
        }
        
        // Remove event listeners
        window.removeEventListener('scroll', this.handleScroll);
        
        // Clear memory
        this.clearCache();
    }

    optimizeScroll() {
        window.addEventListener('scroll', () => {
            if (this.scrollTimeout) {
                window.cancelAnimationFrame(this.scrollTimeout);
            }
            
            this.scrollTimeout = window.requestAnimationFrame(() => {
                this.handleScroll();
            });
        }, { passive: true });
    }

    handleScroll() {
        const backToTop = document.getElementById('backToTop');
        if (backToTop) {
            backToTop.classList.toggle('visible', window.scrollY > 300);
        }
    }

    cacheElements() {
        // Cache frequently accessed elements
        this.cachedElements = {
            notificationBell: document.querySelector('.notification-bell'),
            notificationPanel: document.getElementById('notificationsPanel'),
            orderCards: document.querySelectorAll('.order-card')
        };
    }

    clearCache() {
        this.cachedElements = null;
    }

    initLazyLoading() {
        // Create intersection observer for lazy loading
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    if (element.dataset.src) {
                        element.src = element.dataset.src;
                        element.removeAttribute('data-src');
                    }
                    observer.unobserve(element);
                }
            });
        }, {
            rootMargin: '50px'
        });

        // Observe elements with data-src attribute
        document.querySelectorAll('[data-src]').forEach(element => {
            observer.observe(element);
        });
    }
}

// Initialize optimizer
const pageOptimizer = new PageOptimizer();
document.addEventListener('DOMContentLoaded', () => pageOptimizer.init()); 