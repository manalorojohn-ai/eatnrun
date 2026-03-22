<?php
// Test script to check if the rating system works
require_once './config/db.php';

// Check if the ratings_api.php file exists
echo "Checking if ratings_api.php exists: " . (file_exists('ratings_api.php') ? 'YES' : 'NO') . "\n";

// Check if the get-order-menu-item.php file exists
echo "Checking if api/get-order-menu-item.php exists: " . (file_exists('api/get-order-menu-item.php') ? 'YES' : 'NO') . "\n";

// Check if the food-order-history.php file exists
echo "Checking if api/food-order-history.php exists: " . (file_exists('api/food-order-history.php') ? 'YES' : 'NO') . "\n";

// Check if the orders table has menu_item_id column
$result = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'menu_item_id'");
echo "menu_item_id column exists in orders table: " . (mysqli_num_rows($result) > 0 ? 'YES' : 'NO') . "\n";

// Check if the order_details table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'order_details'");
echo "order_details table exists: " . (mysqli_num_rows($result) > 0 ? 'YES' : 'NO') . "\n";

// Check if the order_items table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'order_items'");
echo "order_items table exists: " . (mysqli_num_rows($result) > 0 ? 'YES' : 'NO') . "\n";

// Check if the ratings table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'ratings'");
echo "ratings table exists: " . (mysqli_num_rows($result) > 0 ? 'YES' : 'NO') . "\n";

// Check if there are orders with menu_item_id = 0 or NULL
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE menu_item_id IS NULL OR menu_item_id = 0");
$row = mysqli_fetch_assoc($result);
echo "Orders with menu_item_id = 0 or NULL: " . $row['count'] . "\n";

// Check if there are orders with menu_item_id > 0
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE menu_item_id > 0");
$row = mysqli_fetch_assoc($result);
echo "Orders with menu_item_id > 0: " . $row['count'] . "\n";

// Check if there are ratings
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM ratings");
$row = mysqli_fetch_assoc($result);
echo "Number of ratings: " . $row['count'] . "\n";

// Check the structure of the main.js file
echo "main.js file exists: " . (file_exists('main.js') ? 'YES' : 'NO') . "\n";
if (file_exists('main.js')) {
    $content = file_get_contents('main.js');
    echo "main.js contains rateOrder function: " . (strpos($content, 'function rateOrder') !== false ? 'YES' : 'NO') . "\n";
    echo "main.js contains fetchRatingAndShowModal function: " . (strpos($content, 'function fetchRatingAndShowModal') !== false ? 'YES' : 'NO') . "\n";
}

echo "Test completed.\n"; 