<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Modify the SQL query to include a calculation for subtotal from order_items
    $query = "SELECT o.id, o.status, o.created_at, o.delivery_address, o.payment_method, o.payment_status, 
              o.delivery_fee, o.total_amount, 
              (o.total_amount - o.delivery_fee) AS subtotal
              FROM orders o 
              WHERE o.user_id = ? 
              ORDER BY o.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $orders = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $order_id = $row['id'];
        $items_query = "SELECT m.name, od.quantity, m.price
                        FROM order_details od
                        JOIN menu_items m ON od.menu_item_id = m.id
                        WHERE od.order_id = ?";
                        
        $items_stmt = mysqli_prepare($conn, $items_query);
        mysqli_stmt_bind_param($items_stmt, "i", $order_id);
        mysqli_stmt_execute($items_stmt);
        $items_result = mysqli_stmt_get_result($items_stmt);
        
        $items = [];
        $calculated_subtotal = 0;
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
            $calculated_subtotal += $item['price'] * $item['quantity'];
        }
        
        // Use calculated subtotal if available, otherwise use the one from the query
        $subtotal = count($items) > 0 ? $calculated_subtotal : $row['subtotal'];
        
        $orders[] = [
            'id' => $row['id'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'delivery_address' => $row['delivery_address'],
            'payment_method' => $row['payment_method'],
            'payment_status' => $row['payment_status'],
            'delivery_fee' => (float)$row['delivery_fee'],
            'total_amount' => (float)$row['total_amount'],
            'subtotal' => (float)$subtotal,
            'items' => $items
        ];
    }
    
    mysqli_stmt_close($stmt);
    echo json_encode($orders);
    
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to fetch orders. Please try again later.']);
}

mysqli_close($conn);
?> 