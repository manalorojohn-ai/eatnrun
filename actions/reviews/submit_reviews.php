<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to submit reviews']);
    exit;
}

// Ensure request is POST and has JSON content
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_SERVER['CONTENT_TYPE']) || 
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['reviews']) || !is_array($data['reviews'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid review data']);
    exit;
}

$userId = $_SESSION['user_id'];
$updatedItems = [];
$pdo = getConnection();

try {
    $pdo->beginTransaction();

    // Prepare statements
    $insertReview = $pdo->prepare("
        INSERT INTO reviews (user_id, menu_item_id, rating, comment, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $updateMenuItem = $pdo->prepare("
        UPDATE menu_items 
        SET average_rating = (
            SELECT AVG(rating) 
            FROM reviews 
            WHERE menu_item_id = ?
        ),
        total_reviews = (
            SELECT COUNT(*) 
            FROM reviews 
            WHERE menu_item_id = ?
        )
        WHERE id = ?
    ");

    $getUpdatedStats = $pdo->prepare("
        SELECT id, average_rating, total_reviews 
        FROM menu_items 
        WHERE id = ?
    ");

    foreach ($data['reviews'] as $review) {
        // Validate review data
        if (!isset($review['menu_item_id'], $review['rating']) ||
            !is_numeric($review['menu_item_id']) ||
            !is_numeric($review['rating']) ||
            $review['rating'] < 1 || 
            $review['rating'] > 5) {
            throw new Exception('Invalid review data');
        }

        $menuItemId = (int)$review['menu_item_id'];
        $rating = (int)$review['rating'];
        $comment = isset($review['comment']) ? trim($review['comment']) : '';

        // Insert review
        $insertReview->execute([$userId, $menuItemId, $rating, $comment]);

        // Update menu item stats
        $updateMenuItem->execute([$menuItemId, $menuItemId, $menuItemId]);

        // Get updated stats
        $getUpdatedStats->execute([$menuItemId]);
        $updatedItems[] = $getUpdatedStats->fetch(PDO::FETCH_ASSOC);
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Reviews submitted successfully',
        'updated_items' => $updatedItems
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit reviews: ' . $e->getMessage()
    ]);
} 