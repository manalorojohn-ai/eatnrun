<?php
session_start();
require_once 'config/db.php';

// Set content type for AJAX responses
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit();
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['cancel_reason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$order_id = intval($data['order_id']);
$cancel_reason = mysqli_real_escape_string($conn, $data['cancel_reason']);
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // Check if order exists and belongs to user
    $check_order = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $check_order);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Order not found or does not belong to user');
    }

    $order = mysqli_fetch_assoc($result);
    if (!in_array(strtolower($order['status']), ['pending', 'processing'])) {
        throw new Exception('Only pending or processing orders can be cancelled');
    }

    // Update order status to cancelled
    $update_order = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_order);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update order status');
    }

    // Log the cancellation reason
    $log_sql = "INSERT INTO order_logs (order_id, user_id, action, details) 
                VALUES (?, ?, 'cancellation', ?)";
    $stmt = mysqli_prepare($conn, $log_sql);
    mysqli_stmt_bind_param($stmt, "iis", $order_id, $user_id, $cancel_reason);
    mysqli_stmt_execute($stmt);

    // Create notification for admin
    $notification_text = "Order #$order_id has been cancelled by the customer. Reason: $cancel_reason";
    require_once 'includes/notification_helper.php';
    notify_all_admins('order_cancelled', $notification_text, "admin/order_details.php?id=$order_id");

    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
} 