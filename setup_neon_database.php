<?php
/**
 * Neon Database Setup Script
 * Run this to initialize the database schema and create test users
 */

// Load environment variables
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

$db_host = getenv('DB_HOST');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');
$db_port = getenv('DB_PORT') ?: 5432;

echo "🔌 Connecting to Neon PostgreSQL...\n";
echo "Host: $db_host\n";
echo "Database: $db_name\n";
echo "User: $db_user\n\n";

try {
    // Connect to PostgreSQL
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "✅ Connected to Neon successfully!\n\n";
    
    // Check if tables exist
    $result = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'");
    $table_count = $result->fetchColumn();
    
    if ($table_count == 0) {
        echo "📋 Creating database schema...\n";
        
        // Read the SQL file
        $sql_file = __DIR__ . '/database/food_ordering_export.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            
            // Split by statements and execute
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement) && strpos($statement, 'SET') !== 0) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Skip duplicate table errors
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "⚠️  Error: " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
            
            echo "✅ Database schema created!\n\n";
        }
    } else {
        echo "✅ Database tables already exist ($table_count tables found)\n\n";
    }
    
    // Check for test users
    $result = $pdo->query("SELECT COUNT(*) FROM users WHERE email IN ('john@gmail.com', 'maria@yahoo.com', 'juan@outlook.com')");
    $user_count = $result->fetchColumn();
    
    if ($user_count == 0) {
        echo "👥 Creating test users...\n";
        
        $test_users = [
            ['johndoe', 'john@gmail.com', 'password123', 'John Doe', '09123456789'],
            ['maria', 'maria@yahoo.com', 'maria123', 'Maria Santos', '09987654321'],
            ['juancruz', 'juan@outlook.com', 'juan2024', 'Juan Cruz', '09234567890']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, phone, role, status, is_verified) 
            VALUES (?, ?, ?, ?, ?, 'user', 'active', 1)
            ON CONFLICT (email) DO NOTHING
        ");
        
        foreach ($test_users as [$username, $email, $password, $full_name, $phone]) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt->execute([$username, $email, $hashed_password, $full_name, $phone]);
            echo "  ✅ Created user: $username ($email)\n";
        }
        
        echo "\n✅ Test users created!\n";
    } else {
        echo "✅ Test users already exist ($user_count found)\n\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 Setup Complete!\n";
    echo str_repeat("=", 50) . "\n\n";
    
    echo "Test Credentials:\n";
    echo "1. Email: john@gmail.com | Password: password123\n";
    echo "2. Email: maria@yahoo.com | Password: maria123\n";
    echo "3. Email: juan@outlook.com | Password: juan2024\n\n";
    
    echo "You can now:\n";
    echo "✓ Log in at: https://eatnrun.onrender.com/login\n";
    echo "✓ Browse menu at: https://eatnrun.onrender.com/menu\n";
    echo "✓ Place orders\n\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Troubleshooting:\n";
    echo "1. Check your .env file has correct credentials\n";
    echo "2. Verify Neon database is active\n";
    echo "3. Check firewall/network connectivity\n";
    exit(1);
}
?>
