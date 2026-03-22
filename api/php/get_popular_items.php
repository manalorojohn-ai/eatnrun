<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/db.php';

try {
    // Get popular items with order count
    $popular_items_query = "SELECT m.id, m.name, m.price, 
                                  COUNT(od.menu_item_id) as order_count
                           FROM menu_items m
                           LEFT JOIN order_details od ON m.id = od.menu_item_id
                           LEFT JOIN orders o ON od.order_id = o.id
                           WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                           GROUP BY m.id
                           ORDER BY order_count DESC
                           LIMIT 5";
    $popular_items_result = mysqli_query($conn, $popular_items_query);

    if (!$popular_items_result) {
        throw new Exception(mysqli_error($conn));
    }

    $items = [];
    while ($item = mysqli_fetch_assoc($popular_items_result)) {
        $items[] = [
            'id' => (int)$item['id'],
            'name' => htmlspecialchars($item['name']),
            'price' => (float)$item['price'],
            'order_count' => (int)$item['order_count']
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'items' => $items,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching popular items: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 