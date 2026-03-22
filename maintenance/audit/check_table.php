<?php
require_once './config/db.php';

// Check if the orders table has menu_item_id column
$result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'menu_item_id'");
$exists = (mysqli_num_rows($result) > 0);

echo "menu_item_id column exists in orders table: " . ($exists ? "YES" : "NO") . "\n";

// Show all columns in orders table
echo "\nAll columns in orders table:\n";
$result = mysqli_query($conn, "SHOW COLUMNS FROM orders");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

// Check if order_details table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'order_details'");
$exists = (mysqli_num_rows($result) > 0);

echo "\norder_details table exists: " . ($exists ? "YES" : "NO") . "\n";

// Show all columns in order_details table if it exists
if ($exists) {
    echo "\nAll columns in order_details table:\n";
    $result = mysqli_query($conn, "SHOW COLUMNS FROM order_details");
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
} 