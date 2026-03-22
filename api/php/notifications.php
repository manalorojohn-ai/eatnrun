<?php
require_once '../config/database.php';
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    // Get unread count
    $unread_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $unread_stmt->execute([$user_id]);
    $unread_count = $unread_stmt->fetchColumn();
    
    // Get notifications
    $query = "SELECT 
        n.id,
        n.message,
        n.created_at,
        n.is_read,
        n.notification_type,
        CASE 
            WHEN n.notification_type = 'order' THEN 'fa-shopping-bag'
            WHEN n.notification_type = 'delivery' THEN 'fa-truck'
            WHEN n.notification_type = 'payment' THEN 'fa-credit-card'
            WHEN n.notification_type = 'system' THEN 'fa-bell'
            ELSE 'fa-info-circle'
        END as icon,
        CASE
            WHEN TIMESTAMPDIFF(SECOND, n.created_at, NOW()) < 60 THEN 'Just now'
            WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) = 1 THEN '1 minute ago'
            WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, n.created_at, NOW()), ' minutes ago')
            WHEN TIMESTAMPDIFF(HOUR, n.created_at, NOW()) = 1 THEN '1 hour ago'
            WHEN TIMESTAMPDIFF(HOUR, n.created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, n.created_at, NOW()), ' hours ago')
            WHEN TIMESTAMPDIFF(DAY, n.created_at, NOW()) = 1 THEN 'Yesterday'
            WHEN TIMESTAMPDIFF(DAY, n.created_at, NOW()) < 7 THEN CONCAT(TIMESTAMPDIFF(DAY, n.created_at, NOW()), ' days ago')
            ELSE DATE_FORMAT(n.created_at, '%M %d, %Y')
        END as time_ago
    FROM notifications n
    WHERE n.user_id = ? AND n.id > ?
    ORDER BY n.created_at DESC
    LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $last_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get the highest notification ID
    $max_id = 0;
    if (!empty($notifications)) {
        $max_id = max(array_column($notifications, 'id'));
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'last_id' => $max_id
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?> 