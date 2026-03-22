<?php
require_once 'config/db.php';

$item_id = $_GET['item_id'] ?? 0;
$response = [
    'reviews' => [],
    'average_rating' => 0
];

try {
    // Get reviews for the item
    $stmt = $conn->prepare("
        SELECT r.*, u.name as user_name 
        FROM ratings r
        JOIN users u ON r.user_id = u.id
        WHERE r.menu_item_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$item_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average rating
    if (!empty($reviews)) {
        $total = array_sum(array_column($reviews, 'rating'));
        $response['average_rating'] = $total / count($reviews);
    }

    $response['reviews'] = $reviews;
    
} catch (PDOException $e) {
    error_log("Error fetching reviews: " . $e->getMessage());
    http_response_code(500);
    $response['error'] = "Failed to load reviews";
}

header('Content-Type: application/json');
echo json_encode($response); 