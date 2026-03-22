<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get notifications for the user
    $query = "SELECT n.*, 
              DATE_FORMAT(n.created_at, '%M %d, %Y %h:%i %p') as formatted_time
              FROM notifications n 
              WHERE n.user_id = ? 
              ORDER BY n.created_at DESC 
              LIMIT 20";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = [
            'id' => $row['id'],
            'message' => $row['message'],
            'type' => $row['type'],
            'is_read' => $row['is_read'] == 1,
            'created_at' => $row['formatted_time']
        ];
    }
    
    // Get unread count
    $unread_query = "SELECT COUNT(*) as unread_count 
                     FROM notifications 
                     WHERE user_id = ? AND is_read = 0";
    
    $unread_stmt = mysqli_prepare($conn, $unread_query);
    mysqli_stmt_bind_param($unread_stmt, "i", $user_id);
    mysqli_stmt_execute($unread_stmt);
    $unread_result = mysqli_stmt_get_result($unread_stmt);
    $unread_row = mysqli_fetch_assoc($unread_result);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)$unread_row['unread_count']
    ]);

} catch (Exception $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching notifications'
    ]);
}
?> 