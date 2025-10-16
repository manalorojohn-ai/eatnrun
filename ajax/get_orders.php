<?php
session_start();
require_once '../includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$orders_query = "SELECT o.*, 
                 COUNT(oi.id) as items_count,
                 GROUP_CONCAT(CONCAT(oi.quantity, 'x ', m.name) SEPARATOR ', ') as items
                 FROM orders o
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 LEFT JOIN menu_items m ON oi.menu_item_id = m.id
                 WHERE o.user_id = ?
                 GROUP BY o.id
                 ORDER BY o.created_at DESC";

$stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$orders = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders[] = [
        'id' => $row['id'],
        'status' => $row['status'],
        'items' => htmlspecialchars($row['items']),
        'total_amount' => (float)$row['total_amount'],
        'delivery_fee' => (float)$row['delivery_fee'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'orders' => $orders
]); 