<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get recent activities
    $activityStmt = $conn->prepare("
        SELECT 
            'order' as type,
            id,
            status as action,
            created_at,
            total_amount as additional_data
        FROM orders 
        WHERE status_changed_by = ?
        UNION ALL
        SELECT 
            'product' as type,
            id,
            'added' as action,
            created_at,
            name as additional_data
        FROM products 
        WHERE added_by = ?
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $activityStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format activities for response
    $formattedActivities = array_map(function($activity) {
        $description = '';
        if ($activity['type'] === 'order') {
            $status = ucfirst($activity['action']);
            $orderId = '#ORD-' . $activity['id'];
            $description = "Order $orderId marked as $status";
        } else {
            $productName = $activity['additional_data'];
            $description = "Added new product: $productName";
        }

        return [
            'type' => $activity['type'],
            'description' => $description,
            'created_at' => date('M d, Y H:i', strtotime($activity['created_at']))
        ];
    }, $activities);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($formattedActivities);

} catch(PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error']);
}
?> 