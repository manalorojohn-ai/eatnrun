<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $notification_id = isset($data['notification_id']) ? intval($data['notification_id']) : 0;
    $user_id = $_SESSION['user_id'];

    if ($notification_id <= 0) {
        throw new Exception('Invalid notification ID');
    }

    // Mark notification as read
    $update_query = "UPDATE notifications 
                    SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                    WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to mark notification as read');
    }

    // Get updated unread count
    $count_query = "SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0 AND user_id = ?";
    $stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $unread_count = mysqli_fetch_assoc($result)['unread'];

    echo json_encode([
        'success' => true,
        'unread_count' => $unread_count
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 