<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'food_ordering';

// Create connection without database first
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!mysqli_query($conn, $sql)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
mysqli_select_db($conn, $dbname);

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    reset_token VARCHAR(6) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!mysqli_query($conn, $sql)) {
    die("Error creating users table: " . mysqli_error($conn));
}

// Create a test user if it doesn't exist
$test_email = 'test@example.com';
$test_password = password_hash('password123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, email, password, full_name, role) 
        VALUES ('testuser', '$test_email', '$test_password', 'Test User', 'user')";
mysqli_query($conn, $sql);

// Drop existing password_resets table if it exists to recreate with proper schema
$sql = "DROP TABLE IF EXISTS password_resets";
if (!mysqli_query($conn, $sql)) {
    die("Error dropping password_resets table: " . mysqli_error($conn));
}

// Create password_resets table with proper schema
$sql = "CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    used TINYINT(1) DEFAULT 0,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (token),
    INDEX (user_id),
    INDEX (expires_at)
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating password_resets table: " . mysqli_error($conn));
}

// Return connection
return $conn;
?> 