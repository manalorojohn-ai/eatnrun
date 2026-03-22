<?php
require_once '../includes/session.php';
require_once '../config/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'];

    // Clear all notifications for the user
    $delete_query = "DELETE FROM notifications 
                    WHERE user_id = ? OR user_id = 0";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    error_log("Error in clear_notifications.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while clearing notifications.'
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
} 