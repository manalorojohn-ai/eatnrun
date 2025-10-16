class NotificationsHandler {
    constructor() {
        this.bell = document.querySelector('.notification-bell');
        this.dropdown = document.querySelector('.notification-dropdown');
        this.list = document.querySelector('.notification-list');
        this.badge = document.querySelector('.notification-badge');
        this.markAllReadBtn = document.querySelector('.mark-all-read');
        this.unreadCount = 0;
        this.lastNotificationId = 0;
        this.isInitialized = false;
        this.pollInterval = 30000; // 30 seconds
    }

    initialize() {
        if (this.isInitialized || !this.bell) return;
        
        // Setup event listeners
        this.bell.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggleDropdown();
        });

        document.addEventListener('click', (e) => {
            if (!this.bell.contains(e.target)) {
                this.hideDropdown();
            }
        });

        if (this.markAllReadBtn) {
            this.markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }

        // Start polling for new notifications
        this.fetchNotifications();
        setInterval(() => this.checkNewNotifications(), this.pollInterval);
        
        this.isInitialized = true;
    }

    async fetchNotifications() {
        try {
            const response = await fetch('fetch_notifications.php');
            if (!response.ok) throw new Error('Failed to fetch notifications');
            
            const notifications = await response.json();
            this.updateNotificationsList(notifications);
            this.updateUnreadCount();
        } catch (error) {
            console.error('Error fetching notifications:', error);
            this.showError('Failed to load notifications');
        }
    }

    async checkNewNotifications() {
        try {
            const response = await fetch(`check_new_notifications.php?last_id=${this.lastNotificationId}`);
            if (!response.ok) throw new Error('Failed to check new notifications');
            
            const data = await response.json();
            if (data.success) {
                if (data.notifications.length > 0) {
                    this.addNewNotifications(data.notifications);
                    this.shakeBell();
                }
                this.unreadCount = data.unread_count;
                this.updateUnreadCount();
            }
        } catch (error) {
            console.error('Error checking new notifications:', error);
        }

    }

    updateNotificationsList(notifications) {
        if (!this.list) return;
        
        if (notifications.length === 0) {
            this.list.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications yet</p>
                </div>`;
            return;
        }

        this.list.innerHTML = notifications.map(notification => this.createNotificationItem(notification)).join('');
        this.lastNotificationId = Math.max(...notifications.map(n => n.id));
    }

    createNotificationItem(notification) {
        return `
            <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-icon">
                    <i class="fas ${notification.icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${notification.time_ago}</div>
                </div>
                ${notification.link ? `<a href="${notification.link}" class="notification-link">View</a>` : ''}
            </div>
        `;
    }

    addNewNotifications(notifications) {
        if (!this.list) return;
        
        notifications.forEach(notification => {
            const element = document.createElement('div');
            element.innerHTML = this.createNotificationItem(notification);
            this.list.insertBefore(element.firstChild, this.list.firstChild);
        });
        
        this.lastNotificationId = Math.max(this.lastNotificationId, ...notifications.map(n => n.id));
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch('mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            if (!response.ok) throw new Error('Failed to mark notification as read');
            
            const item = this.list.querySelector(`[data-id="${notificationId}"]`);
            if (item) item.classList.remove('unread');
            
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.updateUnreadCount();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('mark_all_notifications_read.php', {
                method: 'POST'
            });
            
            if (!response.ok) throw new Error('Failed to mark all notifications as read');
            
            const unreadItems = this.list.querySelectorAll('.notification-item.unread');
            unreadItems.forEach(item => item.classList.remove('unread'));
            
            this.unreadCount = 0;
            this.updateUnreadCount();
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    updateUnreadCount() {
        if (this.badge) {
            this.badge.textContent = this.unreadCount;
            this.badge.style.display = this.unreadCount > 0 ? 'flex' : 'none';
        }
    }

    addNotification(message, type = 'info') {
        // This method is used by other parts of the application to add new notifications
        const notification = {
            id: Date.now(),
            message,
            type,
            is_read: false,
            time_ago: 'Just now',
            icon: this.getIconForType(type)
        };
        
        this.addNewNotifications([notification]);
        this.unreadCount++;
        this.updateUnreadCount();
        this.shakeBell();
    }

    getIconForType(type) {
        switch (type) {
            case 'order': return 'fa-shopping-bag';
            case 'payment': return 'fa-credit-card';
            case 'delivery': return 'fa-truck';
            case 'error': return 'fa-exclamation-circle';
            case 'success': return 'fa-check-circle';
            case 'info': return 'fa-info-circle';
            default: return 'fa-bell';
        }
    }

    toggleDropdown() {
        if (this.dropdown) {
            this.dropdown.classList.toggle('show');
            if (this.dropdown.classList.contains('show')) {
                this.fetchNotifications();
            }
        }
    }

    hideDropdown() {
        if (this.dropdown) {
            this.dropdown.classList.remove('show');
        }
    }

    shakeBell() {
        if (this.bell) {
            this.bell.classList.add('shake');
            setTimeout(() => this.bell.classList.remove('shake'), 1000);
        }
    }

    showError(message) {
        if (this.list) {
            this.list.innerHTML = `
                <div class="notification-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>${message}</p>
                </div>`;
        }
    }
}

// Initialize the notifications handler when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.notificationsHandler = new NotificationsHandler();
    window.notificationsHandler.initialize();
}); 