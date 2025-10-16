<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to submit a review']);
    exit;
}

// Validate request method and content type
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Validate review data
if (!isset($data['menuItemId']) || !isset($data['rating'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$menuItemId = filter_var($data['menuItemId'], FILTER_VALIDATE_INT);
$rating = filter_var($data['rating'], FILTER_VALIDATE_INT);
$comment = isset($data['comment']) ? trim($data['comment']) : '';
$userId = $_SESSION['user_id'];

// Validate input values
if (!$menuItemId || !$rating || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid rating or menu item ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if menu item exists
    $stmt = $pdo->prepare("SELECT id FROM menu_items WHERE id = ?");
    $stmt->execute([$menuItemId]);
    if (!$stmt->fetch()) {
        throw new Exception('Menu item not found');
    }

    // Check if user has already reviewed this item
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND menu_item_id = ?");
    $stmt->execute([$userId, $menuItemId]);
    if ($stmt->fetch()) {
        throw new Exception('You have already reviewed this item');
    }

    // Insert review
    $stmt = $pdo->prepare("
        INSERT INTO reviews (user_id, menu_item_id, rating, comment)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $menuItemId, $rating, $comment]);

    // Get updated menu item statistics
    $stmt = $pdo->prepare("
        SELECT average_rating, total_reviews 
        FROM menu_items 
        WHERE id = ?
    ");
    $stmt->execute([$menuItemId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    // Return success response with updated stats
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully',
        'stats' => [
            'averageRating' => round($stats['average_rating'], 2),
            'totalReviews' => $stats['total_reviews']
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
} 