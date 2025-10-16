<?php
require_once 'config/db.php';
require_once 'includes/NotificationManager.php';

// Create a test notification
$notificationManager = new NotificationManager($conn);
$userId = 1; // Replace with an actual user ID
$message = "This is a real-time test notification - " . date('Y-m-d H:i:s');
$type = "test";

$notification = $notificationManager->createNotification($userId, $message, $type);

if ($notification) {
    echo "Test notification created successfully!\n";
    echo "Notification details:\n";
    echo json_encode($notification, JSON_PRETTY_PRINT);
} else {
    echo "Failed to create test notification.";
}
?> 