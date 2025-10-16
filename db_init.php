<?php
/**
 * Database initialization script
 * This script will create necessary tables if they don't exist
 */

// Include database connection
require_once 'config/db.php';

// Check if notifications table exists
$check_table = "SHOW TABLES LIKE 'notifications'";
$table_result = mysqli_query($conn, $check_table);

// Create notifications table if it doesn't exist
if (mysqli_num_rows($table_result) == 0) {
    $create_table = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        notification_type VARCHAR(50) DEFAULT 'system',
        link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (mysqli_query($conn, $create_table)) {
        echo "Notifications table created successfully<br>";
    } else {
        echo "Error creating notifications table: " . mysqli_error($conn) . "<br>";
    }
}

// Check if cart table exists
$check_cart_table = "SHOW TABLES LIKE 'cart'";
$cart_table_result = mysqli_query($conn, $check_cart_table);

// Create cart table if it doesn't exist
if (mysqli_num_rows($cart_table_result) == 0) {
    $create_cart_table = "CREATE TABLE cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if (mysqli_query($conn, $create_cart_table)) {
        echo "Cart table created successfully<br>";
    } else {
        echo "Error creating cart table: " . mysqli_error($conn) . "<br>";
    }
}

echo "Database initialization completed";

// Close connection
mysqli_close($conn);
?> 