<?php
/**
 * Create Test Users Script
 * Properly hashes passwords and inserts test users into Neon database
 * Access via: https://eatnrun.onrender.com/create_test_users.php
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Test Users</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .output { background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 5px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>🔧 Eat&Run Test Users Setup</h1>
    
    <?php
    try {
        // Connect to PostgreSQL
        $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        echo '<p class="success">✅ Connected to Neon successfully!</p>';
        
        // Define test users
        $test_users = [
            [
                'username' => 'johndoe',
                'email' => 'john@gmail.com',
                'password' => 'password123',
                'full_name' => 'John Doe',
                'phone' => '09123456789'
            ],
            [
                'username' => 'maria',
                'email' => 'maria@yahoo.com',
                'password' => 'maria123',
                'full_name' => 'Maria Santos',
                'phone' => '09987654321'
            ],
            [
                'username' => 'juancruz',
                'email' => 'juan@outlook.com',
                'password' => 'juan2024',
                'full_name' => 'Juan Cruz',
                'phone' => '09234567890'
            ]
        ];
        
        echo '<h2>👥 Creating Test Users...</h2>';
        echo '<div class="output">';
        
        foreach ($test_users as $user) {
            // Check if user exists
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$user['email']]);
            
            if ($check->rowCount() > 0) {
                echo "⚠️  User '{$user['email']}' already exists. Deleting...\n";
                $delete = $pdo->prepare("DELETE FROM users WHERE email = ?");
                $delete->execute([$user['email']]);
            }
            
            // Hash password
            $hashed_password = password_hash($user['password'], PASSWORD_BCRYPT);
            
            // Insert user
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, phone, role, status, is_verified)
                VALUES (?, ?, ?, ?, ?, 'user', 'active', 1)
            ");
            
            $stmt->execute([
                $user['username'],
                $user['email'],
                $hashed_password,
                $user['full_name'],
                $user['phone']
            ]);
            
            echo "✅ Created user: {$user['full_name']}\n";
            echo "   Email: {$user['email']}\n";
            echo "   Password: {$user['password']}\n\n";
        }
        
        echo '</div>';
        
        echo '<h2>🎉 Success!</h2>';
        echo '<p class="success">All test users have been created!</p>';
        echo '<div class="output">
You can now login with:

1. Email: john@gmail.com
   Password: password123

2. Email: maria@yahoo.com
   Password: maria123

3. Email: juan@outlook.com
   Password: juan2024
</div>';
        
        echo '<p><a href="/login" style="background: #006C3B; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Go to Login →</a></p>';
        
    } catch (PDOException $e) {
        echo '<p class="error">❌ Connection failed!</p>';
        echo '<div class="output">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<p class="info">Make sure your .env file has correct Neon credentials.</p>';
    }
    ?>
</body>
</html>

