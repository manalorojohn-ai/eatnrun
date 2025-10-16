<?php
session_start();
// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/db.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/food_order_api_errors.log');

// Function to ensure user exists
function ensure_user_exists($conn, $user_id, $full_name, $phone) {
    // Check if user exists
    $check_user = "SELECT id FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_user);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        // User doesn't exist, create them
        $create_user = "INSERT INTO users (id, username, email, password, full_name, phone, role) 
                       VALUES (?, ?, ?, ?, ?, ?, 'user')";
        $stmt = mysqli_prepare($conn, $create_user);
        $username = "user" . $user_id;
        $email = "user" . $user_id . "@example.com";
        $password = password_hash("defaultpass", PASSWORD_DEFAULT);
        
        mysqli_stmt_bind_param($stmt, "isssss", 
            $user_id, 
            $username,
            $email,
            $password,
            $full_name,
            $phone
        );
        
        return mysqli_stmt_execute($stmt);
    }
    
    return true;
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Handle POST request for creating order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid request data',
            'message' => 'Request body must be valid JSON'
        ]);
        exit();
    }
    
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // Ensure user exists
        if (!ensure_user_exists($conn, $data['user_id'], $data['full_name'], $data['phone'])) {
            throw new Exception("Failed to create/verify user");
        }
        
        // Create order
        $order_query = "INSERT INTO orders (user_id, full_name, phone, delivery_address, 
                                          payment_method, notes, delivery_notes, subtotal, 
                                          delivery_fee, total_amount, status) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                       
        $stmt = mysqli_prepare($conn, $order_query);
        if (!$stmt) {
            throw new Exception("Failed to prepare order query: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "issssssddd",
            $data['user_id'],
            $data['full_name'],
            $data['phone'],
            $data['delivery_address'],
            $data['payment_method'],
            $data['notes'],
            $data['delivery_notes'],
            $data['subtotal'],
            $data['delivery_fee'],
            $data['total_amount']
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to create order: " . mysqli_stmt_error($stmt));
        }
        
        $order_id = mysqli_insert_id($conn);
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
            'message' => 'Order created successfully'
        ]);
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Order creation error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create order',
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

// Get user_id from session or query parameter
$user_id = null;
if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
} else if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
}

// Basic security check
if (!$user_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Missing user_id parameter',
        'message' => 'User ID is required'
    ]);
    exit();
}

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid user_id',
        'message' => 'Please provide a valid user ID'
    ]);
    exit();
}

try {
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Enable error reporting for debugging
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Log the query execution
    error_log("Executing order history query for user_id: " . $user_id);

    // Fetch orders with formatted date and all necessary information
    $sql = "SELECT o.*, 
            DATE_FORMAT(o.created_at, '%M %d, %Y %h:%i %p') as formatted_date,
            DATE_FORMAT(o.received_at, '%M %d, %Y %h:%i %p') as formatted_received_date,
            CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as has_rating,
            r.rating as rating_value,
            r.comment as rating_comment,
            o.menu_item_id  -- Make sure menu_item_id is explicitly included
            FROM orders o 
            LEFT JOIN ratings r ON o.id = r.order_id AND r.user_id = o.user_id
            WHERE o.user_id = ? 
            ORDER BY o.created_at DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare orders query: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to execute orders query: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new Exception("Failed to get result set: " . mysqli_error($conn));
    }

    $orders = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $order_id = $row['id'];
        
        // Fetch order items
        $item_sql = "SELECT od.*, mi.name, mi.price 
                     FROM order_details od 
                     JOIN menu_items mi ON od.menu_item_id = mi.id 
                     WHERE od.order_id = ?";
        
        $item_stmt = mysqli_prepare($conn, $item_sql);
        if (!$item_stmt) {
            throw new Exception("Failed to prepare items query: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($item_stmt, 'i', $order_id);
        if (!mysqli_stmt_execute($item_stmt)) {
            throw new Exception("Failed to execute items query: " . mysqli_stmt_error($item_stmt));
        }

        $item_result = mysqli_stmt_get_result($item_stmt);
        if ($item_result === false) {
            throw new Exception("Failed to get items result set: " . mysqli_error($conn));
        }

        $items = [];
        
        while ($item = mysqli_fetch_assoc($item_result)) {
            $items[] = [
                'name' => $item['name'],
                'quantity' => intval($item['quantity']),
                'price' => floatval($item['price']),
                'subtotal' => floatval($item['price'] * $item['quantity'])
            ];
        }
        
        mysqli_stmt_close($item_stmt);

        // Build receipt URL with full path
        $receipt_url = "/print_receipt.php?order_id=" . $order_id;

        // Add order to array with proper type casting
        $orders[] = [
            'id' => intval($order_id),
            'created_at' => $row['formatted_date'],
            'received_at' => $row['formatted_received_date'],
            'total_amount' => floatval($row['total_amount']),
            'subtotal' => floatval($row['subtotal']),
            'delivery_fee' => floatval($row['delivery_fee']),
            'status' => $row['status'],
            'payment_status' => $row['payment_status'],
            'payment_method' => $row['payment_method'],
            'delivery_address' => $row['delivery_address'],
            'has_rating' => (bool)$row['has_rating'],
            'rating' => $row['rating_value'] ? floatval($row['rating_value']) : null,
            'rating_comment' => $row['rating_comment'],
            'menu_item_id' => $row['menu_item_id'] ? intval($row['menu_item_id']) : 0,
            'items' => $items,
            'receipt_url' => $receipt_url
        ];
    }

    mysqli_stmt_close($stmt);

    // Log the number of orders found
    error_log("Found " . count($orders) . " orders for user_id: " . $user_id);

    if (empty($orders)) {
        echo json_encode([
            'success' => true,
            'orders' => [],
            'message' => 'No orders found for this user'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'message' => 'Orders retrieved successfully'
        ]);
    }

} catch (Exception $e) {
    error_log("Order history API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'An error occurred while fetching orders',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt) && $stmt) {
        mysqli_stmt_close($stmt);
    }
    if (isset($item_stmt) && $item_stmt) {
        mysqli_stmt_close($item_stmt);
    }
}