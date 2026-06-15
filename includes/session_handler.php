<?php
/**
 * Session Handler - Manages session lifecycle and validation
 * Prevents premature session timeouts and tracks activity
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define session timeout (7 days in seconds)
define('SESSION_TIMEOUT', 604800);

/**
 * Validate and refresh user session
 * Resets the last activity timestamp to prevent timeout
 */
function validate_session() {
    // If no user is logged in, no need to validate
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Track last activity
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    $last_activity = $_SESSION['last_activity'];
    $current_time = time();
    $elapsed = $current_time - $last_activity;
    
    // Update last activity timestamp on every page load
    $_SESSION['last_activity'] = $current_time;
    
    // Check if session has expired (7 days)
    if ($elapsed > SESSION_TIMEOUT) {
        // Session expired - destroy it
        session_destroy();
        return false;
    }
    
    return true;
}

/**
 * Check if user is logged in
 * Also validates session is still active
 */
function is_user_logged_in() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Validate session is still active
    return validate_session();
}

/**
 * Get logged in user ID
 */
function get_user_id() {
    if (is_user_logged_in()) {
        return $_SESSION['user_id'];
    }
    return null;
}

/**
 * Get user role
 */
function get_user_role() {
    if (is_user_logged_in()) {
        return $_SESSION['role'] ?? 'user';
    }
    return null;
}

/**
 * Get user full name
 */
function get_user_full_name() {
    if (is_user_logged_in()) {
        return $_SESSION['full_name'] ?? 'User';
    }
    return null;
}

/**
 * Logout user
 */
function logout_user() {
    $_SESSION = [];
    session_destroy();
}

/**
 * Initialize session with user data
 */
function init_user_session($user_id, $role = 'user', $full_name = 'User') {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set user session data
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = $role;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

// Automatically validate session on every page load
validate_session();
?>
