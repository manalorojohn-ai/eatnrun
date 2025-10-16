<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/db.php';

try {
    // Get recent orders with user details
    $recent_orders_query = "SELECT o.*, u.full_name 
                           FROM orders o 
                           LEFT JOIN users u ON o.user_id = u.id 
                           ORDER BY o.created_at DESC 
                           LIMIT 5";
    $recent_orders_result = mysqli_query($conn, $recent_orders_query);

    if (!$recent_orders_result) {
        throw new Exception(mysqli_error($conn));
    }

    $orders = [];
    while ($order = mysqli_fetch_assoc($recent_orders_result)) {
        $orders[] = [
            'id' => (int)$order['id'],
            'full_name' => htmlspecialchars($order['full_name']),
            'total_amount' => (float)$order['total_amount'],
            'status' => $order['status'],
            'created_at' => $order['created_at']
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching recent orders: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 