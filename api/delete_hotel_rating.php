<?php
header('Content-Type: application/json');

// Include database utilities
require_once 'db_utils.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Rating ID is required'
    ]);
    exit;
}

$rating_id = intval($_GET['id']);

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Get hotel database connection
    $hotel_conn = get_hotel_db_connection();
    
    // Ensure the rating table exists
    ensure_hotel_rating_table($hotel_conn);
    
    // Prepare and execute delete statement
    $stmt = $hotel_conn->prepare("DELETE FROM eatnrun_rating WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $hotel_conn->error);
    }
    
    $stmt->bind_param("i", $rating_id);
    $result = $stmt->execute();
    
    if (!$result) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    $hotel_conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => ($affected_rows > 0) ? 'Hotel rating deleted successfully' : 'No rating found with the provided ID',
        'affected_rows' => $affected_rows
    ]);
    
} catch (Exception $e) {
    // Handle any errors
    if (isset($hotel_conn)) {
        $hotel_conn->close();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 