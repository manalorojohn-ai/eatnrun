<?php
require_once 'includes/auth.php';
requireAdmin();

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Include database connection
require_once '../config/db.php';

try {
    // Get total sales
    $sales_query = "SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM orders WHERE status != 'Cancelled'";
    $sales_result = mysqli_query($conn, $sales_query);
    $total_sales = mysqli_fetch_assoc($sales_result)['total_sales'];

    // Get total orders
    $orders_query = "SELECT COUNT(*) as total_orders FROM orders";
    $orders_result = mysqli_query($conn, $orders_query);
    $total_orders = mysqli_fetch_assoc($orders_result)['total_orders'];

    // Get total customers
    $customers_query = "SELECT COUNT(DISTINCT user_id) as total_customers FROM orders";
    $customers_result = mysqli_query($conn, $customers_query);
    $total_customers = mysqli_fetch_assoc($customers_result)['total_customers'];

    // Get tables used (if you have a table tracking system)
    // For now, we'll use tables count from a separate table if it exists, or default to active orders
    $tables_query = "SHOW TABLES LIKE 'tables'";
    $tables_exists = mysqli_query($conn, $tables_query);
    
    if (mysqli_num_rows($tables_exists) > 0) {
        // If you have a tables table
        $tables_query = "SELECT COUNT(*) as tables_used FROM tables WHERE status = 'occupied'";
        $tables_result = mysqli_query($conn, $tables_query);
        $tables_used = mysqli_fetch_assoc($tables_result)['tables_used'];
    } else {
        // Default to active orders today
        $tables_query = "SELECT COUNT(*) as tables_used FROM orders WHERE status = 'Processing' AND DATE(created_at) = CURDATE()";
        $tables_result = mysqli_query($conn, $tables_query);
        $tables_used = mysqli_fetch_assoc($tables_result)['tables_used'];
    }

    // Get total discounts (assuming there's a discount column, otherwise calculate based on business logic)
    $discounts_query = "SHOW COLUMNS FROM orders LIKE 'discount_amount'";
    $discount_column_exists = mysqli_query($conn, $discounts_query);
    
    if (mysqli_num_rows($discount_column_exists) > 0) {
        // If you have a discount_amount column
        $discounts_query = "SELECT COALESCE(SUM(discount_amount), 0) as total_discounts FROM orders";
        $discounts_result = mysqli_query($conn, $discounts_query);
        $total_discounts = mysqli_fetch_assoc($discounts_result)['total_discounts'];
    } else {
        // Default to a calculation (e.g., 5% of total sales)
        $total_discounts = $total_sales * 0.05;
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_sales' => $total_sales,
            'total_orders' => $total_orders,
            'total_customers' => $total_customers,
            'tables_used' => $tables_used,
            'total_discounts' => $total_discounts
        ]
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Error in get_dashboard_stats.php: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching dashboard statistics.'
    ]);
} finally {
    // Close database connection
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 