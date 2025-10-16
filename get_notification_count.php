<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT COUNT(*) as count 
              FROM notifications 
              WHERE user_id = ? AND is_read = 0";
              
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);
    echo json_encode(['count' => (int)$row['count']]);
    
} catch (Exception $e) {
    error_log("Error getting notification count: " . $e->getMessage());
    echo json_encode(['count' => 0]);
}

mysqli_close($conn);
?> 