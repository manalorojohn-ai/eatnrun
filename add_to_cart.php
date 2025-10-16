<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to add items to cart',
        'redirect' => 'login.php'
    ]);
    exit();
}

// Get the POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Handle both AJAX and form submissions
$item_id = null;
$quantity = 1;

if ($data !== null) {
    // JSON/AJAX request
    $item_id = isset($data['item_id']) ? intval($data['item_id']) : null;
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;
} else {
    // Form submission
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : null;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
}

if (!$item_id || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid item or quantity'
    ]);
    exit();
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // Check if item exists and is available
    $check_item = mysqli_prepare($conn, "SELECT id, name, price, status FROM menu_items WHERE id = ? FOR UPDATE");
    mysqli_stmt_bind_param($check_item, "i", $item_id);
    mysqli_stmt_execute($check_item);
    $result = mysqli_stmt_get_result($check_item);
    $menu_item = mysqli_fetch_assoc($result);
    mysqli_stmt_close($check_item);

    if (!$menu_item) {
        throw new Exception('Item not found');
    }

    if ($menu_item['status'] !== 'available') {
        throw new Exception('Item is currently not available');
    }

    // Check if item already exists in cart with row lock
    $check_cart = mysqli_prepare($conn, "SELECT id, quantity FROM cart WHERE user_id = ? AND menu_item_id = ? FOR UPDATE");
    mysqli_stmt_bind_param($check_cart, "ii", $_SESSION['user_id'], $item_id);
    mysqli_stmt_execute($check_cart);
    $cart_result = mysqli_stmt_get_result($check_cart);
    $cart_item = mysqli_fetch_assoc($cart_result);
    mysqli_stmt_close($check_cart);

    if ($cart_item) {
        // Update existing cart item
        $new_quantity = $cart_item['quantity'] + $quantity;
        $update_stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $cart_item['id']);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    } else {
        // Insert new cart item
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "iii", $_SESSION['user_id'], $item_id, $quantity);
        mysqli_stmt_execute($insert_stmt);
        mysqli_stmt_close($insert_stmt);
    }

    // Get updated cart count
    $count_stmt = mysqli_prepare($conn, "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
    mysqli_stmt_bind_param($count_stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    mysqli_stmt_close($count_stmt);

    // Commit transaction
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart successfully',
        'cartCount' => (int)$count_row['count'],
        'item' => [
            'name' => $menu_item['name'],
            'price' => (float)$menu_item['price']
        ]
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    
    error_log("Cart error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?> 