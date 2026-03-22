<?php
require_once __DIR__ . '/../../config/db.php';

// Create notifications table if it doesn't exist
function create_notifications_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'system',
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (!mysqli_query($conn, $sql)) {
        error_log("Error creating notifications table: " . mysqli_error($conn));
        return false;
    }
    return true;
}

// Create a notification for a new order
function create_order_notification($conn, $order_id, $message, $admin_id) {
    $sql = "INSERT INTO admin_notifications (admin_id, message, type, link) 
            VALUES (?, ?, 'order', ?)";
    $stmt = mysqli_prepare($conn, $sql);
    $link = "order_details.php?id=" . $order_id;
    mysqli_stmt_bind_param($stmt, "iss", $admin_id, $message, $link);
    return mysqli_stmt_execute($stmt);
}

// Check for order status changes
function check_order_status_changes($conn, $admin_id) {
    $sql = "SELECT o.id, o.status, o.updated_at, u.full_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.updated_at >= NOW() - INTERVAL 5 MINUTE 
            AND o.status IN ('processing', 'completed', 'cancelled')
            AND NOT EXISTS (
                SELECT 1 FROM admin_notifications an 
                WHERE an.link LIKE CONCAT('%?id=', o.id)
                AND an.created_at > o.updated_at
            )";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $message = "Order #{$row['id']} from {$row['full_name']} has been updated to {$row['status']}";
            create_order_notification($conn, $row['id'], $message, $admin_id);
        }
    }
}

// Check for new orders and create notifications
function check_new_orders($conn, $admin_id) {
    $sql = "SELECT o.id, o.created_at, u.full_name 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.created_at >= NOW() - INTERVAL 5 MINUTE 
            AND NOT EXISTS (
                SELECT 1 FROM admin_notifications an 
                WHERE an.link LIKE CONCAT('%?id=', o.id)
            )";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $message = "New order #{$row['id']} received from {$row['full_name']}";
            create_order_notification($conn, $row['id'], $message, $admin_id);
        }
    }
}

// Get unread notifications count
function get_admin_unread_count($conn, $admin_id) {
    $sql = "SELECT COUNT(*) as count 
            FROM admin_notifications 
            WHERE admin_id = ? AND is_read = 0";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    return $row['count'];
}

// Get recent notifications
function get_admin_recent_notifications($conn, $admin_id, $limit = 5) {
    $sql = "SELECT * FROM admin_notifications 
            WHERE admin_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $admin_id, $limit);
    mysqli_stmt_execute($stmt);
    
    return mysqli_stmt_get_result($stmt);
}

// Mark a notification as read
function mark_notification_read($conn, $notification_id, $admin_id) {
    $sql = "UPDATE admin_notifications 
            SET is_read = 1 
            WHERE id = ? AND admin_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $admin_id);
    
    return mysqli_stmt_execute($stmt);
}

// Mark all notifications as read
function mark_all_notifications_read($conn, $admin_id) {
    $sql = "UPDATE admin_notifications 
            SET is_read = 1 
            WHERE admin_id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    
    return mysqli_stmt_execute($stmt);
}

// Render notification bell
function render_notification_bell($unread_count = 0) {
    $badge = $unread_count > 0 ? "<span class='notification-badge'>$unread_count</span>" : "";
    return "
        <div class='notification-bell'>
            <i class='fas fa-bell'></i>
            $badge
        </div>
    ";
}

// Render notification dropdown
function render_notification_dropdown($notifications) {
    $html = "<div class='notification-dropdown'>";
    $html .= "<div class='notification-header'>";
    $html .= "<div class='notification-header-title'>";
    $html .= "<span>Notifications</span>";
    $html .= "<button class='mark-all-read'>Mark all as read</button>";
    $html .= "</div>";
    $html .= "</div>";
    
    $html .= "<div class='notification-list'>";
    
    if (mysqli_num_rows($notifications) > 0) {
        while ($notification = mysqli_fetch_assoc($notifications)) {
            $icon = get_notification_icon($notification['type']);
            $time = format_notification_time($notification['created_at']);
            $read_class = $notification['is_read'] ? 'read' : 'unread';
            
            $html .= "
                <div class='notification-item $read_class' data-id='{$notification['id']}'>
                    <div class='notification-icon'>
                        $icon
                    </div>
                    <div class='notification-content'>
                        <div class='notification-message'>{$notification['message']}</div>
                        <div class='notification-time'>$time</div>
                    </div>
                </div>
            ";
        }
    } else {
        $html .= "
            <div class='no-notifications'>
                <i class='fas fa-bell-slash'></i>
                <p>No notifications</p>
            </div>
        ";
    }
    
    $html .= "</div>";
    $html .= "</div>";
    
    return $html;
}

// Get notification icon based on type
function get_notification_icon($type) {
    $icons = [
        'order' => 'fa-shopping-bag',
        'status' => 'fa-info-circle',
        'system' => 'fa-cog',
        'user' => 'fa-user',
        'default' => 'fa-bell'
    ];
    
    $icon_class = isset($icons[$type]) ? $icons[$type] : $icons['default'];
    return "<i class='fas $icon_class'></i>";
}

// Format notification time
function format_notification_time($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " min" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } else {
        return date("M d, Y", $time);
    }
} 