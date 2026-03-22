<?php
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Function to check if a table exists
 * @param mysqli $conn - Database connection
 * @param string $table - Table name
 * @return bool - True if table exists, false otherwise
 */
function table_exists($conn, $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return mysqli_num_rows($result) > 0;
}

/**
 * Function to check if a column exists in a table
 * @param mysqli $conn - Database connection
 * @param string $table - Table name
 * @param string $column - Column name
 * @return bool - True if column exists, false otherwise
 */
function column_exists($conn, $table, $column) {
    if (!table_exists($conn, $table)) {
        return false;
    }
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$column'");
    return mysqli_num_rows($result) > 0;
}

/**
 * Function to add a column to a table if it doesn't exist
 * @param mysqli $conn - Database connection
 * @param string $table - Table name
 * @param string $column - Column name
 * @param string $definition - Column definition (e.g. "VARCHAR(50) NOT NULL DEFAULT 'system'")
 * @param string $after - Column after which to add the new column (optional)
 * @return bool - True if column was added or already exists, false on error
 */
function add_column_if_missing($conn, $table, $column, $definition, $after = null) {
    if (!table_exists($conn, $table)) {
        error_log("Table $table does not exist");
        return false;
    }
    
    if (column_exists($conn, $table, $column)) {
        return true; // Column already exists
    }
    
    $after_sql = $after ? "AFTER $after" : "";
    $query = "ALTER TABLE $table ADD COLUMN $column $definition $after_sql";
    
    if (mysqli_query($conn, $query)) {
        error_log("Added column $column to table $table");
        return true;
    } else {
        error_log("Failed to add column $column to table $table: " . mysqli_error($conn));
        return false;
    }
}

/**
 * Function to check all required tables and columns
 * @param mysqli $conn - Database connection
 * @return array - Array of issues found
 */
function check_db_structure($conn) {
    $issues = [];
    
    // Check important tables
    $required_tables = ['users', 'menu_items', 'cart', 'orders', 'order_details', 'notifications'];
    
    foreach ($required_tables as $table) {
        if (!table_exists($conn, $table)) {
            $issues[] = "Table '$table' does not exist";
        }
    }
    
    // Check important columns
    $required_columns = [
        // Table => [[column, definition, after], ...]
        'notifications' => [
            ['type', 'VARCHAR(50) NOT NULL DEFAULT \'system\'', 'message'],
            ['is_read', 'TINYINT(1) DEFAULT 0', 'type']
        ],
        // We'll keep only the essential columns for orders
        'orders' => [
            ['payment_proof', 'VARCHAR(255) NULL', 'payment_method'],
            ['notes', 'TEXT NULL', 'payment_proof']
        ]
    ];
    
    foreach ($required_columns as $table => $columns) {
        if (table_exists($conn, $table)) {
            foreach ($columns as $column_info) {
                list($column, $definition, $after) = $column_info;
                if (!column_exists($conn, $table, $column)) {
                    $issues[] = "Column '$column' does not exist in table '$table'";
                    
                    // Try to fix the issue
                    if (add_column_if_missing($conn, $table, $column, $definition, $after)) {
                        $issues[] = "✓ Fixed: Added column '$column' to table '$table'";
                    } else {
                        $issues[] = "✗ Failed to add column '$column' to table '$table'";
                    }
                }
            }
        }
    }
    
    return $issues;
}

// Add new function to recreate orders tables
function recreate_orders_tables($conn) {
    // Drop existing tables if they exist
    mysqli_query($conn, "DROP TABLE IF EXISTS order_details");
    mysqli_query($conn, "DROP TABLE IF EXISTS orders");
    
    // Create orders table with proper structure
    $create_orders_sql = "CREATE TABLE `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `full_name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `phone` varchar(20) NOT NULL,
        `delivery_address` text NOT NULL,
        `payment_method` varchar(50) NOT NULL,
        `payment_proof` varchar(255) DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `payment_status` varchar(20) DEFAULT 'pending',
        `delivery_notes` text DEFAULT NULL,
        `subtotal` decimal(10,2) DEFAULT 0.00,
        `delivery_fee` decimal(10,2) DEFAULT 0.00,
        `total_amount` decimal(10,2) DEFAULT 0.00,
        `status` varchar(20) NOT NULL DEFAULT 'pending',
        `cancel_reason` text DEFAULT NULL,
        `is_rated` tinyint(1) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `received_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Create order_details table with proper structure
    $create_order_details_sql = "CREATE TABLE `order_details` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `menu_item_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `subtotal` decimal(10,2) GENERATED ALWAYS AS (quantity * price) STORED,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `menu_item_id` (`menu_item_id`),
        CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
        CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Execute the creation queries
    $success = true;
    if (!mysqli_query($conn, $create_orders_sql)) {
        error_log("Error creating orders table: " . mysqli_error($conn));
        $success = false;
    }
    
    if (!mysqli_query($conn, $create_order_details_sql)) {
        error_log("Error creating order_details table: " . mysqli_error($conn));
        $success = false;
    }
    
    return $success;
}

// Check if this script is being run directly
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    echo "<h1>Database Structure Check</h1>";
    
    // Recreate orders tables
    if (recreate_orders_tables($conn)) {
        echo "<p style='color: green;'>✓ Orders tables have been recreated successfully.</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to recreate orders tables.</p>";
    }
    
    // Run the regular structure check
    $issues = check_db_structure($conn);
    
    if (empty($issues)) {
        echo "<p style='color: green;'>✓ All required tables and columns exist.</p>";
    } else {
        echo "<h2>Issues Found:</h2>";
        echo "<ul>";
        foreach ($issues as $issue) {
            $color = strpos($issue, "Fixed") !== false ? "green" : (strpos($issue, "Failed") !== false ? "red" : "orange");
            echo "<li style='color: $color;'>$issue</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><a href='checkout.php' style='display: inline-block; padding: 10px 20px; background-color: #006C3B; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px;'>Return to Checkout</a></p>";
}

// Return issues if included in another file
return isset($issues) ? $issues : [];
?> 