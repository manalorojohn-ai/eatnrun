<?php
// Check if session is supported and start it safely
if (extension_loaded('session')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    die("PHP Session module is not installed. Please enable it in php.ini");
}

require_once '../config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Prepare data structure
$data = [
    'stats' => [],
    'recent_orders' => [],
    'popular_items' => []
];

// Get statistics
// Total Orders Today
$today_query = "SELECT COUNT(*) as total_orders FROM orders WHERE DATE(created_at) = CURDATE()";
$result = mysqli_query($conn, $today_query);
$data['stats']['orders_today'] = mysqli_fetch_assoc($result)['total_orders'];

// Monthly Revenue
$monthly_revenue_query = "SELECT SUM(total_amount) as revenue 
                         FROM orders 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$result = mysqli_query($conn, $monthly_revenue_query);
$data['stats']['daily_revenue'] = mysqli_fetch_assoc($result)['revenue'] ?? 0;

// Total Users
$users_query = "SELECT COUNT(*) as total_users FROM users WHERE role = 'customer'";
$users_result = mysqli_query($conn, $users_query);
$data['stats']['total_users'] = mysqli_fetch_assoc($users_result)['total_users'];

// Get recent orders
$recent_orders_query = "SELECT o.id, o.user_id, o.total_amount, o.status, o.created_at, u.full_name 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       ORDER BY o.created_at DESC 
                       LIMIT 5";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);

while ($order = mysqli_fetch_assoc($recent_orders_result)) {
    $data['recent_orders'][] = [
        'id' => $order['id'],
        'user_id' => $order['user_id'],
        'user_name' => $order['full_name'],
        'total_amount' => $order['total_amount'],
        'status' => $order['status'],
        'formatted_date' => date('M d, Y h:i a', strtotime($order['created_at'])),
        'created_at' => $order['created_at']
    ];
}

// Get popular items
$popular_items_query = "SELECT m.id, m.name, m.image_url, m.price,
                              COUNT(oi.menu_item_id) as order_count
                       FROM menu_items m
                       LEFT JOIN order_items oi ON m.id = oi.menu_item_id
                       GROUP BY m.id
                       ORDER BY order_count DESC
                       LIMIT 5";
$popular_items_result = mysqli_query($conn, $popular_items_query);

while ($item = mysqli_fetch_assoc($popular_items_result)) {
    $data['popular_items'][] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'image_url' => $item['image_url'],
        'price' => $item['price'],
        'order_count' => $item['order_count']
    ];
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($data); 