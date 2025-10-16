<?php
session_start();
require_once 'config/db.php';

// Send JSON response
header('Content-Type: application/json');

// Function to return JSON response
function json_response($success, $message, $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    json_response(false, 'You must be logged in to perform this action.');
}

$user_id = $_SESSION['user_id'];

// Check if order_id is provided
if (!isset($_POST['order_id'])) {
    json_response(false, 'Order ID is required.');
}

$order_id = $_POST['order_id'];

// Validate order belongs to the user
$check_order_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $check_order_query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    json_response(false, 'Order not found or does not belong to you.');
}

$order = mysqli_fetch_assoc($result);

// Check if order is already completed
if ($order['status'] === 'Completed') {
    json_response(false, 'This order is already marked as completed.');
}

// Only allow changing status if order is Processing or Pending
if ($order['status'] !== 'Processing' && $order['status'] !== 'Pending') {
    json_response(false, 'Cannot mark this order as received in its current status.');
}

// Update order status to Completed and payment status to Paid
$update_query = "UPDATE orders SET status = 'Completed', payment_status = 'Paid', updated_at = NOW() WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
$update_result = mysqli_stmt_execute($stmt);

if ($update_result) {
    // Log the status change
    $log_query = "INSERT INTO order_logs (order_id, user_id, action, details, created_at) 
                 VALUES (?, ?, 'status_update', 'Order marked as received by customer. Status changed to Completed and payment marked as Paid.', NOW())";
    $stmt = mysqli_prepare($conn, $log_query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
        mysqli_stmt_execute($stmt);
    }
    
    json_response(true, 'Order has been marked as received and payment completed!');
} else {
    json_response(false, 'Failed to update order status. Please try again.');
}
?> 