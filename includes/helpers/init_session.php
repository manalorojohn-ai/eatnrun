<?php
/**
 * Unified Session Initialization Helper
 * Ensures consistent cookie settings, session name, and session directory across all entry points.
 */

if (!function_exists('init_app_session')) {
    function init_app_session() {
        if (session_status() === PHP_SESSION_NONE) {
            $sessionDir = dirname(__DIR__, 2) . '/tmp/sessions';
            if (!is_dir($sessionDir)) {
                @mkdir($sessionDir, 0777, true);
            }
            if (is_dir($sessionDir) && is_writable($sessionDir)) {
                session_save_path($sessionDir);
            }

            ini_set('session.gc_maxlifetime', 604800);
            ini_set('session.cookie_lifetime', 604800);
            session_set_cookie_params([
                'lifetime' => 604800,
                'path' => '/',
                'domain' => '',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_name('EATNRUN_SESSION');
            session_start();
        }
    }
}

// Auto-run when included
init_app_session();
