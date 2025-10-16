<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to rate orders']);
    exit;
}

require_once '../config/db.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['order_id']) || !isset($input['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$order_id = (int)$input['order_id'];
$rating = (int)$input['rating'];
$comment = isset($input['comment']) ? trim($input['comment']) : '';
$user_id = $_SESSION['user_id'];

// Get menu_item_id from input if provided
$menu_item_id = isset($input['menu_item_id']) ? (int)$input['menu_item_id'] : 0;

// Validate rating value
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // Check if order exists and belongs to the user
    $check_sql = "SELECT id, status FROM orders WHERE id = ? AND user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception("Order not found or doesn't belong to you");
    }
    
    // First check if rating already exists for this order
    $check_rating_sql = "SELECT id FROM ratings WHERE order_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $check_rating_sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $rating_result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($rating_result) > 0) {
        // Rating already exists, update it instead
        $rating_row = mysqli_fetch_assoc($rating_result);
        $rating_id = $rating_row['id'];
        
        $update_sql = "UPDATE ratings SET rating = ?, comment = ?, created_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "isi", $rating, $comment, $rating_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update rating");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Rating updated successfully'
        ]);
    } else {
        // If menu_item_id was not provided in the request or is 0, try to get it from order
        if ($menu_item_id <= 0) {
            // First check if the order has menu_item_id
            $order_menu_sql = "SELECT menu_item_id FROM orders WHERE id = ? AND menu_item_id > 0";
            $stmt = mysqli_prepare($conn, $order_menu_sql);
            mysqli_stmt_bind_param($stmt, "i", $order_id);
            mysqli_stmt_execute($stmt);
            $order_menu_result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($order_menu_result) > 0) {
                $order_menu = mysqli_fetch_assoc($order_menu_result);
                $menu_item_id = $order_menu['menu_item_id'];
            } else {
                // Get menu items for this order from order_items
                $menu_items_sql = "SELECT DISTINCT menu_item_id FROM order_items WHERE order_id = ?";
                $stmt = mysqli_prepare($conn, $menu_items_sql);
                mysqli_stmt_bind_param($stmt, "i", $order_id);
                mysqli_stmt_execute($stmt);
                $menu_items_result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($menu_items_result) > 0) {
                    // Use the first menu item from the order
                    $menu_item = mysqli_fetch_assoc($menu_items_result);
                    $menu_item_id = $menu_item['menu_item_id'];
                } else {
                    // If no menu items found, use a default menu item ID
                    $default_menu_sql = "SELECT id FROM menu_items ORDER BY id ASC LIMIT 1";
                    $default_result = mysqli_query($conn, $default_menu_sql);
                    
                    if (mysqli_num_rows($default_result) > 0) {
                        $default_menu = mysqli_fetch_assoc($default_result);
                        $menu_item_id = $default_menu['id'];
                    } else {
                        // Last resort: use ID 1
                        $menu_item_id = 1;
                    }
                }
            }
        }
        
        // Insert rating with menu_item_id
        $insert_sql = "INSERT INTO ratings (order_id, user_id, menu_item_id, rating, comment, created_at) 
                      VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, "iiiis", $order_id, $user_id, $menu_item_id, $rating, $comment);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to submit rating. Please try again later.");
        }

        $rating_id = mysqli_insert_id($conn);

        // Broadcast new rating via WebSocket
        require_once '../includes/websocket_helper.php';
        broadcastNewRating($rating_id);
    }
    
    // Update order's is_rated status
    $update_sql = "UPDATE orders SET is_rated = 1 WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    
    // Commit transaction
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Rating submitted successfully'
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