<?php
// This file checks for PHP errors that might affect JSON output

// First check error settings
echo "<h1>PHP Error Settings</h1>";
echo "<p>Display errors: " . ini_get('display_errors') . "</p>";
echo "<p>Error reporting level: " . error_reporting() . "</p>";
echo "<p>log_errors: " . ini_get('log_errors') . "</p>";
echo "<p>error_log: " . ini_get('error_log') . "</p>";

// Check for common errors
echo "<h1>Error Check</h1>";
$errors = [];

// Check if includes/functions.php exists
if (!file_exists('includes/functions.php')) {
    $errors[] = "includes/functions.php file is missing";
}

// Check if config/db.php exists
if (!file_exists('config/db.php')) {
    $errors[] = "config/db.php file is missing";
}

// Check if order_logs table exists
try {
    require_once 'config/db.php';
    if ($conn) {
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'order_logs'");
        if (mysqli_num_rows($check_table) == 0) {
            $errors[] = "order_logs table does not exist in the database";
        }
        
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'notifications'");
        if (mysqli_num_rows($check_table) == 0) {
            $errors[] = "notifications table does not exist in the database";
        }
    } else {
        $errors[] = "Database connection failed";
    }
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Display any errors found
if (empty($errors)) {
    echo "<p style='color: green;'>✓ No common errors detected</p>";
} else {
    echo "<ul style='color: red;'>";
    foreach ($errors as $error) {
        echo "<li>✗ {$error}</li>";
    }
    echo "</ul>";
}

// Test JSON generation
echo "<h1>JSON Test</h1>";
$test_data = [
    'success' => true,
    'message' => 'Test successful',
    'data' => [
        'test_number' => 123,
        'test_string' => 'Hello world',
        'test_bool' => true,
        'test_null' => null
    ]
];

echo "<pre>";
echo htmlspecialchars(json_encode($test_data, JSON_PRETTY_PRINT));
echo "</pre>";

// Check if headers are modified somewhere
echo "<h1>Headers Check</h1>";
$headers_list = headers_list();
echo "<pre>";
print_r($headers_list);
echo "</pre>";
?> 