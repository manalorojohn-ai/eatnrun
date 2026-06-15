<?php
/**
 * Optimized Database Core for Eat&Run
 * Supports: PostgreSQL (PDO), MySQL (MySQLi/PDO), and JSON fallback
 */

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'food_ordering');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: (getenv('RENDER') ? 5432 : 3306));

$using_postgres = false;
$using_mysql = false;
$using_json = false;
$is_render = (bool)getenv('RENDER');
$conn = null;

// Try PostgreSQL first ONLY if the extension is actually loaded
if (extension_loaded('pdo_pgsql')) {
    try {
        // For Neon: extract endpoint ID and add it to connection options
        $host = DB_HOST;
        $endpoint_id = '';
        $password = DB_PASS;
        
        if (strpos($host, 'neon') !== false) {
            // Extract endpoint ID from host (first part of domain)
            $host_parts = explode('.', $host);
            if (count($host_parts) > 0) {
                $endpoint_id = $host_parts[0];
                // Workaround D: Specify endpoint ID in password field
                // Use $ as separator if ; is problematic
                $password = "endpoint=" . $endpoint_id . ";" . DB_PASS;
                error_log("Neon endpoint ID: " . $endpoint_id);
            }
            $dsn = "pgsql:host=" . $host . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
        } else {
            $dsn = "pgsql:host=" . $host . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
        }
        
        error_log("PostgreSQL DSN: " . $dsn);
        $conn = new PDO($dsn, DB_USER, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]);
        $using_postgres = true;
        error_log("PostgreSQL connection successful!");
    } catch (PDOException $e) {
        error_log("PostgreSQL connection failed: " . $e->getMessage());
        $conn = null;
    }
} else {
    error_log("PostgreSQL PDO extension not loaded, skipping PostgreSQL connection attempt");
}

// Try MySQL with PDO if PostgreSQL failed
if (!$using_postgres && !isset($conn) && extension_loaded('pdo_mysql')) {
    try {
        // Use standard MySQL port (3306) for MySQL fallback, regardless of config
        $mysql_port = (DB_PORT == 5432) ? 3306 : DB_PORT;  // Don't use PostgreSQL port for MySQL
        
        // Check if host looks like a remote server
        $is_local_mysql = in_array(DB_HOST, ['localhost', '127.0.0.1', '::1']);
        
        if (!$is_local_mysql) {
            // For remote hosts, skip if port suggests PostgreSQL
            if (DB_PORT == 5432) {
                error_log("Skipping MySQL for PostgreSQL-like remote configuration");
                $conn = null;
            } else {
                // Try with very short timeout for remote MySQL
                $old_max_exec = ini_get('max_execution_time');
                ini_set('max_execution_time', 3);
                
                $dsn = "mysql:host=" . DB_HOST . ";port=" . $mysql_port . ";dbname=" . DB_NAME . ";charset=utf8mb4;connect_timeout=2";
                $conn = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $using_mysql = true;
                
                ini_set('max_execution_time', $old_max_exec);
                error_log("MySQL PDO connection successful!");
            }
        } else {
            // Local MySQL can have a slightly longer timeout
            $dsn = "mysql:host=" . DB_HOST . ";port=" . $mysql_port . ";dbname=" . DB_NAME . ";charset=utf8mb4;connect_timeout=3";
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $using_mysql = true;
            error_log("MySQL PDO connection successful!");
        }
    } catch (PDOException $e) {
        error_log("MySQL PDO connection failed: " . $e->getMessage());
        $conn = null;
    }
}

