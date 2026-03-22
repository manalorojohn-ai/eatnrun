<?php
/**
 * Database Configuration for Hotel Management Database
 * 
 * This connects to the local hotel_management database
 */

// Local database (food_ordering)
define('LOCAL_DB_HOST', 'localhost');
define('LOCAL_DB_USER', 'root');
define('LOCAL_DB_PASS', ''); // Your local database password
define('LOCAL_DB_NAME', 'food_ordering');

// Hotel database (hotel_management) - Remote database on another laptop
define('REMOTE_DB_HOST', '192.168.0.101');  // IP address of laptop with hotel database
define('REMOTE_DB_USER', 'root');
define('REMOTE_DB_PASS', '');  // No password for remote MySQL
define('REMOTE_DB_NAME', 'hotel_management');

/**
 * Instructions:
 * 
 * This configuration connects to the local hotel_management database
 * which should be running on the same XAMPP server as your food_ordering database.
 * 
 * If you need to connect to a remote database, change REMOTE_DB_HOST to the remote IP.
 */
?>
