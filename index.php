<?php
/**
 * Advanced Root Router for Eat&Run
 * Organized by category to ensure each page file is in a dedicated folder.
 * Automatically finds files in sub-directories within /pages.
 */

// Basic routing logic
$request = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir = str_replace('index.php', '', $script_name);

// Built-in server router
if (php_sapi_name() == 'cli-server') {
    $url = parse_url($request);
    $file = './' . ltrim($url['path'], '/');
    if (file_exists($file) && is_file($file)) {
        return false; // serve the requested resource as-is.
    }
}

// Get the actual request path after the base directory
$path = str_replace($base_dir, '', $request);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Define default route
if (empty($path) || $path === 'index.php' || $path === 'index') {
    require_once 'pages/home.php';
    exit();
}

// Special case for dashboard (often accessed as dashboard.php)
if ($path === 'dashboard' || $path === 'dashboard.php') {
    $path = 'dashboard.php';
}

// Prepare the page file name
$page_file = $path;
if (!str_ends_with($page_file, '.php')) {
    $page_file .= '.php';
}

// Special case for admin routing
if (str_starts_with($path, 'admin/') || $path === 'admin') {
    require_once 'admin/index.php';
    exit();
}

// Search for the page in the organized folders
$search_folders = [
    'pages/ordering', 
    'pages/account', 
    'pages/auth', 
    'pages/info', 
    'pages/reviews',
    'actions/cart',
    'actions/auth',
    'actions/order',
    'actions/account',
    'actions/notifications',
    'actions/reviews',
    'actions'
];
$found = false;

// Check primary pages/ folder first
if (file_exists('pages/' . $page_file)) {
    require_once 'pages/' . $page_file;
    $found = true;
} else {
    // Search subfolders
    foreach ($search_folders as $folder) {
        if (file_exists("$folder/$page_file")) {
            require_once "$folder/$page_file";
            $found = true;
            break;
        }
    }
}

// Fallback to error page or standard 404
if (!$found) {
    if (file_exists('pages/info/error.php')) {
        require_once 'pages/info/error.php';
    } else {
        http_response_code(404);
        echo "404 - Page not found (" . htmlspecialchars($path) . ")";
    }
}
