<?php
/**
 * Helper function to add a notification for a user
 * 
 * This file can be included in other PHP files to easily add notifications
 * 
 * Usage:
 * require_once 'add_notification.php';
 * add_notification($user_id, 'Your order has been processed', 'order', 'orders.php?id=123');
 */

// Include database connection if not already included
if (!isset($conn)) {
    require_once 'config/db.php';
}

/**
 * Add a notification for a user
 * 
 * @param int $user_id User ID to add notification for
 * @param string $message Notification message
 * @param string $type (Optional) Type of notification (order, payment, delivery, system)
 * @param string $link (Optional) Link to redirect to when notification is clicked
 * @return bool True if notification was added, false otherwise
 */
function add_notification($user_id, $message, $type = null, $link = null) {
    global $conn;
    
    // Sanitize inputs
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $message = mysqli_real_escape_string($conn, $message);
    $type = $type ? mysqli_real_escape_string($conn, $type) : null;
    $link = $link ? mysqli_real_escape_string($conn, $link) : null;
    
    // Prepare query
    $query = "INSERT INTO notifications (user_id, message, type, link, created_at) 
              VALUES ('$user_id', '$message', " . 
              ($type ? "'$type'" : "NULL") . ", " . 
              ($link ? "'$link'" : "NULL") . ", NOW())";
    
    // Execute query
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("Failed to add notification: " . mysqli_error($conn));
        return false;
    }
    
    return true;
}

/**
 * Add a notification for multiple users
 * 
 * @param array $user_ids Array of user IDs to add notification for
 * @param string $message Notification message
 * @param string $type (Optional) Type of notification (order, payment, delivery, system)
 * @param string $link (Optional) Link to redirect to when notification is clicked
 * @return bool True if all notifications were added, false otherwise
 */
function add_notification_to_many($user_ids, $message, $type = null, $link = null) {
    if (!is_array($user_ids) || empty($user_ids)) {
        return false;
    }
    
    $success = true;
    foreach ($user_ids as $user_id) {
        if (!add_notification($user_id, $message, $type, $link)) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * Add a notification to all users
 * 
 * @param string $message Notification message
 * @param string $type (Optional) Type of notification (order, payment, delivery, system)
 * @param string $link (Optional) Link to redirect to when notification is clicked
 * @return bool True if notifications were added, false otherwise
 */
function add_notification_to_all($message, $type = null, $link = null) {
    global $conn;
    
    // Get all user IDs
    $query = "SELECT id FROM users";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("Failed to get users: " . mysqli_error($conn));
        return false;
    }
    
    $user_ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $user_ids[] = $row['id'];
    }
    
    return add_notification_to_many($user_ids, $message, $type, $link);
}

// If this file is accessed directly, return an error
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    echo "This file cannot be accessed directly.";
    exit;
}
?> 