// Try MySQLi if PDO MySQL failed (but skip for remote hosts to avoid long timeouts)
$is_localhost = in_array(DB_HOST, ['localhost', '127.0.0.1', '::1']);
if (!$using_postgres && !$using_mysql && !isset($conn) && extension_loaded('mysqli') && $is_localhost) {
    try {
        // Use standard MySQL port (3306) for MySQL fallback, regardless of config
        $mysql_port = (DB_PORT == 5432) ? 3306 : DB_PORT;
        
        // Set a short timeout using ini_set for the connection attempt
        $old_timeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', 3);  // 3 second timeout
        
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $mysql_port);
        
        ini_set('default_socket_timeout', $old_timeout);  // Restore timeout
        
        if ($conn->connect_error) {
            error_log("MySQLi connection failed: " . $conn->connect_error);
            $conn = null;
        } else {
            $conn->set_charset("utf8mb4");
            $using_mysql = true;
            error_log("MySQLi connection successful!");
        }
    } catch (Exception $e) {
        ini_set('default_socket_timeout', $old_timeout);  // Restore timeout
        error_log("MySQLi connection error: " . $e->getMessage());
        $conn = null;
    }
} else if (!$using_postgres && !$using_mysql && !isset($conn) && extension_loaded('mysqli')) {
    error_log("Skipping MySQLi for remote host '" . DB_HOST . "' to avoid long timeout");
}

// Fallback to JSON file storage for development/testing
if (!$conn) {
    error_log("No database connection available. Using JSON file storage fallback.");
    $using_json = true;
    
    // Sample data for fallback
    $sample_categories = [
        ['id' => 1, 'name' => 'Burgers'],
        ['id' => 2, 'name' => 'Rice Meals'],
        ['id' => 3, 'name' => 'Beverages'],
        ['id' => 4, 'name' => 'Desserts']
    ];
    
    $sample_menu_items = [
        ['id' => 1, 'name' => 'Plain Burger', 'description' => 'Classic beef burger with fresh vegetables', 'price' => 85.00, 'category_id' => 1, 'category_name' => 'Burgers', 'image_path' => 'assets/images/menu/Burgers/plain-burger.jpg', 'status' => 'available'],
        ['id' => 2, 'name' => 'Cheese Burger', 'description' => 'Juicy beef patty with melted cheese', 'price' => 95.00, 'category_id' => 1, 'category_name' => 'Burgers', 'image_path' => 'assets/images/menu/Burgers/cheese-burger.jpg', 'status' => 'available'],
        ['id' => 3, 'name' => 'Adobo with Rice', 'description' => 'Classic Filipino pork adobo', 'price' => 130.00, 'category_id' => 2, 'category_name' => 'Rice Meals', 'image_path' => 'assets/images/menu/Rice Meals/adobo.jpg', 'status' => 'available'],
        ['id' => 4, 'name' => 'Bicol Express', 'description' => 'Spicy coconut milk pork dish', 'price' => 120.00, 'category_id' => 2, 'category_name' => 'Rice Meals', 'image_path' => 'assets/images/menu/Rice Meals/bicol-express.jpg', 'status' => 'available'],
        ['id' => 5, 'name' => 'Coke', 'description' => 'Refreshing cola drink', 'price' => 45.00, 'category_id' => 3, 'category_name' => 'Beverages', 'image_path' => 'assets/images/menu/Beverages/coke.jpg', 'status' => 'available'],
        ['id' => 6, 'name' => 'Mango Juice', 'description' => 'Fresh Philippine mango juice', 'price' => 55.00, 'category_id' => 3, 'category_name' => 'Beverages', 'image_path' => 'assets/images/menu/Beverages/mango-juice.jpg', 'status' => 'available'],
        ['id' => 7, 'name' => 'Halo-Halo', 'description' => 'Filipino shaved ice dessert', 'price' => 85.00, 'category_id' => 4, 'category_name' => 'Desserts', 'image_path' => 'assets/images/menu/Desserts/halo-halo.jpg', 'status' => 'available'],
        ['id' => 8, 'name' => 'Leche Flan', 'description' => 'Classic caramel custard', 'price' => 60.00, 'category_id' => 4, 'category_name' => 'Desserts', 'image_path' => 'assets/images/menu/Desserts/leche-flan.jpg', 'status' => 'available']
    ];
    
    // Create a simple JSON-based storage class
    class JSONDatabase {
        private $data_dir;
        private $sample_categories;
        private $sample_menu_items;
        
        public function __construct($categories, $menu_items) {
            $this->data_dir = dirname(__DIR__) . '/data';
            $this->sample_categories = $categories;
            $this->sample_menu_items = $menu_items;
            if (!is_dir($this->data_dir)) {
                mkdir($this->data_dir, 0755, true);
            }
        }
        
        public function query($sql) {
            error_log("JSON DB Query: " . substr($sql, 0, 100));
            $sql = strtolower($sql);
            
            if (strpos($sql, 'menu_items') !== false && strpos($sql, 'select') !== false) {
                return new JSONResult($this->sample_menu_items);
            } elseif (strpos($sql, 'categories') !== false && strpos($sql, 'select') !== false) {
                return new JSONResult($this->sample_categories);
            }
            
            return new JSONResult([]);
        }
        
        public function prepare($sql) {
            return new JSONStatement($sql, $this->sample_categories, $this->sample_menu_items);
        }
        
        public function real_escape_string($str) {
            return addslashes($str);
        }
        
        public function begin_transaction() { return true; }
        public function commit() { return true; }
        public function rollback() { return true; }
        public function affected_rows() { return 0; }
        public $connect_error = null;
    }
    
    class JSONResult {
        private $data;
        public $num_rows = 0;
        
        public function __construct($data = []) {
            $this->data = $data;
            $this->num_rows = count($data);
        }
        
        public function fetch_assoc() {
            return array_shift($this->data);
        }
        
        public function fetch_array() {
            return array_shift($this->data);
        }
        
        public function fetch_all($mode = 1) {
            return $this->data;
        }
    }
    
    class JSONStatement {
        private $sql;
        private $sample_categories;
        private $sample_menu_items;
        private $params = [];
        
        public function __construct($sql, $categories, $menu_items) {
            $this->sql = $sql;
            $this->sample_categories = $categories;
            $this->sample_menu_items = $menu_items;
        }
        
        public function bind_param($types, ...$params) {
            $this->params = $params;
            return true;
        }
        
        public function execute() {
            return true;
        }
        
        public function get_result() {
            $sql = strtolower($this->sql);
            if (strpos($sql, 'categories') !== false && strpos($sql, 'select') !== false) {
                return new JSONResult($this->sample_categories);
            } elseif (strpos($sql, 'menu_items') !== false && strpos($sql, 'select') !== false) {
                return new JSONResult($this->sample_menu_items);
            }
            return new JSONResult([]);
        }
        
        public function close() {
            return true;
        }
    }
    
    $conn = new JSONDatabase($sample_categories, $sample_menu_items);
}

