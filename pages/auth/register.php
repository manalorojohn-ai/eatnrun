<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$composerAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    die('Composer dependencies are missing. Run "composer install" from the project root and ensure the vendor directory exists.');
}
require_once $composerAutoload;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Function to generate OTP
function generateOTP() {
    return sprintf("%06d", mt_rand(0, 999999));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate Full Name
    $full_name = trim($_POST['full_name']);
    if (empty($full_name)) {
        $error = 'Full name is required';
    } elseif (!preg_match('/^[a-zA-Z\s]{2,50}$/', $full_name)) {
        $error = 'Name should only contain letters and spaces';
    }

    // Validate Username
    $username = trim($_POST['username']);
    if (empty($username)) {
        $error = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        $error = 'Username should be 4-20 characters and contain only letters, numbers, and underscores';
    } else {
        // Check if username exists
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        mysqli_stmt_close($stmt);
        
        if ($count > 0) {
            $error = 'Username already exists';
        }
    }

    // Validate Email
    $email = trim($_POST['email']);
    if (empty($email)) {
        $error = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (!preg_match('/^[\w.+-]+@(gmail|yahoo|outlook)\.com$/', $email)) {
        $error = 'Only gmail.com, yahoo.com, or outlook.com emails are allowed';
    } else {
        // Check if email exists
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];
        mysqli_stmt_close($stmt);
        
        if ($count > 0) {
            $error = 'Email already exists';
        }
    }

    // Validate Phone Number
    $phone = trim($_POST['phone']);
    if (empty($phone)) {
        $error = 'Phone number is required';
    } elseif (!preg_match('/^09\d{9}$/', $phone)) {
        $error = 'Phone number should start with 09 and be 11 digits long';
    }

    // Validate Password
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password)) {
        $error = 'Password is required';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!preg_match('/^[A-Za-z0-9]+$/', $password)) {
        $error = 'Password can only contain letters and numbers';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    }

    // If no errors, proceed with registration and OTP
    if (empty($error)) {
        mysqli_begin_transaction($conn);
        
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $otp = generateOTP();
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Insert user
            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, username, email, phone, password, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
            mysqli_stmt_bind_param($stmt, "sssss", $full_name, $username, $email, $phone, $hashed_password);
            mysqli_stmt_execute($stmt);
            $user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Store OTP
            $stmt = mysqli_prepare($conn, "INSERT INTO email_verifications (user_id, email, otp, expiry) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isss", $user_id, $email, $otp, $otp_expiry);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Send verification email
            $mail = new PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'manalorojohn@gmail.com';
                $mail->Password = 'jrsp vsza dxvw zcsa';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('miguelantonioramos140@gmail.com', 'Eat&Run');
                $mail->addAddress($email, $full_name);

                $mail->isHTML(true);
                $mail->Subject = 'Welcome to Eat&Run!';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color: #006C3B; text-align: center;'>Welcome to Eat&Run!</h2>
                        <p>Dear {$full_name},</p>
                        <p>Thank you for registering with Eat&Run. To complete your registration, please use the following OTP code:</p>
                        <div style='background: #f4f4f4; padding: 20px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                            {$otp}
                        </div>
                        <p>This code will expire in 10 minutes.</p>
                        <p>If you didn't request this verification, please ignore this email.</p>
                        <p>Best regards,<br>The Eat&Run Team</p>
                    </div>";

                $mail->send();
                mysqli_commit($conn);

                // Store user data in session for verification
                $_SESSION['temp_user_id'] = $user_id;
                $_SESSION['temp_email'] = $email;
                
                header("Location: verify-email.php");
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("Email error: " . $mail->ErrorInfo);
                $error = "Failed to send verification email. Please try again.";
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("Registration error: " . $e->getMessage());
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Eat&Run</title>
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Common Styles -->
    <link rel="stylesheet" href="assets/css/shared-styles.css">
    <link rel="stylesheet" href="assets/css/navbar-enhanced.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        :root {
            --bs-primary: #006C3B;
            --bs-primary-rgb: 0, 108, 59;
            --bs-body-font-family: 'Poppins', sans-serif;
            --bs-body-bg: #f8f9fa;
            --bs-body-color: #212529;
            --bs-border-radius: 1rem;
            --bs-border-radius-sm: 0.75rem;
            --bs-border-radius-lg: 1.25rem;
        }

        :root {
            --primary-color: #006C3B;
            --primary-dark: #005530;
            --primary-light: #00A65A;
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --card-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.08);
            --input-shadow: 0 0 0 0.25rem rgba(0, 108, 59, 0.15);
        }

        body {
            min-height: 100vh;
            background: var(--bg-gradient);
            font-family: var(--bs-body-font-family);
            color: #1f2937;
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--card-shadow);
        }

        .card-body {
            background: linear-gradient(
                145deg,
                rgba(255, 255, 255, 0.95) 0%,
                rgba(255, 255, 255, 0.85) 100%
            );
        }

        .register-container {
            width: 100%;
            max-width: 520px;
            margin: 2rem auto;
            padding: 0 1rem;
            position: relative;
            z-index: 1;
        }

        .page-wrapper {
            min-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
            position: relative;
            padding: 2rem 0;
            margin-top: 60px;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .form-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: var(--bs-border-radius-lg);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            width: 100%;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--bs-primary), #00A65A);
            z-index: 1;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--bs-primary) 0%, #00A65A 100%);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .logo-container {
            width: 110px;
            height: 110px;
            margin: 0 auto 1.5rem;
            position: relative;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: var(--bs-border-radius);
            background: linear-gradient(145deg, #ffffff, #f5f5f5);
            padding: 1rem;
            box-shadow: 
                0 4px 15px rgba(0, 0, 0, 0.08),
                inset 2px 2px 5px rgba(255, 255, 255, 0.95),
                inset -2px -2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .logo-container:hover {
            transform: translateY(-5px) scale(1.02);
        }

        .logo-container:hover img {
            box-shadow: 
                0 8px 25px rgba(0, 0, 0, 0.12),
                inset 2px 2px 5px rgba(255, 255, 255, 0.95),
                inset -2px -2px 5px rgba(0, 0, 0, 0.05);
        }

        .register-header h1 {
            color: var(--bs-primary);
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0.5rem 0;
            letter-spacing: -0.02em;
        }

        .register-header p {
            font-size: 1.1rem;
            color: #666;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
            animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }

        .row .form-group {
            margin-bottom: 1rem;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateY(10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-group {
            position: relative;
            margin-bottom: 1rem;
        }

        /* Modern form styling */
        .form-floating {
            position: relative;
            margin-bottom: 1rem;
        }

        .form-floating > .form-control,
        .form-floating > .form-select {
            height: calc(3.5rem + 2px);
            padding: 1rem 0.75rem;
            font-size: 0.95rem;
            line-height: 1.25;
        }

        .form-floating > label {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 1rem 0.75rem;
            overflow: hidden;
            text-align: start;
            text-overflow: ellipsis;
            white-space: nowrap;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out,transform .1s ease-in-out;
            color: #6c757d;
            font-size: 0.95rem;
        }

        .form-floating > .form-control {
            padding: 1.625rem 0.75rem 0.625rem;
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
        }

        .form-floating > .form-control:focus {
            border-color: #006C3B;
            box-shadow: 0 0 0 0.25rem rgba(0, 108, 59, 0.15);
            outline: 0;
        }

        .form-floating > .form-control:hover:not(:focus) {
            border-color: #adb5bd;
        }

        .form-floating > .form-control:not(:placeholder-shown) ~ label,
        .form-floating > .form-control:focus ~ label {
            opacity: 0.65;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
            background-color: white;
            padding: 0 0.25rem;
            height: auto;
        }

        /* Button styling */
        .btn-primary {
            --bs-btn-bg: #006C3B;
            --bs-btn-border-color: #006C3B;
            --bs-btn-hover-bg: #005530;
            --bs-btn-hover-border-color: #005530;
            --bs-btn-active-bg: #004025;
            --bs-btn-active-border-color: #004025;
            font-weight: 500;
            padding: 0.8rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.15);
        }

        .btn-lg {
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
        }

        /* Card styling */
        .card {
            border: none;
            transition: all 0.3s ease;
        }

        .card-body {
            background: linear-gradient(to bottom right, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.95));
            backdrop-filter: blur(10px);
        }

        /* Form validation */
        .invalid-feedback {
            font-size: 0.875rem;
            color: #dc3545;
            margin-top: 0.25rem;
        }

        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        /* Links */
        a {
            color: #006C3B;
            transition: all 0.2s ease;
        }

        a:hover {
            color: #005530;
            text-decoration: none;
        }

        /* Modern webkit autofill styles */
        .form-control:-webkit-autofill,
        .form-control:-webkit-autofill:hover,
        .form-control:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0px 1000px white inset;
            -webkit-text-fill-color: #1f2937;
            transition: background-color 5000s ease-in-out 0s;
        }

        .form-label {
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .helper-text {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .helper-text i {
            font-size: 0.875rem;
            color: var(--bs-primary);
        }

        .btn {
            --bs-btn-padding-x: 1.5rem;
            --bs-btn-padding-y: 0.75rem;
            --bs-btn-font-size: 1rem;
            --bs-btn-font-weight: 500;
            --bs-btn-line-height: 1.5;
            --bs-btn-border-radius: 0.5rem;
            --bs-btn-transition: all 0.2s ease-in-out;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--gradient-start), var(--gradient-end));
            border: none;
            color: white;
            font-weight: 600;
            padding: 1rem 2rem;
            box-shadow: 0 4px 6px rgba(0, 108, 59, 0.1);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 108, 59, 0.15);
            background: linear-gradient(to right, var(--gradient-end), var(--gradient-start));
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(0, 108, 59, 0.1);
        }

        .btn-lg {
            padding: 1.125rem 2.25rem;
            font-size: 1.125rem;
            border-radius: 0.625rem;
        }

        .form-control::placeholder {
            color: #666;
            opacity: 0.7;
        }

        /* Helper text styles */
        .helper-text {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.5rem;
            padding-left: 15px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0.8;
            text-align: left;
        }

        .helper-text i {
            font-size: 0.8rem;
            opacity: 1;
            color: #666;
            margin-right: 4px;
        }

        /* Input states */
        .form-control:hover {
            border-color: #006C3B;
        }

        .form-control:focus::placeholder {
            opacity: 0.7;
        }

        .btn-register {
            width: 100%;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            margin-top: 2rem;
            background: linear-gradient(90deg, var(--bs-primary) 0%, #00A65A 100%);
            border: none;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.8s ease-out;
            animation-delay: 0.8s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 108, 59, 0.3);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .btn-register i {
            margin-right: 0.5rem;
            transition: transform 0.3s ease;
        }

        .btn-register:hover i {
            transform: rotate(10deg);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
            font-size: 1rem;
            color: #666;
            animation: fadeIn 1s ease-out;
            animation-delay: 1s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        .login-link a {
            color: var(--bs-primary);
            text-decoration: none;
            font-weight: 600;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--bs-primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: right;
        }

        .login-link a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .alert {
            border: none;
            border-radius: var(--bs-border-radius);
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.925rem;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            animation: slideInDown 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            border-left: 4px solid;
        }

        .alert-danger {
            border-left-color: #dc3545;
            background: linear-gradient(to right, rgba(220, 53, 69, 0.05), rgba(220, 53, 69, 0.02));
        }

        .alert-success {
            border-left-color: var(--bs-primary);
            background: linear-gradient(to right, rgba(var(--bs-primary-rgb), 0.05), rgba(var(--bs-primary-rgb), 0.02));
        }

        @keyframes slideInDown {
            0% {
                opacity: 0;
                transform: translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert i {
            font-size: 1.1rem;
        }

        .alert-danger i {
            color: #dc3545;
        }

        .alert-success i {
            color: var(--bs-primary);
        }

        .invalid-feedback {
            font-size: 0.825rem;
            color: #dc3545;
            margin-top: 0.375rem;
            margin-left: 0.75rem;
            display: none;
            opacity: 0;
            transition: all 0.2s ease;
        }

        .was-validated .form-control:invalid,
        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: none;
        }

        .was-validated .form-control:invalid:focus,
        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }

        .was-validated .form-control:invalid ~ .invalid-feedback,
        .form-control.is-invalid ~ .invalid-feedback {
            display: block;
            opacity: 1;
            animation: fadeIn 0.3s ease-out;
        }

        .was-validated .form-control:valid {
            border-color: var(--bs-primary);
            background-image: none;
        }

        .was-validated .form-control:valid:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            color: #6b7280;
            font-size: 0.95rem;
            animation: fadeIn 0.6s ease-out;
            animation-delay: 0.8s;
            animation-fill-mode: forwards;
            opacity: 0;
        }

        .login-link a {
            color: var(--bs-primary);
            text-decoration: none;
            font-weight: 600;
            margin-left: 0.25rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .login-link a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: var(--bs-primary);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .login-link a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        @media (max-width: 991.98px) {
            .page-wrapper {
                padding: 1rem 0;
                margin-top: 40px;
            }

            .form-card {
                padding: 2rem;
            }
        }

        @media (max-width: 767.98px) {
            .register-container {
                padding: 1rem;
                margin: 1rem;
            }

            .logo-container {
                width: 100px;
                height: 100px;
            }

            .register-header h1 {
                font-size: 1.5rem;
            }

            .register-header p {
                font-size: 0.9rem;
            }

            .form-control {
                height: 3rem;
                font-size: 0.9rem;
            }

            .btn-register {
                height: 3rem;
                font-size: 0.95rem;
            }

            .helper-text {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 575.98px) {
            .page-wrapper {
                padding: 0;
            }

            .form-card {
                padding: 1.5rem;
                border-radius: 
                    var(--bs-border-radius) var(--bs-border-radius) 0 0;
            }

            .register-container {
                padding: 0;
                margin: 0;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .row {
                margin: 0 -0.5rem;
            }

            .row > * {
                padding: 0 0.5rem;
            }

            .alert {
                margin: 1rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include 'includes/ui/navbar.php'; ?>
    
    <main class="flex-grow-1">
    <div class="d-flex align-items-center py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-9 col-lg-7 col-xl-6">
                    <div class="card shadow-lg border-0 rounded-4 bg-white">
                        <div class="card-body p-4 p-md-5">
                            <div class="text-center mb-4">
                                <img src="assets/images/logo.png" alt="Eat&Run Logo" class="img-fluid rounded-3 mb-3" width="90" height="90">
                                <h1 class="h3 fw-bold text-primary mb-2">Create Your Account</h1>
                                <p class="text-muted">Join Eat&Run and discover amazing local food!</p>
                            </div>

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger d-flex align-items-center rounded-3 mb-4" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                    <form method="POST" action="register.php" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="full_name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                        value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                                        placeholder="Juan Dela Cruz" required pattern="[a-zA-Z\s]{2,50}">
                                    <div class="invalid-feedback">
                                        Please enter a valid name (2-50 characters, letters and spaces)
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                        placeholder="yourname" required pattern="[a-zA-Z0-9_]{4,20}">
                                    <div class="invalid-feedback">
                                        4-20 characters, letters, numbers, and underscores only
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                        placeholder="09XXXXXXXXX" required pattern="09\d{9}">
                                    <div class="invalid-feedback">
                                        Enter a valid phone number (09XXXXXXXXX)
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                        placeholder="name@gmail.com" required pattern="[\w.+-]+@(gmail|yahoo|outlook)\.com">
                                    <div class="invalid-feedback">
                                        Enter a valid gmail.com, yahoo.com, or outlook.com address
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group" id="passwordGroup">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                        placeholder="••••••" required pattern="[A-Za-z0-9]{6,}">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback">6+ characters, letters and numbers only</div>
                                </div>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div id="passwordStrengthBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                        placeholder="••••••" required>
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#confirm_password">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback">Passwords must match</div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register mt-3">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>

                        <div class="login-link">
                            Already have an account? <a href="login.php">Sign in here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </main>

    <?php include 'includes/ui/footer.php'; ?>

    <!-- Bootstrap 5 JS and Form Validation -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            'use strict'

            const forms = document.querySelectorAll('.needs-validation')
            const password = document.querySelector('#password')
            const confirmPassword = document.querySelector('#confirm_password')
            const strengthBar = document.querySelector('#passwordStrengthBar')

            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match')
                } else {
                    confirmPassword.setCustomValidity('')
                }
            }

            function updateStrength() {
                if (!strengthBar) return
                const value = password.value || ''
                let score = 0
                if (value.length >= 6) score += 30
                if (/[A-Z]/.test(value)) score += 20
                if (/[a-z]/.test(value)) score += 20
                if (/[0-9]/.test(value)) score += 20
                if (value.length >= 10) score += 10
                strengthBar.style.width = `${score}%`
                strengthBar.className = 'progress-bar'
                if (score < 40) {
                    strengthBar.classList.add('bg-danger')
                } else if (score < 70) {
                    strengthBar.classList.add('bg-warning')
                } else {
                    strengthBar.classList.add('bg-success')
                }
            }

            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    validatePassword()
                    
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })

            password.addEventListener('input', () => { validatePassword(); updateStrength(); })
            confirmPassword.addEventListener('input', validatePassword)

            // Toggle password visibility for buttons
            document.querySelectorAll('.toggle-password').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = document.querySelector(btn.getAttribute('data-target'))
                    if (!target) return
                    const isPassword = target.getAttribute('type') === 'password'
                    target.setAttribute('type', isPassword ? 'text' : 'password')
                    const icon = btn.querySelector('i')
                    if (icon) {
                        icon.classList.toggle('fa-eye')
                        icon.classList.toggle('fa-eye-slash')
                    }
                })
            })

            // Add smooth hover effects and animations
            const formControls = document.querySelectorAll('.form-control')
            formControls.forEach(control => {
                control.addEventListener('focus', () => {
                    control.parentElement.style.transform = 'translateY(-2px)'
                    control.parentElement.style.transition = 'transform 0.3s ease'
                })
                control.addEventListener('blur', () => {
                    control.parentElement.style.transform = 'translateY(0)'
                })
            })

            // Animate form groups on page load
            document.querySelectorAll('.form-group').forEach((group, index) => {
                group.style.animationDelay = `${0.2 + (index * 0.1)}s`
            })

            // Add animation to success message
            const alerts = document.querySelectorAll('.alert')
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease-out'
                    alert.style.opacity = '0'
                    setTimeout(() => alert.remove(), 500)
                }, 5000)
            })
        })()
    </script>
</body>
</html>
