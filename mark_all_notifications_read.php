<?php
// Start session
session_start();

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

// Include database connection
require_once 'config/db.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Update all unread notifications as read
    $update_query = "UPDATE notifications 
                    SET is_read = 1 
                    WHERE user_id = ? AND is_read = 0";
    
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $success = mysqli_stmt_execute($stmt);
    
    if (!$success) {
        throw new Exception("Failed to update notifications");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'All notifications marked as read'
    ]);

} catch (Exception $e) {
    error_log("Error marking all notifications as read: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error marking notifications as read'
    ]);
} finally {
    // Close connection
    mysqli_close($conn);
}
?> 