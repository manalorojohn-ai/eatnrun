<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Connect to remote hotel management database
    $remote_db = mysqli_connect("192.168.0.101", "root", "", "hotel_management");
    
    if (!$remote_db) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Set charset to handle special characters properly
    mysqli_set_charset($remote_db, "utf8mb4");

    // Fetch ratings from remote database
    $query = "SELECT 
        id,
        rating,
        comment,
        created_at,
        updated_at,
        order_id
        FROM eatrun_rating 
        ORDER BY created_at DESC";

    $result = mysqli_query($remote_db, $query);

    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($remote_db));
    }

    $ratings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ratings[] = [
            'id' => $row['id'],
            'rating' => $row['rating'],
            'comment' => $row['comment'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'order_id' => $row['order_id'],
            'source' => 'hotel'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $ratings
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($remote_db) && $remote_db) {
        mysqli_close($remote_db);
    }
}
?>
