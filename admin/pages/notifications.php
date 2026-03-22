<?php
session_start();
require_once '../config/db.php';
require_once 'includes/notifications_handler.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];

// Check for new notifications
check_new_orders($conn, $admin_id);
check_order_status_changes($conn, $admin_id);

// Get all notifications
$notifications_query = "SELECT * FROM admin_notifications 
                       WHERE admin_id = ? 
                       ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $notifications_query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$notifications_result = mysqli_stmt_get_result($stmt);

// Get unread count
$unread_count = get_admin_unread_count($conn, $admin_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-rgb: 0, 108, 59;
            --primary-light: rgba(var(--primary-rgb), 0.1);
            --text-dark: #2c3e50;
            --text-muted: #6c757d;
            --border-color: rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 240px;
            padding: 2rem;
            min-height: 100vh;
            transition: var(--transition);
        }

        .notification-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        .page-title {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0;
        }

        .notification-item {
            background: white;
            border-radius: 0.75rem;
            padding: 1.25rem;
            margin: 1rem;
            border: 1px solid var(--border-color);
            transition: var(--transition);
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .notification-item.unread {
            background: rgba(var(--primary-rgb), 0.03);
            border-left: 4px solid var(--primary);
        }
        
        .notification-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-message {
            color: var(--text-dark);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        
        .notification-time {
            color: var(--text-muted);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .notification-time i {
            font-size: 0.8rem;
        }
        
        .btn-mark-all {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }
        
        .btn-mark-all:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            color: white;
        }

        .btn-mark-read {
            color: var(--primary);
            background: var(--primary-light);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .btn-mark-read:hover {
            background: rgba(var(--primary-rgb), 0.15);
        }
        
        .notification-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 2rem;
            margin-left: 0.75rem;
            transition: var(--transition);
        }

        .no-notifications {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }
        
        .no-notifications i {
            font-size: 3rem;
            color: #adb5bd;
            margin-bottom: 1.5rem;
        }

        .no-notifications p {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin: 0;
        }

        .notification-list {
            max-height: 600px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
        }

        .notification-list::-webkit-scrollbar {
            width: 6px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .notification-item {
                margin: 0.75rem;
                padding: 1rem;
            }
            
            .btn-mark-read {
                position: static;
                transform: none;
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
            }
        }

        /* Animation classes */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }

        .notification-item {
            opacity: 0;
            animation: fadeIn 0.3s ease-out forwards;
        }

        .notification-item:nth-child(1) { animation-delay: 0.1s; }
        .notification-item:nth-child(2) { animation-delay: 0.2s; }
        .notification-item:nth-child(3) { animation-delay: 0.3s; }
        .notification-item:nth-child(4) { animation-delay: 0.4s; }
        .notification-item:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="main-content">
        <div class="notification-container">
            <div class="card fade-in">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="page-title">Notifications</h1>
                            <p class="page-subtitle">
                                Manage your notifications and updates
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge">
                                        <i class="fas fa-bell me-1"></i>
                                        <?php echo $unread_count; ?> unread
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($unread_count > 0): ?>
                            <button class="btn-mark-all" onclick="markAllAsRead()">
                                <i class="fas fa-check-double"></i>
                                Mark all as read
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <?php if (mysqli_num_rows($notifications_result) > 0): ?>
                        <div class="notification-list">
                            <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
                                <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                                     data-id="<?php echo $notification['id']; ?>">
                                    <div class="notification-icon">
                                        <i class="fas <?php echo get_notification_icon($notification['type']); ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-message">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </div>
                                        <div class="notification-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo format_notification_time($notification['created_at']); ?>
                                        </div>
                                    </div>
                                    <?php if (!$notification['is_read']): ?>
                                        <button class="btn-mark-read" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                            Mark as read
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <p>You're all caught up! No new notifications.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markAsRead(notificationId) {
            fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_read',
                    notification_id: notificationId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text().then(text => {
                    try {
                        return text ? JSON.parse(text) : {};
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                        return {};
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    const notification = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                    if (notification) {
                        notification.classList.remove('unread');
                        const markReadBtn = notification.querySelector('.btn-mark-read');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                        updateUnreadCount();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function markAllAsRead() {
            fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_all_read'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text().then(text => {
                    try {
                        return text ? JSON.parse(text) : {};
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                        return {};
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        const markReadBtn = item.querySelector('.btn-mark-read');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                    });
                    const markAllBtn = document.querySelector('.btn-mark-all');
                    if (markAllBtn) {
                        markAllBtn.remove();
                    }
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        badge.remove();
                    }
                    updateUnreadCount();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function updateUnreadCount() {
            const countBadge = document.querySelector('.notification-badge');
            fetch('api/notifications.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text().then(text => {
                        try {
                            return text ? JSON.parse(text) : {};
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            return {};
                        }
                    });
                })
                .then(data => {
                    if (data.unread_count > 0) {
                        if (countBadge) {
                            countBadge.textContent = data.unread_count + ' unread';
                        }
                    } else {
                        if (countBadge) {
                            countBadge.remove();
                        }
                        const markAllBtn = document.querySelector('.btn-mark-all');
                        if (markAllBtn) {
                            markAllBtn.remove();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
    </script>
</body>
</html> 