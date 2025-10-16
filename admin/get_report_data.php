<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get total orders
    $total_orders_query = "SELECT COUNT(*) as count FROM orders";
    $total_orders_result = mysqli_query($conn, $total_orders_query);
    $total_orders = mysqli_fetch_assoc($total_orders_result)['count'] ?? 0;

    // Get total revenue
    $total_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status != 'Cancelled'";
    $total_revenue_result = mysqli_query($conn, $total_revenue_query);
    $total_revenue = mysqli_fetch_assoc($total_revenue_result)['revenue'] ?? 0;

    // Get total cancelled orders
    $cancelled_orders_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'Cancelled'";
    $cancelled_orders_result = mysqli_query($conn, $cancelled_orders_query);
    $cancelled_orders = mysqli_fetch_assoc($cancelled_orders_result)['count'] ?? 0;

    // Get weekly revenue data
    $weekly_revenue_query = "SELECT 
        DATE(created_at) as date,
        COALESCE(SUM(total_amount), 0) as revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND status != 'Cancelled'
        GROUP BY DATE(created_at)
        ORDER BY date ASC";
    $weekly_revenue_result = mysqli_query($conn, $weekly_revenue_query);
    $weekly_revenue_data = [];
    $weekly_revenue_labels = [];
    while ($row = mysqli_fetch_assoc($weekly_revenue_result)) {
        $weekly_revenue_data[] = $row['revenue'];
        $weekly_revenue_labels[] = date('D', strtotime($row['date']));
    }

    // Get monthly revenue data
    $monthly_revenue_query = "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COALESCE(SUM(total_amount), 0) as revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        AND status != 'Cancelled'
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC";
    $monthly_revenue_result = mysqli_query($conn, $monthly_revenue_query);
    $monthly_revenue_data = [];
    $monthly_revenue_labels = [];
    while ($row = mysqli_fetch_assoc($monthly_revenue_result)) {
        $monthly_revenue_data[] = $row['revenue'];
        $monthly_revenue_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    }

    // Get order status distribution
    $order_status_query = "SELECT 
        status,
        COUNT(*) as count
        FROM orders
        GROUP BY status";
    $order_status_result = mysqli_query($conn, $order_status_query);
    $order_status_data = [];
    $order_status_labels = [];
    while ($row = mysqli_fetch_assoc($order_status_result)) {
        $order_status_data[] = $row['count'];
        $order_status_labels[] = $row['status'];
    }

    // Get top selling items
    $popular_items_query = "SELECT 
        m.name,
        COUNT(od.menu_item_id) as order_count,
        COALESCE(SUM(od.quantity), 0) as total_quantity
        FROM menu_items m
        LEFT JOIN order_details od ON m.id = od.menu_item_id
        LEFT JOIN orders o ON od.order_id = o.id
        WHERE o.status != 'Cancelled'
        GROUP BY m.id
        ORDER BY total_quantity DESC
        LIMIT 10";
    $popular_items_result = mysqli_query($conn, $popular_items_query);
    $popular_items_data = [];
    $popular_items_labels = [];
    while ($row = mysqli_fetch_assoc($popular_items_result)) {
        $popular_items_data[] = $row['total_quantity'];
        $popular_items_labels[] = $row['name'];
    }

    // Prepare response data
    $response = [
        'totalOrders' => (int)$total_orders,
        'totalRevenue' => (float)$total_revenue,
        'cancelledOrders' => (int)$cancelled_orders,
        'chartData' => [
            'weekly' => [
                'labels' => $weekly_revenue_labels,
                'data' => array_map('floatval', $weekly_revenue_data)
            ],
            'monthly' => [
                'labels' => $monthly_revenue_labels,
                'data' => array_map('floatval', $monthly_revenue_data)
            ],
            'orderStatus' => [
                'labels' => $order_status_labels,
                'data' => array_map('intval', $order_status_data)
            ],
            'popularItems' => [
                'labels' => $popular_items_labels,
                'data' => array_map('intval', $popular_items_data)
            ]
        ]
    ];

    // Send response
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
} 