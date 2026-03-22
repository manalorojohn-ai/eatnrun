<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../config/db.php';

session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    exit();
}

while (true) {
    $query = "
        SELECT 
            o.*,
            u.full_name,
            u.email,
            GROUP_CONCAT(CONCAT(mi.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "data: " . json_encode(['orders' => $orders]) . "\n\n";
        
        ob_flush();
        flush();
        
        sleep(2); // Check for updates every 2 seconds
    } catch(PDOException $e) {
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    }
} 