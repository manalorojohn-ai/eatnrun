<?php
require_once 'config/db.php';

echo "<h1>Fixing Order Subtotals</h1>";

// First, ensure the subtotal column exists in the orders table
$check_query = "SHOW COLUMNS FROM orders LIKE 'subtotal'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) === 0) {
    echo "<p>Adding 'subtotal' column to orders table...</p>";
    $alter_query = "ALTER TABLE orders ADD COLUMN subtotal DECIMAL(10,2) AFTER delivery_fee";
    if (mysqli_query($conn, $alter_query)) {
        echo "<p style='color: green'>Added subtotal column successfully!</p>";
    } else {
        echo "<p style='color: red'>Failed to add subtotal column: " . mysqli_error($conn) . "</p>";
    }
}

// Find all orders with zero or null subtotal
$query = "SELECT o.id, o.total_amount, o.delivery_fee, o.subtotal 
          FROM orders o 
          WHERE o.subtotal IS NULL OR o.subtotal = 0";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$total_orders = mysqli_num_rows($result);
echo "<p>Found $total_orders orders with missing or zero subtotals.</p>";

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
        $delivery_fee = $order['delivery_fee'] ?? 50;
        $subtotal = $order['total_amount'] - $delivery_fee;
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

if ($updated_count == 0 && $error_count == 0 && $total_orders == 0) {
    echo "<p>No orders needed updating. All subtotals appear to be set correctly.</p>";
}

echo "<p><a href='orders.php'>Back to Orders</a></p>";
?> 