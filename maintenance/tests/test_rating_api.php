<?php
// Test script to simulate a rating submission
session_start();
require_once './config/db.php';

// Set a test user ID
$_SESSION['user_id'] = 1;

// Get the first order ID from the database
$result = mysqli_query($conn, "SELECT id FROM orders WHERE user_id = 1 LIMIT 1");
if ($row = mysqli_fetch_assoc($result)) {
    $order_id = $row['id'];
} else {
    echo "No orders found for user ID 1.\n";
    exit;
}

// Get a valid menu item ID
$result = mysqli_query($conn, "SELECT id FROM menu_items LIMIT 1");
if ($row = mysqli_fetch_assoc($result)) {
    $menu_item_id = $row['id'];
} else {
    echo "No menu items found.\n";
    exit;
}

// Update the order with the menu item ID
mysqli_query($conn, "UPDATE orders SET menu_item_id = {$menu_item_id} WHERE id = {$order_id}");
echo "Updated order #{$order_id} with menu_item_id = {$menu_item_id}\n";

// Create a function to simulate API requests
function simulateApiRequest($method, $params = [], $jsonBody = null) {
    global $conn;
    
    // Save original globals
    $originalServer = $_SERVER;
    $originalGet = $_GET;
    $originalPost = $_POST;
    
    // Set up the request
    $_SERVER['REQUEST_METHOD'] = $method;
    $_GET = ($method === 'GET') ? $params : [];
    $_POST = ($method === 'POST') ? $params : [];
    
    // Start output buffering
    ob_start();
    
    // Include the API file with our custom input
    if ($jsonBody !== null) {
        // Mock the php://input stream
        require_once 'ratings_api_test.php';
    } else {
        require_once 'ratings_api.php';
    }
    
    // Get the output
    $output = ob_get_clean();
    
    // Restore original globals
    $_SERVER = $originalServer;
    $_GET = $originalGet;
    $_POST = $originalPost;
    
    return $output;
}

// Create a temporary version of the ratings API that uses our test data
$ratingsApiContent = file_get_contents('ratings_api.php');
$testApiContent = str_replace(
    'file_get_contents(\'php://input\')',
    'json_encode(' . var_export([
        'order_id' => $order_id,
        'menu_item_id' => $menu_item_id,
        'rating' => 5,
        'comment' => 'Test rating from test_rating_api.php'
    ], true) . ')',
    $ratingsApiContent
);
file_put_contents('ratings_api_test.php', $testApiContent);

// Test GET request
echo "Simulating GET request to ratings_api.php...\n";
echo "order_id = {$order_id}, menu_item_id = {$menu_item_id}\n";
$getResponse = simulateApiRequest('GET', [
    'order_id' => $order_id,
    'menu_item_id' => $menu_item_id
]);
echo "Response from GET request:\n{$getResponse}\n\n";

// Test POST request
echo "Simulating POST request to ratings_api.php...\n";
$postData = [
    'order_id' => $order_id,
    'menu_item_id' => $menu_item_id,
    'rating' => 5,
    'comment' => 'Test rating from test_rating_api.php'
];
echo "Data: " . json_encode($postData) . "\n";
$postResponse = simulateApiRequest('POST', [], $postData);
echo "Response from POST request:\n{$postResponse}\n\n";

// Clean up
unlink('ratings_api_test.php');

echo "Test completed.\n"; 