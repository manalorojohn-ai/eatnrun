<?php
header('Content-Type: application/json');
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get order ID from request
if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

try {
    // Get order details
    $order_query = "SELECT o.*, 
                    DATE_FORMAT(o.created_at, '%M %d, %Y %h:%i %p') as formatted_date
                    FROM orders o 
                    WHERE o.id = ? AND o.user_id = ?";
    
    $stmt = mysqli_prepare($conn, $order_query);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    $order = mysqli_fetch_assoc($result);
    
    // Get order items
    $items_query = "SELECT od.*, m.name 
                    FROM order_details od 
                    JOIN menu_items m ON od.menu_item_id = m.id 
                    WHERE od.order_id = ?";
    
    $stmt = mysqli_prepare($conn, $items_query);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $items_result = mysqli_stmt_get_result($stmt);
    
    $items = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = $item;
    }
    
    // Add items to order array
    $order['items'] = $items;
    $order['created_at'] = $order['formatted_date'];
    
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);

} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching order details'
    ]);
}
?> 