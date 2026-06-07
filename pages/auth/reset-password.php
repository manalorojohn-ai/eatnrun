<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['error'] = 'Invalid reset link';
    header("Location: forgot-password.php");
    exit();
}

// Verify token
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    $_SESSION['error'] = 'Invalid or expired reset link';
    header("Location: forgot-password.php");
    exit();
}

$user_id = $user['id'];

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate password
    if (empty($password)) {
        $error = 'Password is required';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!preg_match('/^[A-Za-z0-9]+$/', $password)) {
        $error = 'Password can only contain letters and numbers';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    }
    
    if (empty($error)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        $_SESSION['success'] = 'Your password has been reset successfully. You can now login.';
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Eat&Run</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/navbar-enhanced.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-dark: #005530;
            --primary-light: #00A65A;
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --card-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.08);
        }

        body {
            min-height: 100vh;
            background: var(--bg-gradient);
            font-family: 'Poppins', sans-serif;
            color: #1f2937;
        }

        .page-wrapper {
            min-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .reset-container {
            width: 100%;
            max-width: 450px;
            margin: 3rem auto;
            padding: 0 1rem;
            flex: 1;
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--card-shadow);
            border-radius: 1.5rem;
            overflow: hidden;
        }

        .card-body {
            background: linear-gradient(
                145deg,
                rgba(255, 255, 255, 0.95) 0%,
                rgba(255, 255, 255, 0.85) 100%
            );
            padding: 2.5rem;
        }

        .form-control {
            border: 1.5px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 108, 59, 0.15);
            background-color: #f8fafb;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 108, 59, 0.2);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .alert {
            border-radius: 0.75rem;
            border: none;
            animation: slideDown 0.3s ease;
        }

        .password-strength {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #ef4444; }
        .strength-fair { background: #f97316; }
        .strength-good { background: #eab308; }
        .strength-strong { background: #22c55e; }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        footer {
            margin-top: auto;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/ui/navbar.php'; ?>

    <div class="page-wrapper">
        <div class="reset-container">
            <div class="card">
                <div class="card-body">
                    <h1 class="page-title text-center">
                        <i class="fas fa-key"></i> Reset Password
                    </h1>
                    <p class="page-subtitle text-center">
                        Enter your new password below
                    </p>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="reset-password.php?token=<?php echo urlencode($token); ?>" novalidate>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-600 mb-2">
                                <i class="fas fa-lock me-2" style="color: var(--primary-color);"></i>New Password
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Enter new password"
                                   required>
                            <small class="text-muted">At least 6 characters (letters and numbers only)</small>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-600 mb-2">
                                <i class="fas fa-lock-open me-2" style="color: var(--primary-color);"></i>Confirm Password
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Confirm your password"
                                   required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-check me-2"></i>Reset Password
                        </button>
                    </form>

                    <div class="text-center">
                        <a href="login.php" class="text-decoration-none fw-600" style="color: var(--primary-color);">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include dirname(__DIR__) . '/includes/ui/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const strengthBar = document.getElementById('strengthBar');
            let strength = 0;

            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
            if (/\d/.test(password)) strength += 25;

            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) strengthBar.className = 'password-strength-bar strength-weak';
            else if (strength <= 50) strengthBar.className = 'password-strength-bar strength-fair';
            else if (strength <= 75) strengthBar.className = 'password-strength-bar strength-good';
            else strengthBar.className = 'password-strength-bar strength-strong';
        });
    </script>
</body>
</html>
