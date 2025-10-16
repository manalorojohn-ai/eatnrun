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

try {
    $userId = $_SESSION['user_id'];
    
    // Update all unread notifications as read
    $query = "UPDATE notifications 
              SET `read` = 1 
              WHERE user_id = ? AND `read` = 0";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'All notifications marked as read'
    ]);
    
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