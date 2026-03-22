<?php
session_start();
require_once 'config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check for POST data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : null;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : null;

// Validate input
if (!$cart_id || $quantity < 1 || $quantity > 99) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit();
}

try {
    // Start transaction for data consistency
    mysqli_begin_transaction($conn);

    // First verify the cart item exists and belongs to the user
    $verify_query = "SELECT c.id, c.menu_item_id, m.price, m.name 
                     FROM cart c 
                     JOIN menu_items m ON c.menu_item_id = m.id 
                     WHERE c.id = ? AND c.user_id = ?";
    
    $stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cart_item = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$cart_item) {
        throw new Exception('Cart item not found');
    }

    // Update cart quantity
    $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $_SESSION['user_id']);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to update quantity');
    }
    mysqli_stmt_close($stmt);

    // Calculate new total
    $total_query = "SELECT SUM(c.quantity * m.price) as total 
                    FROM cart c 
                    JOIN menu_items m ON c.menu_item_id = m.id 
                    WHERE c.user_id = ?";
    
    $stmt = mysqli_prepare($conn, $total_query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'new_total' => (float)$total_data['total'],
        'item_price' => (float)$cart_item['price'],
        'cart_id' => $cart_id,
        'quantity' => $quantity,
        'item_name' => $cart_item['name']
    ]);

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    error_log("Cart update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update cart. Please try again.'
    ]);
}

mysqli_close($conn); 