<?php
// Database configuration
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: getenv('DB_USFR') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'food_ordering');
if (!defined('DB_PORT')) {
    $env_port = getenv('DB_PORT');
    // If the port is not a valid number (like the string 'DB_PORT'), default to 5432
    if (!$env_port || !is_numeric($env_port)) {
        define('DB_PORT', getenv('DB_HOST') ? 5432 : 3306);
    } else {
        define('DB_PORT', (int)$env_port);
    }
}

// ---------------------------------------------------------
// HYBRID CONNECTION LOGIC (POSTGRES FOR RENDER, MYSQL FOR LOCAL)
// ---------------------------------------------------------
$using_postgres = false;
$is_render = (bool)getenv('RENDER');

// On Render, we MUST use Postgres (Neon)
if ($is_render || (getenv('DB_HOST') && extension_loaded('pdo_pgsql'))) {
    try {
        // Neon requires the endpoint ID as an option if the client library is older
        $endpoint = explode('.', DB_HOST)[0];
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require;options=--endpoint=$endpoint";
        
        $conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        $using_postgres = true;
    } catch (PDOException $e) {
        // If we are on Render, we cannot fallback. Show the actual Neon error.
        if ($is_render) {
            die("Neon Connection Error: " . $e->getMessage());
        }
        error_log("Postgres failed, falling back to MySQL: " . $e->getMessage());
    }
}

// Local Fallback: Only use MySQL if NOT on Render
if (!$using_postgres && !$is_render) {
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

// Create categories table
$categories_table = $using_postgres ? 
    "CREATE TABLE IF NOT EXISTS categories (
        id SERIAL PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        description TEXT,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )" : 
    "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        description TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $categories_table);

// Create menu_items table
$menu_items_table = $using_postgres ? 
    "CREATE TABLE IF NOT EXISTS menu_items (
        id SERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
        image_path VARCHAR(255),
        status VARCHAR(20) DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )" : 
    "CREATE TABLE IF NOT EXISTS menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category_id INT,
        image_path VARCHAR(255),
        status ENUM('available', 'unavailable') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $menu_items_table);

// Create orders table
$orders_table = $using_postgres ? 
    "CREATE TABLE IF NOT EXISTS orders (
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
    )" : 
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        delivery_address TEXT NOT NULL,
        payment_method VARCHAR(50) NOT NULL DEFAULT 'cod',
        payment_proof VARCHAR(255),
        payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
        delivery_notes TEXT,
        subtotal DECIMAL(10,2) NOT NULL,
        delivery_fee DECIMAL(10,2) DEFAULT 50.00,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
        is_rated TINYINT(1) DEFAULT 0,
        cancel_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        received_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $orders_table);

// Create order_details table
$order_details_table = $using_postgres ? 
    "CREATE TABLE IF NOT EXISTS order_details (
        id SERIAL PRIMARY KEY,
        order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
        menu_item_id INTEGER NOT NULL REFERENCES menu_items(id),
        quantity INTEGER NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )" : 
    "CREATE TABLE IF NOT EXISTS order_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $order_details_table);

// Create notifications table
$notifications_table = $using_postgres ? 
    "CREATE TABLE IF NOT EXISTS notifications (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        message TEXT NOT NULL,
        type VARCHAR(50) NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        link VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL
    )" : 
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        link VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $notifications_table);

return $conn;
?>