<?php
session_start();
require_once '../config/db.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_id']) || (!isset($_SESSION['user_role']) && !isset($_SESSION['role'])) || 
    (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'admin') && 
    (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Get unread message count
$unread_query = "SELECT COUNT(*) as unread FROM messages WHERE is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_count = mysqli_fetch_assoc($unread_result)['unread'];

// Get latest unread message if exists
$latest_message = null;
if ($unread_count > 0) {
    $latest_query = "SELECT id, name, email, message, created_at FROM messages WHERE is_read = 0 ORDER BY created_at DESC LIMIT 1";
    $latest_result = mysqli_query($conn, $latest_query);
    if ($latest_result && mysqli_num_rows($latest_result) > 0) {
        $latest_message = mysqli_fetch_assoc($latest_result);
    }
}

// Return JSON response
echo json_encode([
    'unread' => $unread_count,
    'latest' => $latest_message
]);
?> 