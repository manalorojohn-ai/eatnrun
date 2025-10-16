<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to rate items']);
    exit;
}

// Check if ratings table exists, create it if it doesn't
$check_table_query = "SHOW TABLES LIKE 'ratings'";
$table_exists = mysqli_query($conn, $check_table_query);

if (mysqli_num_rows($table_exists) == 0) {
    // Create ratings table
    $create_table_query = "CREATE TABLE IF NOT EXISTS ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        rating INT NOT NULL,
        review TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY user_item (user_id, menu_item_id)
    )";
    
    if (!mysqli_query($conn, $create_table_query)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create ratings table: ' . mysqli_error($conn)]);
        exit;
    }
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['item_id']) || !isset($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$item_id = (int)$data['item_id'];
$rating = (int)$data['rating'];
$review = isset($data['review']) ? $data['review'] : '';

try {
    // Check if user has already rated this item
    $check_query = "SELECT id FROM ratings WHERE user_id = ? AND menu_item_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $item_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) > 0) {
        // Update existing rating
        $rating_id = mysqli_fetch_assoc($result)['id'];
        $update_query = "UPDATE ratings SET rating = ?, review = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "isi", $rating, $review, $rating_id);
        $success = mysqli_stmt_execute($update_stmt);
    } else {
        // Insert new rating
        $insert_query = "INSERT INTO ratings (user_id, menu_item_id, rating, review) VALUES (?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "iiis", $user_id, $item_id, $rating, $review);
        $success = mysqli_stmt_execute($insert_stmt);
    }

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Rating submitted successfully']);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to submit rating: ' . mysqli_error($conn)
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?> 