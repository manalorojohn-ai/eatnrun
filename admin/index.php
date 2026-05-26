<?php
/**
 * Admin Router for Eat&Run
 * Routes admin requests to organized subfolders in administrative/pages.
 * 
 * This router handles:
 * - Admin-specific URL rewriting
 * - Request parsing for admin paths
 * - File searching in admin folders
 * - Error handling and 404 responses
 * - Admin authentication (optional)
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);

// Basic routing logic
$request = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir = str_replace('index.php', '', $script_name);

// Get the actual request path after the base directory
$path = str_replace($base_dir, '', $request);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Strip 'admin/' prefix if present (when called from root router)
if (str_starts_with($path, 'admin/')) {
    $path = substr($path, 6);
} elseif ($path === 'admin') {
    $path = '';
}

// Default to dashboard
if (empty($path) || $path === 'index.php' || $path === 'index') {
    require_once 'pages/dashboard.php';
    exit();
}

// Special case for login
if ($path === 'login' || $path === 'login.php') {
    require_once 'pages/auth/login.php';
    exit();
}

// Check for the page in the /pages folder and its subfolders
$page_file = $path;
if (!str_ends_with($page_file, '.php')) {
    $page_file .= '.php';
}

$search_folders = ['', 'auth', 'products', 'orders', 'users', 'reviews', 'reports'];
$found = false;

foreach ($search_folders as $folder) {
    $test_path = 'pages/' . (empty($folder) ? '' : $folder . '/') . $page_file;
    if (file_exists($test_path)) {
        require_once $test_path;
        $found = true;
        break;
    }
}

// Check in actions/ (for some direct API calls that might be in root legacy links)
if (!$found) {
    foreach (['', 'auth', 'cart', 'order', 'account', 'notifications', 'reviews'] as $action_folder) {
        $test_action_path = 'actions/' . (empty($action_folder) ? '' : $action_folder . '/') . $page_file;
        if (file_exists($test_action_path)) {
            require_once $test_action_path;
            $found = true;
            break;
        }
    }
}

// Fallback
if (!$found) {
    http_response_code(404);
    echo "404 - Admin page not found ($path)";
}