<?php
/**
 * Database Connection Test Script
 * Tests connection to Neon PostgreSQL database
 */

// Load environment variables from .env if it exists
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Get connection details
$host = getenv('DB_HOST') ?: $_POST['host'] ?? 'localhost';
$user = getenv('DB_USER') ?: $_POST['user'] ?? 'root';
$password = getenv('DB_PASS') ?: $_POST['password'] ?? '';
$database = getenv('DB_NAME') ?: $_POST['database'] ?? 'food_ordering';
$port = getenv('DB_PORT') ?: $_POST['port'] ?? '5432';

// Determine if this is a JSON request
$is_json = isset($_POST['host']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

$result = [
    'connected' => false,
    'host' => $host,
    'user' => $user,
    'database' => $database,
    'port' => $port,
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Try PostgreSQL connection
    $dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=require";
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    // Test query
    $test_query = $conn->query("SELECT 1 as test");
    if ($test_query) {
        $result['connected'] = true;
        $result['message'] = 'Successfully connected to Neon PostgreSQL database';
        $result['type'] = 'PostgreSQL';
    }
    
} catch (PDOException $e) {
    $result['error'] = $e->getMessage();
    $result['code'] = $e->getCode();
    
    // Try to provide helpful error message
    if (strpos($e->getMessage(), 'could not translate host name') !== false) {
        $result['hint'] = 'Invalid host. Check your Neon connection string.';
    } elseif (strpos($e->getMessage(), 'password authentication failed') !== false) {
        $result['hint'] = 'Invalid credentials. Check your username and password.';
    } elseif (strpos($e->getMessage(), 'does not exist') !== false) {
        $result['hint'] = 'Database does not exist. Create it in Neon console.';
    } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
        $result['hint'] = 'Connection refused. Check if Neon service is running.';
    } elseif (strpos($e->getMessage(), 'timeout') !== false) {
        $result['hint'] = 'Connection timeout. Check your internet connection.';
    }
}

// Return response
if ($is_json) {
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
} else {
    // HTML response
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Connection Test</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #333;
                border-bottom: 2px solid #006C3B;
                padding-bottom: 10px;
            }
            .status {
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 20px;
                font-weight: bold;
            }
            .status.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .status.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .details {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 4px;
                border: 1px solid #dee2e6;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #dee2e6;
            }
            .detail-row:last-child {
                border-bottom: none;
            }
            .detail-label {
                font-weight: bold;
                color: #333;
            }
            .detail-value {
                color: #666;
                word-break: break-all;
            }
            .hint {
                background: #fff3cd;
                border: 1px solid #ffc107;
                color: #856404;
                padding: 12px;
                border-radius: 4px;
                margin-top: 15px;
            }
            .code {
                background: #f4f4f4;
                padding: 10px;
                border-radius: 4px;
                font-family: monospace;
                overflow-x: auto;
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🗄️ Database Connection Test</h1>
            
            <div class="status <?php echo $result['connected'] ? 'success' : 'error'; ?>">
                <?php echo $result['connected'] ? '✓ Connection Successful' : '✗ Connection Failed'; ?>
            </div>
            
            <div class="details">
                <div class="detail-row">
                    <span class="detail-label">Host:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($host); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">User:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($user); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Database:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($database); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Port:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($port); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Timestamp:</span>
                    <span class="detail-value"><?php echo $result['timestamp']; ?></span>
                </div>
                <?php if ($result['connected']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value"><?php echo $result['type']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Message:</span>
                        <span class="detail-value"><?php echo $result['message']; ?></span>
                    </div>
                <?php else: ?>
                    <div class="detail-row">
                        <span class="detail-label">Error:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($result['error']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!$result['connected'] && isset($result['hint'])): ?>
                <div class="hint">
                    <strong>💡 Hint:</strong> <?php echo $result['hint']; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$result['connected']): ?>
                <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 4px; color: #004085;">
                    <strong>Troubleshooting:</strong>
                    <ul>
                        <li>Verify your Neon connection string is correct</li>
                        <li>Check that your credentials are accurate</li>
                        <li>Ensure the database exists in Neon</li>
                        <li>Verify your internet connection</li>
                        <li>Check if Neon service is running</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
?>
