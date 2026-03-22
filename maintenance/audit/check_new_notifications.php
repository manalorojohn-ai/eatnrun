<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Include database connection
require_once 'config/db.php';

// Get user ID and last notification ID
$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Get unread count
$unread_query = "SELECT COUNT(*) as unread_count FROM notifications 
                 WHERE user_id = '$user_id' AND is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_count = 0;

if ($unread_result) {
    $row = mysqli_fetch_assoc($unread_result);
    $unread_count = (int)$row['unread_count'];
}

// Get new notifications
$query = "SELECT * FROM notifications 
          WHERE user_id = '$user_id' AND id > $last_id 
          ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

$notifications = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Determine icon based on notification type
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
            }
        }

        $notifications[] = [
            'id' => $row['id'],
            'message' => $row['message'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'icon' => $icon,
            'link' => $row['link'] ?? null
        ];
    }
}

// Close the connection
mysqli_close($conn);

// Return response
echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);
?> 