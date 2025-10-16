<?php
function add_admin_notification($admin_id, $type, $message, $link = null) {
    global $conn;
    
    $query = "INSERT INTO admin_notifications (admin_id, type, message, link) 
              VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isss", $admin_id, $type, $message, $link);
    
    return mysqli_stmt_execute($stmt);
}

function get_admin_ids() {
    global $conn;
    
    $query = "SELECT id FROM users WHERE role = 'admin'";
    $result = mysqli_query($conn, $query);
    
    $admin_ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $admin_ids[] = $row['id'];
    }
    
    return $admin_ids;
}

function notify_all_admins($type, $message, $link = null) {
    $admin_ids = get_admin_ids();
    
    foreach ($admin_ids as $admin_id) {
        add_admin_notification($admin_id, $type, $message, $link);
    }
} 