<?php
session_start();
require_once '../../config/db.php';
require_once '../includes/notifications_handler.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    exit('Unauthorized');
}

$admin_id = $_SESSION['user_id'];

// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

// Function to send SSE data
function sendSSE($data) {
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Send initial connection confirmation
sendSSE(['type' => 'connected', 'message' => 'Connected to notification stream']);

// Keep track of last notification ID to detect new ones
$last_notification_id = 0;

// Get the latest notification ID
$last_id_query = "SELECT MAX(id) as last_id FROM admin_notifications WHERE admin_id = ?";
$stmt = mysqli_prepare($conn, $last_id_query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
$last_notification_id = $row['last_id'] ?? 0;

// Send initial notification count
$count_query = "SELECT COUNT(*) as unread_count FROM admin_notifications WHERE admin_id = ? AND is_read = 0";
$stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$unread_count = mysqli_fetch_assoc($result)['unread_count'];

sendSSE([
    'type' => 'unread_count',
    'count' => $unread_count
]);

// Main polling loop
while (true) {
    // Check for new notifications
    $new_notifications_query = "SELECT * FROM admin_notifications 
                               WHERE admin_id = ? AND id > ? 
                               ORDER BY created_at DESC 
                               LIMIT 10";
    
    $stmt = mysqli_prepare($conn, $new_notifications_query);
    mysqli_stmt_bind_param($stmt, "ii", $admin_id, $last_notification_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $new_notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $new_notifications[] = [
            'id' => $row['id'],
            'type' => $row['type'],
            'message' => $row['message'],
            'link' => $row['link'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'formatted_time' => format_notification_time($row['created_at'])
        ];
        
        // Update last notification ID
        if ($row['id'] > $last_notification_id) {
            $last_notification_id = $row['id'];
        }
    }
    
    // If there are new notifications, send them
    if (!empty($new_notifications)) {
        sendSSE([
            'type' => 'new_notifications',
            'notifications' => $new_notifications
        ]);
        
        // Update unread count
        $count_query = "SELECT COUNT(*) as unread_count FROM admin_notifications WHERE admin_id = ? AND is_read = 0";
        $stmt = mysqli_prepare($conn, $count_query);
        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $unread_count = mysqli_fetch_assoc($result)['unread_count'];
        
        sendSSE([
            'type' => 'unread_count',
            'count' => $unread_count
        ]);
    }
    
    // Check for order status changes and new orders
    check_new_orders($conn, $admin_id);
    check_order_status_changes($conn, $admin_id);
    
    // Send heartbeat every 30 seconds
    static $last_heartbeat = 0;
    if (time() - $last_heartbeat >= 30) {
        sendSSE(['type' => 'heartbeat', 'timestamp' => time()]);
        $last_heartbeat = time();
    }
    
    // Sleep for 2 seconds before next check
    sleep(2);
    
    // Check if client disconnected
    if (connection_aborted()) {
        break;
    }
}
?>
