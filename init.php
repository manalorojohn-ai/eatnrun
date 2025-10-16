<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
$conn = require_once __DIR__ . '/config/db.php';

// Handle database connection error
if (!$conn) {
    // Clear any existing output
    if (ob_get_level()) ob_end_clean();
    
    // Set content type
    header('Content-Type: text/html; charset=utf-8');
    
    // Show appropriate error page
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
        require_once __DIR__ . '/admin/error.php';
    } else {
        require_once __DIR__ . '/error.php';
    }
    exit();
} 