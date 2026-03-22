<?php
session_start();
require_once 'config/db.php';
require_once 'db_structure_check.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admin privileges required.");
}

// Set headers for proper error display
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Recreating Orders Tables</h1>";

// Backup existing data (optional)
$backup_orders = [];
$backup_details = [];

try {
    // Get existing orders
    $orders_result = mysqli_query($conn, "SELECT * FROM orders");
    if ($orders_result) {
        while ($row = mysqli_fetch_assoc($orders_result)) {
            $backup_orders[] = $row;
        }
    }

    // Get existing order details
    $details_result = mysqli_query($conn, "SELECT * FROM order_details");
    if ($details_result) {
        while ($row = mysqli_fetch_assoc($details_result)) {
            $backup_details[] = $row;
        }
    }

    // Recreate tables
    if (recreate_orders_tables($conn)) {
        echo "<p style='color: green;'>✓ Tables recreated successfully!</p>";
        
        // Restore data if needed
        if (!empty($backup_orders)) {
            echo "<p>Found " . count($backup_orders) . " existing orders to restore.</p>";
            
            // You can uncomment this section if you want to restore the old data
            /*
            foreach ($backup_orders as $order) {
                // Insert order logic here
            }
            foreach ($backup_details as $detail) {
                // Insert detail logic here
            }
            */
        }
        
    } else {
        echo "<p style='color: red;'>✗ Failed to recreate tables. Check error logs for details.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='admin/orders.php' style='display: inline-block; padding: 10px 20px; background-color: #006C3B; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px;'>Return to Orders</a></p>"; 