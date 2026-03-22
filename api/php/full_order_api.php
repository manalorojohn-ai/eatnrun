<?php
header('Content-Type: application/json');
require_once 'config/db.php';

// Get POSTed JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit();
}

$user_id = $input['user_id'] ?? null;
$address = $input['address'] ?? '';
$items = $input['items'] ?? [];
$total = $input['total'] ?? 0;
$notes = $input['notes'] ?? '';

if (!$user_id || !$address || empty($items) || !$total) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);
try {
    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, address, total_amount, notes, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param('isds', $user_id, $address, $total, $notes);
    if (!$stmt->execute()) throw new Exception('Failed to create order: ' . $stmt->error);
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert order items
    $item_stmt = $conn->prepare("INSERT INTO order_details (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $menu_item_id = $item['menu_item_id'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        $item_stmt->bind_param('iiid', $order_id, $menu_item_id, $quantity, $price);
        if (!$item_stmt->execute()) throw new Exception('Failed to add order item: ' . $item_stmt->error);
    }
    $item_stmt->close();

    mysqli_commit($conn);
    // Return order info and receipt link
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'receipt_url' => "/online-food-ordering/receipt.php?order_id=$order_id",
        'message' => 'Order placed successfully.'
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}