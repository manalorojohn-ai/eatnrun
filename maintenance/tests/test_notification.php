<?php
session_start();
require_once 'config/db.php';
require_once 'includes/NotificationManager.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$notificationManager = new NotificationManager($conn);
$message = "Test notification - " . date('Y-m-d H:i:s');
$notification = $notificationManager->createNotification($_SESSION['user_id'], $message, 'test');

if ($notification) {
    echo json_encode([
        'success' => true,
        'message' => 'Test notification created',
        'notification' => $notification
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create notification'
    ]);
}
?> 