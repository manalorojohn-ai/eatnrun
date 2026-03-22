<?php
// Use the correct path to database configuration
$db_config_path = __DIR__ . '/../config/db.php';
$db_config_alt_path = __DIR__ . '/../config/database.php';

if (file_exists($db_config_path)) {
    require_once $db_config_path;
} elseif (file_exists($db_config_alt_path)) {
    require_once $db_config_alt_path;
} else {
    die("Database configuration file not found.");
}

// Create admin_notifications table if it doesn't exist
$create_table_query = "CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'order',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    reference_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($conn, $create_table_query)) {
    echo "Admin notifications table created successfully\n";
    
    // Insert a test notification
    $test_query = "INSERT INTO admin_notifications (title, message, type, link) 
                  VALUES ('Welcome to Admin Panel', 'This is a test notification. You can view customer orders here.', 'system', 'orders.php')";
    if (mysqli_query($conn, $test_query)) {
        echo "Test notification added successfully\n";
    } else {
        echo "Error adding test notification: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
echo "Setup complete!\n";
?> 