// PostgreSQL/MySQL wrapper for compatibility
if ($using_postgres || $using_mysql) {
    class PDO_Result_Wrapper {
        private $stmt;
        public $num_rows = 0;
        public function __construct($stmt) { $this->stmt = $stmt; $this->num_rows = $stmt->rowCount(); }
        public function fetch_assoc() { return $this->stmt->fetch(PDO::FETCH_ASSOC); }
        public function fetch_array() { return $this->stmt->fetch(PDO::FETCH_BOTH); }
        public function fetch_all($m) { return $this->stmt->fetchAll($m == 1 ? PDO::FETCH_ASSOC : PDO::FETCH_BOTH); }
    }

    class PDO_Stmt_Wrapper {
        private $stmt;
        private $params = [];
        public function __construct($stmt) { $this->stmt = $stmt; }
        public function bind_param($t, ...$v) { $this->params = $v; return true; }
        public function execute() { try { return $this->stmt->execute($this->params); } catch (Exception $e) { return false; } }
        public function get_result() { return new PDO_Result_Wrapper($this->stmt); }
        public function close() { return true; }
        public function __get($n) { return ($n === 'num_rows') ? $this->stmt->rowCount() : null; }
    }

    class PDO_Conn_Wrapper {
        private $pdo;
        public $connect_error = null;
        public function __construct($pdo) { $this->pdo = $pdo; }
        public function getPDO() { return $this->pdo; }
        public function prepare($q) { 
            $q = str_replace('`', '"', $q);
            $q = str_ireplace(['TINYINT(1)', 'DATETIME'], ['BOOLEAN', 'TIMESTAMP'], $q);
            return new PDO_Stmt_Wrapper($this->pdo->prepare($q)); 
        }
        public function query($q) {
            $q = str_ireplace(['AUTO_INCREMENT', 'INT ', 'TINYINT(1)', 'DATETIME', '`', 'ENGINE=InnoDB', 'DEFAULT CHARSET=utf8mb4'], ['SERIAL', 'INTEGER ', 'BOOLEAN', 'TIMESTAMP', '"', '', ''], $q);
            try { $res = $this->pdo->query($q); return $res ? new PDO_Result_Wrapper($res) : false; } catch (Exception $e) { return false; }
        }
        public function real_escape_string($s) { return str_replace("'", "''", $s); }
        public function begin_transaction() { return $this->pdo->beginTransaction(); }
        public function commit() { return $this->pdo->commit(); }
        public function rollback() { return $this->pdo->rollBack(); }
        public function affected_rows($s = null) { return ($s && method_exists($s, 'rowCount')) ? $s->rowCount() : 0; }
    }
    $conn = new PDO_Conn_Wrapper($conn);
}

