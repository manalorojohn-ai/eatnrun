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

// Validate input
if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
    exit();
}

try {
    // Start transaction for data consistency
    mysqli_begin_transaction($conn);

    // First verify the cart item exists and belongs to the user
    $verify_query = "SELECT id FROM cart WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cart_item = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$cart_item) {
        throw new Exception('Cart item not found or does not belong to user');
    }

    // Delete the cart item
    $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "ii", $cart_id, $_SESSION['user_id']);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to remove item from cart');
    }
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Item removed successfully',
        'cart_id' => $cart_id
    ]);

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    error_log("Cart removal error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove item. Please try again.'
    ]);
}

mysqli_close($conn);
?>
