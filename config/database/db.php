<?php
// Database configuration
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'food_ordering');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: (getenv('DB_HOST') ? '5432' : '3306'));

// ---------------------------------------------------------
// HYBRID CONNECTION LOGIC (POSTGRES FOR RENDER, MYSQL FOR LOCAL)
// ---------------------------------------------------------
$using_postgres = false;

// Only try Postgres if DB_HOST is set (usually Render) and driver exists
if (getenv('DB_HOST') && extension_loaded('pdo_pgsql')) {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
        $conn = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $using_postgres = true;
    } catch (PDOException $e) {
        // Fallback to MySQL if Postgres connection fails
        error_log("Postgres failed, falling back to MySQL: " . $e->getMessage());
    }
}

// Local Fallback: Use MySQL if not using Postgres
if (!$using_postgres) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
}

// ---------------------------------------------------------
// REPLACEMENT FOR MYSQLI FUNCTIONS (SMOOTH MIGRATION)
// ---------------------------------------------------------
// If we ARE using Postgres, we need the shim for mysqli_ functions
if ($using_postgres) {
    if (!function_exists('mysqli_query')) {
        function mysqli_query($c, $q) {
            try {
                // MySQL to PG Syntax Translation
                $q = str_ireplace('AUTO_INCREMENT', 'SERIAL', $q);
                $q = str_ireplace('INT ', 'INTEGER ', $q);
                $q = str_ireplace('TINYINT(1)', 'BOOLEAN', $q);
                $q = str_ireplace('DATETIME', 'TIMESTAMP', $q);
                $q = str_ireplace('`', '"', $q);
                $q = str_ireplace('ENGINE=InnoDB', '', $q);
                $q = str_ireplace('DEFAULT CHARSET=utf8mb4', '', $q);
                $q = str_ireplace('COLLATE=utf8mb4_unicode_ci', '', $q);
                
                return $c->query($q);
            } catch (Exception $e) { return false; }
        }
    }

    if (!function_exists('mysqli_fetch_assoc')) {
        function mysqli_fetch_assoc($res) {
            return $res ? $res->fetch(PDO::FETCH_ASSOC) : false;
        }
    }

    if (!function_exists('mysqli_num_rows')) {
        function mysqli_num_rows($res) {
            return $res ? $res->rowCount() : 0;
        }
    }

    if (!function_exists('mysqli_real_escape_string')) {
        function mysqli_real_escape_string($c, $s) {
            return str_replace("'", "''", $s);
        }
    }

    if (!function_exists('mysqli_insert_id')) {
        function mysqli_insert_id($c) {
            return $c->lastInsertId();
        }
    }
}

// ---------------------------------------------------------
// SCHEMA INITIALIZATION
// ---------------------------------------------------------
$users_table = $using_postgres ? 
    "CREATE TABLE IF NOT EXISTS users (
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
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )" : 
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        phone VARCHAR(20),
        role ENUM('admin', 'customer', 'rider') DEFAULT 'customer',
        status ENUM('active', 'inactive') DEFAULT 'active',
        is_verified TINYINT(1) DEFAULT 0,
        document_status ENUM('none', 'pending', 'approved', 'rejected') DEFAULT 'none',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conn, $users_table);

return $conn;
?>

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