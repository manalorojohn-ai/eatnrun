<?php
// hotel_order_api.php (on Computer B)
require_once 'config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['user_id'], $data['cart_items'], $data['delivery_address'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$user_id = intval($data['user_id']);
$cart_items = $data['cart_items'];
$delivery_address = $data['delivery_address'];
$notes = $data['notes'] ?? '';
$phone = $data['phone'] ?? '';
$total = 0;

foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
$delivery_fee = 50.00;
$total_with_delivery = $total + $delivery_fee;

// Example: Save to hotel_orders table (customize as needed)
try {
    $order_query = "INSERT INTO hotel_orders (user_id, subtotal, total_amount, delivery_fee, delivery_address, notes, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = mysqli_prepare($conn, $order_query);
    mysqli_stmt_bind_param($stmt, "idddsss", $user_id, $total, $total_with_delivery, $delivery_fee, $delivery_address, $notes, $phone);
    mysqli_stmt_execute($stmt);
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Save order items
    $detail_query = "INSERT INTO hotel_order_details (order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $detail_query);
    foreach ($cart_items as $item) {
        mysqli_stmt_bind_param($stmt, "isid", $order_id, $item['name'], $item['quantity'], $item['price']);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);

    // Autofill hotel info or trigger other hotel-side logic here
    // ...

    echo json_encode(['success' => true, 'message' => 'Order received by hotel!', 'order_id' => $order_id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Hotel order failed: ' . $e->getMessage()]);
} 