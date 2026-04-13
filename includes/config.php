<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once dirname(__DIR__) . '/config/database/db.php';
// $conn is now available from db.php

// Site configuration
define('SITE_NAME', 'Eat&Run');
define('SITE_URL', 'http://192.168.123.44:3000');

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

// Initialize login history table (now using shimmed function)
$create_login_history_table = "CREATE TABLE IF NOT EXISTS login_history (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $create_login_history_table);
?> 