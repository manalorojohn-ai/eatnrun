<?php
ob_clean();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
require_once __DIR__ . '/db_structure_check.php';
require_once 'config/db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$user_id = intval($_POST['user_id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$delivery_address = trim($_POST['delivery_address'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$payment_proof_path = isset($_POST['payment_proof']) ? trim($_POST['payment_proof']) : '';
$delivery_notes = trim($_POST['delivery_notes'] ?? '');
$subtotal = floatval($_POST['subtotal'] ?? 0);
$delivery_fee = floatval($_POST['delivery_fee'] ?? 0);
$total_amount = floatval($_POST['total_amount'] ?? ($subtotal + $delivery_fee));
$status = 'Pending';

// Get email from POST data
$email = trim($_POST['email'] ?? '');

// Debug log all POST data
error_log("=============== ORDER DEBUG START ===============");
error_log("POST DATA RECEIVED:");
foreach ($_POST as $key => $value) {
    error_log("$key: $value");
}

// Basic field validation
if (!$user_id || !$full_name || !$phone || !$delivery_address || !$payment_method) {
    error_log("VALIDATION FAILED - Missing basic required fields");
    error_log("user_id: $user_id");
    error_log("full_name: $full_name");
    error_log("phone: $phone");
    error_log("delivery_address: $delivery_address");
    error_log("payment_method: $payment_method");
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Handle email - if not provided in POST, generate from full name
if (empty($email)) {
    error_log("No email provided in POST, generating from full name");
    // Remove spaces and special characters, convert to lowercase
    $email = strtolower(str_replace(' ', '', preg_replace('/[^a-zA-Z0-9\s]/', '', $full_name))) . '@gmail.com';
    error_log("Generated email: $email");
}

error_log("FINAL VALUES TO BE INSERTED:");
error_log("Name: $full_name");
error_log("Email: $email");
error_log("Phone: $phone");
error_log("Address: $delivery_address");

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert order
    $query = "INSERT INTO orders (
        user_id,
        full_name,
        email,
        phone,
        delivery_address,
        payment_method,
        payment_proof,
        delivery_notes,
        subtotal,
        delivery_fee,
        total_amount,
        status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    error_log("PREPARING QUERY: $query");
    
$stmt = $conn->prepare($query);
if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
}

    error_log("BINDING PARAMETERS - Email value being bound: $email");

$stmt->bind_param(
        "isssssssddds",
    $user_id,
    $full_name,
        $email,      // This should be the exact email from the form
    $phone,
    $delivery_address,
    $payment_method,
    $payment_proof_path,
    $delivery_notes,
    $subtotal,
    $delivery_fee,
    $total_amount,
    $status
);

    error_log("EXECUTING QUERY");

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $order_id = $stmt->insert_id;
    error_log("ORDER INSERTED SUCCESSFULLY - ID: $order_id");
    
    // Verify the inserted email
    $verify_query = "SELECT email FROM orders WHERE id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param('i', $order_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $inserted_data = $verify_result->fetch_assoc();
    error_log("VERIFICATION - Email in database: " . $inserted_data['email']);
    
    $stmt->close();
    error_log("=============== ORDER DEBUG END ===============");

    // Insert order items into order_details
    $items_query = "INSERT INTO order_details (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)";
    $items_stmt = $conn->prepare($items_query);

    if (!$items_stmt) {
        throw new Exception('Failed to prepare items statement: ' . $conn->error);
    }

    // Get cart items
    $cart_query = "SELECT c.*, m.price FROM cart c 
                   JOIN menu_items m ON c.menu_item_id = m.id 
                   WHERE c.user_id = ?";
    $cart_stmt = $conn->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();

    while ($item = $cart_result->fetch_assoc()) {
        $items_stmt->bind_param("iiid", 
            $order_id, 
            $item['menu_item_id'], 
            $item['quantity'], 
            $item['price']
        );
        if (!$items_stmt->execute()) {
            throw new Exception('Failed to insert order item: ' . $items_stmt->error);
        }
    }

    // Clear cart
    $clear_cart = "DELETE FROM cart WHERE user_id = ?";
    $clear_stmt = $conn->prepare($clear_cart);
    $clear_stmt->bind_param("i", $user_id);
    $clear_stmt->execute();

    // Commit transaction
    mysqli_commit($conn);

    // Add notification for the user
    require_once 'add_notification.php';
    $notification_message = "Your order #$order_id has been placed successfully!";
    add_notification($user_id, $notification_message, 'order', "orders.php?highlight=$order_id");

    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Order failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
} 