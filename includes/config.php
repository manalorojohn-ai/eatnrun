<?php
// Error reporting - production safe
error_reporting(E_ALL);
$is_production = getenv('APP_ENV') === 'production' || getenv('RENDER');
ini_set('display_errors', $is_production ? 0 : 1);
ini_set('log_errors', 1);

// Load .env file if it exists
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!getenv($name)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Session configuration - standardized via init_session.php
require_once __DIR__ . '/helpers/init_session.php';

// Database connection
require_once dirname(__DIR__) . '/config/database/db.php';
// $conn is now available from db.php

// Session handler utilities
require_once dirname(__DIR__) . '/includes/session_handler.php';

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

// Initialize login history table - only if using real DB
if (isset($using_json) && !$using_json && isset($conn)) {
    $create_login_history_table = "CREATE TABLE IF NOT EXISTS login_history (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id),
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    try {
        if ($conn instanceof PDO) {
            $conn->exec($create_login_history_table);
        } elseif ($conn instanceof mysqli) {
            mysqli_query($conn, $create_login_history_table);
        }
    } catch (Exception $e) {
        error_log("Failed to create login_history table: " . $e->getMessage());
    }
}
?> 