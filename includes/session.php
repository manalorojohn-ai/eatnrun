<?php
// Start the session if it hasn't been started yet
function start_session_once() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        
        session_start();
    }
}

// Initialize session
start_session_once();

// Check if user is logged in and has admin role
function require_admin_auth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../login.php");
        exit();
    }
}
?> 