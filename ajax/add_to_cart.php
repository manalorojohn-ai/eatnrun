<?php
session_start();
require_once '../includes/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;
$user_id = $_SESSION['user_id'];

// Verify if the menu item exists
$check_item = "SELECT id, price FROM menu_items WHERE id = ?";
$stmt = mysqli_prepare($conn, $check_item);
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($menu_item = mysqli_fetch_assoc($result)) {
    // Check if item already in cart
    $check_cart = "SELECT id, quantity FROM cart WHERE user_id = ? AND menu_item_id = ?";
    $stmt = mysqli_prepare($conn, $check_cart);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
    mysqli_stmt_execute($stmt);
    $cart_result = mysqli_stmt_get_result($stmt);

    if ($cart_item = mysqli_fetch_assoc($cart_result)) {
        // Update quantity if item exists
        $new_quantity = $cart_item['quantity'] + 1;
        $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", $new_quantity, $cart_item['id']);
        $success = mysqli_stmt_execute($stmt);
    } else {
        // Insert new item if it doesn't exist
        $insert_query = "INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, 1)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $item_id);
        $success = mysqli_stmt_execute($stmt);
    }

    if ($success) {
        // Get updated cart count
        $count_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $count_result = mysqli_stmt_get_result($stmt);
        $count_row = mysqli_fetch_assoc($count_result);

        echo json_encode([
            'success' => true,
            'message' => 'Item added to cart successfully',
            'cartCount' => $count_row['count']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add item to cart'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found or unavailable'
    ]);
}
?> 