// Global Procedural Shims for MySQLi compatibility
if (!function_exists('mysqli_query')) { 
    function mysqli_query($c, $q) { 
        if ($c instanceof PDO_Conn_Wrapper || $c instanceof JSONDatabase) {
            return $c->query($q); 
        }
        return false; 
    } 
}
if (!function_exists('mysqli_fetch_assoc')) { 
    function mysqli_fetch_assoc($r) { 
        if ($r instanceof PDO_Result_Wrapper || $r instanceof JSONResult) {
            return $r->fetch_assoc(); 
        }
        return false; 
    } 
}
if (!function_exists('mysqli_num_rows')) { 
    function mysqli_num_rows($r) { 
        if ($r instanceof PDO_Result_Wrapper || $r instanceof JSONResult) {
            return $r->num_rows; 
        }
        return 0; 
    } 
}
if (!function_exists('mysqli_insert_id')) { 
    function mysqli_insert_id($c) { 
        if ($c instanceof PDO_Conn_Wrapper) {
            try { return $c->getPDO()->lastInsertId(); } catch (Exception $e) { return 0; }
        }
        return 0;
    } 
}
if (!function_exists('mysqli_prepare')) { 
    function mysqli_prepare($c, $q) { 
        if ($c instanceof PDO_Conn_Wrapper || $c instanceof JSONDatabase) {
            return $c->prepare($q); 
        }
        return false; 
    } 
}
if (!function_exists('mysqli_stmt_bind_param')) { function mysqli_stmt_bind_param($s, $t, ...$v) { return $s->bind_param($t, ...$v); } }
if (!function_exists('mysqli_stmt_execute')) { function mysqli_stmt_execute($s) { return $s->execute(); } }
if (!function_exists('mysqli_stmt_get_result')) { 
    function mysqli_stmt_get_result($s) { 
        if ($s instanceof PDO_Stmt_Wrapper || $s instanceof JSONStatement) {
            return $s->get_result(); 
        }
        return false; 
    } 
}
if (!function_exists('mysqli_stmt_close')) { function mysqli_stmt_close($s) { return true; } }
if (!function_exists('mysqli_free_result')) { function mysqli_free_result($r) { return true; } }
if (!function_exists('mysqli_fetch_all')) { 
    function mysqli_fetch_all($r, $m = 1) { 
        if ($r instanceof PDO_Result_Wrapper || $r instanceof JSONResult) {
            return $r->fetch_all($m); 
        }
        return []; 
    } 
}
if (!function_exists('mysqli_affected_rows')) { 
    function mysqli_affected_rows($c) { 
        if ($c instanceof PDO_Conn_Wrapper || $c instanceof JSONDatabase) {
            return $c->affected_rows(); 
        }
        return 0; 
    } 
}
if (!function_exists('mysqli_begin_transaction')) { 
    function mysqli_begin_transaction($c) { 
        if ($c instanceof PDO_Conn_Wrapper || $c instanceof JSONDatabase) {
            return $c->begin_transaction(); 
        }
        return false; 
    } 
}
if (!function_exists('mysqli_commit')) { 
    function mysqli_commit($c) { 
        if ($c instanceof PDO_Conn_Wrapper || $c instanceof JSONDatabase) {
            return $c->commit(); 
        }
        return false; 
    } 
}
if (!function_exists('mysqli_rollback')) { 
    function mysqli_rollback($c) { 
        if ($c instanceof PDO_Conn_Wrapper || $c instanceof JSONDatabase) {
            return $c->rollback(); 
        }
        return false; 
    } 
}
if (!function_exists('mysqli_real_escape_string')) { 
    function mysqli_real_escape_string($c, $s) { 
        if ($c instanceof PDO_Conn_Wrapper || $c instanceof JSONDatabase) {
            return $c->real_escape_string($s); 
        }
        return str_replace("'", "''", $s); 
    } 
}
if (!function_exists('mysqli_error')) { function mysqli_error($c) { return ($c instanceof PDO_Conn_Wrapper) ? ($c->connect_error ?? 'Unknown error') : ''; } }
if (!function_exists('mysqli_fetch_array')) { 
    function mysqli_fetch_array($r) { 
        if ($r instanceof PDO_Result_Wrapper || $r instanceof JSONResult) {
            return $r->fetch_array(); 
        }
        return false; 
    } 
}
if (!function_exists('mysqli_close')) { function mysqli_close($c) { return true; } }
if (!function_exists('mysqli_report')) { function mysqli_report($flags) { return true; } }
if (!function_exists('mysqli_stmt_affected_rows')) { function mysqli_stmt_affected_rows($s) { return 0; } }
if (!function_exists('mysqli_stmt_error')) { function mysqli_stmt_error($s) { return ''; } }
if (!function_exists('mysqli_data_seek')) { function mysqli_data_seek($r, $o) { return true; } }

