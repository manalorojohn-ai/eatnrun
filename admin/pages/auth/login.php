<?php
session_start();

// Navigate up 3 levels from /admin/pages/auth to reach root
require_once dirname(__DIR__, 3) . '/config/db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Test admin users for development/testing
    $test_admins = [
        [
            'id' => 1,
            'email' => 'admin@eatnrun.com',
            'password_plain' => 'admin123',
            'full_name' => 'Admin User',
            'role' => 'admin',
            'status' => 'active',
        ]
    ];
    
    // Try database first
    $user = null;
    try {
        if ($conn instanceof PDO) {
            // PDO connection
            $query = "SELECT * FROM users WHERE LOWER(email) = LOWER(?) AND role = 'admin' AND status = 'active' LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // MySQLi connection
            $query = "SELECT * FROM users WHERE LOWER(email) = LOWER(?) AND role = 'admin' AND status = 'active' LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        error_log("Admin login database error: " . $e->getMessage());
    }
    
    // Fall back to test admin if no database user found
    if (!$user) {
        foreach ($test_admins as $test_admin) {
            if (strtolower($test_admin['email']) === strtolower($email)) {
                $user = $test_admin;
                break;
            }
        }
    }
    
    // Verify password
    $password_valid = false;
    if ($user) {
        if (isset($user['password_plain'])) {
            // Test admin with plain password
            $password_valid = ($password === $user['password_plain']);
        } else {
            // Database admin with hashed password
            $password_valid = password_verify($password, $user['password']);
        }
    }
    
    if ($user && $password_valid) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'] ?? 'Admin';
        header("Location: /admin/dashboard");
        exit();
    } else {
        $error_message = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Eat&Run</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #FDF5E6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header img {
            height: 50px;
            margin-bottom: 15px;
        }
        .login-header h1 {
            color: #006400;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control {
            height: 45px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #006400;
            box-shadow: 0 0 0 0.2rem rgba(0,100,0,0.25);
        }
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            cursor: pointer;
            padding: 5px;
        }
        .btn-login {
            background-color: #006400;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background-color: #005000;
        }
        .error-message {
            background-color: #ffe6e6;
            color: #dc3545;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }
        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-site a {
            color: #006400;
            text-decoration: none;
            font-size: 14px;
        }
        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/images/logo.png" alt="Eat&Run Logo">
            <h1>Admin Login</h1>
            <p>Enter your credentials to access the admin panel</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <span class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn-login">LOGIN</button>
        </form>

        <div class="back-to-site">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 