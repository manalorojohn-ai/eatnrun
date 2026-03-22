<?php
/**
 * Admin Route Redirector
 * This file handles various redirect scenarios in the admin panel
 */

// Define all possible redirects
$redirects = [
    'index.php' => 'dashboard.php',
    'home.php' => 'dashboard.php',
    '' => 'dashboard.php'
];

// Get the current script name
$current_script = basename($_SERVER['SCRIPT_NAME']);

// Check if we need to redirect
if (array_key_exists($current_script, $redirects)) {
    header("Location: " . $redirects[$current_script]);
    exit();
}

// Handle "not found" scenario - this is for cases where the requested URL doesn't exist
if (isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] == 404) {
    header("Location: dashboard.php");
    exit();
}
?> 