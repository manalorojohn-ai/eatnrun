<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['item_id'])) {
    echo json_encode(['error' => 'Item ID is required']);
    exit;
}

$item_id = mysqli_real_escape_string($conn, $_GET['item_id']);

$query = "SELECT 
    COALESCE(AVG(rating), 0) as average,
    COUNT(*) as count
    FROM ratings 
    WHERE menu_item_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ratings = mysqli_fetch_assoc($result);

echo json_encode([
    'average' => (float)$ratings['average'],
    'count' => (int)$ratings['count']
]);

mysqli_close($conn);
?> 