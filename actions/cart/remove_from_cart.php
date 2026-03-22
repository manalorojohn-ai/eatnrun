<?php
session_start();
require_once '../includes/connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'];

$query = "DELETE FROM cart WHERE user_id = ? AND menu_item_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $item_id);
$success = mysqli_stmt_execute($stmt);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Item removed' : 'Failed to remove item'
]);
?> 