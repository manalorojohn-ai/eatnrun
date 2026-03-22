o<?php
require_once 'config/db.php';

try {
    // Check if table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'ratings'");
    
    if (mysqli_num_rows($table_check) == 0) {
        // Create ratings table
        $create_table_sql = "CREATE TABLE ratings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            item_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_item (user_id, item_id)
        )";
        
        if (mysqli_query($conn, $create_table_sql)) {
            echo "Successfully created ratings table.\n";
        } else {
            throw new Exception("Failed to create ratings table: " . mysqli_error($conn));
        }
    } else {
        echo "Ratings table already exists.\n";
    }
    
    echo "Database structure check completed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    mysqli_close($conn);
}
?> 