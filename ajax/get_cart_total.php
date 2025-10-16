<?php
session_start();
require_once '../includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$query = "SELECT SUM(c.quantity * m.price) as total 
          FROM cart c 
          JOIN menu_items m ON c.menu_item_id = m.id 
          WHERE c.user_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

echo json_encode([
    'success' => true,
    'total' => (float)$row['total'] ?: 0
]); 