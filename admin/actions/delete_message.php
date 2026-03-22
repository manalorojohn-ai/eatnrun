<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the message ID from the POST request
$data = json_decode(file_get_contents('php://input'), true);
$messageId = isset($data['message_id']) ? intval($data['message_id']) : 0;

if ($messageId > 0) {
    try {
        $query = "DELETE FROM messages WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $messageId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting message'
            ]);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid message ID'
    ]);
}

$conn->close();
?> 