return $conn;
?>

if ($using_postgres) {
    class PDO_Result_Wrapper {
        private $stmt;
        public $num_rows = 0;
        public function __construct($stmt) { $this->stmt = $stmt; $this->num_rows = $stmt->rowCount(); }
        public function fetch_assoc() { return $this->stmt->fetch(PDO::FETCH_ASSOC); }
        public function fetch_array() { return $this->stmt->fetch(PDO::FETCH_BOTH); }
        public function fetch_all($m) { return $this->stmt->fetchAll($m == 1 ? PDO::FETCH_ASSOC : PDO::FETCH_BOTH); }
    }

    class PDO_Stmt_Wrapper {
        private $stmt;
        private $params = [];
        public function __construct($stmt) { $this->stmt = $stmt; }
        public function bind_param($t, ...$v) { $this->params = $v; return true; }
        public function execute() { try { return $this->stmt->execute($this->params); } catch (Exception $e) { return false; } }
        public function get_result() { return new PDO_Result_Wrapper($this->stmt); }
        public function close() { return true; }
        public function __get($n) { return ($n === 'num_rows') ? $this->stmt->rowCount() : null; }
    }

    class PDO_Conn_Wrapper {
        private $pdo;
        public $connect_error = null;
        public function __construct($pdo) { $this->pdo = $pdo; }
        public function getPDO() { return $this->pdo; }
        public function prepare($q) { 
            $q = str_replace('`', '"', $q);
            // Translate common types for prepared statements
            $q = str_ireplace(['TINYINT(1)', 'DATETIME'], ['BOOLEAN', 'TIMESTAMP'], $q);
            return new PDO_Stmt_Wrapper($this->pdo->prepare($q)); 
        }
        public function query($q) {
            $q = str_ireplace(['AUTO_INCREMENT', 'INT ', 'TINYINT(1)', 'DATETIME', '`', 'ENGINE=InnoDB', 'DEFAULT CHARSET=utf8mb4'], ['SERIAL', 'INTEGER ', 'BOOLEAN', 'TIMESTAMP', '"', '', ''], $q);
            try { $res = $this->pdo->query($q); return $res ? new PDO_Result_Wrapper($res) : false; } catch (Exception $e) { return false; }
        }
        public function real_escape_string($s) { return str_replace("'", "''", $s); }
        public function begin_transaction() { return $this->pdo->beginTransaction(); }
        public function commit() { return $this->pdo->commit(); }
        public function rollback() { return $this->pdo->rollBack(); }
        public function affected_rows($s = null) { return ($s && method_exists($s, 'rowCount')) ? $s->rowCount() : 0; }
    }
    $conn = new PDO_Conn_Wrapper($conn);
}

