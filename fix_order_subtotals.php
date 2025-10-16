<?php
session_start();
require_once 'config/db.php';

// Validate admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<h1>Access Denied</h1>";
    echo "<p>You must be an administrator to run this script.</p>";
    echo "<p><a href='login.php'>Back to Login</a></p>";
    exit();
}

echo "<h1>Fixing Order Subtotals</h1>";

// Find all orders with zero or null subtotal
$query = "SELECT o.id, o.total_amount, o.delivery_fee, o.subtotal 
          FROM orders o 
          WHERE o.subtotal IS NULL OR o.subtotal = 0";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$updated_count = 0;
$error_count = 0;

while ($order = mysqli_fetch_assoc($result)) {
    $order_id = $order['id'];
    
    // First try to calculate subtotal from order details
    $details_query = "SELECT SUM(quantity * price) as calculated_subtotal
                     FROM order_details 
                     WHERE order_id = ?";
                     
    $stmt = mysqli_prepare($conn, $details_query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $details_result = mysqli_stmt_get_result($stmt);
    $details = mysqli_fetch_assoc($details_result);
    
    if ($details && $details['calculated_subtotal'] > 0) {
        // Use subtotal from order details
        $subtotal = $details['calculated_subtotal'];
    } else {
        // Fall back to total_amount - delivery_fee
        $subtotal = $order['total_amount'] - ($order['delivery_fee'] ?? 50);
    }
    
    // Make sure subtotal is not negative
    $subtotal = max(0, $subtotal);
    
    // Update order with new subtotal
    $update_query = "UPDATE orders SET subtotal = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "di", $subtotal, $order_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        echo "<p>Updated order #$order_id: Set subtotal to ₱" . number_format($subtotal, 2) . "</p>";
        $updated_count++;
    } else {
        echo "<p style='color: red'>Failed to update order #$order_id: " . mysqli_error($conn) . "</p>";
        $error_count++;
    }
}

echo "<h2>Summary</h2>";
echo "<p>Total orders updated: $updated_count</p>";
echo "<p>Errors encountered: $error_count</p>";

if ($updated_count == 0 && $error_count == 0) {
    echo "<p>No orders needed updating. All subtotals appear to be set correctly.</p>";
}

echo "<p><a href='admin/index.php'>Back to Admin Dashboard</a></p>";
?> 