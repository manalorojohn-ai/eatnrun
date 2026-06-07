<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$composerAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    die('Composer dependencies are missing.');
}
require_once $composerAutoload;

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $_SESSION['error'] = 'Email is required';
        header("Location: ../../pages/auth/forgot-password.php");
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format';
        header("Location: ../../pages/auth/forgot-password.php");
        exit();
    }
    
    // Check if user exists
    $stmt = mysqli_prepare($conn, "SELECT id, full_name FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$user) {
        $_SESSION['error'] = 'Email not found';
        header("Location: ../../pages/auth/forgot-password.php");
        exit();
    }
    
    // Generate reset token
    $reset_token = bin2hex(random_bytes(32));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store reset token
    $stmt = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ssi", $reset_token, $token_expiry, $user['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Send password reset email
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'eatnrun70@gmail.com';
        $mail->Password = 'xeyf snnt dvnq bqpb';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 10;
        
        $mail->setFrom('eatnrun70@gmail.com', 'Eat&Run');
        $mail->addAddress($email, $user['full_name']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password - Eat&Run';
        
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/pages/auth/reset-password.php?token=" . $reset_token;
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #006C3B; text-align: center;'>Password Reset Request</h2>
                <p>Dear {$user['full_name']},</p>
                <p>We received a request to reset your password. Click the button below to proceed:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$reset_link}' style='background: #006C3B; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Reset Password
                    </a>
                </div>
                <p>Or copy and paste this link in your browser:</p>
                <p style='word-break: break-all; color: #006C3B;'>{$reset_link}</p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this reset, please ignore this email.</p>
                <p>Best regards,<br>The Eat&Run Team</p>
            </div>";
        
        $mail->send();
        
        $_SESSION['success'] = 'Password reset link has been sent to your email';
        header("Location: ../../pages/auth/forgot-password.php");
        exit();
        
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        $_SESSION['error'] = 'Failed to send reset email. Please try again.';
        header("Location: ../../pages/auth/forgot-password.php");
        exit();
    }
}
?>
