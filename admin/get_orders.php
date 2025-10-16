<?php
session_start();
require_once '../includes/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

try {
    $query = "SELECT o.*, u.username as customer_name, u.email as customer_email,
              COUNT(oi.id) as items_count,
              GROUP_CONCAT(CONCAT(m.name, ' x', oi.quantity) SEPARATOR ', ') as items
              FROM orders o
              JOIN users u ON o.user_id = u.id
              LEFT JOIN order_items oi ON o.id = oi.order_id
              LEFT JOIN menu_items m ON oi.menu_item_id = m.id
              GROUP BY o.id
              ORDER BY o.created_at DESC";
              
    $result = mysqli_query($conn, $query);
    $orders = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = [
            'id' => $row['id'],
            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'],
            'items_count' => $row['items_count'],
            'items' => $row['items'],
            'total_amount' => $row['total_amount'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode($orders);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch orders']);
}
?> 