<?php
require_once dirname(__DIR__, 2) . '/includes/helpers/init_session.php';

// Unset all session variables
$_SESSION = array();

// If session cookie exists, destroy cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home or login page
header("Location: /login");
exit();