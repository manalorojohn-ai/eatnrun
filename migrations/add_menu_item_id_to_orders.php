<?php
// Migration script to add menu_item_id column to orders table

require_once __DIR__ . '/../config/db.php';

echo "Starting migration: Add menu_item_id column to orders table\n";

// Check if the column already exists
$checkColumnQuery = "SHOW COLUMNS FROM orders LIKE 'menu_item_id'";
$columnExists = mysqli_query($conn, $checkColumnQuery);

if (mysqli_num_rows($columnExists) > 0) {
    echo "Column menu_item_id already exists in orders table.\n";
} else {
    // Add the column
    $addColumnQuery = "ALTER TABLE orders 
                      ADD COLUMN menu_item_id INT(11) NULL AFTER received_at, 
                      ADD CONSTRAINT fk_orders_menu_item 
                      FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) 
                      ON DELETE SET NULL ON UPDATE CASCADE";
    
    if (mysqli_query($conn, $addColumnQuery)) {
        echo "Successfully added menu_item_id column to orders table.\n";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "\n";
        exit(1);
    }
}

// Update existing orders by getting menu_item_id from order_details
$updateQuery = "UPDATE orders o
               JOIN (
                   SELECT order_id, menu_item_id 
                   FROM order_details 
                   GROUP BY order_id 
                   HAVING COUNT(*) = 1
               ) od ON o.id = od.order_id
               SET o.menu_item_id = od.menu_item_id
               WHERE o.menu_item_id IS NULL";

if (mysqli_query($conn, $updateQuery)) {
    $affectedRows = mysqli_affected_rows($conn);
    echo "Updated menu_item_id for {$affectedRows} existing orders.\n";
} else {
    echo "Error updating existing orders: " . mysqli_error($conn) . "\n";
}

echo "Migration completed successfully.\n"; 