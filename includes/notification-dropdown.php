<?php
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id): 
?>
<div class="notifications-container">
    <div class="notification-bell" id="notificationBell">
        <i class="fas fa-bell"></i>
        <span class="notification-badge" style="display: none;">0</span>
    </div>
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button class="mark-all-read">Mark all as read</button>
        </div>
        
        <!-- Loading State -->
        <div id="notification-loading" class="notification-state">
            <div class="loading-spinner"></div>
            <p>Loading notifications...</p>
        </div>
        
        <!-- Empty State -->
        <div id="notification-empty" class="notification-state">
            <i class="fas fa-bell-slash"></i>
            <p>No notifications yet</p>
        </div>
        
        <!-- Error State -->
        <div id="notification-error" class="notification-state">
            <i class="fas fa-exclamation-circle"></i>
            <p>Failed to load notifications</p>
        </div>
        
        <!-- Notifications List -->
        <div class="notification-list"></div>
        
        <!-- View All Link -->
        <a href="notifications.php" class="view-all-link">View All Notifications</a>
    </div>
</div>

<!-- Notification Item Template -->
<template id="notification-item-template">
    <div class="notification-item">
        <div class="notification-icon">
            <i class="fas fa-bell"></i>
        </div>
        <div class="notification-content">
            <div class="notification-message"></div>
            <div class="notification-time"></div>
        </div>
    </div>
</template>

<style>
.notifications-container {
    position: relative;
    margin-right: 1rem;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.notification-bell:hover {
    background: rgba(0, 108, 59, 0.1);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.notification-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: -10px;
    width: 320px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    max-height: 480px;
    overflow-y: auto;
}

.notification-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.notification-header {
    padding: 16px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    background: white;
    z-index: 1;
}

.notification-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.mark-all-read {
    background: none;
    border: none;
    color: #006C3B;
    font-size: 14px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.mark-all-read:hover {
    background: rgba(0, 108, 59, 0.1);
}

.notification-state {
    padding: 24px;
    text-align: center;
    color: #666;
    display: none;
}

.loading-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid #eee;
    border-top-color: #006C3B;
    border-radius: 50%;
    margin: 0 auto 12px;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.notification-item {
    padding: 12px 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border-bottom: 1px solid #eee;
    transition: all 0.3s ease;
    cursor: pointer;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #e8f5e9;
}

.notification-item.unread:hover {
    background: #d7f0db;
}

.notification-item.new {
    animation: slideInDown 0.3s ease-out;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(0, 108, 59, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #006C3B;
}

.notification-content {
    flex: 1;
}

.notification-message {
    font-size: 14px;
    color: #333;
    margin-bottom: 4px;
    line-height: 1.4;
}

.notification-time {
    font-size: 12px;
    color: #888;
}

.view-all-link {
    display: block;
    padding: 12px;
    text-align: center;
    color: #006C3B;
    text-decoration: none;
    font-weight: 500;
    border-top: 1px solid #eee;
    transition: all 0.3s ease;
}

.view-all-link:hover {
    background: #f8f9fa;
}

/* Custom scrollbar */
.notification-dropdown::-webkit-scrollbar {
    width: 6px;
}

.notification-dropdown::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.notification-dropdown::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.notification-dropdown::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationList = document.querySelector('.notification-list');
    const loadingState = document.getElementById('notification-loading');
    const emptyState = document.getElementById('notification-empty');
    const errorState = document.getElementById('notification-error');
    const markAllReadBtn = document.querySelector('.mark-all-read');
    let lastNotificationId = 0;
    let pollingInterval;

    // Toggle dropdown
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('show');
        if (notificationDropdown.classList.contains('show')) {
            fetchNotifications();
            startPolling();
        } else {
            stopPolling();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
            notificationDropdown.classList.remove('show');
            stopPolling();
        }
    });

    // Mark all as read
    markAllReadBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        fetch('api/mark-all-notifications-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
                    item.classList.remove('unread');
                });
                updateBadgeCount(0);
            }
        })
        .catch(error => console.error('Error marking all as read:', error));
    });

    function fetchNotifications(isPolling = false) {
        if (!isPolling) showLoadingState();

        fetch(`api/notifications.php?last_id=${lastNotificationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.notifications && data.notifications.length > 0) {
                        if (isPolling) {
                            prependNewNotifications(data.notifications);
                        } else {
                            updateNotifications(data.notifications);
                        }
                        lastNotificationId = data.last_id;
                    } else if (!isPolling && notificationList.children.length === 0) {
                        showEmptyState();
                    }
                    updateBadgeCount(data.unread_count);
                }
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                if (!isPolling) showErrorState();
            });
    }

    function updateNotifications(notifications) {
        notificationList.innerHTML = '';
        hideAllStates();
        appendNotifications(notifications);
        notificationList.style.display = 'block';
    }

    function prependNewNotifications(notifications) {
        notifications.reverse().forEach(notification => {
            const element = createNotificationElement(notification);
            element.classList.add('new');
            notificationList.insertBefore(element, notificationList.firstChild);
            
            // Remove new class after animation
            setTimeout(() => {
                element.classList.remove('new');
            }, 300);
        });
    }

    function appendNotifications(notifications) {
        notifications.forEach(notification => {
            const element = createNotificationElement(notification);
            notificationList.appendChild(element);
        });
    }

    function createNotificationElement(notification) {
        const template = document.getElementById('notification-item-template');
        const clone = template.content.cloneNode(true);
        const item = clone.querySelector('.notification-item');

        if (!notification.is_read) {
            item.classList.add('unread');
        }

        item.dataset.id = notification.id;
        
        // Update icon based on notification type
        const icon = item.querySelector('.notification-icon i');
        icon.className = `fas ${notification.icon}`;
        
        item.querySelector('.notification-message').textContent = notification.message;
        item.querySelector('.notification-time').textContent = notification.time_ago;

        item.addEventListener('click', () => markAsRead(notification.id));

        return item;
    }

    function markAsRead(notificationId) {
        fetch('api/mark-notification-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                updateBadgeCount(data.unread_count);
            }
        })
        .catch(error => console.error('Error marking as read:', error));
    }

    function updateBadgeCount(count) {
        const badge = document.querySelector('.notification-badge');
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }

    function showLoadingState() {
        hideAllStates();
        loadingState.style.display = 'block';
    }

    function showEmptyState() {
        hideAllStates();
        emptyState.style.display = 'block';
    }

    function showErrorState() {
        hideAllStates();
        errorState.style.display = 'block';
    }

    function hideAllStates() {
        loadingState.style.display = 'none';
        emptyState.style.display = 'none';
        errorState.style.display = 'none';
        notificationList.style.display = 'none';
    }

    function startPolling() {
        // Poll every 5 seconds for new notifications
        pollingInterval = setInterval(() => {
            fetchNotifications(true);
        }, 5000);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    }

    // Initial fetch
    fetchNotifications();
    
    // Check for new notifications every 30 seconds even when dropdown is closed
    setInterval(() => {
        if (!notificationDropdown.classList.contains('show')) {
            fetchNotifications(true);
        }
    }, 30000);
});
</script>
<?php endif; ?> 