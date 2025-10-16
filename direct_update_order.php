<?php
// Simple script to update order status without AJAX
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("<p>Error: You must be logged in to update orders</p><a href='login.php'>Login</a>");
}

// Include database connection
require_once 'config/db.php';

// Get user ID and check for order ID
$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    die("<p>Error: Invalid order ID</p><a href='orders.php'>Back to orders</a>");
}

try {
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    // Check if order exists and belongs to the user
    $check_query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    
    if (!$stmt) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception("Order not found or doesn't belong to you");
    }
    
    $order = mysqli_fetch_assoc($result);
    
    // Check if already paid
    if (strtolower($order['payment_status']) === 'paid') {
        throw new Exception("This order has already been marked as paid");
    }
    
    // Update the order
    $update_query = "UPDATE orders SET 
                    payment_status = 'Paid', 
                    status = 'Completed', 
                    received_at = NOW()
                    WHERE id = ? AND user_id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to update order: " . mysqli_stmt_error($stmt));
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Success - redirect back to orders page
    header("Location: orders.php?success=1&order_id=" . $order_id);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    
    // Show error message
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<a href='orders.php'>Back to orders</a>";
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?> 