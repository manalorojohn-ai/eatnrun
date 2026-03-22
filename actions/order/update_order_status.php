<?php
header('Content-Type: application/json');
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['order_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$order_id = intval($data['order_id']);
$action = $data['action'];
$user_id = $_SESSION['user_id'];
$cancel_reason = isset($data['reason']) ? mysqli_real_escape_string($conn, $data['reason']) : null;

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // Verify order belongs to user
    $verify_query = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Order not found or unauthorized');
    }

    $order = mysqli_fetch_assoc($result);
    
    // Check if order can be updated
    if (!in_array($order['status'], ['pending', 'processing'])) {
        throw new Exception('Order cannot be updated in its current status');
    }

    // Update order status based on action
    $new_status = '';
    $payment_status = '';
    $received_at = null;

    switch ($action) {
        case 'receive':
            $new_status = 'completed';
            $payment_status = 'paid';
            $received_at = 'NOW()';
            break;
        case 'cancel':
            $new_status = 'cancelled';
            break;
        default:
            throw new Exception('Invalid action');
    }

    // Update order with cancellation reason if provided
    $update_query = "UPDATE orders SET 
                    status = ?, 
                    payment_status = COALESCE(?, payment_status),
                    received_at = " . ($received_at ? $received_at : "received_at") . ",
                    " . ($cancel_reason ? "cancel_reason = '" . $cancel_reason . "'," : "") . "
                    updated_at = NOW()
                    WHERE id = ? AND user_id = ?";
                    
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ssii", $new_status, $payment_status, $order_id, $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update order status');
    }

    // Create notification with reason if cancelling
    $notification_message = $action === 'receive' 
        ? "Order #$order_id has been marked as received" 
        : "Order #$order_id has been cancelled" . ($cancel_reason ? " - Reason: $cancel_reason" : "");

    $notify_query = "INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'order', NOW())";
    $stmt = mysqli_prepare($conn, $notify_query);
    mysqli_stmt_bind_param($stmt, "is", $user_id, $notification_message);
    mysqli_stmt_execute($stmt);

    // Also notify admin about cancellation
    if ($action === 'cancel') {
        $admin_notification = "Order #$order_id has been cancelled by customer" . ($cancel_reason ? " - Reason: $cancel_reason" : "");
        $admin_id = 1; // Assuming admin user_id is 1
        $stmt = mysqli_prepare($conn, $notify_query);
        mysqli_stmt_bind_param($stmt, "is", $admin_id, $admin_notification);
        mysqli_stmt_execute($stmt);
    }

    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order updated successfully',
        'new_status' => $new_status
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?> 