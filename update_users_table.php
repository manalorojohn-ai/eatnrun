<?php
require_once 'config/database.php';

// Check if reset_token columns exist
$check_reset_token = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'reset_token'");
$check_reset_token_expiry = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'reset_token_expiry'");

if (mysqli_num_rows($check_reset_token) == 0 || mysqli_num_rows($check_reset_token_expiry) == 0) {
    // Add reset token columns if they don't exist
    $alter_users = "ALTER TABLE users 
                    ADD COLUMN reset_token VARCHAR(6) DEFAULT NULL,
                    ADD COLUMN reset_token_expiry DATETIME DEFAULT NULL";
                    
    if (mysqli_query($conn, $alter_users)) {
        echo "Reset token columns added successfully.";
    } else {
        echo "Error adding reset token columns: " . mysqli_error($conn);
    }
} else {
    echo "Reset token columns already exist.";
}
?> 