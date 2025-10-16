<?php
// Script to update menu_item_id for existing orders
require_once './config/db.php';

echo "Starting update of menu_item_id for existing orders...\n";

// First, update from order_details table
$sql1 = "UPDATE orders o
        JOIN (
            SELECT order_id, menu_item_id 
            FROM order_details 
            GROUP BY order_id 
            HAVING COUNT(*) = 1
        ) od ON o.id = od.order_id
        SET o.menu_item_id = od.menu_item_id
        WHERE o.menu_item_id IS NULL OR o.menu_item_id = 0";

if (mysqli_query($conn, $sql1)) {
    $affected_rows = mysqli_affected_rows($conn);
    echo "Updated {$affected_rows} orders from order_details table.\n";
} else {
    echo "Error updating from order_details: " . mysqli_error($conn) . "\n";
}

// Check if order_items table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'order_items'");
if (mysqli_num_rows($result) > 0) {
    // Then, update from order_items table
    $sql2 = "UPDATE orders o
            JOIN (
                SELECT order_id, menu_item_id 
                FROM order_items 
                GROUP BY order_id 
                HAVING COUNT(*) = 1
            ) oi ON o.id = oi.order_id
            SET o.menu_item_id = oi.menu_item_id
            WHERE o.menu_item_id IS NULL OR o.menu_item_id = 0";

    if (mysqli_query($conn, $sql2)) {
        $affected_rows = mysqli_affected_rows($conn);
        echo "Updated {$affected_rows} orders from order_items table.\n";
    } else {
        echo "Error updating from order_items: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "order_items table does not exist, skipping that update.\n";
}

// Check how many orders still have menu_item_id = NULL or 0
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE menu_item_id IS NULL OR menu_item_id = 0");
$row = mysqli_fetch_assoc($result);
echo "Orders with menu_item_id = NULL or 0 after update: {$row['count']}\n";

echo "Update completed.\n"; 