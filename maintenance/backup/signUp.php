<?php
session_start();
require_once "config/database.php";

// If user is already logged in, redirect to home page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if username already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already exists";
        } else {
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already exists";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
                
                if ($stmt->execute([$username, $email, $hashed_password])) {
                    $_SESSION['success_message'] = "Registration successful! Please login.";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        /* Navigation Styles */
        .navbar {
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
        }

        .nav-logo img {
            height: 40px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .nav-menu a {
            text-decoration: none;
            color: #333;
            font-size: 14px;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
        }

        .login-btn {
            color: #006C3B;
            text-decoration: none;
        }

        .signup-btn {
            background: #006C3B;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
        }

        /* Hero Section */
        .hero-section {
            background: #FFF8E7;
            padding: 40px;
            text-align: center;
        }

        .hero-title {
            color: #006C3B;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .hero-subtitle {
            color: #666;
            font-size: 14px;
        }

        /* Form Styles */
        .signup-form {
            max-width: 400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .signup-button {
            width: 100%;
            padding: 12px;
            background: #006C3B;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            margin-bottom: 15px;
        }

        .login-link {
            text-align: center;
            font-size: 14px;
        }

        .login-link a {
            color: #006C3B;
            text-decoration: none;
        }

        /* Footer Styles */
        .footer {
            background: #006C3B;
            color: white;
            padding: 40px;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
        }

        .footer-section h3 {
            color: #FFB800;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .footer-section p,
        .footer-section a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            line-height: 1.8;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-logo">
            <img src="assets/images/Eat&Run Logo.png" alt="Eat&Run Logo">
        </a>
        <div class="nav-menu">
            <a href="index.php">Home</a>
            <a href="restaurants.php">Restaurants</a>
            <a href="menu.php">Menu</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <div class="nav-buttons">
                <a href="login.php" class="login-btn">Login</a>
                <a href="signup.php" class="signup-btn">Sign Up</a>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <h1 class="hero-title">Create an Account</h1>
        <p class="hero-subtitle">Join Eat&Run and start ordering your favorite meals!</p>
    </section>

    <form class="signup-form" method="POST" action="">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" class="form-input" name="username" required>
        </div>

        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" class="form-input" name="email" required>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" class="form-input" name="password" required>
        </div>

        <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-input" name="confirm_password" required>
        </div>

        <button type="submit" class="signup-button">Sign Up</button>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </form>

    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Eat&Run</h3>
                <p>Your favorite local restaurants delivered to your doorstep. Fast, fresh, and convenient food delivery service.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="menu.php">Menu</a></li>
                    <li><a href="restaurants.php">Restaurants</a></li>
                    <li><a href="about.php">About Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Help & Support</h3>
                <ul class="footer-links">
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="terms.php">Terms of Service</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="delivery.php">Delivery Information</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Info</h3>
                <p>📞 0912 345 6789</p>
                <p>✉️ eat&run@example.com</p>
                <p>📍 E. Taleon st, Santisima Cruz, Philippines</p>
            </div>
        </div>
    </footer>
</body>
</html> 