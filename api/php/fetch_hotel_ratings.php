<?php
header('Content-Type: application/json');
require_once 'db_utils.php';

try {
    // Get hotel database connection
    $hotel_conn = get_hotel_db_connection();
    
    // Ensure the rating table exists
    ensure_hotel_rating_table($hotel_conn);
    
    // Insert sample data if table is empty
    $newly_inserted = insert_sample_hotel_ratings($hotel_conn);
    
    // Fetch all ratings
    $query = "SELECT * FROM eatnrun_rating ORDER BY created_at DESC";
    $result = $hotel_conn->query($query);
    
    if (!$result) {
        throw new Exception("Error fetching ratings: " . $hotel_conn->error);
    }
    
    $ratings = [];
    while ($row = $result->fetch_assoc()) {
        $row['source'] = 'hotel';
        $ratings[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Hotel ratings fetched successfully',
        'count' => count($ratings),
        'newly_inserted' => $newly_inserted,
        'data' => $ratings
    ]);
    
    $result->free();
    $hotel_conn->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 