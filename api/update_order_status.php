<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection and notification helper
require_once '../config/db.php';
require_once '../includes/notification_helper.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['order_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$order_id = (int)$input['order_id'];
$new_status = strtolower($input['status']);

// Validate status
$valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // Get current order details
    $check_sql = "SELECT o.*, u.id as user_id FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE o.id = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        throw new Exception("Order not found");
    }

    $order = mysqli_fetch_assoc($result);
    
    // Validate status transition
    $current_status = strtolower($order['status']);
    if ($current_status === 'completed' || $current_status === 'cancelled') {
        throw new Exception("Cannot update status of a {$current_status} order");
    }

    // Update order status
    $update_sql = "UPDATE orders SET 
                   status = ?,
                   payment_status = CASE WHEN ? = 'Cancelled' THEN 'Cancelled' ELSE payment_status END,
                   updated_at = NOW() 
                   WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ssi", $new_status, $new_status, $order_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception("Failed to update order status");
    }

    // Add notification for the customer
    $notification_message = "Your Order #{$order_id} has been updated to " . ucfirst($new_status);
    // add_notification($order['user_id'], $notification_message, 'order', "orders.php?highlight={$order_id}"); // This line is removed as per the new_code

    // Get order details for notification
    $order_query = "SELECT o.*, u.full_name FROM orders o 
                   LEFT JOIN users u ON o.user_id = u.id 
                   WHERE o.id = ?";
    $order_stmt = mysqli_prepare($conn, $order_query);
    mysqli_stmt_bind_param($order_stmt, "i", $order_id);
    mysqli_stmt_execute($order_stmt);
    $order_result = mysqli_stmt_get_result($order_stmt);
    $order_details = mysqli_fetch_assoc($order_result);

    // Create notification for all admins
    $customer_name = $order_details['full_name'] ?? 'Customer';
    $notification_message = "Order #$order_id from $customer_name has been updated to $new_status";
    $notification_link = "order_details.php?id=$order_id";
    
    notify_all_admins(
        'order_status',
        $notification_message,
        $notification_link
    );

    // Log the status change
    $log_sql = "INSERT INTO order_logs (order_id, action, details, created_by) 
                VALUES (?, 'status_change', ?, ?)";
    $log_details = "Status changed from {$current_status} to {$new_status}";
    $stmt = mysqli_prepare($conn, $log_sql);
    mysqli_stmt_bind_param($stmt, "isi", $order_id, $log_details, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);

    // Commit transaction
    mysqli_commit($conn);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Order status updated successfully",
        'data' => [
            'order_id' => $order_id,
            'status' => $new_status,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 