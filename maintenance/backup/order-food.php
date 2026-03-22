<?php
// api/order-food.php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['user_id'], $data['cart_items'], $data['delivery_address'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}
$user_id = $data['user_id'];
$cart_items = $data['cart_items'];
$delivery_address = $data['delivery_address'];
$notes = $data['notes'] ?? '';
$phone = $data['phone'] ?? '';
// Save to DB (replace with your DB logic)
require_once '../config/db.php';
$stmt = $conn->prepare("INSERT INTO orders (user_id, delivery_address, notes, phone, status) VALUES (?, ?, ?, ?, 'pending')");
$stmt->bind_param("isss", $user_id, $delivery_address, $notes, $phone);
$stmt->execute();
$order_id = $stmt->insert_id;
foreach ($cart_items as $item) {
    $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, name, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("isid", $order_id, $item['name'], $item['quantity'], $item['price']);
    $stmt2->execute();
}
echo json_encode(['success' => true, 'message' => 'Order placed!', 'order_id' => $order_id]); 