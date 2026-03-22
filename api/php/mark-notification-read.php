<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if notification ID is provided
if (!isset($_POST['notification_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Notification ID is required']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    $notificationId = $_POST['notification_id'];
    
    // Update notification as read
    $query = "UPDATE notifications 
              SET `read` = 1 
              WHERE id = ? AND user_id = ?";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([$notificationId, $userId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Notification not found']);
        exit;
    }
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
    error_log("Database Error: " . $e->getMessage());
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
    error_log("Error: " . $e->getMessage());
    exit;
} 