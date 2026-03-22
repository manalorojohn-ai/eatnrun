<?php
require_once '../config/db.php';

function get_hotel_db_connection() {
    $hotel_db = new mysqli('localhost', 'root', '', 'hotel_management');
    
    if ($hotel_db->connect_error) {
        throw new Exception("Connection failed: " . $hotel_db->connect_error);
    }
    
    return $hotel_db;
}

function ensure_hotel_rating_table($conn) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS eatnrun_rating (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rating INT NOT NULL,
        comment TEXT,
        order_id VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($create_table_sql)) {
        throw new Exception("Error creating table: " . $conn->error);
    }
}

function insert_sample_hotel_ratings($conn) {
    // Check if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM eatnrun_rating");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $sample_data = [
            [
                'rating' => 5,
                'comment' => 'Excellent service!',
                'order_id' => '101'
            ],
            [
                'rating' => 4,
                'comment' => 'Good food, would order again',
                'order_id' => '102'
            ],
            [
                'rating' => 5,
                'comment' => 'Very fast delivery',
                'order_id' => '103'
            ]
        ];
        
        $stmt = $conn->prepare("INSERT INTO eatnrun_rating (rating, comment, order_id) VALUES (?, ?, ?)");
        
        foreach ($sample_data as $data) {
            $stmt->bind_param("iss", $data['rating'], $data['comment'], $data['order_id']);
            $stmt->execute();
        }
        
        return $stmt->affected_rows;
    }
    
    return 0;
}
?> 