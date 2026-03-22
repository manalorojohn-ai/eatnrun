<?php
session_start();
require_once "config/database.php";

$error = null;
$success = null;
$valid_token = false;
$token = $_GET['token'] ?? '';

if (!$token) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Verify token
try {
    $query = "SELECT pr.*, u.email 
              FROM password_resets pr 
              JOIN users u ON pr.user_id = u.id 
              WHERE pr.token = :token 
              AND pr.expires_at > NOW() 
              ORDER BY pr.created_at DESC 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":token", $token);
    $stmt->execute();
    
    if ($reset = $stmt->fetch()) {
        $valid_token = true;
        $user_id = $reset['user_id'];
        $user_email = $reset['email'];
        
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($password !== $confirm_password) {
                $error = "Passwords do not match";
            } else {
                // Update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = :password WHERE id = :user_id";
                
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':password' => $hashed_password,
                    ':user_id' => $user_id
                ]);
                
                // Invalidate token
                $query = "DELETE FROM password_resets WHERE user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->execute([':user_id' => $user_id]);
                
                $success = "Password has been reset successfully. You can now login with your new password.";
            }
        }
    } else {
        $error = "Invalid or expired reset link.";
    }
} catch(Exception $e) {
    $error = "An error occurred. Please try again later.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #006C3B;
            --secondary-green: #008543;
            --accent-yellow: #FFB800;
            --light-yellow: #FFF5D6;
            --text-dark: #2C2C2C;
            --text-light: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--light-yellow);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .forgot-container {
            background: var(--text-light);
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            color: var(--primary-green);
            text-align: center;
            margin-bottom: 1rem;
            font-size: 2rem;
            font-weight: 600;
        }

        p {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #E5E5E5;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-green);
        }

        button {
            width: 100%;
            padding: 1rem;
            background: var(--primary-green);
            color: var(--text-light);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 1rem;
        }

        button:hover {
            background: var(--secondary-green);
        }

        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-to-login a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }

        .error {
            color: #FF4B4B;
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: rgba(255, 75, 75, 0.1);
            border-radius: 5px;
        }

        .success {
            color: var(--primary-green);
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: rgba(0, 108, 59, 0.1);
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <h2>Reset Password</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
            <div class="back-to-login">
                <a href="login.php">Back to Login</a>
            </div>
        <?php elseif ($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="back-to-login">
                <a href="login.php">Go to Login</a>
            </div>
        <?php elseif ($valid_token): ?>
            <p>Enter your new password for <?php echo htmlspecialchars($user_email); ?></p>
            
            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>