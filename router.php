<?php
/**
 * Router for PHP Built-in Server
 * Handles routing for the development server since it doesn't support .htaccess
 * 
 * Run: php -S localhost:3000 router.php
 */

$requested_file = $_SERVER['REQUEST_URI'];
$requested_file = explode('?', $requested_file)[0]; // Remove query string

// Remove leading slash
$path = ltrim($requested_file, '/');

// DEBUG: Log all requests
error_log("========== ROUTER DEBUG ==========");
error_log("Requested URI: " . $_SERVER['REQUEST_URI']);
error_log("Requested path: " . $path);
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("===================================");

// List of static file extensions that should be served directly
$static_extensions = ['.js', '.css', '.jpg', '.jpeg', '.png', '.gif', '.svg', '.woff', '.woff2', '.ttf', '.eot', '.ico', '.json'];

// Check if the request is for a static file
foreach ($static_extensions as $ext) {
    if (substr($path, -strlen($ext)) === $ext) {
        // Serve static file if it exists
        $file = __DIR__ . '/' . $path;
        if (file_exists($file) && is_file($file)) {
            error_log("Serving static file: " . $path);
            return false; // Let the server serve the file
        }
    }
}

// Special handling for /assets directory
if (strpos($path, 'assets/') === 0) {
    $file = __DIR__ . '/' . $path;
    if (file_exists($file) && is_file($file)) {
        error_log("Serving asset: " . $path);
        return false; // Let the server serve the file
    }
}

// Handle direct file requests (.php files that exist)
if (strpos($path, '.php') !== false) {
    $file = __DIR__ . '/' . $path;
    if (file_exists($file) && is_file($file)) {
        error_log("Serving direct PHP file: " . $path);
        require $file;
        return true;
    }
}

// Route everything else through index.php
// This simulates what .htaccess does with mod_rewrite
if (strpos($path, '.php') === false && !empty($path)) {
    error_log("Searching for route: " . $path);
    
    // Define search folders (same as in index.php)
    $search_folders = [
        'pages/' . $path . '.php',
        'pages/auth/' . $path . '.php',
        'pages/account/' . $path . '.php',
        'pages/ordering/' . $path . '.php',
        'pages/info/' . $path . '.php',
        'pages/reviews/' . $path . '.php',
        'actions/' . $path . '.php',
        'actions/auth/' . $path . '.php',
        'actions/account/' . $path . '.php',
        'actions/cart/' . $path . '.php',
        'actions/order/' . $path . '.php',
        'actions/notifications/' . $path . '.php',
        'actions/reviews/' . $path . '.php',
        'admin/pages/' . $path . '.php',
    ];
    
    foreach ($search_folders as $php_file) {
        $full_path = __DIR__ . '/' . $php_file;
        error_log("Checking: " . $php_file);
        if (file_exists($full_path) && is_file($full_path)) {
            error_log("✓ FOUND! Loading: " . $php_file);
            $_SERVER['REQUEST_URI'] = '/' . $path;
            require $full_path;
            return true;
        }
    }
    
    error_log("✗ NOT FOUND in any folder, loading index.php as fallback");
}

// Default: route to index.php
error_log("Loading index.php as default route");
$_SERVER['REQUEST_URI'] = '/' . $path;
require __DIR__ . '/index.php';
