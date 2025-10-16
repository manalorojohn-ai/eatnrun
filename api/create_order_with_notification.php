<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/notification_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['items', 'total_amount', 'delivery_address', 'payment_method'];
foreach ($required_fields as $field) {
    if (!isset($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    $user_id = $_SESSION['user_id'];
    $items = $input['items'];
    $total_amount = floatval($input['total_amount']);
    $delivery_address = mysqli_real_escape_string($conn, $input['delivery_address']);
    $payment_method = mysqli_real_escape_string($conn, $input['payment_method']);
    $delivery_notes = isset($input['delivery_notes']) ? mysqli_real_escape_string($conn, $input['delivery_notes']) : '';
    
    // Get user details
    $user_query = "SELECT full_name, phone, email FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($user_result);
    
    // Create order
    $order_query = "INSERT INTO orders (user_id, full_name, phone, email, delivery_address, delivery_notes, total_amount, payment_method, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = mysqli_prepare($conn, $order_query);
    mysqli_stmt_bind_param($stmt, "isssssds", 
        $user_id, 
        $user['full_name'], 
        $user['phone'], 
        $user['email'], 
        $delivery_address, 
        $delivery_notes, 
        $total_amount, 
        $payment_method
    );
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to create order");
    }
    
    $order_id = mysqli_insert_id($conn);
    
    // Add order items
    foreach ($items as $item) {
        $item_query = "INSERT INTO order_items (order_id, menu_item_id, quantity, price, created_at) 
                       VALUES (?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $item_query);
        mysqli_stmt_bind_param($stmt, "iiid", 
            $order_id, 
            $item['id'], 
            $item['quantity'], 
            $item['price']
        );
        mysqli_stmt_execute($stmt);
    }
    
    // Create notification for all admins about new order
    $customer_name = $user['full_name'] ?? 'Customer';
    $notification_message = "New order #$order_id received from $customer_name";
    $notification_link = "admin/order_details.php?id=$order_id";
    
    notify_all_admins(
        'order',
        $notification_message,
        $notification_link
    );
    
    // Log the order creation
    $log_sql = "INSERT INTO order_logs (order_id, action, details, created_by) 
                VALUES (?, 'created', 'Order created by customer', ?)";
    $stmt = mysqli_prepare($conn, $log_sql);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order created successfully',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>
