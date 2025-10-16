<?php
require_once 'config/db.php';

try {
    echo "Checking order_details table...\n";
    
    // Check if table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'order_details'");
    
    if (mysqli_num_rows($table_check) == 0) {
        echo "Creating order_details table...\n";
        
        $create_table_sql = "CREATE TABLE order_details (
            id INT PRIMARY KEY AUTO_INCREMENT,
            order_id INT NOT NULL,
            menu_item_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT
        )";
        
        if (mysqli_query($conn, $create_table_sql)) {
            echo "Successfully created order_details table.\n";
        } else {
            throw new Exception("Failed to create table: " . mysqli_error($conn));
        }
    } else {
        echo "order_details table already exists.\n";
    }
    
    echo "Database structure check completed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?> 