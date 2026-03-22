<?php
header('Content-Type: application/json');
require_once 'config/db.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid order_id']);
    exit;
}

$query = "SELECT id, full_name, phone, delivery_address, payment_method, notes, subtotal, total_amount, delivery_fee, status, created_at
          FROM orders WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($order = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
}
$stmt->close(); 