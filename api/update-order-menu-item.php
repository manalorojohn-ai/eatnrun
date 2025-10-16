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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate required parameters
if (!isset($data['order_id']) || !isset($data['menu_item_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$order_id = intval($data['order_id']);
$menu_item_id = intval($data['menu_item_id']);
$user_id = $_SESSION['user_id'];

// Validate values
if ($order_id <= 0 || $menu_item_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid order_id or menu_item_id'
    ]);
    exit;
}

// Check if the order belongs to the current user
$check_query = "SELECT id FROM orders WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Order not found or not authorized'
    ]);
    mysqli_stmt_close($stmt);
    exit;
}

// Update the order with the menu_item_id
$update_query = "UPDATE orders SET menu_item_id = ? WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "iii", $menu_item_id, $order_id, $user_id);
$success = mysqli_stmt_execute($stmt);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Order updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update order: ' . mysqli_error($conn)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?> 