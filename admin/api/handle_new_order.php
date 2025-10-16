<?php
session_start();
require_once '../../config/db.php';
require_once '../includes/notifications_handler.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['user_id'];

try {
    // Get all admins
    $admin_query = "SELECT id FROM users WHERE role = 'admin'";
    $admin_result = mysqli_query($conn, $admin_query);
    
    // Get new orders from the last 5 minutes
    $orders_query = "SELECT o.*, u.full_name 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.created_at >= NOW() - INTERVAL 5 MINUTE 
                    AND NOT EXISTS (
                        SELECT 1 FROM admin_notifications 
                        WHERE order_id = o.id 
                        AND type = 'order'
                    )";
    
    $orders_result = mysqli_query($conn, $orders_query);
    
    $notifications_created = 0;
    
    // Create notifications for each new order for all admins
    while ($order = mysqli_fetch_assoc($orders_result)) {
        mysqli_data_seek($admin_result, 0);
        while ($admin = mysqli_fetch_assoc($admin_result)) {
            $message = "New order #" . $order['id'] . " received from " . $order['full_name'];
            if (create_order_notification($conn, $order['id'], $message, $admin['id'])) {
                $notifications_created++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'notifications_created' => $notifications_created
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 