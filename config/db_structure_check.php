<?php
// Create order_logs table if it doesn't exist
$order_logs_table = "CREATE TABLE IF NOT EXISTS order_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $order_logs_table)) {
    error_log("Error creating order_logs table: " . mysqli_error($conn));
}

// Function to check and update database structure
function check_db_structure($conn) {
    // Check if order_logs table exists
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'order_logs'");
    if (mysqli_num_rows($table_check) == 0) {
        // Create order_logs table
        $order_logs_table = "CREATE TABLE IF NOT EXISTS order_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            user_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!mysqli_query($conn, $order_logs_table)) {
            error_log("Error creating order_logs table: " . mysqli_error($conn));
        }
    }

    // Check if orders table has all required columns
    $orders_columns = [
        'status' => "ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending'",
        'payment_status' => "ALTER TABLE orders MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending'",
        'is_rated' => "ALTER TABLE orders ADD COLUMN is_rated TINYINT(1) DEFAULT 0 AFTER status"
    ];

    $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM orders");
    $existing_columns = [];
    while ($row = mysqli_fetch_assoc($columns_result)) {
        $existing_columns[] = $row['Field'];
    }

    foreach ($orders_columns as $column => $alter_sql) {
        if (!in_array($column, $existing_columns)) {
            if (!mysqli_query($conn, $alter_sql)) {
                error_log("Error adding column $column to orders table: " . mysqli_error($conn));
            }
        }
    }
} 