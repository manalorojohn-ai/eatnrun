<?php
require_once 'config/database.php';

try {
    // Add link column if it doesn't exist
    $check_column = "SHOW COLUMNS FROM notifications LIKE 'link'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows === 0) {
        $alter_table = "ALTER TABLE notifications 
                       ADD COLUMN link VARCHAR(255) DEFAULT NULL AFTER message";
        
        if ($conn->query($alter_table)) {
            echo "Successfully added link column to notifications table\n";
            
            // Update existing order notifications with links
            $update_links = "UPDATE notifications 
                           SET link = CONCAT('orders.php?id=', SUBSTRING_INDEX(message, '#', -1))
                           WHERE type = 'order' AND message LIKE '%Order #%'";
            
            if ($conn->query($update_links)) {
                echo "Successfully updated existing notifications with links\n";
            } else {
                echo "Error updating links: " . $conn->error . "\n";
            }
        } else {
            echo "Error adding link column: " . $conn->error . "\n";
        }
    } else {
        echo "Link column already exists\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    $conn->close();
}
?> 