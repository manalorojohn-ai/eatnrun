<?php
session_start();
require_once 'includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

// Verify CSRF token
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($_SESSION['csrf_token']) || !isset($data['csrf_token']) || 
    $_SESSION['csrf_token'] !== $data['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get current profile image
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && !empty($user['profile_image'])) {
    // Delete the file
    if (file_exists($user['profile_image'])) {
        unlink($user['profile_image']);
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update database']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No profile image found']);
}
?> 