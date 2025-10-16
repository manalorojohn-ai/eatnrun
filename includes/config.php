<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'food_ordering');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Site configuration
define('SITE_NAME', 'Eat&Run');
define('SITE_URL', 'http://localhost:3000');

// CSS Variables
define('CSS_VARS', [
    'primary' => '#006C3B',
    'primary-dark' => '#005530',
    'primary-light' => '#e8f5e9',
    'accent' => '#FFC107',
    'text-dark' => '#333333',
    'text-light' => '#666666',
    'white' => '#ffffff',
    'bg-light' => '#f8f9fa',
    'border-color' => '#e0e0e0',
    'success' => '#28a745'
]);

// Other Configuration Constants
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Time zone
date_default_timezone_set('Asia/Manila');

// Global functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function format_date($date) {
    return date('F j, Y, g:i a', strtotime($date));
}

// Initialize login history table if it doesn't exist
$create_login_history_table = "CREATE TABLE IF NOT EXISTS login_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

$conn->query($create_login_history_table);
?> 