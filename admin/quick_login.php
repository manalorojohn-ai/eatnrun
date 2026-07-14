<?php
/**
 * Quick Login - Direct database password update
 * Connects directly to Neon and updates password
 */

$db_host = 'ep-odd-art-apysk1bo-pooler.c-7.us-east-1.aws.neon.tech';
$db_user = 'neondb_owner';
$db_pass = 'npg_L3bEXhDZSiK6';
$db_name = 'neondb';
$db_port = 5432;

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        $message = '❌ Email and password required';
    } else {
        try {
            // Direct PDO connection to Neon
            $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require;application_name=eatnrun";
            $pdo = new PDO($dsn, $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);
            
            // Hash password
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            
            // Update user
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $email]);
            
            if ($stmt->rowCount() > 0) {
                $message = "✅ SUCCESS!<br>Email: $email<br>Password: $password<br><br>You can now login!";
                $success = true;
            } else {
                $message = "❌ User not found: $email";
            }
            
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Login Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #006C3B 0%, #005530 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 400px;
            width: 100%;
        }
        h1 {
            color: #006C3B;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus {
            outline: none;
            border-color: #006C3B;
            box-shadow: 0 0 5px rgba(0,108,59,0.3);
        }
        button {
            width: 100%;
            padding: 12px;
            background: #006C3B;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #005530;
        }
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .link {
            text-align: center;
            margin-top: 20px;
        }
        .link a {
            color: #006C3B;
            text-decoration: none;
        }
        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Set Login Password</h1>
        
        <form method="POST">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required placeholder="maria@yahoo.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required placeholder="Password123!" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
            </div>
            
            <button type="submit">Update Password</button>
        </form>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
            <?php if ($success): ?>
                <div class="link">
                    <a href="/login">← Go to Login</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
