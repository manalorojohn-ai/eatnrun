<?php
require_once '../includes/session.php';
require_once '../config/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    // Get date range from request
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

    // Revenue Trend Data
    $revenue_query = "SELECT DATE(created_at) as date, 
                            SUM(total_amount) as revenue
                     FROM orders 
                     WHERE created_at BETWEEN ? AND ? 
                     AND status != 'Cancelled'
                     GROUP BY DATE(created_at)
                     ORDER BY date";
    
    $stmt = $conn->prepare($revenue_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $revenue_result = $stmt->get_result();
    
    $revenue_trend = [
        'dates' => [],
        'values' => []
    ];
    
    while ($row = $revenue_result->fetch_assoc()) {
        $revenue_trend['dates'][] = $row['date'];
        $revenue_trend['values'][] = floatval($row['revenue']);
    }

    // Labor Costs and Customer Count
    $labor_customer_query = "SELECT DATE(created_at) as date,
                                   COUNT(DISTINCT user_id) as customer_count,
                                   SUM(total_amount) * 0.2 as labor_cost
                            FROM orders
                            WHERE created_at BETWEEN ? AND ?
                            GROUP BY DATE(created_at)
                            ORDER BY date";
    
    $stmt = $conn->prepare($labor_customer_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $labor_result = $stmt->get_result();
    
    $labor_customer = [
        'dates' => [],
        'labor' => [],
        'customers' => []
    ];
    
    while ($row = $labor_result->fetch_assoc()) {
        $labor_customer['dates'][] = $row['date'];
        $labor_customer['labor'][] = floatval($row['labor_cost']);
        $labor_customer['customers'][] = intval($row['customer_count']);
    }

    // Order Status Distribution
    $status_query = "SELECT 
                        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) as processing,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
                     FROM orders
                     WHERE created_at BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($status_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $status_result = $stmt->get_result();
    $order_status = $status_result->fetch_assoc();

    // Cost Composition Analysis
    $total_revenue_query = "SELECT SUM(total_amount) as total_revenue
                           FROM orders
                           WHERE created_at BETWEEN ? AND ?
                           AND status != 'Cancelled'";
    
    $stmt = $conn->prepare($total_revenue_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $revenue_result = $stmt->get_result();
    $total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;

    $cost_composition = [
        'food_cost' => $total_revenue * 0.3, // 30% food cost
        'labor' => $total_revenue * 0.25,    // 25% labor cost
        'overhead' => $total_revenue * 0.15,  // 15% overhead
        'profit' => $total_revenue * 0.3     // 30% profit
    ];

    // Return the data
    echo json_encode([
        'success' => true,
        'revenue_trend' => $revenue_trend,
        'labor_customer' => $labor_customer,
        'order_status' => $order_status,
        'cost_composition' => $cost_composition
    ]);

} catch (Exception $e) {
    error_log("Error in get_chart_data.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching chart data.'
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
} 