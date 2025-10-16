<?php
// Prevent any output before JSON response
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/db.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    if (!isset($conn)) {
        throw new Exception('Database connection not established');
    }

    // Get total sales (excluding cancelled orders)
    $sales_query = "SELECT COALESCE(SUM(total_amount), 0) as total_sales 
                    FROM orders 
                    WHERE status != 'Cancelled'";
    $sales_result = mysqli_query($conn, $sales_query);
    if (!$sales_result) {
        throw new Exception(mysqli_error($conn));
    }
    $total_sales = mysqli_fetch_assoc($sales_result)['total_sales'];

    // Get total orders
    $orders_query = "SELECT COUNT(*) as total_orders FROM orders";
    $orders_result = mysqli_query($conn, $orders_query);
    if (!$orders_result) {
        throw new Exception(mysqli_error($conn));
    }
    $total_orders = mysqli_fetch_assoc($orders_result)['total_orders'];

    // Get total customers (unique users who have placed orders)
    $customers_query = "SELECT COUNT(DISTINCT user_id) as total_customers 
                       FROM orders";
    $customers_result = mysqli_query($conn, $customers_query);
    if (!$customers_result) {
        throw new Exception(mysqli_error($conn));
    }
    $total_customers = mysqli_fetch_assoc($customers_result)['total_customers'];

    // Get tables used (active orders)
    $tables_query = "SELECT COUNT(*) as tables_used 
                    FROM orders 
                    WHERE status = 'Processing' 
                    AND DATE(created_at) = CURDATE()";
    $tables_result = mysqli_query($conn, $tables_query);
    if (!$tables_result) {
        throw new Exception(mysqli_error($conn));
    }
    $tables_used = mysqli_fetch_assoc($tables_result)['tables_used'];

    // Calculate total discounts (assuming standard discount rate)
    $discounts_query = "SELECT COALESCE(SUM(total_amount * 0.10), 0) as total_discounts 
                       FROM orders 
                       WHERE status != 'Cancelled'";
    $discounts_result = mysqli_query($conn, $discounts_query);
    if (!$discounts_result) {
        throw new Exception(mysqli_error($conn));
    }
    $total_discounts = mysqli_fetch_assoc($discounts_result)['total_discounts'];

    // Return the results
    echo json_encode([
        'success' => true,
        'data' => [
            'total_sales' => number_format($total_sales, 2),
            'total_orders' => $total_orders,
            'total_customers' => $total_customers,
            'tables_used' => $tables_used,
            'total_discounts' => number_format($total_discounts, 2)
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_realtime_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching statistics.'
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 