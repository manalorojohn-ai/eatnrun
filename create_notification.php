<?php
require_once 'config/db.php';
require_once 'includes/NotificationManager.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['message']) || !isset($data['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    $notificationManager = new NotificationManager($conn);
    $notification = $notificationManager->createNotification(
        $data['user_id'],
        $data['message'],
        $data['type']
    );

    if ($notification) {
        echo json_encode([
            'success' => true,
            'notification' => $notification
        ]);
    } else {
        throw new Exception("Failed to create notification");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?> 