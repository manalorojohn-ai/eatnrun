<?php
require_once '../includes/config.php';
require_once '../config/db.php';

// Check if phone column exists
$result = $conn->query("SHOW COLUMNS FROM orders LIKE 'phone'");
$exists = $result->num_rows;

if (!$exists) {
    // Add phone column
    $sql = "ALTER TABLE orders ADD COLUMN phone VARCHAR(20) NULL AFTER delivery_address";
    
    if ($conn->query($sql) === TRUE) {
        echo "Phone column added successfully to orders table\n";
    } else {
        echo "Error adding phone column: " . $conn->error . "\n";
    }
} else {
    echo "Phone column already exists in orders table\n";
}

$conn->close();
?> 