<?php
require_once '../includes/session.php';
require_once '../config/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get date range from request
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

try {
    $response = [
        'success' => true,
        'totalSales' => 0,
        'totalOrders' => 0,
        'totalCustomers' => 0,
        'tablesUsed' => 0,
        'totalDiscounts' => 0,
        'revenueTrend' => ['dates' => [], 'revenue' => []],
        'laborCustomer' => ['dates' => [], 'laborCost' => [], 'customerCount' => []],
        'orderStatus' => [
            'completed' => 0,
            'processing' => 0,
            'pending' => 0,
            'cancelled' => 0
        ],
        'costComposition' => [
            'foodCost' => 0,
            'labor' => 0,
            'overhead' => 0,
            'profit' => 0
        ]
    ];

    // Get total sales and orders
    $query = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                SUM(delivery_fee) as total_delivery_fees
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $response['totalOrders'] = (int)$row['total_orders'];
    $response['totalSales'] = (float)$row['total_sales'];
    $response['totalDiscounts'] = 0; // Since there's no direct discount column

    // Get total unique customers
    $query = "SELECT COUNT(DISTINCT user_id) as total_customers 
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $response['totalCustomers'] = (int)$row['total_customers'];

    // Get tables used (if table_number exists in your orders)
    $response['tablesUsed'] = 0; // Set to 0 since table tracking isn't implemented yet

    // Get daily revenue trend
    $query = "SELECT 
                DATE(created_at) as date,
                SUM(total_amount) as daily_revenue
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['revenueTrend']['dates'][] = $row['date'];
        $response['revenueTrend']['revenue'][] = (float)$row['daily_revenue'];
    }

    // Get order status distribution
    $query = "SELECT 
                status,
                COUNT(*) as count
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY status";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $status = strtolower($row['status']);
        if (isset($response['orderStatus'][$status])) {
            $response['orderStatus'][$status] = (int)$row['count'];
        }
    }

    // Get labor costs and customer count per day
    $query = "SELECT 
                DATE(created_at) as date,
                COUNT(DISTINCT user_id) as customer_count,
                COUNT(*) * 50 as labor_cost
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['laborCustomer']['dates'][] = $row['date'];
        $response['laborCustomer']['customerCount'][] = (int)$row['customer_count'];
        $response['laborCustomer']['laborCost'][] = (float)$row['labor_cost'];
    }

    // Calculate cost composition
    $totalSales = $response['totalSales'];
    if ($totalSales > 0) {
        $response['costComposition'] = [
            'foodCost' => $totalSales * 0.35, // 35% food cost
            'labor' => $totalSales * 0.25,    // 25% labor cost
            'overhead' => $totalSales * 0.20,  // 20% overhead
            'profit' => $totalSales * 0.20     // 20% profit
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching statistics: ' . $e->getMessage()
    ]);
} 