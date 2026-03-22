<?php
// Check if constants are not already defined
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'food_ordering');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Create database if it doesn't exist
$create_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (!mysqli_query($conn, $create_db)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Select the database
mysqli_select_db($conn, DB_NAME);

// Set SQL mode and other configurations
mysqli_query($conn, "SET SESSION sql_mode = ''");
mysqli_query($conn, "SET NAMES utf8mb4");
mysqli_query($conn, "SET CHARACTER SET utf8mb4");
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

// Create users table if it doesn't exist
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    reset_otp VARCHAR(6) DEFAULT NULL,
    reset_otp_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $users_table)) {
    die("Error creating users table: " . mysqli_error($conn));
}

// Check if reset_otp columns exist
$check_reset_otp = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'reset_otp'");
if (mysqli_num_rows($check_reset_otp) == 0) {
    $alter_users = "ALTER TABLE users 
                    ADD COLUMN reset_otp VARCHAR(6) DEFAULT NULL,
                    ADD COLUMN reset_otp_expiry DATETIME DEFAULT NULL";
    if (!mysqli_query($conn, $alter_users)) {
        die("Error adding reset_otp columns: " . mysqli_error($conn));
    }
}

// Create categories table if it doesn't exist
$categories_table = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $categories_table)) {
    die("Error creating categories table: " . mysqli_error($conn));
}

// Check if categories already exist
$check_categories = "SELECT COUNT(*) as count FROM categories";
$result = mysqli_query($conn, $check_categories);
$row = mysqli_fetch_assoc($result);

// Only insert default categories if the table is empty
if ($row['count'] == 0) {
    $default_categories = [
        ['name' => 'Rice Meals', 'description' => 'Traditional Filipino rice meals'],
        ['name' => 'Burgers', 'description' => 'Delicious burger selections'],
        ['name' => 'Desserts', 'description' => 'Sweet treats and desserts'],
        ['name' => 'Beverages', 'description' => 'Refreshing drinks']
    ];

    foreach ($default_categories as $category) {
        $name = mysqli_real_escape_string($conn, $category['name']);
        $description = mysqli_real_escape_string($conn, $category['description']);
        
        $insert_category = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
        if (!mysqli_query($conn, $insert_category)) {
            error_log("Error inserting category '$name': " . mysqli_error($conn));
        }
    }
}

// Create menu_items table if it doesn't exist
$menu_items_table = "CREATE TABLE IF NOT EXISTS menu_items (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $menu_items_table)) {
    error_log("Error creating menu_items table: " . mysqli_error($conn));
}

// Create orders table if it doesn't exist
$orders_table = "CREATE TABLE IF NOT EXISTS orders (
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
    cancel_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    received_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($conn, $orders_table)) {
    error_log("Error creating orders table: " . mysqli_error($conn));
}

// Add cancel_reason column if it doesn't exist
$check_cancel_reason = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'cancel_reason'");
if (mysqli_num_rows($check_cancel_reason) == 0) {
    $alter_orders = "ALTER TABLE orders ADD COLUMN cancel_reason TEXT AFTER status";
    if (!mysqli_query($conn, $alter_orders)) {
        error_log("Error adding cancel_reason column: " . mysqli_error($conn));
    }
}

// Create order_details table if it doesn't exist
$order_details_table = "CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($conn, $order_details_table)) {
    error_log("Error creating order_details table: " . mysqli_error($conn));
}

// Create order_logs table if it doesn't exist
$order_logs_table = "CREATE TABLE IF NOT EXISTS order_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $order_logs_table)) {
    error_log("Error creating order_logs table: " . mysqli_error($conn));
}

// Create ratings table if it doesn't exist
$ratings_table = "CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rating (order_id, menu_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $ratings_table)) {
    error_log("Error creating ratings table: " . mysqli_error($conn));
}

// Add is_rated column to orders table if it doesn't exist
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'is_rated'");
if (mysqli_num_rows($check_column) == 0) {
    $alter_orders = "ALTER TABLE orders ADD COLUMN is_rated TINYINT(1) DEFAULT 0 AFTER status";
    if (!mysqli_query($conn, $alter_orders)) {
        error_log("Error adding is_rated column: " . mysqli_error($conn));
    }
}

// Add received_at column to orders table if it doesn't exist
$check_received_at = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'received_at'");
if (mysqli_num_rows($check_received_at) == 0) {
    $alter_orders = "ALTER TABLE orders ADD COLUMN received_at TIMESTAMP NULL AFTER updated_at";
    if (!mysqli_query($conn, $alter_orders)) {
        error_log("Error adding received_at column: " . mysqli_error($conn));
    }
}

// Create notifications table if it doesn't exist
$notifications_table = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $notifications_table)) {
    error_log("Error creating notifications table: " . mysqli_error($conn));
}

// Create admin_notifications table if it doesn't exist
$admin_notifications_table = "CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'system',
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $admin_notifications_table)) {
    error_log("Error creating admin_notifications table: " . mysqli_error($conn));
}

// Re-enable foreign key checks
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

// Return the connection object
return $conn;
?> 