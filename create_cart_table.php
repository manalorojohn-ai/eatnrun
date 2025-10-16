<?php
require_once 'config/db.php';

try {
    // Check if table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'cart'");
    
    if (mysqli_num_rows($table_check) == 0) {
        // Create cart table
        $create_table_sql = "CREATE TABLE cart (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            special_instructions TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES menu_items(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product (user_id, product_id)
        )";
        
        if (mysqli_query($conn, $create_table_sql)) {
            echo "Successfully created cart table.\n";
        } else {
            throw new Exception("Failed to create cart table: " . mysqli_error($conn));
        }
    } else {
        // Check if special_instructions column exists
        $column_check = mysqli_query($conn, "SHOW COLUMNS FROM cart LIKE 'special_instructions'");
        if (mysqli_num_rows($column_check) == 0) {
            // Add the column if it doesn't exist
            $add_column_sql = "ALTER TABLE cart ADD COLUMN special_instructions TEXT AFTER quantity";
            if (mysqli_query($conn, $add_column_sql)) {
                echo "Added special_instructions column to cart table.\n";
            } else {
                throw new Exception("Failed to add special_instructions column: " . mysqli_error($conn));
            }
        }
        
        echo "Cart table already exists.\n";
    }
    
    echo "Database structure check completed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    mysqli_close($conn);
}
?> 