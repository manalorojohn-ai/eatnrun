<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit;
}

// Update quantity
$query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $quantity, $cart_id, $user_id);
$success = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($success) {
    // Get updated cart info
    $count_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $count_result = mysqli_stmt_get_result($stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success' => true,
        'message' => 'Cart updated successfully',
        'cartCount' => $count_row['count']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update cart'
    ]);
}
?>
