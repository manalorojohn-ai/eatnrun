<?php
session_start();
header('Content-Type: application/json');

// Require database connection
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

// Get order ID from query
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if (!$order_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Order ID is required'
    ]);
    exit;
}

// Check if the order belongs to the current user
$user_id = $_SESSION['user_id'];
$query = "SELECT o.*, 
          DATE_FORMAT(o.created_at, '%M %d, %Y %h:%i %p') as formatted_date,
          DATE_FORMAT(o.received_at, '%M %d, %Y %h:%i %p') as formatted_received_date
          FROM orders o 
          WHERE o.id = ? AND o.user_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Order not found or not authorized'
    ]);
    exit;
}

$order = mysqli_fetch_assoc($result);

// Get order items
$items_query = "SELECT od.*, mi.name, mi.price 
               FROM order_details od 
               JOIN menu_items mi ON od.menu_item_id = mi.id 
               WHERE od.order_id = ?";

$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = [
        'id' => intval($item['id']),
        'menu_item_id' => intval($item['menu_item_id']),
        'name' => $item['name'],
        'quantity' => intval($item['quantity']),
        'price' => floatval($item['price']),
        'subtotal' => floatval($item['price'] * $item['quantity'])
    ];
}

// Add items to order
$order['items'] = $items;

// Check if there's a rating for this order
$rating_query = "SELECT r.* FROM ratings r WHERE r.order_id = ? AND r.user_id = ? LIMIT 1";
$rating_stmt = mysqli_prepare($conn, $rating_query);
mysqli_stmt_bind_param($rating_stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($rating_stmt);
$rating_result = mysqli_stmt_get_result($rating_stmt);

if ($rating = mysqli_fetch_assoc($rating_result)) {
    $order['rating'] = [
        'id' => intval($rating['id']),
        'rating' => intval($rating['rating']),
        'comment' => $rating['comment'],
        'created_at' => $rating['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'order' => $order
]);

mysqli_stmt_close($stmt);
mysqli_stmt_close($items_stmt);
mysqli_stmt_close($rating_stmt);
mysqli_close($conn);
?> 