<?php
session_start();
require_once 'config/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user has a temporary session
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
    header("Location: register.php");
    exit();
}

$user_id = $_SESSION['temp_user_id'];
$email = $_SESSION['temp_email'];

// Function to generate OTP
function generateOTP() {
    return sprintf("%06d", mt_rand(0, 999999));
}

try {
    // Get user details
    $user_query = "SELECT full_name FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) {
        throw new Exception("User not found");
    }

    $otp = generateOTP();
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    mysqli_begin_transaction($conn);

    // Delete old OTPs for this user
    $delete_old_otps = "DELETE FROM email_verifications WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_old_otps);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Insert new OTP
    $insert_otp = "INSERT INTO email_verifications (user_id, email, otp, expiry) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_otp);
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $email, $otp, $otp_expiry);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Send verification email
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'manalorojohn@gmail.com'; // Your Gmail
    $mail->Password = 'jrsp vsza dxvw zcsa'; // Your App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('miguelantonioramos140@gmail.com', 'Eat&Run');
    $mail->addAddress($email, $user['full_name']);

    $mail->isHTML(true);
    $mail->Subject = 'New Verification Code - Eat&Run';
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #006C3B; text-align: center;'>New Verification Code</h2>
            <p>Dear {$user['full_name']},</p>
            <p>Here is your new verification code:</p>
            <div style='background: #f4f4f4; padding: 20px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                {$otp}
            </div>
            <p>This code will expire in 10 minutes.</p>
            <p>If you didn't request this code, please ignore this email.</p>
            <p>Best regards,<br>The Eat&Run Team</p>
        </div>";

    $mail->send();
    mysqli_commit($conn);

    // Redirect back to verify-email.php with success message
    header("Location: verify-email.php?resent=1");
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Error resending OTP: " . $e->getMessage());
    header("Location: verify-email.php?error=resend");
    exit();
}
?> 
