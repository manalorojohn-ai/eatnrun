<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

// Get the notification ID from POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['notification_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Notification ID is required'
    ]);
    exit;
}

// Include database connection
require_once 'config/db.php';

try {
    $user_id = $_SESSION['user_id'];
    $notification_id = $data['notification_id'];
    
    // Update the notification as read
    $update_query = "UPDATE notifications 
                    SET is_read = 1 
                    WHERE id = ? AND user_id = ?";
    
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    $success = mysqli_stmt_execute($stmt);
    
    if (!$success) {
        throw new Exception("Failed to update notification");
    }
    
    // Get updated unread count
    $count_query = "SELECT COUNT(*) as unread_count 
                    FROM notifications 
                    WHERE user_id = ? AND is_read = 0";
    
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, "i", $user_id);
    mysqli_stmt_execute($count_stmt);
    $result = mysqli_stmt_get_result($count_stmt);
    $row = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$row['unread_count']
    ]);

} catch (Exception $e) {
    error_log("Error marking notification as read: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error marking notification as read'
    ]);
}

// Close the connection
mysqli_close($conn);
?> 