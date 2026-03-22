<!-- Add this in the header navigation -->
<div class="header-right">
    <div class="notification-container">
        <div class="notification-bell" id="notificationBell">
            <i class="fas fa-bell"></i>
            <span class="notification-count" id="notificationCount">0</span>
        </div>
        <div class="notification-panel" id="notificationPanel">
            <div class="notification-header">
                <h3>Notifications</h3>
                <button id="markAllRead">Clear All</button>
            </div>
            <div class="notification-list" id="notificationList"></div>
        </div>
    </div>
    <!-- Other header elements -->
</div>

<style>
.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notification-container {
    position: relative;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.3s ease;
    color: #006C3B;
}

.notification-bell:hover {
    background-color: rgba(0, 108, 59, 0.1);
}

.notification-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ef4444;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    min-width: 18px;
    text-align: center;
    display: none;
}

.notification-panel {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 320px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    display: none;
    z-index: 1000;
}

.notification-panel.show {
    display: block;
    animation: slideIn 0.3s ease-out;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.notification-header h3 {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    color: #1f2937;
}

#markAllRead {
    background: none;
    border: none;
    color: #006C3B;
    cursor: pointer;
    font-size: 14px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

#markAllRead:hover {
    background-color: rgba(0, 108, 59, 0.1);
}

.notification-list {
    max-height: 360px;
    overflow-y: auto;
    padding: 8px 0;
}

.notification-list:empty::after {
    content: 'No notifications';
    display: block;
    text-align: center;
    padding: 20px;
    color: #6b7280;
    font-size: 14px;
}

.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #e5e7eb;
    transition: all 0.2s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background-color: #f9fafb;
}

.notification-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.notification-title {
    font-weight: 600;
    font-size: 14px;
    color: #1f2937;
}

.notification-time {
    color: #6b7280;
    font-size: 12px;
}

.notification-message {
    color: #374151;
    font-size: 14px;
    margin: 0;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Toast Notification */
.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #006C3B;
    color: white;
    padding: 12px 24px;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    z-index: 1000;
}

.toast.show {
    transform: translateY(0);
    opacity: 1;
}
</style>

<script>
let ws = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

function connectWebSocket() {
    ws = new WebSocket('ws://localhost:8080');

    ws.onopen = () => {
        console.log('Connected to notification server');
        reconnectAttempts = 0;
    };

    ws.onmessage = (event) => {
        try {
            const data = JSON.parse(event.data);
            if (data.type === 'order_status') {
                showNotification(data);
            }
        } catch (error) {
            console.error('Error processing message:', error);
        }
    };

    ws.onclose = () => {
        console.log('Disconnected from notification server');
        if (reconnectAttempts < maxReconnectAttempts) {
            reconnectAttempts++;
            setTimeout(connectWebSocket, 3000);
        }
    };

    ws.onerror = (error) => {
        console.error('WebSocket error:', error);
    };
}

function showNotification(data) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'notification-item';
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-header">
                <span class="notification-title">Order Status Update</span>
                <span class="notification-time">${new Date(data.timestamp).toLocaleTimeString()}</span>
            </div>
            <p class="notification-message">Order #${data.orderId} is now ${data.status}</p>
        </div>
    `;

    // Add to notification list
    const notificationList = document.getElementById('notificationList');
    notificationList.insertBefore(notification, notificationList.firstChild);

    // Update count
    const count = document.getElementById('notificationCount');
    count.textContent = parseInt(count.textContent) + 1;
    count.style.display = 'block';

    // Show toast
    showToast(`Order #${data.orderId} is now ${data.status}`);
}

function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    });
}

// Toggle notification panel
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.getElementById('notificationBell');
    const notificationPanel = document.getElementById('notificationPanel');
    const markAllRead = document.getElementById('markAllRead');

    // Toggle notification panel
    notificationBell.addEventListener('click', (e) => {
        e.stopPropagation();
        notificationPanel.classList.toggle('show');
    });

    // Close panel when clicking outside
    document.addEventListener('click', (e) => {
        if (!notificationPanel.contains(e.target) && !notificationBell.contains(e.target)) {
            notificationPanel.classList.remove('show');
        }
    });

    // Clear all notifications
    markAllRead.addEventListener('click', () => {
        document.getElementById('notificationList').innerHTML = '';
        document.getElementById('notificationCount').textContent = '0';
        document.getElementById('notificationCount').style.display = 'none';
    });
});

// Initialize WebSocket connection
connectWebSocket();
</script> 