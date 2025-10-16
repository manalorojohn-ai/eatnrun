<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['checkout_started']) || !isset($_SESSION['checkout_data'])) {
    header("Location: checkout.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$checkout_data = $_SESSION['checkout_data'];

// Extract data from session
$full_name = $checkout_data['full_name'];
$phone = isset($checkout_data['phone']) ? $checkout_data['phone'] : '';
$address = $checkout_data['delivery_address'];
$payment_method = $checkout_data['payment_method'];
$notes = isset($checkout_data['notes']) ? $checkout_data['notes'] : '';
$payment_proof = isset($checkout_data['payment_proof']) ? $checkout_data['payment_proof'] : null;

// Calculate total amount and delivery fee
$cart_query = "SELECT c.*, m.name, m.price FROM cart c 
                JOIN menu_items m ON c.menu_item_id = m.id 
                WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cart_items = [];

while ($item = mysqli_fetch_assoc($result)) {
    $cart_items[] = $item;
}

if (empty($cart_items)) {
    $_SESSION['error'] = "Your cart is empty";
    header("Location: checkout.php");
    exit();
}

$subtotal = 0;
foreach ($cart_items as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
}

$delivery_fee = 50.00; // Fixed delivery fee
$total_amount = $subtotal + $delivery_fee;

try {
    mysqli_begin_transaction($conn);
    
    // Insert into orders table with all fields including full_name and subtotal
    $order_query = "INSERT INTO orders (user_id, full_name, phone, delivery_address, payment_method, payment_proof, subtotal, total_amount, delivery_fee, status, delivery_notes) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
    
    $stmt = mysqli_prepare($conn, $order_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "isssssddds", 
        $user_id,
        $full_name,
        $phone,
        $address,
        $payment_method,
        $payment_proof,
        $subtotal,
        $total_amount,
        $delivery_fee,
        $notes
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    $order_id = mysqli_insert_id($conn);
    
    // Insert items into order_details table
    foreach ($cart_items as $item) {
        $insert_item = "INSERT INTO order_details (order_id, menu_item_id, quantity, price) 
                       VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_item);
        mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item['menu_item_id'], $item['quantity'], $item['price']);
        mysqli_stmt_execute($stmt);
    }
    
    // Clear cart
    $clear_cart = "DELETE FROM cart WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $clear_cart);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    
    // Clean up session data
    unset($_SESSION['checkout_started']);
    unset($_SESSION['checkout_time']);
    unset($_SESSION['checkout_data']);
    
    mysqli_commit($conn);
    
    // Add notification for successful order placement
    require_once 'add_notification.php';
    $notification_message = "Your order #{$order_id} has been placed successfully!";
    add_notification($user_id, $notification_message, 'order', "orders.php?highlight={$order_id}");
    
    header("Location: order_success.php?order_id=" . $order_id);
    exit();
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    require_once 'add_notification.php';
    $error_message = "Failed to place order: " . $e->getMessage();
    add_notification($user_id, $error_message, 'system', null);
    header("Location: checkout.php");
    exit();
}
?> 