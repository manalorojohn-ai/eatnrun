/**
 * Advanced AJAX Handler for Eat&Run
 * Provides optimized, reusable AJAX utilities with caching and error handling
 */

class AjaxHandler {
    constructor() {
        this.cache = new Map();
        this.pendingRequests = new Map();
        this.requestTimeout = 10000; // 10 seconds
    }

    /**
     * Make an AJAX request with caching support
     */
    async request(url, options = {}) {
        const {
            method = 'GET',
            data = null,
            cache = false,
            cacheTime = 5000, // 5 seconds default
            timeout = this.requestTimeout,
            headers = {}
        } = options;

        // Create cache key
        const cacheKey = `${method}:${url}:${JSON.stringify(data)}`;

        // Check cache
        if (cache && method === 'GET' && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < cacheTime) {
                return cached.data;
            }
        }

        // Check if request is already pending (deduplicate)
        if (this.pendingRequests.has(cacheKey)) {
            return this.pendingRequests.get(cacheKey);
        }

        // Build request options
        const requestOptions = {
            method: method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...headers
            }
        };

        // Handle data
        if (data) {
            if (method === 'GET') {
                const params = new URLSearchParams(data);
                url += '?' + params.toString();
            } else {
                if (headers['Content-Type']?.includes('application/json')) {
                    requestOptions.body = JSON.stringify(data);
                } else {
                    requestOptions.body = new URLSearchParams(data);
                }
            }
        }

        // Create promise
        const requestPromise = this._fetchWithTimeout(url, requestOptions, timeout)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Cache the result
                if (cache && method === 'GET') {
                    this.cache.set(cacheKey, {
                        data: data,
                        timestamp: Date.now()
                    });
                }
                this.pendingRequests.delete(cacheKey);
                return data;
            })
            .catch(error => {
                this.pendingRequests.delete(cacheKey);
                throw error;
            });

        // Store pending request
        this.pendingRequests.set(cacheKey, requestPromise);

        return requestPromise;
    }

    /**
     * Fetch with timeout
     */
    _fetchWithTimeout(url, options, timeout) {
        return Promise.race([
            fetch(url, options),
            new Promise((_, reject) =>
                setTimeout(() => reject(new Error('Request timeout')), timeout)
            )
        ]);
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
    }

    /**
     * Clear cache for specific pattern
     */
    clearCachePattern(pattern) {
        for (let key of this.cache.keys()) {
            if (key.includes(pattern)) {
                this.cache.delete(key);
            }
        }
    }
}

// Create global instance
const ajax = new AjaxHandler();

/**
 * Enhanced notification system
 */
class NotificationManager {
    constructor() {
        this.queue = [];
        this.activeNotification = null;
        this.initStyles();
    }

    initStyles() {
        if (document.getElementById('notification-styles-v2')) return;

        const styles = document.createElement('style');
        styles.id = 'notification-styles-v2';
        styles.textContent = `
            .notification-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                pointer-events: none;
            }

            .notification-toast {
                background: white;
                border-radius: 12px;
                padding: 16px 20px;
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                gap: 16px;
                min-width: 300px;
                max-width: 450px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
                animation: slideInRight 0.3s ease-out;
                pointer-events: all;
                border-left: 4px solid #ddd;
            }

            .notification-toast.success {
                border-left-color: #10b981;
                background: #f0fdf4;
            }

            .notification-toast.error {
                border-left-color: #ef4444;
                background: #fef2f2;
            }

            .notification-toast.info {
                border-left-color: #3b82f6;
                background: #eff6ff;
            }

            .notification-toast.warning {
                border-left-color: #f59e0b;
                background: #fffbeb;
            }

            .notification-icon {
                font-size: 20px;
                min-width: 24px;
            }

            .notification-toast.success .notification-icon {
                color: #10b981;
            }

            .notification-toast.error .notification-icon {
                color: #ef4444;
            }

            .notification-toast.info .notification-icon {
                color: #3b82f6;
            }

            .notification-toast.warning .notification-icon {
                color: #f59e0b;
            }

            .notification-content {
                flex: 1;
            }

            .notification-title {
                font-weight: 600;
                margin-bottom: 4px;
                color: #1f2937;
            }

            .notification-message {
                font-size: 14px;
                color: #6b7280;
            }

            .notification-close {
                background: none;
                border: none;
                color: #9ca3af;
                cursor: pointer;
                padding: 0;
                font-size: 16px;
                transition: color 0.2s;
            }

            .notification-close:hover {
                color: #4b5563;
            }

            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }

            .notification-toast.removing {
                animation: slideOutRight 0.3s ease-out forwards;
            }

            @media (max-width: 640px) {
                .notification-container {
                    left: 10px;
                    right: 10px;
                    top: 10px;
                }

                .notification-toast {
                    min-width: auto;
                    max-width: none;
                }
            }
        `;
        document.head.appendChild(styles);
    }

