<?php
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get total orders
    $orderStmt = $conn->query("SELECT COUNT(*) as total FROM orders");
    $totalOrders = $orderStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get total revenue
    $revenueStmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
    $totalRevenue = $revenueStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get total users
    $userStmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $totalUsers = $userStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get total products
    $productStmt = $conn->query("SELECT COUNT(*) as total FROM menu_items");
    $totalProducts = $productStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get recent orders
    $recentOrdersStmt = $conn->query("
        SELECT o.*, u.username, 
        GROUP_CONCAT(mi.name SEPARATOR ', ') as items,
        COUNT(oi.id) as item_count
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
        GROUP BY o.id
        ORDER BY o.created_at DESC LIMIT 5
    ");
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'totalOrders' => $totalOrders,
        'totalRevenue' => $totalRevenue,
        'totalUsers' => $totalUsers,
        'totalProducts' => $totalProducts,
        'recentOrders' => $recentOrders,
        'lastUpdate' => date('Y-m-d H:i:s')
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 