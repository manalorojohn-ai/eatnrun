<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'includes/connection.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Query the database
    $query = "SELECT * FROM users WHERE email = ? AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verify password using password_verify
    if ($user && password_verify($password, $user['password'])) {
        // Admin users bypass document verification checks
        if ($user['role'] === 'admin') {
            // Login successful for admin
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect admin to dashboard
            header("Location: admin/dashboard.php");
            exit();
        }
        
        // Regular customer verification checks
        // Check if email is verified
        if (!$user['is_verified']) {
            $error_message = "Please verify your email address first. Check your inbox for the verification code.";
        }
        // Check if documents are approved
        elseif ($user['document_status'] === 'pending') {
            $error_message = "Your documents are under review. Please wait for admin approval.";
        }
        elseif ($user['document_status'] === 'rejected') {
            $error_message = "Your documents were rejected. Please contact support for more information.";
        }
        elseif ($user['document_status'] === 'approved') {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect customer to main page
            header("Location: index.php");
            exit();
        } else {
            // Document status is null or invalid - redirect to document submission
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email_verified'] = true;
            header("Location: submit_documents.php");
            exit();
        }
    } else {
        // Login failed
        $error_message = "Invalid email or password.";
    }
}

// If we reach here, we need to show the login form
// Don't include navbar.php here as it sends output before headers
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/buttons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html {
            height: 100%;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            opacity: 0;
            animation: fadeInPage 0.8s ease-out forwards;
        }

        @keyframes fadeInPage {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-banner {
            background: linear-gradient(135deg, #006C3B, #005530);
            color: white;
            text-align: center;
            padding: 3rem 1rem;
            margin-top: 60px;
            position: relative;
            overflow: hidden;
            animation: slideInBanner 1s ease-out;
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .welcome-banner h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .welcome-banner p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .main-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            margin-top: -3rem;
            position: relative;
            z-index: 1;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            position: relative;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .login-card h2 {
            color: #006C3B;
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .login-card h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #006C3B, #00A65A);
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .login-card:hover h2::after {
            width: 80px;
        }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            animation: slideUpFade 0.5s ease-out forwards;
        }

        .form-group:nth-child(2) {
            animation-delay: 0.1s;
        }

        @keyframes slideUpFade {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            color: #333;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #006C3B;
            box-shadow: 0 0 0 4px rgba(0, 108, 59, 0.1);
        }

        .forgot-password {
            text-align: right;
            margin: 1.2rem 0;
            opacity: 0;
            animation: fadeIn 0.5s ease-out 0.3s forwards;
        }

        .forgot-password a {
            color: #006C3B;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password a:hover {
            color: #00A65A;
            text-decoration: underline;
        }

        .sign-in-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #006C3B, #00A65A);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            margin: 1.8rem 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            animation: fadeIn 0.5s ease-out 0.4s forwards;
            position: relative;
            overflow: hidden;
        }

        .sign-in-btn:hover {
            background: linear-gradient(135deg, #005530, #008C4A);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 108, 59, 0.3);
        }

        .sign-in-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(45deg);
            transition: 0.5s;
            opacity: 0;
        }

        .sign-in-btn:hover::after {
            animation: shine 1s;
        }

        @keyframes shine {
            0% {
                transform: rotate(45deg) translateX(-100%);
                opacity: 0;
            }
            50% {
                opacity: 0.7;
            }
            100% {
                transform: rotate(45deg) translateX(100%);
                opacity: 0;
            }
        }

        .register-prompt {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            font-size: 0.95rem;
            opacity: 0;
            animation: fadeIn 0.5s ease-out 0.5s forwards;
        }

        .register-prompt a {
            color: #006C3B;
            text-decoration: none;
            font-weight: 600;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }

        .register-prompt a:hover {
            color: #00A65A;
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 768px) {
            .welcome-banner {
                padding: 2rem 1rem;
            }

            .welcome-banner h1 {
                font-size: 2rem;
            }

            .main-container {
                padding: 1.5rem;
                margin-top: -2rem;
            }

            .login-card {
                padding: 2rem;
                border-radius: 20px;
            }
        }

        @media (max-height: 700px) {
            .welcome-banner {
                padding: 2rem 1rem;
            }

            .main-container {
                margin-top: -2rem;
            }
        }

        .password-field {
            position: relative;
            width: 100%;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #006C3B;
        }

        /* Enhanced Navbar Styles */
        .navbar {
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(0, 108, 59, 0.1);
            transition: all 0.3s ease;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.99) !important;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.15);
            padding: 0.8rem 0;
        }

        .navbar-brand {
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover {
            transform: translateY(-1px);
        }

        .navbar-brand img {
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
        }

        .navbar-brand span {
            color: #006C3B !important;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .nav-link {
            color: #333 !important;
            font-weight: 500;
            padding: 0.75rem 1rem !important;
            border-radius: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .nav-link:hover {
            background: rgba(0, 108, 59, 0.1) !important;
            color: #006C3B !important;
            transform: translateY(-1px);
        }

        .nav-link i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .nav-link:hover i {
            transform: translateY(-2px);
        }

        .btn-outline-success {
            border: 2px solid #006C3B !important;
            color: #006C3B !important;
            background: transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-outline-success:hover {
            background: #006C3B !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.15);
        }

        .btn-success {
            background: #006C3B !important;
            border: 2px solid #006C3B !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-success:hover {
            background: #005530 !important;
            border-color: #005530 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.15);
        }

        .navbar-toggler {
            border: none !important;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(0, 108, 59, 0.25) !important;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Mobile responsiveness */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(15px);
                -webkit-backdrop-filter: blur(15px);
                border-radius: 15px;
                margin-top: 1rem;
                padding: 1rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            }

            .nav-link {
                padding: 1rem !important;
                margin: 0.25rem 0;
                text-align: center;
            }

            .d-flex.gap-2 {
                flex-direction: column;
                gap: 0.75rem !important;
                margin-top: 1rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="login-page">
    <!-- Modern Bootstrap 5 Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/logo.png" alt="Eat&Run Logo" height="40" class="me-2">
                <span class="fw-bold text-success fs-4">Eat&Run</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="menu.php">
                            <i class="fas fa-utensils me-1"></i>Menu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="about.php">
                            <i class="fas fa-info-circle me-1"></i>About
                        </a>
                    </li>
                </ul>
                
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-success px-3 py-2 fw-medium" href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a class="btn btn-success px-3 py-2 fw-medium" href="register.php">
                        <i class="fas fa-user-plus me-1"></i>Register
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="welcome-banner">
        <h1>Welcome Back!</h1>
        <p>Sign in to your account to continue ordering your favorite meals</p>
    </div>
            
    <div class="main-container">
        <div class="login-container">
            <div class="login-card">
                <h2>Sign In</h2>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #f5c6cb;">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #c3e6cb;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" required>
                            <span class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="forgot-password">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>

                    <button type="submit" class="sign-in-btn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>

                    <div class="register-prompt">
                        Don't have an account? <a href="register.php">Sign Up</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            document.querySelector('.password-toggle').addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleIcon.classList.remove('fa-eye');
                    toggleIcon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    toggleIcon.classList.remove('fa-eye-slash');
                    toggleIcon.classList.add('fa-eye');
                }
            });
            
            // Navbar scroll effect
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
            
        });
    </script>
</body>
</html>