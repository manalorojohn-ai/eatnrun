// Real-time Notifications handling
const NotificationManager = {
    init: function() {
        this.notificationBell = document.querySelector('.notification-bell');
        this.notificationBadge = document.querySelector('.notification-badge');
        this.notificationDropdown = document.querySelector('.notification-dropdown');
        this.notificationList = document.querySelector('.notification-list');
        this.eventSource = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        
        if (this.notificationBell) {
            this.setupEventListeners();
            this.startRealTimeConnection();
            this.loadInitialNotifications();
        }
    },
    
    setupEventListeners: function() {
        // Handle notification item clicks
        document.addEventListener('click', (e) => {
            const notificationItem = e.target.closest('.notification-item');
            if (notificationItem && !notificationItem.classList.contains('read')) {
                const id = notificationItem.dataset.id;
                if (id) {
                    this.markAsRead(id);
                    notificationItem.classList.remove('unread');
                    notificationItem.classList.add('read');
                }
                
                // If there's a link, navigate to it
                const link = notificationItem.dataset.link;
                if (link) {
                    window.location.href = link;
                }
            }
        });

        // Handle "Mark all as read" button
        const markAllReadBtn = document.querySelector('.mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.markAllAsRead();
            });
        }
    },
    
    loadNotifications: function() {
        fetch('api/notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && this.notificationList) {
                    this.notificationList.innerHTML = '';
                    
                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notification => {
                            const item = document.createElement('div');
                            item.className = `notification-item ${notification.is_read ? 'read' : 'unread'}`;
                            item.dataset.id = notification.id;
                            if (notification.link) {
                                item.dataset.link = notification.link;
                            }
                            
                            item.innerHTML = `
                                <div class="notification-icon">
                                    <i class="fas ${this.getNotificationIcon(notification.type)}"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message">${notification.message}</div>
                                    <div class="notification-time">${notification.formatted_time}</div>
                                </div>
                            `;
                            
                            this.notificationList.appendChild(item);
                        });
                    } else {
                        this.notificationList.innerHTML = `
                            <div class="no-notifications">
                                <i class="fas fa-bell-slash"></i>
                                <p>No notifications</p>
                            </div>
                        `;
                    }
                    
                    this.updateBadgeCount(data.unread_count || 0);
                }
            })
            .catch(error => console.error('Error loading notifications:', error));
    },
    
    updateBadgeCount: function(count) {
        if (count > 0) {
            if (!this.notificationBadge) {
                const badge = document.createElement('span');
                badge.className = 'notification-badge';
                this.notificationBell.appendChild(badge);
                this.notificationBadge = badge;
            }
            this.notificationBadge.textContent = count;
            
            // Update the counter in the header if it exists
            const headerCounter = document.querySelector('.notification-count-badge');
            if (headerCounter) {
                headerCounter.innerHTML = `<i class="fas fa-bell"></i> ${count} New`;
            }
        } else {
            if (this.notificationBadge) {
                this.notificationBadge.remove();
                this.notificationBadge = null;
            }
            
            // Remove the counter from the header
            const headerCounter = document.querySelector('.notification-count-badge');
            if (headerCounter) {
                headerCounter.remove();
            }
        }
    },
    
    markAsRead: function(id) {
        fetch('api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_read',
                notification_id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadNotifications();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    },
    
    markAllAsRead: function() {
        fetch('api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_all_read'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadNotifications();
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    },
    
    getNotificationIcon: function(type) {
        const icons = {
            'order': 'fa-shopping-bag',
            'status': 'fa-info-circle',
            'system': 'fa-cog',
            'user': 'fa-user',
            'default': 'fa-bell'
        };
        return icons[type] || icons.default;
    },
    
    startRealTimeConnection: function() {
        try {
            this.eventSource = new EventSource('api/notifications_sse.php');
            
            this.eventSource.onopen = () => {
                console.log('Real-time notifications connected');
                this.reconnectAttempts = 0;
            };
            
            this.eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleSSEMessage(data);
                } catch (error) {
                    console.error('Error parsing SSE data:', error);
                }
            };
            
            this.eventSource.onerror = (error) => {
                console.error('SSE connection error:', error);
                this.handleConnectionError();
            };
            
        } catch (error) {
            console.error('Error creating SSE connection:', error);
            this.fallbackToPolling();
        }
    },
    
    handleSSEMessage: function(data) {
        switch (data.type) {
            case 'connected':
                console.log('Connected to notification stream');
                break;
                
            case 'new_notifications':
                this.addNewNotifications(data.notifications);
                break;
                
            case 'unread_count':
                this.updateBadgeCount(data.count);
                break;
                
            case 'heartbeat':
                // Connection is alive
                break;
                
            default:
                console.log('Unknown SSE message type:', data.type);
        }
    },
    
    addNewNotifications: function(notifications) {
        if (!this.notificationList || !notifications.length) return;
        
        // Remove "no notifications" message if it exists
        const noNotifications = this.notificationList.querySelector('.no-notifications');
        if (noNotifications) {
            noNotifications.remove();
        }
        
        // Add new notifications to the top
        notifications.forEach(notification => {
            const item = document.createElement('div');
            item.className = `notification-item ${notification.is_read ? 'read' : 'unread'}`;
            item.dataset.id = notification.id;
            if (notification.link) {
                item.dataset.link = notification.link;
            }
            
            item.innerHTML = `
                <div class="notification-icon">
                    <i class="fas ${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${notification.formatted_time}</div>
                </div>
            `;
            
            // Add animation for new notifications
            item.style.opacity = '0';
            item.style.transform = 'translateY(-10px)';
            this.notificationList.insertBefore(item, this.notificationList.firstChild);
            
            // Animate in
            setTimeout(() => {
                item.style.transition = 'all 0.3s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, 100);
        });
        
        // Keep only the latest 10 notifications
        const items = this.notificationList.querySelectorAll('.notification-item');
        if (items.length > 10) {
            for (let i = 10; i < items.length; i++) {
                items[i].remove();
            }
        }
    },
    
    handleConnectionError: function() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
        
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            console.log(`Attempting to reconnect... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
            
            setTimeout(() => {
                this.startRealTimeConnection();
            }, 5000 * this.reconnectAttempts); // Exponential backoff
        } else {
            console.log('Max reconnection attempts reached. Falling back to polling.');
            this.fallbackToPolling();
        }
    },
    
    fallbackToPolling: function() {
        console.log('Falling back to polling method');
        this.loadNotifications();
        setInterval(() => this.loadNotifications(), 30000);
    },
    
    loadInitialNotifications: function() {
        // Load initial notifications via regular API
        this.loadNotifications();
    },
    
    destroy: function() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    NotificationManager.init();
});

// Clean up when page unloads
window.addEventListener('beforeunload', () => {
    NotificationManager.destroy();
}); 