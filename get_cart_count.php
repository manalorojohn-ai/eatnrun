<?php
session_start();
require_once 'config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    $query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'count' => (int)($data['count'] ?? 0)
    ]);
} catch (Exception $e) {
    error_log("Error getting cart count: " . $e->getMessage());
    echo json_encode(['count' => 0]);
}

mysqli_close($conn); 