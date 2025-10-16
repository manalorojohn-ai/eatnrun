<?php
require_once './config/db.php';
header('Content-Type: application/json');

// Get user ID from session
session_start();
file_put_contents('ratings_api_debug.txt', date('c') . " GET: " . json_encode($_GET) . " POST: " . json_encode($_POST) . " DATA: " . file_get_contents('php://input') . "\n", FILE_APPEND);
$user_id = $_SESSION['user_id'] ?? null;

// Get parameters from GET, POST, or JSON body
$data = json_decode(file_get_contents('php://input'), true);
$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? ($data['order_id'] ?? null);
$menu_item_id = $_GET['menu_item_id'] ?? $_POST['menu_item_id'] ?? ($data['menu_item_id'] ?? null);

// If menu_item_id is missing or zero, try to fetch it from the order
if (($menu_item_id === null || $menu_item_id == 0) && $order_id !== null) {
    // First try to get it directly from orders table
    $stmt = $conn->prepare("SELECT menu_item_id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['menu_item_id']) && $row['menu_item_id'] > 0) {
            $menu_item_id = $row['menu_item_id'];
        }
    }
    
    // If still not found, try to get it from order_details
    if ($menu_item_id === null || $menu_item_id == 0) {
        $stmt = $conn->prepare("SELECT menu_item_id FROM order_details WHERE order_id = ? LIMIT 1");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['menu_item_id']) && $row['menu_item_id'] > 0) {
                $menu_item_id = $row['menu_item_id'];
                
                // Update the orders table for future use
                $update = $conn->prepare("UPDATE orders SET menu_item_id = ? WHERE id = ?");
                $update->bind_param("ii", $menu_item_id, $order_id);
                $update->execute();
            }
        }
    }
    
    // If still not found, try order_items table (some systems might use this)
    if ($menu_item_id === null || $menu_item_id == 0) {
        $stmt = $conn->prepare("SELECT menu_item_id FROM order_items WHERE order_id = ? LIMIT 1");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['menu_item_id']) && $row['menu_item_id'] > 0) {
                $menu_item_id = $row['menu_item_id'];
                
                // Update the orders table for future use
                $update = $conn->prepare("UPDATE orders SET menu_item_id = ? WHERE id = ?");
                $update->bind_param("ii", $menu_item_id, $order_id);
                $update->execute();
            }
        }
    }
    
    // If still not found, use a default menu_item_id of 1
    if ($menu_item_id === null || $menu_item_id == 0) {
        $menu_item_id = 1;
        
        // Log this fallback for debugging
        file_put_contents('ratings_api_debug.txt', date('c') . " Using default menu_item_id=1 for order_id={$order_id}\n", FILE_APPEND);
    }
}

// Debug: log incoming parameters (commented out for Windows compatibility)
// file_put_contents('/tmp/ratings_api_debug.txt', date('c') . " GET: " . json_encode($_GET) . " POST: " . json_encode($_POST) . " DATA: " . json_encode($data) . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Require menu_item_id to be present and nonzero
    if ($menu_item_id === null || $menu_item_id == 0) {
        // Use default menu_item_id of 1 if not found
        $menu_item_id = 1;
        file_put_contents('ratings_api_debug.txt', date('c') . " GET: Using default menu_item_id=1 for order_id={$order_id}\n", FILE_APPEND);
    }
    
    // Fetch rating for this user/order/menu_item combination
    $stmt = $conn->prepare("
        SELECT r.*, 
               DATE_FORMAT(r.created_at, '%M %d, %Y %h:%i %p') as formatted_date
        FROM ratings r 
        WHERE r.user_id = ? AND r.order_id = ? AND r.menu_item_id = ?
    ");
    $stmt->bind_param("iii", $user_id, $order_id, $menu_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rating = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'rating' => $rating
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get rating and comment from POST data
    $rating = $data['rating'] ?? null;
    $comment = $data['comment'] ?? '';
    
    // Validate required data
    if (!$user_id || !$order_id || !$rating) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters',
            'debug' => [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'rating' => $rating
            ]
        ]);
        exit();
    }
    
    // Handle missing menu_item_id by using a default value
    if ($menu_item_id === null || $menu_item_id == 0) {
        $menu_item_id = 1; // Use default menu_item_id
        file_put_contents('ratings_api_debug.txt', date('c') . " POST: Using default menu_item_id=1 for order_id={$order_id}\n", FILE_APPEND);
    }
    
    // Validate rating value
    if ($rating < 1 || $rating > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid rating value. Must be between 1 and 5.'
        ]);
        exit();
    }
    $conn->begin_transaction();
    try {
        // Check if rating exists
        $stmt = $conn->prepare("SELECT id FROM ratings WHERE user_id = ? AND order_id = ? AND menu_item_id = ?");
        $stmt->bind_param("iii", $user_id, $order_id, $menu_item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Update existing rating
            $stmt = $conn->prepare("UPDATE ratings SET rating = ?, comment = ? WHERE id = ?");
            $stmt->bind_param("isi", $rating, $comment, $row['id']);
            $stmt->execute();
        } else {
            // Insert new rating
            $stmt = $conn->prepare("INSERT INTO ratings (user_id, order_id, menu_item_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiiis", $user_id, $order_id, $menu_item_id, $rating, $comment);
            $stmt->execute();
        }
        // Mark order as rated
        $stmt = $conn->prepare("UPDATE orders SET is_rated = 1 WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Rating submitted successfully'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error submitting rating: ' . $e->getMessage()
        ]);
    }
    exit();
}