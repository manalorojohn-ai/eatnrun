<?php
session_start();
// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once 'config/db.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/food_order_api_errors.log');

// Function to ensure user exists
function ensure_user_exists($conn, $user_id, $full_name, $phone) {
    $check_user = "SELECT id FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $check_user);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 0) {
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
        mysqli_begin_transaction($conn);

        if (!ensure_user_exists($conn, $data['user_id'], $data['full_name'], $data['phone'])) {
            throw new Exception("Failed to create/verify user");
        }

        $order_query = "INSERT INTO orders (user_id, full_name, phone, delivery_address, 
                                            payment_method, notes, delivery_notes, subtotal, 
                                            delivery_fee, total_amount, status, menu_item_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
        $stmt = mysqli_prepare($conn, $order_query);
        if (!$stmt) {
            throw new Exception("Failed to prepare order query: " . mysqli_error($conn));
        }

        $menu_item_id = isset($data['menu_item_id']) ? intval($data['menu_item_id']) : null;
        mysqli_stmt_bind_param($stmt, "issssssdddI",
            $data['user_id'],
            $data['full_name'],
            $data['phone'],
            $data['delivery_address'],
            $data['payment_method'],
            $data['notes'],
            $data['delivery_notes'],
            $data['subtotal'],
            $data['delivery_fee'],
            $data['total_amount'],
            $menu_item_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to create order: " . mysqli_stmt_error($stmt));
        }

        $order_id = mysqli_insert_id($conn);

        // Insert order items
        if (!empty($data['items']) && is_array($data['items'])) {
            error_log('Order items array: ' . print_r($data['items'], true)); // <--- ADDED

            $item_query = "INSERT INTO order_details (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)";
            $item_stmt = mysqli_prepare($conn, $item_query);
            if (!$item_stmt) {
                throw new Exception("Failed to prepare order item query: " . mysqli_error($conn));
            }

            foreach ($data['items'] as $item) {
                error_log('Order item: ' . print_r($item, true)); // <--- ADDED

                $menu_item_id = intval($item['menu_item_id']);
                $quantity = intval($item['quantity']);
                $price = floatval($item['price']);

                $check_menu = mysqli_prepare($conn, "SELECT id FROM menu_items WHERE id = ?");
                mysqli_stmt_bind_param($check_menu, "i", $menu_item_id);
                mysqli_stmt_execute($check_menu);
                $result = mysqli_stmt_get_result($check_menu);
                if (mysqli_num_rows($result) === 0) {
                    throw new Exception("Invalid menu_item_id: $menu_item_id does not exist in menu_items.");
                }
                mysqli_stmt_close($check_menu);

                mysqli_stmt_bind_param($item_stmt, "iiid", $order_id, $menu_item_id, $quantity, $price);
                if (!mysqli_stmt_execute($item_stmt)) {
                    throw new Exception("Failed to insert order item: " . mysqli_stmt_error($item_stmt));
                }
            }
            mysqli_stmt_close($item_stmt);
        }

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

// Handle GET request for fetching orders
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

    if (!$user_id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing user_id',
            'message' => 'User ID is required'
        ]);
        exit();
    }

    try {
        if (!$conn) throw new Exception("Database connection failed");
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $orders_query = "SELECT o.*, 
                            DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i:%s') as created_at_fmt,
                            DATE_FORMAT(o.received_at, '%Y-%m-%d %H:%i:%s') as received_at_fmt,
                            CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as has_rating,
                            r.rating as rating_value,
                            r.comment as rating_comment
                         FROM orders o
                         LEFT JOIN ratings r ON o.id = r.order_id AND r.user_id = o.user_id
                         WHERE o.user_id = ?
                         ORDER BY o.created_at DESC";
        $stmt = mysqli_prepare($conn, $orders_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $orders_result = mysqli_stmt_get_result($stmt);

        $orders = [];
        while ($order = mysqli_fetch_assoc($orders_result)) {
            $order_id = $order['id'];
            $items_query = "SELECT od.menu_item_id, od.quantity, mi.name, od.price
                            FROM order_details od
                            LEFT JOIN menu_items mi ON od.menu_item_id = mi.id
                            WHERE od.order_id = ?";
            $item_stmt = mysqli_prepare($conn, $items_query);
            mysqli_stmt_bind_param($item_stmt, "i", $order_id);
            mysqli_stmt_execute($item_stmt);
            $items_result = mysqli_stmt_get_result($item_stmt);

            $items = [];
            while ($item = mysqli_fetch_assoc($items_result)) {
                $items[] = [
                    "menu_item_id" => (int)$item['menu_item_id'],
                    "name" => $item['name'],
                    "quantity" => (int)$item['quantity'],
                    "price" => number_format($item['price'], 2)
                ];
            }
            mysqli_stmt_close($item_stmt);

            $order['items'] = $items;
            $order['subtotal'] = number_format($order['subtotal'], 2);
            $order['delivery_fee'] = number_format($order['delivery_fee'], 2);
            $order['total_amount'] = number_format($order['total_amount'], 2);

            $orders[] = $order;
        }
        mysqli_stmt_close($stmt);

        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'message' => 'Orders retrieved successfully'
        ]);
    } catch (Exception $e) {
        error_log("Error fetching orders: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch orders',
            'message' => $e->getMessage()
        ]);
    }
    exit();
}
?>
