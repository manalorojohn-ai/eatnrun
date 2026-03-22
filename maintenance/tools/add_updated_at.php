<?php
require_once 'config/db.php';

try {
    // Check if column exists
    $check_column = "SHOW COLUMNS FROM orders LIKE 'updated_at'";
    $result = mysqli_query($conn, $check_column);
    
    if (mysqli_num_rows($result) == 0) {
        // Add updated_at column
        $alter_table = "ALTER TABLE orders 
                       ADD COLUMN updated_at TIMESTAMP 
                       DEFAULT CURRENT_TIMESTAMP 
                       ON UPDATE CURRENT_TIMESTAMP 
                       AFTER created_at";
        
        if (mysqli_query($conn, $alter_table)) {
            echo "Successfully added updated_at column to orders table";
        } else {
            echo "Error adding column: " . mysqli_error($conn);
        }
    } else {
        echo "Column updated_at already exists";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 