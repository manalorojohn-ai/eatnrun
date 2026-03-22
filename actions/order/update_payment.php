<?php
// Simple direct script to update payment status
// No advanced error handling or output buffering to keep it simple

// Set headers
header('Content-Type: application/json');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Include database connection
require_once 'config/db.php';
// Include notification helper
require_once 'add_notification.php';

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['order_id']) || !is_numeric($input['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$order_id = (int)$input['order_id'];

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    // First check the current order status
    $check_sql = "SELECT status, payment_status 
                  FROM orders 
                  WHERE id = ? AND user_id = ? 
                  LIMIT 1";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception("Order not found or doesn't belong to you");
    }
    
    $order = mysqli_fetch_assoc($result);
    
    // Validate order status
    if ($order['status'] === 'Completed') {
        throw new Exception("Order is already completed");
    } else if ($order['status'] === 'Cancelled') {
        throw new Exception("Cannot update a cancelled order");
    }
    
    // Update order status and payment status
    $update_sql = "UPDATE orders 
                   SET status = 'Completed',
                       payment_status = 'paid',
                       received_at = NOW(),
                       updated_at = NOW() 
                   WHERE id = ? 
                   AND user_id = ? 
                   AND status = 'Processing'
                   LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update order: " . mysqli_stmt_error($stmt));
    }
    
    if (mysqli_stmt_affected_rows($stmt) === 0) {
        throw new Exception("Could not update order. Please ensure the order is in Processing status.");
    }
    
    // Log the update - make it non-blocking
    $log_sql = "INSERT INTO order_logs (order_id, user_id, action, details) 
                VALUES (?, ?, 'status_update', 'Order marked as received and completed')";
    $log_stmt = mysqli_prepare($conn, $log_sql);
    mysqli_stmt_bind_param($log_stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($log_stmt);
    
    // Add notification - make it non-blocking
    $notification_message = "Your Order #{$order_id} has been marked as received and completed.";
    add_notification($user_id, $notification_message, 'order', "orders.php?highlight={$order_id}");
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order updated successfully',
        'data' => [
            'order_id' => $order_id,
            'status' => 'Completed',
            'payment_status' => 'paid'
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
    // Close database connection
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 