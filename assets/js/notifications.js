// WebSocket connection
let ws;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

function initWebSocket() {
    ws = new WebSocket('ws://localhost:8080');

    ws.onopen = function() {
        console.log('Connected to notification server');
        reconnectAttempts = 0;
        
        // Register user with WebSocket server
        if (userId) {
            ws.send(JSON.stringify({
                type: 'register',
                userId: userId
            }));
        }
    };

    ws.onmessage = function(event) {
        const notification = JSON.parse(event.data);
        handleNewNotification(notification);
    };

    ws.onclose = function() {
        console.log('Disconnected from notification server');
        
        // Attempt to reconnect with exponential backoff
        if (reconnectAttempts < maxReconnectAttempts) {
            const timeout = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
            reconnectAttempts++;
            
            setTimeout(() => {
                console.log(`Attempting to reconnect (${reconnectAttempts}/${maxReconnectAttempts})`);
                initWebSocket();
            }, timeout);
        }
    };

    ws.onerror = function(error) {
        console.error('WebSocket error:', error);
    };
}

function handleNewNotification(notification) {
    // Update notification count
    const countElement = document.getElementById('notification-count');
    if (countElement) {
        const currentCount = parseInt(countElement.textContent) || 0;
        countElement.textContent = currentCount + 1;
        countElement.style.display = 'inline-block';
    }

    // Add notification to dropdown
    const container = document.getElementById('notification-list');
    if (container) {
        const notificationHtml = createNotificationElement(notification);
        container.insertAdjacentHTML('afterbegin', notificationHtml);

        // Remove oldest notification if more than 10
        const items = container.children;
        if (items.length > 10) {
            container.removeChild(items[items.length - 1]);
        }
    }

    // Show toast notification
    showToast(notification.message, notification.type);
}

function createNotificationElement(notification) {
    const timeAgo = moment(notification.created_at).fromNow();
    const icon = getNotificationIcon(notification.type);
    
    return `
        <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
            <div class="d-flex align-items-center">
                <div class="notification-icon">
                    <i class="${icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
                ${notification.link ? `<a href="${notification.link}" class="btn btn-sm btn-outline-primary">View</a>` : ''}
            </div>
        </div>
    `;
}

function getNotificationIcon(type) {
    switch (type) {
        case 'order':
            return 'fas fa-shopping-bag';
        case 'system':
            return 'fas fa-cog';
        case 'alert':
            return 'fas fa-exclamation-circle';
        default:
            return 'fas fa-bell';
    }
}

function showToast(message, type = 'info') {
    // Remove any existing toasts with a fade out
    const existingToasts = document.querySelectorAll('.toast-notification');
    existingToasts.forEach(toast => {
        toast.style.animation = 'fade-out 0.5s forwards';
        setTimeout(() => toast.remove(), 500);
    });

    // Create new toast
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    
    // Get icon based on type
    const icon = getToastIcon(type);
    
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="${icon}"></i>
        </div>
        <div class="toast-content">
            <p class="toast-message">${message}</p>
        </div>
        <button class="toast-close" aria-label="Close notification">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Add click handler for close button
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => {
        toast.style.animation = 'fade-out 0.5s forwards';
        setTimeout(() => toast.remove(), 500);
    });

    document.body.appendChild(toast);
    
    // Trigger show animation with a slight delay
    requestAnimationFrame(() => {
        setTimeout(() => {
            toast.classList.add('show');
        }, 50);
        
        // Auto remove after 6 seconds (more relaxed timing)
        setTimeout(() => {
            if (document.body.contains(toast)) {
                toast.style.animation = 'fade-out 0.5s forwards';
                setTimeout(() => toast.remove(), 500);
            }
        }, 6000);
    });
}

function getToastIcon(type) {
    switch (type) {
        case 'success':
            return 'fas fa-check';
        case 'error':
            return 'fas fa-exclamation-circle';
        case 'info':
            return 'fas fa-info-circle';
        default:
            return 'fas fa-bell';
    }
}

// Mark notification as read
document.addEventListener('click', function(e) {
    const notificationItem = e.target.closest('.notification-item');
    if (notificationItem) {
        const notificationId = notificationItem.dataset.id;
        markAsRead(notificationId);
    }
});

function markAsRead(notificationId) {
    fetch('/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to reflect read status
            const countElement = document.getElementById('notification-count');
            if (countElement) {
                const currentCount = parseInt(countElement.textContent) || 0;
                const newCount = Math.max(0, currentCount - 1);
                countElement.textContent = newCount;
                countElement.style.display = newCount > 0 ? 'inline-block' : 'none';
            }
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

// Initialize WebSocket connection when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initWebSocket();
    
    // Load initial notifications
    loadNotifications();
});

