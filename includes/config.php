<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Session configuration - use a local temp directory
// IMPORTANT: session_save_path MUST be called BEFORE session_start()
$sessionDir = dirname(__DIR__) . '/tmp/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0777, true);
}

// Only set session path if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    session_save_path($sessionDir);
    
    // Configure session timeout (set to 7 days = 604800 seconds)
    ini_set('session.gc_maxlifetime', 604800);
    ini_set('session.cookie_lifetime', 604800);
    
    // Configure session cookies for better persistence
    session_set_cookie_params([
        'lifetime' => 604800,      // 7 days
        'path' => '/',
        'domain' => '',
        'secure' => false,         // Set to true if using HTTPS
        'httponly' => true,        // Prevent JavaScript access
        'samesite' => 'Lax'       // CSRF protection
    ]);
    
    // Regenerate session ID on login for security
    session_name('EATNRUN_SESSION');
    session_start();
    
    // Register session cleanup for old files
    register_shutdown_function('session_write_close');
}

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
if (isset($using_json) && !$using_json) {
    $create_login_history_table = "CREATE TABLE IF NOT EXISTS login_history (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id),
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_login_history_table);
}
?> 