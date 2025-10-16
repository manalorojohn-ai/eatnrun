class AdminNotifications {
    constructor() {
        this.unreadCount = 0;
        this.notificationList = document.querySelector('.notification-list .list-group');
        this.unreadBadge = document.querySelector('.notification-count-badge');
        this.dropdownMenu = document.querySelector('.dropdown-menu');
        this.lastCheck = new Date();
        this.checkInterval = 30000; // Check every 30 seconds
        
        this.initialize();
    }

    initialize() {
        // Initial load
        this.loadNotifications();
        
        // Set up polling
        setInterval(() => this.loadNotifications(), this.checkInterval);
        
        // Set up event listeners
        document.addEventListener('click', (e) => {
            if (e.target.matches('.mark-all-read')) {
                this.markAllAsRead();
            } else if (e.target.closest('.notification-item')) {
                const item = e.target.closest('.notification-item');
                const notificationId = item.dataset.id;
                this.markAsRead(notificationId);
            }
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('admin/get_notifications.php');
            const data = await response.json();
            
            if (data.unread_count !== this.unreadCount) {
                this.unreadCount = data.unread_count;
                this.updateUnreadBadge();
            }
            
            this.renderNotifications(data.notifications);
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    renderNotifications(notifications) {
        if (!this.notificationList) return;
        
        if (notifications.length === 0) {
            this.notificationList.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-bell-slash text-muted mb-2 fs-4"></i>
                    <p class="text-muted mb-0">No new notifications</p>
                </div>
            `;
            return;
        }

        this.notificationList.innerHTML = notifications.map(notification => `
            <a href="${notification.reference_type ? `${notification.reference_type}.php?id=${notification.reference_id}` : '#'}" 
               class="notification-item list-group-item list-group-item-action px-4 py-3 ${!notification.is_read ? 'unread' : ''}"
               data-id="${notification.id}">
                <div class="d-flex align-items-center">
                    <div class="notification-icon me-3">
                        ${this.getNotificationIcon(notification.type)}
                    </div>
                    <div class="flex-grow-1 pe-2">
                        <p class="mb-0 fw-medium">${notification.title}</p>
                        <p class="mb-0 text-muted small">${notification.message}</p>
                        <small class="text-muted">${notification.time_ago}</small>
                    </div>
                    ${!notification.is_read ? '<span class="notification-dot"></span>' : ''}
                </div>
            </a>
        `).join('');
    }

    getNotificationIcon(type) {
        const icons = {
            'new_order': '<i class="fas fa-shopping-bag text-primary"></i>',
            'order_status_change': '<i class="fas fa-exchange-alt text-warning"></i>',
            'payment': '<i class="fas fa-credit-card text-success"></i>',
            'system': '<i class="fas fa-cog text-info"></i>',
            'other': '<i class="fas fa-bell text-secondary"></i>'
        };
        return icons[type] || icons.other;
    }

    updateUnreadBadge() {
        if (!this.unreadBadge) return;
        
        if (this.unreadCount > 0) {
            this.unreadBadge.innerHTML = `
                <i class="fas fa-bell"></i>
                ${this.unreadCount} New
            `;
            this.unreadBadge.style.display = 'flex';
        } else {
            this.unreadBadge.style.display = 'none';
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('admin/get_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notification_id=${notificationId}`
            });
            
            const data = await response.json();
            if (data.success) {
                this.loadNotifications(); // Refresh notifications
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('admin/get_notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_all_read'
            });
            
            const data = await response.json();
            if (data.success) {
                this.loadNotifications(); // Refresh notifications
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
}

// Initialize notifications when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.adminNotifications = new AdminNotifications();
}); 