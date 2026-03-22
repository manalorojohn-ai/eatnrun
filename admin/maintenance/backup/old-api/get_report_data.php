<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get date range from request
$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end'] ?? date('Y-m-d');

try {
    // Get total orders within date range
    $total_orders_query = "SELECT COUNT(*) as count FROM orders 
                          WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = mysqli_prepare($conn, $total_orders_query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $total_orders = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'] ?? 0;

    // Get total revenue within date range (excluding cancelled orders)
    $total_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders 
                           WHERE DATE(created_at) BETWEEN ? AND ? 
                           AND status != 'Cancelled'";
    $stmt = mysqli_prepare($conn, $total_revenue_query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $total_revenue = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['revenue'] ?? 0;

    // Get cancelled orders within date range
    $cancelled_orders_query = "SELECT COUNT(*) as count FROM orders 
                              WHERE DATE(created_at) BETWEEN ? AND ? 
                              AND status = 'Cancelled'";
    $stmt = mysqli_prepare($conn, $cancelled_orders_query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $cancelled_orders = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'] ?? 0;

    // Get daily revenue data for chart (excluding cancelled orders)
    $revenue_chart_query = "SELECT DATE(created_at) as date, 
                           COALESCE(SUM(total_amount), 0) as revenue 
                           FROM orders 
                           WHERE DATE(created_at) BETWEEN ? AND ?
                           AND status != 'Cancelled'
                           GROUP BY DATE(created_at) 
                           ORDER BY date";
    $stmt = mysqli_prepare($conn, $revenue_chart_query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $revenue_result = mysqli_stmt_get_result($stmt);
    
    $revenue_labels = [];
    $revenue_data = [];
    while ($row = mysqli_fetch_assoc($revenue_result)) {
        $revenue_labels[] = date('M j', strtotime($row['date']));
        $revenue_data[] = (float)$row['revenue'];
    }

    // Get order status distribution
    $status_query = "SELECT 
                        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
                    FROM orders 
                    WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = mysqli_prepare($conn, $status_query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $status_result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    
    $status_data = [
        (int)$status_result['completed'],
        (int)$status_result['processing'],
        (int)$status_result['pending'],
        (int)$status_result['cancelled']
    ];

    // Get popular items with proper menu item names
    $popular_items_query = "SELECT m.name, COUNT(od.menu_item_id) as order_count 
                           FROM order_details od 
                           JOIN menu_items m ON m.id = od.menu_item_id 
                           JOIN orders o ON od.order_id = o.id 
                           WHERE DATE(o.created_at) BETWEEN ? AND ?
                           AND o.status != 'Cancelled'
                           GROUP BY m.id, m.name 
                           ORDER BY order_count DESC 
                           LIMIT 10";
    $stmt = mysqli_prepare($conn, $popular_items_query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $popular_result = mysqli_stmt_get_result($stmt);
    
    $popular_labels = [];
    $popular_data = [];
    while ($row = mysqli_fetch_assoc($popular_result)) {
        $popular_labels[] = $row['name'];
        $popular_data[] = (int)$row['order_count'];
    }

    // Prepare response
    $response = [
        'success' => true,
        'stats' => [
            'total_orders' => (int)$total_orders,
            'total_revenue' => (float)$total_revenue,
            'cancelled_orders' => (int)$cancelled_orders
        ],
        'revenue_chart' => [
            'labels' => $revenue_labels,
            'data' => $revenue_data
        ],
        'order_status_chart' => $status_data,
        'popular_items' => [
            'labels' => $popular_labels,
            'data' => $popular_data
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    error_log('Reports Error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching report data',
        'error' => $e->getMessage()
    ]);
}
?> 