// Load initial notifications
function loadNotifications() {
    fetch('/fetch_notifications.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('notification-list');
            if (container) {
                container.innerHTML = data.notifications.map(notification => 
                    createNotificationElement(notification)
                ).join('');
                
                // Update notification count
                const countElement = document.getElementById('notification-count');
                if (countElement) {
                    const unreadCount = data.unread_count;
                    countElement.textContent = unreadCount;
                    countElement.style.display = unreadCount > 0 ? 'inline-block' : 'none';
                }
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

class NotificationHandler {
    constructor(userId) {
        this.userId = userId;
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.notificationCount = 0;
        this.connect();
        this.setupEventListeners();
    }

    connect() {
        try {
            this.ws = new WebSocket('ws://localhost:8080');

            this.ws.onopen = () => {
                console.log('Connected to notification server');
                this.reconnectAttempts = 0;
                
                // Register with the server
                this.ws.send(JSON.stringify({
                    type: 'register',
                    userId: this.userId
                }));
            };

            this.ws.onmessage = (event) => {
                try {
                    const notification = JSON.parse(event.data);
                    this.handleNewNotification(notification);
                } catch (e) {
                    console.error('Error processing notification:', e);
                }
            };

            this.ws.onclose = () => {
                console.log('Disconnected from notification server');
                this.attemptReconnect();
            };

            this.ws.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
        } catch (e) {
            console.error('Error connecting to WebSocket:', e);
            this.attemptReconnect();
        }
    }

    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            const timeout = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);
            this.reconnectAttempts++;
            
            console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
            setTimeout(() => this.connect(), timeout);
        }
    }

    handleNewNotification(notification) {
        // Update notification count
        this.notificationCount++;
        this.updateNotificationBadge();

        // Add to notification list
        this.addNotificationToList(notification);

        // Show toast notification
        this.showToast(notification);
    }

    addNotificationToList(notification) {
        const container = document.getElementById('notification-list');
        if (!container) return;

        const notificationHtml = this.createNotificationHtml(notification);
        container.insertAdjacentHTML('afterbegin', notificationHtml);

        // Limit the number of shown notifications
        const items = container.children;
        if (items.length > 10) {
            container.removeChild(items[items.length - 1]);
        }
    }

    createNotificationHtml(notification) {
        const timeAgo = this.formatTimeAgo(notification.created_at);
        return `
            <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-icon">
                    <i class="${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
                ${notification.link ? `<a href="${notification.link}" class="btn btn-sm btn-outline-primary">View</a>` : ''}
            </div>
        `;
    }

    getNotificationIcon(type) {
        const icons = {
            order: 'fas fa-shopping-bag',
            payment: 'fas fa-credit-card',
            system: 'fas fa-cog',
            alert: 'fas fa-exclamation-circle'
        };
        return icons[type] || 'fas fa-bell';
    }

    formatTimeAgo(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)} minutes ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)} hours ago`;
        return date.toLocaleDateString();
    }

    showToast(notification) {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        toast.innerHTML = `
            <div class="notification-toast-content">
                <i class="${this.getNotificationIcon(notification.type)}"></i>
                <span>${notification.message}</span>
            </div>
        `;

        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }, 100);
    }

    updateNotificationBadge() {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            badge.textContent = this.notificationCount;
            badge.style.display = this.notificationCount > 0 ? 'block' : 'none';
        }
    }

    setupEventListeners() {
        // Handle notification clicks
        document.addEventListener('click', (e) => {
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem && !notificationItem.classList.contains('read')) {
                const id = notificationItem.dataset.id;
                this.markAsRead(id);
            }
        });

        // Handle mark all as read
        const markAllReadBtn = document.getElementById('mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }
    }

    markAsRead(notificationId) {
        fetch('/mark_notification_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.add('read');
                }
                this.notificationCount = Math.max(0, this.notificationCount - 1);
                this.updateNotificationBadge();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }

    markAllAsRead() {
        fetch('/mark_all_notifications_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item.unread')
                    .forEach(item => item.classList.remove('unread'));
                this.notificationCount = 0;
                this.updateNotificationBadge();
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    }
}

// Initialize notification handler when document is ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof userId !== 'undefined') {
        window.notificationHandler = new NotificationHandler(userId);
    }
}); 