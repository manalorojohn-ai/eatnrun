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

// Get the actual request path after the base directory
$path = str_replace($base_dir, '', $request);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Define default route
if (empty($path) || $path === 'index.php') {
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

// Search for the page in the organized /pages subfolders
$search_folders = ['ordering', 'account', 'auth', 'info', 'reviews'];
$found = false;

// Check primary pages/ folder first
if (file_exists('pages/' . $page_file)) {
    require_once 'pages/' . $page_file;
    $found = true;
} else {
    // Search subfolders
    foreach ($search_folders as $folder) {
        if (file_exists("pages/$folder/$page_file")) {
            require_once "pages/$folder/$page_file";
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
