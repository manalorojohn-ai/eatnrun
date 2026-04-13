<?php
/**
 * Optimized Database Core for Eat&Run
 * Optimized for Render/Postgres Performance
 */

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'food_ordering');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: (getenv('RENDER') ? 5432 : 3306));

$using_postgres = false;
$is_render = (bool)getenv('RENDER');

if ($is_render || (getenv('DB_HOST') && extension_loaded('pdo_pgsql'))) {
    try {
        $endpoint = explode('.', DB_HOST)[0];
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
        $neon_pass = "endpoint=$endpoint;" . DB_PASS;
        $conn = new PDO($dsn, DB_USER, $neon_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]);
        $using_postgres = true;
    } catch (PDOException $e) {
        if ($is_render) die("Neon Connection Error: " . $e->getMessage());
    }
}

if (!$using_postgres && !isset($conn)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
    $conn->set_charset("utf8mb4");
}

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

return $conn;
?>