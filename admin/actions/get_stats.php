<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized');
}

try {
    // Get orders processed count
    $orderStmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'");
    $orderStmt->execute();
    $ordersProcessed = $orderStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Get products added count
    $productStmt = $conn->prepare("SELECT COUNT(*) as total FROM products");
    $productStmt->execute();
    $productsAdded = $productStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Calculate response rate
    $rateStmt = $conn->prepare("SELECT 
        (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed,
        COUNT(*) as total 
        FROM orders WHERE status != 'cancelled'");
    $rateStmt->execute();
    $rateData = $rateStmt->fetch(PDO::FETCH_ASSOC);
    $responseRate = $rateData['total'] > 0 ? 
        round(($rateData['completed'] / $rateData['total']) * 100) : 0;

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'ordersProcessed' => number_format($ordersProcessed),
        'productsAdded' => number_format($productsAdded),
        'responseRate' => $responseRate
    ]);

} catch(PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database error']);
}
?> 