<?php
// Database configuration - Neon (PostgreSQL)
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'postgres');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'food_ordering');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '5432');

// Connection string for PostgreSQL
$conn_string = "host=" . DB_HOST . " port=" . DB_PORT . " dbname=" . DB_NAME . " user=" . DB_USER . " password=" . DB_PASS . " sslmode=require";

// Connect to Neon
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

// ---------------------------------------------------------
// REPLACEMENT FOR MYSQLI FUNCTIONS (SMOOTH MIGRATION)
// ---------------------------------------------------------
if (!function_exists('mysqli_query')) {
    function mysqli_query($c, $q) {
        // Convert some common MySQL syntax to PG
        $q = str_ireplace('AUTO_INCREMENT', 'SERIAL', $q);
        $q = str_ireplace('INT ', 'INTEGER ', $q);
        $q = str_ireplace('TINYINT(1)', 'BOOLEAN', $q);
        $q = str_ireplace('DATETIME', 'TIMESTAMP', $q);
        $q = str_ireplace('`', '"', $q); // MySQL backticks to PG double quotes
        return pg_query($c, $q);
    }
}

if (!function_exists('mysqli_fetch_assoc')) {
    function mysqli_fetch_assoc($r) {
        return pg_fetch_assoc($r);
    }
}

if (!function_exists('mysqli_real_escape_string')) {
    function mysqli_real_escape_string($c, $s) {
        return pg_escape_string($c, $s);
    }
}

if (!function_exists('mysqli_error')) {
    function mysqli_error($c) {
        return pg_last_error($c);
    }
}

// ---------------------------------------------------------
// SCHEMA DEFINITION (POSTGRESQL VERSION)
// ---------------------------------------------------------

// Create users table
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'customer',
    status VARCHAR(20) DEFAULT 'active',
    is_verified BOOLEAN DEFAULT FALSE,
    document_status VARCHAR(20) DEFAULT 'none',
    reset_otp VARCHAR(6) DEFAULT NULL,
    reset_otp_expiry TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $users_table);

// Create categories table
$categories_table = "CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $categories_table);

// Create menu_items table
$menu_items_table = "CREATE TABLE IF NOT EXISTS menu_items (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    image_path VARCHAR(255),
    status VARCHAR(20) DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $menu_items_table);

// Create orders table
$orders_table = "CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'cod',
    payment_proof VARCHAR(255),
    payment_status VARCHAR(20) DEFAULT 'pending',
    delivery_notes TEXT,
    subtotal DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 50.00,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    is_rated BOOLEAN DEFAULT FALSE,
    cancel_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    received_at TIMESTAMP NULL
)";
mysqli_query($conn, $orders_table);

// Create order_details table
$order_details_table = "CREATE TABLE IF NOT EXISTS order_details (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    menu_item_id INTEGER NOT NULL REFERENCES menu_items(id),
    quantity INTEGER NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $order_details_table);

// Create notifications table
$notifications_table = "CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL
)";
mysqli_query($conn, $notifications_table);

return $conn;
?> 