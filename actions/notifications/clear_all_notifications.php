<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Include database connection
require_once 'config/db.php';

// Sanitize user ID
$user_id = mysqli_real_escape_string($conn, $user_id);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Delete all notifications for the user
    $delete_query = "DELETE FROM notifications WHERE user_id = '$user_id'";
    $result = mysqli_query($conn, $delete_query);
    
    if (!$result) {
        throw new Exception("Failed to delete notifications: " . mysqli_error($conn));
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'All notifications have been deleted'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    // Log error
    error_log("Error in clear_all_notifications.php: " . $e->getMessage());
    
    // Return error
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    // Close connection
    mysqli_close($conn);
}
?> 