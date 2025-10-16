<?php
session_start();
require_once '../includes/connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$user_id = $_SESSION['user_id'];

try {
    // Get comprehensive order statistics from the database
    $stats_query = "SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        MAX(o.created_at) as last_order
    FROM orders o 
    WHERE o.user_id = ?";
    
    $stmt = mysqli_prepare($conn, $stats_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats = mysqli_fetch_assoc($result);
    
    // Format the response
    $response = [
        'orders_count' => (int)$stats['total_orders'],
        'total_spent' => number_format((float)$stats['total_spent'], 2),
        'last_order' => $stats['last_order'] ? date('M d, Y', strtotime($stats['last_order'])) : 'No orders yet',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load stats',
        'message' => $e->getMessage()
    ]);
}
?> 