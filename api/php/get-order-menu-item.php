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
$check_query = "SELECT o.id, o.menu_item_id FROM orders o WHERE o.id = ? AND o.user_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $check_query);
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

// If menu_item_id is NULL or 0, try to get it from order_details
if (!$order['menu_item_id']) {
    $details_query = "SELECT menu_item_id FROM order_details WHERE order_id = ? LIMIT 1";
    $details_stmt = mysqli_prepare($conn, $details_query);
    mysqli_stmt_bind_param($details_stmt, "i", $order_id);
    mysqli_stmt_execute($details_stmt);
    $details_result = mysqli_stmt_get_result($details_stmt);
    
    if ($details_row = mysqli_fetch_assoc($details_result)) {
        $menu_item_id = $details_row['menu_item_id'];
        
        // Update the orders table with this menu_item_id
        $update_query = "UPDATE orders SET menu_item_id = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ii", $menu_item_id, $order_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    } else {
        // If still not found, try to get it from items array in the order_items table
        $items_query = "SELECT menu_item_id FROM order_items WHERE order_id = ? LIMIT 1";
        $items_stmt = mysqli_prepare($conn, $items_query);
        mysqli_stmt_bind_param($items_stmt, "i", $order_id);
        mysqli_stmt_execute($items_stmt);
        $items_result = mysqli_stmt_get_result($items_stmt);
        
        if ($items_row = mysqli_fetch_assoc($items_result)) {
            $menu_item_id = $items_row['menu_item_id'];
            
            // Update the orders table with this menu_item_id
            $update_query = "UPDATE orders SET menu_item_id = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ii", $menu_item_id, $order_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        } else {
            $menu_item_id = 0; // No menu item found
        }
        
        mysqli_stmt_close($items_stmt);
    }
    
    mysqli_stmt_close($details_stmt);
} else {
    $menu_item_id = $order['menu_item_id'];
}

echo json_encode([
    'success' => true,
    'menu_item_id' => $menu_item_id,
    'order_id' => $order_id
]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 