<?php
header('Content-Type: application/json');
require_once './config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;
$cancellation_reason = isset($data['cancellation_reason']) ? trim($data['cancellation_reason']) : '';

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required.']);
    exit;
}

if (empty($cancellation_reason)) {
    echo json_encode(['success' => false, 'message' => 'Cancellation reason is required.']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get user_id from the order
    $sql = "SELECT user_id FROM orders WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Order not found.');
    }
    
    $order = $result->fetch_assoc();
    $user_id = $order['user_id'];
    
    // Verify that the user exists in the users table
    $sql = "SELECT id FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    
    // If user doesn't exist, we'll still allow the cancellation but log it
    if ($user_result->num_rows === 0) {
        // For orphaned orders, we'll still allow cancellation but skip the order_cancellations table
        // Update the order status to 'cancelled' without creating cancellation record
        $sql = "UPDATE orders SET status = 'cancelled', cancel_reason = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $cancellation_reason, $order_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update order status.');
        }
        
        // Insert into order_cancellations with NULL user_id for orphaned orders
        $sql = "INSERT INTO order_cancellations (order_id, user_id, reason, created_at) VALUES (?, NULL, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('is', $order_id, $cancellation_reason);
        
        // Execute the insert (should work now with NULL user_id)
        if (!$stmt->execute()) {
            error_log("Failed to insert cancellation record: " . $stmt->error);
        }
        
        // Commit transaction and return success
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully (orphaned user).']);
        exit;
    }
    
    // Update the order status to 'cancelled' and store cancellation reason
    $sql = "UPDATE orders SET status = 'cancelled', cancel_reason = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $cancellation_reason, $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update order status.');
    }
    
    // Insert cancellation reason into order_cancellations table
    $sql = "INSERT INTO order_cancellations (order_id, user_id, reason, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $order_id, $user_id, $cancellation_reason);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to save cancellation reason.');
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully.']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>