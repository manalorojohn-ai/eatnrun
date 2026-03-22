<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

// Include database connection
require_once 'config/db.php';

// Get limit parameter, default to 10 if not provided
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$limit = max(1, min($limit, 50)); // Ensure limit is between 1 and 50

// Get user ID from session
$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);

// Use prepared statement for security
$query = "SELECT n.*, 
          CASE 
              WHEN TIMESTAMPDIFF(SECOND, n.created_at, NOW()) < 60 THEN 'Just now'
              WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, n.created_at, NOW()), ' minutes ago')
              WHEN TIMESTAMPDIFF(HOUR, n.created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, n.created_at, NOW()), ' hours ago')
              WHEN TIMESTAMPDIFF(DAY, n.created_at, NOW()) < 7 THEN CONCAT(TIMESTAMPDIFF(DAY, n.created_at, NOW()), ' days ago')
              ELSE DATE_FORMAT(n.created_at, '%b %d, %Y at %h:%i %p')
          END AS time_ago
          FROM notifications n
          WHERE n.user_id = ?
          ORDER BY n.created_at DESC
          LIMIT ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $limit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
$unread_count = 0;

// Get unread count in a separate query
$unread_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_stmt = mysqli_prepare($conn, $unread_query);
mysqli_stmt_bind_param($unread_stmt, "i", $user_id);
mysqli_stmt_execute($unread_stmt);
$unread_result = mysqli_stmt_get_result($unread_stmt);
$unread_count = mysqli_fetch_assoc($unread_result)['count'];

while ($row = mysqli_fetch_assoc($result)) {
    // Determine icon based on notification type (if type column exists)
    $icon = 'fa-bell';
    if (isset($row['type'])) {
        switch ($row['type']) {
            case 'order':
                $icon = 'fa-shopping-bag';
                break;
            case 'payment':
                $icon = 'fa-credit-card';
                break;
            case 'delivery':
                $icon = 'fa-truck';
                break;
            case 'system':
                $icon = 'fa-exclamation-circle';
                break;
            default:
                $icon = 'fa-bell';
        }
    }

    $notifications[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'is_read' => (bool)$row['is_read'],
        'created_at' => $row['created_at'],
        'time_ago' => $row['time_ago'],
        'icon' => $icon,
        'link' => $row['link'] ?? null
    ];
}

// Close the connection
mysqli_close($conn);

// Return both notifications and unread count
echo json_encode([
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);
?> 