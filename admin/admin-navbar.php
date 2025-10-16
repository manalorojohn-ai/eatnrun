<?php
// Fix the role check to handle both role and user_role session variables
if (!isset($_SESSION['user_id']) || (!isset($_SESSION['role']) && !isset($_SESSION['user_role']))) {
    header("Location: ../login.php");
    exit();
}

// Check for either 'role' or 'user_role' session variable
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
            (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

if (!$is_admin) {
    header("Location: ../login.php");
    exit();
}

// Get unread notifications count
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $admin_id = $_SESSION['user_id'];
    $count_query = "SELECT COUNT(*) as unread FROM notifications 
                    WHERE is_read = 0 AND user_id = ?";
    
    $stmt = mysqli_prepare($conn, $count_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $unread_count = $row['unread'];
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="../assets/images/logo.png" alt="Eat&Run">
        <div class="sidebar-brand-text">Eat&Run Admin</div>
    </div>
    
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link">
                <i class="fas fa-home sidebar-icon"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="orders.php" class="sidebar-link">
                <i class="fas fa-shopping-bag sidebar-icon"></i>
                <span>Orders</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="menu_items.php" class="sidebar-link">
                <i class="fas fa-utensils sidebar-icon"></i>
                <span>Menu Items</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="users.php" class="sidebar-link">
                <i class="fas fa-users sidebar-icon"></i>
                <span>Users</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="reports.php" class="sidebar-link">
                <i class="fas fa-chart-bar sidebar-icon"></i>
                <span>Reports & Statistics</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="messages.php" class="sidebar-link">
                <i class="fas fa-envelope sidebar-icon"></i>
                <span>Messages</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="../logout.php" class="sidebar-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<div class="notification-container">
    <div class="notification-bell" id="notificationBell">
        <i class="fas fa-bell"></i>
        <!-- Update the notifications badge -->
        <span class="badge bg-danger rounded-pill notification-badge" <?php echo $unread_count > 0 ? '' : 'style="display: none;"'; ?>>
            <?php echo $unread_count; ?>
        </span>
    </div>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button class="mark-all-read" id="markAllRead">Mark all as read</button>
        </div>
        <div class="notification-list" id="notificationList">
            <!-- Notifications will be loaded here -->
        </div>
    </div>
</div>

<style>
:root {
    --primary: #006C3B;
    --primary-dark: #005530;
    --primary-light: #e8f5e9;
    --accent: #FFC107;
    --text-dark: #333;
    --text-light: #666;
    --white: #fff;
    --bg-light: #f8f9fa;
    --border-color: #e0e0e0;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 8px rgba(0,0,0,0.1);
    --radius-sm: 8px;
    --radius-md: 12px;
}

/* Sidebar */
.sidebar {
    width: 180px;
    min-height: 100vh;
    background-color: var(--primary);
    color: white;
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 100;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    gap: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 1rem;
}

.sidebar-brand img {
    width: 36px;
    height: 36px;
}

.sidebar-brand-text {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.2;
}

.sidebar-menu {
    padding: 0;
    flex: 1;
}

.sidebar-item {
    list-style: none;
    margin-bottom: 0.5rem;
}

.sidebar-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
}

.sidebar-link:hover, .sidebar-link.active {
    color: white;
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar-icon {
    margin-right: 0.75rem;
    font-size: 1.25rem;
    width: 24px;
    text-align: center;
}

.sidebar-footer {
    padding: 1rem 1.5rem;
    margin-top: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-logout {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.sidebar-logout:hover {
    color: rgba(255, 255, 255, 0.8);
}

.sidebar-logout i {
    font-size: 1.25rem;
}

/* Responsive styles */
@media (max-width: 992px) {
    .sidebar {
        width: 65px;
    }
    
    .sidebar-brand-text {
        display: none;
    }
    
    .sidebar-link span {
        display: none;
    }
    
    .sidebar-icon {
        margin-right: 0;
        font-size: 1.4rem;
    }
}

/* Add margin to main content */
body {
    margin: 0;
    padding: 0;
    display: flex;
}

.main-content {
    flex: 1;
    margin-left: 180px;
    padding: 2rem;
}

@media (max-width: 992px) {
    .main-content {
        margin-left: 65px;
    }
}

.notification-container {
    position: relative;
    margin-right: 20px;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    padding: 10px;
    border-radius: 50%;
    transition: background-color 0.3s;
}

.notification-bell:hover {
    background-color: rgba(0, 0, 0, 0.1);
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 4px 8px;
    font-size: 12px;
    min-width: 20px;
    text-align: center;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 300px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: none;
    z-index: 1000;
}

.notification-dropdown.show {
    display: block;
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h3 {
    margin: 0;
    font-size: 16px;
}

.mark-all-read {
    background: none;
    border: none;
    color: #006C3B;
    cursor: pointer;
    font-size: 14px;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background-color 0.3s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e8f5e9;
}

.notification-message {
    margin-bottom: 5px;
    font-size: 14px;
}

.notification-time {
    color: #666;
    font-size: 12px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const notificationList = document.getElementById('notificationList');
    const markAllReadBtn = document.getElementById('markAllRead');
    
    let isLoading = false;
    
    // Toggle dropdown
    notificationBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationDropdown.classList.toggle('show');
        if (notificationDropdown.classList.contains('show')) {
            loadNotifications();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
            notificationDropdown.classList.remove('show');
        }
    });
    
    // Mark all as read
    markAllReadBtn.addEventListener('click', function() {
        fetch('mark_all_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                });
                const badge = document.querySelector('.notification-badge');
                if (badge) badge.style.display = 'none';
                updateUnreadCount();
            }
        });
    });
    
    function loadNotifications() {
        if (isLoading) return;
        isLoading = true;
        
        fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            notificationList.innerHTML = '';
            
            if (!data.notifications || data.notifications.length === 0) {
                notificationList.innerHTML = '<div class="notification-item">No notifications</div>';
                return;
            }
            
            data.notifications.forEach(notification => {
                const item = document.createElement('div');
                item.className = `notification-item ${notification.is_read ? '' : 'unread'}`;
                item.innerHTML = `
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${formatTime(notification.created_at)}</div>
                `;
                
                item.addEventListener('click', () => {
                    if (!notification.is_read) {
                        markAsRead(notification.id, item);
                    }
                    if (notification.link) {
                        window.location.href = notification.link;
                    }
                });
                
                notificationList.appendChild(item);
            });
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            notificationList.innerHTML = '<div class="notification-item">Error loading notifications</div>';
        })
        .finally(() => {
            isLoading = false;
        });
    }
    
    function markAsRead(notificationId, element) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.classList.remove('unread');
                updateUnreadCount();
            }
        });
    }
    
    function updateUnreadCount() {
        fetch('get_notifications.php?count=1')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (data.unread_count > 0) {
                if (badge) {
                    badge.textContent = data.unread_count;
                    badge.style.display = '';
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'badge bg-danger rounded-pill notification-badge';
                    newBadge.textContent = data.unread_count;
                    notificationBell.appendChild(newBadge);
                }
            } else if (badge) {
                badge.style.display = 'none';
            }
        });
    }
    
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
        return date.toLocaleDateString();
    }
    
    // Check for new notifications every 30 seconds
    setInterval(updateUnreadCount, 30000);
});
</script> 