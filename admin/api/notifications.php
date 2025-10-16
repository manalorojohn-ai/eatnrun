<?php
header('Content-Type: application/json');
require_once '../../config/db.php';
require_once '../includes/notifications_handler.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION['user_id'];

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Check for new notifications first
            check_new_orders($conn, $admin_id);
            check_order_status_changes($conn, $admin_id);
            
            // Get notifications
            $notifications = [];
            $result = get_admin_recent_notifications($conn, $admin_id, 10);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $notifications[] = [
                    'id' => $row['id'],
                    'title' => '',
                    'message' => $row['message'],
                    'is_read' => (bool)$row['is_read'],
                    'link' => $row['link'],
                    'formatted_time' => format_notification_time($row['created_at']),
                    'created_at' => $row['created_at']
                ];
            }
            
            $unread_count = get_admin_unread_count($conn, $admin_id);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'mark_read':
                    $notification_id = $input['notification_id'] ?? null;
                    if ($notification_id) {
                        $success = mark_notification_read($conn, $notification_id, $admin_id);
                        echo json_encode(['success' => $success]);
                    } else {
                        throw new Exception('Notification ID required');
                    }
                    break;
                    
                case 'mark_all_read':
                    $success = mark_all_notifications_read($conn, $admin_id);
                    echo json_encode(['success' => $success]);
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            break;

        default:
            throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    error_log("Notification API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 