    show(message, type = 'success', duration = 4000, title = null) {
        return new Promise((resolve) => {
            // Create container if needed
            let container = document.querySelector('.notification-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'notification-container';
                document.body.appendChild(container);
            }

            // Create notification
            const notification = document.createElement('div');
            notification.className = `notification-toast ${type}`;

            // Default titles
            const defaultTitles = {
                success: 'Success',
                error: 'Error',
                info: 'Info',
                warning: 'Warning'
            };

            const notificationTitle = title || defaultTitles[type] || 'Notification';

            // Icons
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                info: 'fas fa-info-circle',
                warning: 'fas fa-warning'
            };

            notification.innerHTML = `
                <i class="notification-icon ${icons[type] || 'fas fa-info-circle'}"></i>
                <div class="notification-content">
                    <div class="notification-title">${notificationTitle}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close" aria-label="Close notification">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Close handler
            const close = () => {
                notification.classList.add('removing');
                setTimeout(() => {
                    notification.remove();
                    if (container.children.length === 0) {
                        container.remove();
                    }
                    resolve();
                }, 300);
            };

            notification.querySelector('.notification-close').addEventListener('click', close);

            // Add to container
            container.appendChild(notification);

            // Auto-close
            const timeout = setTimeout(close, duration);

            // Cancel timeout on manual close
            notification.querySelector('.notification-close').addEventListener('click', () => {
                clearTimeout(timeout);
            });
        });
    }

    success(message, duration = 4000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 4000) {
        return this.show(message, 'error', duration);
    }

    info(message, duration = 4000) {
        return this.show(message, 'info', duration);
    }

    warning(message, duration = 4000) {
        return this.show(message, 'warning', duration);
    }
}

// Create global notification instance
const notify = new NotificationManager();

/**
 * Loading indicator system
 */
class LoadingIndicator {
    constructor() {
        this.count = 0;
    }

    show() {
        this.count++;
        if (this.count === 1) {
            this._createIndicator();
        }
    }

    hide() {
        this.count = Math.max(0, this.count - 1);
        if (this.count === 0) {
            this._removeIndicator();
        }
    }

    _createIndicator() {
        if (document.getElementById('global-loader')) return;

        const loader = document.createElement('div');
        loader.id = 'global-loader';
        loader.innerHTML = `
            <style id="loader-styles">
                #global-loader {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 3px;
                    background: linear-gradient(90deg, #006C3B, #00A65A);
                    transform-origin: left;
                    animation: progress 2s ease-in-out infinite;
                    z-index: 9999;
                }

                @keyframes progress {
                    0% { transform: scaleX(0); }
                    50% { transform: scaleX(1); }
                    100% { transform: scaleX(0); }
                }
            </style>
        `;
        document.body.appendChild(loader);
    }

    _removeIndicator() {
        const loader = document.getElementById('global-loader');
        if (loader) {
            setTimeout(() => loader.remove(), 300);
        }
    }
}

const loading = new LoadingIndicator();
