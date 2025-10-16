<?php
require_once 'config/db.php';

// Create notifications table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $create_table_query)) {
    echo "Notifications table created successfully or already exists.";
} else {
    echo "Error creating notifications table: " . mysqli_error($conn);
}
?> 