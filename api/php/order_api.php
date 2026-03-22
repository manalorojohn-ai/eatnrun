<?php
// online-food-ordering/order_api.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once 'db_structure_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['menu_item_id'], $data['address'], $data['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$menu_item_id = intval($data['menu_item_id']);
$address = $data['address'];
$user_id = intval($data['user_id']);

// Insert order into orders table
$order_sql = "INSERT INTO orders (user_id, address, created_at) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($order_sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: prepare failed']);
    exit();
}
$stmt->bind_param('is', $user_id, $address);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: execute failed']);
    exit();
}
$order_id = $stmt->insert_id;
$stmt->close();

// Insert order item into order_details table
$item_sql = "INSERT INTO order_details (order_id, menu_item_id, quantity, price) VALUES (?, ?, 1, (SELECT price FROM menu_items WHERE id = ?))";
$stmt = $conn->prepare($item_sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: prepare failed (item)']);
    exit();
}
$stmt->bind_param('iii', $order_id, $menu_item_id, $menu_item_id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: execute failed (item)']);
    exit();
}
$stmt->close();

// Success response
http_response_code(200);
echo json_encode(['success' => true, 'order_id' => $order_id, 'message' => 'Order placed successfully.']); 