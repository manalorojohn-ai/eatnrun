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
$response = ['success' => false];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? 0;
$status = $data['status'] ?? '';
$reason = $data['reason'] ?? null;

if ($order_id && $status) {
    try {
        mysqli_begin_transaction($conn);
        
        // Update order status
        $update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // If there's a cancellation reason, update it
            if ($reason && $status === 'cancelled') {
                $reason_query = "UPDATE orders SET cancel_reason = ? WHERE id = ?";
                $reason_stmt = mysqli_prepare($conn, $reason_query);
                mysqli_stmt_bind_param($reason_stmt, "si", $reason, $order_id);
                mysqli_stmt_execute($reason_stmt);
            }
            
            // Get order details for notification
            $order_query = "SELECT o.*, u.full_name 
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          WHERE o.id = ?";
            $order_stmt = mysqli_prepare($conn, $order_query);
            mysqli_stmt_bind_param($order_stmt, "i", $order_id);
            mysqli_stmt_execute($order_stmt);
            $order_result = mysqli_stmt_get_result($order_stmt);
            $order = mysqli_fetch_assoc($order_result);
            
            // Create notification
            $message = "Order #" . $order_id . " status updated to " . ucfirst($status);
            if ($status === 'cancelled' && $reason) {
                $message .= " (Reason: " . $reason . ")";
            }
            create_order_notification($conn, $order_id, $message, $admin_id);
            
            mysqli_commit($conn);
            $response['success'] = true;
            $response['message'] = 'Order status updated successfully';
        } else {
            throw new Exception("Failed to update order status");
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request data';
}

header('Content-Type: application/json');
echo json_encode($response); 