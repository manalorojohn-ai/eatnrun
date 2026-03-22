<?php
session_start();
require_once 'includes/connection.php';
require_once 'includes/email_service.php';

$error_message = '';
$success_message = '';

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    
    // Check if user exists and is not verified
    $query = "SELECT id, full_name, email FROM users WHERE email = ? AND is_verified = FALSE";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Delete any existing OTP for this user
        $delete_query = "DELETE FROM email_verification WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $user['id']);
        mysqli_stmt_execute($stmt);
        
        // Generate new OTP
        $otp = sprintf("%06d", mt_rand(0, 999999));
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Store new OTP in database
        $otp_query = "INSERT INTO email_verification (user_id, email, otp_code, expires_at) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $otp_query);
        mysqli_stmt_bind_param($stmt, "isss", $user['id'], $email, $otp, $expires_at);
        
        if (mysqli_stmt_execute($stmt)) {
            // Send OTP email
            $emailService = new EmailService();
            if ($emailService->sendOTP($email, $otp, $user['full_name'])) {
                $_SESSION['temp_user_id'] = $user['id'];
                $_SESSION['temp_email'] = $email;
                $success_message = "Verification email has been resent. Please check your inbox.";
                header("refresh:2;url=verify.php");
            } else {
                $error_message = "Failed to send verification email. Please try again.";
            }
        } else {
            $error_message = "Failed to generate new verification code. Please try again.";
        }
    } else {
        $error_message = "Invalid email address or account is already verified.";
    }
} else {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
        }

        .message-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .title {
            color: #006C3B;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #006C3B;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="message-box">
            <h1 class="title">Email Verification</h1>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <a href="login.php" class="back-link">Back to Login</a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 