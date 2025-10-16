<?php
header('Content-Type: application/json');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once '../../config/database.php';

// Get the last timestamp from the request (if any)
$last_timestamp = isset($_GET['last_timestamp']) ? $_GET['last_timestamp'] : null;

// Query to get new ratings since the last timestamp
$query = "SELECT r.*, u.username, m.name as menu_item_name, o.id as order_number 
          FROM ratings r
          LEFT JOIN users u ON r.user_id = u.id
          LEFT JOIN menu_items m ON r.menu_item_id = m.id
          LEFT JOIN orders o ON r.order_id = o.id ";

// If last timestamp is provided, only get ratings after that time
if ($last_timestamp) {
    $last_timestamp = mysqli_real_escape_string($conn, $last_timestamp);
    $query .= "WHERE r.created_at > '$last_timestamp' ";
}

$query .= "ORDER BY r.created_at DESC LIMIT 20";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
    exit();
}

$ratings = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Sanitize data before sending to client
    $ratings[] = [
        'id' => (int)$row['id'],
        'user_id' => (int)$row['user_id'],
        'menu_item_id' => (int)$row['menu_item_id'],
        'order_id' => (int)$row['order_id'],
        'rating' => (int)$row['rating'],
        'comment' => htmlspecialchars($row['comment'] ?? ''),
        'created_at' => $row['created_at'],
        'username' => htmlspecialchars($row['username'] ?? 'Anonymous'),
        'menu_item_name' => htmlspecialchars($row['menu_item_name'] ?? 'Unknown Item'),
        'order_number' => htmlspecialchars($row['order_number'] ?? 'N/A')
    ];
}

// Get the latest timestamp for next request
$latest_timestamp = !empty($ratings) ? $ratings[0]['created_at'] : ($last_timestamp ?? date('Y-m-d H:i:s'));

echo json_encode([
    'success' => true,
    'ratings' => $ratings,
    'latest_timestamp' => $latest_timestamp,
    'count' => count($ratings)
]); 