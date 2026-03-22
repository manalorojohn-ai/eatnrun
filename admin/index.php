<?php
/**
 * Admin Router for Eat&Run
 * Routes admin requests to organized subfolders in administrative/pages.
 */

// Basic routing logic
$request = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir = str_replace('index.php', '', $script_name);

// Get the actual request path after the base directory
$path = str_replace($base_dir, '', $request);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Default to dashboard
if (empty($path) || $path === 'index.php') {
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