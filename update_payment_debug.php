<?php
// Debug version of update_payment.php with detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log that the script was called
error_log("update_payment_debug.php was called");

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log session data
error_log("SESSION: " . json_encode($_SESSION));

// Log request method and raw input
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
$raw_input = file_get_contents('php://input');
error_log("RAW INPUT: " . $raw_input);

// Set content type to JSON for response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("ERROR: User not logged in");
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Include database connection
require_once 'config/db.php';
// Include notification helper
require_once 'add_notification.php';

// Check if database connection is successful
if (!$conn) {
    error_log("ERROR: Database connection failed");
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];
error_log("User ID: " . $user_id);

// Parse JSON input
$data = json_decode($raw_input, true);
error_log("Parsed data: " . json_encode($data));

// Check if order_id is provided
if (!isset($data['order_id']) || !is_numeric($data['order_id'])) {
    error_log("ERROR: Invalid order ID");
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$order_id = (int) $data['order_id'];
error_log("Order ID: " . $order_id);

// Begin transaction
if (!mysqli_begin_transaction($conn)) {
    error_log("ERROR: Failed to start transaction");
    echo json_encode(['success' => false, 'message' => 'Failed to start transaction']);
    exit;
}

try {
    // Verify the order belongs to the user
    $check_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute statement failed: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception("Order not found or doesn't belong to you");
    }
    
    $order = mysqli_fetch_assoc($result);
    error_log("Order found: " . json_encode($order));
    
    // Check if already paid
    if (strtolower($order['payment_status']) === 'paid') {
        throw new Exception("Payment already marked as paid");
    }
    
    // Update the order
    error_log("Updating order...");
    $update_query = "UPDATE orders SET 
                    payment_status = 'Paid', 
                    status = 'Completed', 
                    received_at = NOW()
                    WHERE id = ? AND user_id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    
    if (!$stmt) {
        throw new Exception("Prepare update statement failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute update statement failed: " . mysqli_stmt_error($stmt));
    }
    
    $affected_rows = mysqli_affected_rows($conn);
    error_log("Affected rows: " . $affected_rows);
    
    // Add notification
    error_log("Adding notification...");
    $notification_message = "Your Order #{$order_id} has been marked as received and completed successfully.";
    if (add_notification($user_id, $notification_message, 'order', "orders.php")) {
        error_log("Notification added successfully");
    } else {
        error_log("Failed to add notification");
    }
    
    // Commit transaction
    if (!mysqli_commit($conn)) {
        throw new Exception("Failed to commit transaction");
    }
    
    error_log("Transaction committed successfully");
    
    // Return success response
    $response = [
        'success' => true,
        'message' => "Order #$order_id marked as received and completed successfully",
        'data' => [
            'order_id' => $order_id,
            'status' => 'Completed',
            'payment_status' => 'Paid'
        ]
    ];
    
    error_log("Response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the error
    error_log("EXCEPTION: " . $e->getMessage());
    
    // Rollback transaction
    mysqli_rollback($conn);
    error_log("Transaction rolled back");
    
    // Return error response
    $error_response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    error_log("Error response: " . json_encode($error_response));
    echo json_encode($error_response);
} finally {
    // Close database connection
    if (isset($conn)) {
        mysqli_close($conn);
        error_log("Database connection closed");
    }
}

error_log("Script completed");
?> 