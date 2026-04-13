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
        // Neon SNI Trick: Embed endpoint ID in the password if direct connection fails
        $endpoint = explode('.', DB_HOST)[0];
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
        
        // Try with password embedding (the most robust way for Neon)
        $neon_pass = "endpoint=$endpoint;" . DB_PASS;
        
        $conn = new PDO($dsn, DB_USER, $neon_pass, [
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
// OO COMPATIBILITY WRAPPER (FOR ->prepare, ->bind_param, etc.)
// ---------------------------------------------------------

if ($using_postgres) {
    class PDO_Stmt_Wrapper {
        private $stmt;
        private $params = [];
        private $types = "";

        public function __construct($stmt) {
            $this->stmt = $stmt;
        }

        public function bind_param($types, ...$vars) {
            $this->types = $types;
            $this->params = $vars;
            return true;
        }

        public function execute() {
            try {
                return $this->stmt->execute($this->params);
            } catch (Exception $e) {
                error_log("Execute Error: " . $e->getMessage());
                return false;
            }
        }

        public function get_result() {
            return new PDO_Result_Wrapper($this->stmt);
        }

        public function close() { return true; }
        
        // Handle metadata or other props if needed
        public function __get($name) {
            if ($name === 'num_rows') return $this->stmt->rowCount();
            return null;
        }
    }

    class PDO_Result_Wrapper {
        private $stmt;
        public $num_rows = 0;

        public function __construct($stmt) {
            $this->stmt = $stmt;
            $this->num_rows = $stmt->rowCount();
        }

        public function fetch_assoc() {
            return $this->stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function fetch_all($mode = 1) {
            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function fetch_array() {
            return $this->stmt->fetch(PDO::FETCH_BOTH);
        }

        public function free() { return true; }
        public function close() { return true; }
    }

    class PDO_Conn_Wrapper {
        private $pdo;
        public $connect_error = null;

        public function __construct($pdo) {
            $this->pdo = $pdo;
        }

        public function prepare($query) {
            // Use standard positional parameters (?) which PDO handles natively even on Postgres
            // Cleaning backticks is still necessary for Postgres
            $query = str_replace('`', '"', $query);

            try {
                $stmt = $this->pdo->prepare($query);
                return new PDO_Stmt_Wrapper($stmt);
            } catch (Exception $e) {
                error_log("Prepare Error: " . $e->getMessage());
                return false;
            }
        }

        public function query($query) {
            return mysqli_query($this, $query);
        }

        public function real_escape_string($s) {
            return mysqli_real_escape_string($this, $s);
        }

        public function close() { return true; }
        public function set_charset($c) { return true; }
        
        // Internal PDO access
        public function getPDO() { return $this->pdo; }
    }

    // Replace the global $conn with the wrapper
    $conn = new PDO_Conn_Wrapper($conn);
}

// ---------------------------------------------------------
// PROCEDURAL SHIM (FOR mysqli_query, etc.)
// ---------------------------------------------------------
if ($using_postgres) {
    if (!function_exists('mysqli_query')) {
        function mysqli_query($c, $q) {
            $pdo = ($c instanceof PDO_Conn_Wrapper) ? $c->getPDO() : $c;
            try {
                $q = str_ireplace('AUTO_INCREMENT', 'SERIAL', $q);
                $q = str_ireplace('INT ', 'INTEGER ', $q);
                $q = str_ireplace('TINYINT(1)', 'BOOLEAN', $q);
                $q = str_ireplace('DATETIME', 'TIMESTAMP', $q);
                $q = str_ireplace('`', '"', $q);
                $q = str_ireplace('ENGINE=InnoDB', '', $q);
                $q = str_ireplace('DEFAULT CHARSET=utf8mb4', '', $q);
                $q = str_ireplace('SET SESSION sql_mode', '-- SET SESSION sql_mode', $q);
                
                $res = $pdo->query($q);
                return $res ? new PDO_Result_Wrapper($res) : false;
            } catch (Exception $e) { 
                error_log("Query Error: " . $e->getMessage());
                return false; 
            }
        }
    }

    if (!function_exists('mysqli_fetch_assoc')) {
        function mysqli_fetch_assoc($res) {
            return ($res instanceof PDO_Result_Wrapper) ? $res->fetch_assoc() : false;
        }
    }

    if (!function_exists('mysqli_fetch_array')) {
        function mysqli_fetch_array($res) {
            return ($res instanceof PDO_Result_Wrapper) ? $res->fetch_array() : false;
        }
    }

    if (!function_exists('mysqli_num_rows')) {
        function mysqli_num_rows($res) {
            return ($res instanceof PDO_Result_Wrapper) ? $res->num_rows : 0;
        }
    }

    if (!function_exists('mysqli_free_result')) {
        function mysqli_free_result($res) { return true; }
    }

    if (!function_exists('mysqli_error')) {
        function mysqli_error($c) { return "Database error (check logs)"; }
    }

    if (!function_exists('mysqli_real_escape_string')) {
        function mysqli_real_escape_string($c, $s) {
            return str_replace("'", "''", $s);
        }
    }

    if (!function_exists('mysqli_insert_id')) {
        function mysqli_insert_id($c) {
            $pdo = ($c instanceof PDO_Conn_Wrapper) ? $c->getPDO() : $c;
            return $pdo->lastInsertId();
        }
    }

    if (!function_exists('mysqli_prepare')) {
        function mysqli_prepare($c, $q) {
            $q = str_replace('`', '"', $q);
            return $c->prepare($q);
        }
    }

    if (!function_exists('mysqli_stmt_bind_param')) {
        function mysqli_stmt_bind_param($s, $t, ...$v) {
            return $s->bind_param($t, ...$v);
        }
    }

    if (!function_exists('mysqli_stmt_execute')) {
        function mysqli_stmt_execute($s) {
            return $s->execute();
        }
    }

    if (!function_exists('mysqli_stmt_get_result')) {
        function mysqli_stmt_get_result($s) {
            return $s->get_result();
        }
    }

    if (!function_exists('mysqli_stmt_close')) {
        function mysqli_stmt_close($s) {
            return $s->close();
        }
    }

    if (!function_exists('mysqli_begin_transaction')) {
        function mysqli_begin_transaction($c) {
            $pdo = ($c instanceof PDO_Conn_Wrapper) ? $c->getPDO() : $c;
            return $pdo->beginTransaction();
        }
    }

    if (!function_exists('mysqli_commit')) {
        function mysqli_commit($c) {
            $pdo = ($c instanceof PDO_Conn_Wrapper) ? $c->getPDO() : $c;
            return $pdo->commit();
        }
    }

    if (!function_exists('mysqli_rollback')) {
        function mysqli_rollback($c) {
            $pdo = ($c instanceof PDO_Conn_Wrapper) ? $c->getPDO() : $c;
            return $pdo->rollBack();
        }
    }
}

// ---------------------------------------------------------
// SCHEMA INITIALIZATION (Using original $conn or wrapper)
// ---------------------------------------------------------
// No changes needed here, mysqli_query shim handles it.

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
        address TEXT,
        profile_photo VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )" : 
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100),
        phone VARCHAR(20),
        address TEXT,
        profile_photo VARCHAR(255),
        role ENUM('admin', 'customer', 'rider') DEFAULT 'customer',
        status ENUM('active', 'inactive') DEFAULT 'active',
        is_verified TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

mysqli_query($conn, $users_table);

// categories, menu_items, orders, etc... (The rest of the file)
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

$cart_table = $using_postgres ? 
    "CREATE TABLE IF NOT EXISTS cart (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        menu_item_id INTEGER NOT NULL REFERENCES menu_items(id) ON DELETE CASCADE,
        quantity INTEGER NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )" : 
    "CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        menu_item_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $cart_table);

$email_verifications_table = $using_postgres ? 
    "CREATE TABLE IF NOT EXISTS email_verifications (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(10) NOT NULL,
        expiry TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )" : 
    "CREATE TABLE IF NOT EXISTS email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        otp VARCHAR(10) NOT NULL,
        expiry DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
mysqli_query($conn, $email_verifications_table);

return $conn;
?>