// Global Procedural Shims
if (!function_exists('mysqli_query')) { function mysqli_query($c, $q) { return ($c instanceof PDO_Conn_Wrapper) ? $c->query($q) : false; } }
if (!function_exists('mysqli_fetch_assoc')) { function mysqli_fetch_assoc($r) { return ($r instanceof PDO_Result_Wrapper) ? $r->fetch_assoc() : false; } }
if (!function_exists('mysqli_num_rows')) { function mysqli_num_rows($r) { return ($r instanceof PDO_Result_Wrapper) ? $r->num_rows : 0; } }
if (!function_exists('mysqli_insert_id')) { function mysqli_insert_id($c) { 
    if ($c instanceof PDO_Conn_Wrapper) {
        try { return $c->getPDO()->lastInsertId(); } catch (Exception $e) { return 0; }
    }
    return 0;
} }
if (!function_exists('mysqli_prepare')) { function mysqli_prepare($c, $q) { return ($c instanceof PDO_Conn_Wrapper) ? $c->prepare($q) : false; } }
if (!function_exists('mysqli_stmt_bind_param')) { function mysqli_stmt_bind_param($s, $t, ...$v) { return $s->bind_param($t, ...$v); } }
if (!function_exists('mysqli_stmt_execute')) { function mysqli_stmt_execute($s) { return $s->execute(); } }
if (!function_exists('mysqli_stmt_get_result')) { function mysqli_stmt_get_result($s) { return $s->get_result(); } }
if (!function_exists('mysqli_stmt_close')) { function mysqli_stmt_close($s) { return true; } }
if (!function_exists('mysqli_free_result')) { function mysqli_free_result($r) { return true; } }
if (!function_exists('mysqli_fetch_all')) { 
    function mysqli_fetch_all($r, $m = 1) { 
        return ($r instanceof PDO_Result_Wrapper) ? $r->fetch_all($m) : []; 
    } 
}
if (!function_exists('mysqli_affected_rows')) { function mysqli_affected_rows($c) { return ($c instanceof PDO_Conn_Wrapper) ? $c->affected_rows() : 0; } }
// Force Redploy Trace: 1713076566
if (!function_exists('mysqli_begin_transaction')) { function mysqli_begin_transaction($c) { return ($c instanceof PDO_Conn_Wrapper) ? $c->begin_transaction() : false; } }
if (!function_exists('mysqli_commit')) { function mysqli_commit($c) { return ($c instanceof PDO_Conn_Wrapper) ? $c->commit() : false; } }
if (!function_exists('mysqli_rollback')) { function mysqli_rollback($c) { return ($c instanceof PDO_Conn_Wrapper) ? $c->rollback() : false; } }
if (!function_exists('mysqli_real_escape_string')) { function mysqli_real_escape_string($c, $s) { return ($c instanceof PDO_Conn_Wrapper) ? $c->real_escape_string($s) : str_replace("'", "''", $s); } }
if (!function_exists('mysqli_error')) { function mysqli_error($c) { return ($c instanceof PDO_Conn_Wrapper) ? ($c->connect_error ?? 'Unknown error') : ''; } }
if (!function_exists('mysqli_fetch_array')) { function mysqli_fetch_array($r) { return ($r instanceof PDO_Result_Wrapper) ? $r->fetch_array() : false; } }
if (!function_exists('mysqli_close')) { function mysqli_close($c) { return true; } }
if (!function_exists('mysqli_report')) { function mysqli_report($flags) { return true; } }
if (!function_exists('mysqli_stmt_affected_rows')) { function mysqli_stmt_affected_rows($s) { return 0; } }
if (!function_exists('mysqli_stmt_error')) { function mysqli_stmt_error($s) { return ''; } }
if (!function_exists('mysqli_data_seek')) { function mysqli_data_seek($r, $o) { return true; } }

return $conn;
?>