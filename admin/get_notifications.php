<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get unread notifications count if requested
if (isset($_GET['count'])) {
    $count_query = "SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0 AND user_id = ?";
    $stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $unread_count = mysqli_fetch_assoc($result)['unread'];
    
    header('Content-Type: application/json');
    echo json_encode(['unread_count' => $unread_count]);
    exit();
}

// Get recent notifications
$query = "SELECT * FROM notifications 
          WHERE user_id = ?
          ORDER BY created_at DESC 
          LIMIT 10";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format time ago
    $created = new DateTime($row['created_at']);
    $now = new DateTime();
    $interval = $created->diff($now);
    
    if ($interval->y > 0) {
        $time_ago = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
    } elseif ($interval->m > 0) {
        $time_ago = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
    } elseif ($interval->d > 0) {
        $time_ago = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
    } elseif ($interval->h > 0) {
        $time_ago = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
    } elseif ($interval->i > 0) {
        $time_ago = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
    } else {
        $time_ago = 'Just now';
    }
    
    $notifications[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'type' => $row['type'],
        'is_read' => (bool)$row['is_read'],
        'link' => $row['link'],
        'created_at' => $row['created_at'],
        'time_ago' => $time_ago
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'notifications' => $notifications
]);
?> 