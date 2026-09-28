<?php
require_once dirname(__DIR__, 2) . '/includes/helpers/init_session.php';
require_once dirname(__DIR__, 2) . '/config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$composerAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
$bundledPHPMailer = dirname(__DIR__, 2) . '/includes/vendor/PHPMailer/src/PHPMailer.php';

if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} elseif (file_exists($bundledPHPMailer)) {
    require_once dirname(__DIR__, 2) . '/includes/vendor/PHPMailer/src/Exception.php';
    require_once dirname(__DIR__, 2) . '/includes/vendor/PHPMailer/src/PHPMailer.php';
    require_once dirname(__DIR__, 2) . '/includes/vendor/PHPMailer/src/SMTP.php';
} else {
    die('Mailer dependencies are missing.');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $_SESSION['error'] = 'Email is required';
        header("Location: /forgot-password");
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format';
        header("Location: /forgot-password");
        exit();
    }
    
    // Check if user exists
    $user = null;
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $stmt = mysqli_prepare($conn, "SELECT id, full_name FROM users WHERE LOWER(email) = LOWER(?)");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
    
    if (!$user) {
        $_SESSION['error'] = 'Email not found';
        header("Location: /forgot-password");
        exit();
    }
    
    // Generate reset token
    $reset_token = bin2hex(random_bytes(32));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store reset token
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
        $stmt->execute([$reset_token, $token_expiry, $user['id']]);
    } elseif ($conn instanceof mysqli) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $reset_token, $token_expiry, $user['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Send password reset email
    $mail = new PHPMailer(true);
    
    try {
        $smtp_user = getenv('MAIL_USERNAME') ?: 'eatnrun70@gmail.com';
        $smtp_pass = getenv('MAIL_PASSWORD') ?: 'xeyf snnt dvnq bqpb';
        $smtp_port = (int)(getenv('MAIL_PORT') ?: 465);

        $mail->isSMTP();
        $mail->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        if ($smtp_port == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port = $smtp_port;
        $mail->Timeout = 15;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $mail->setFrom($smtp_user, 'Eat&Run');
        $mail->addAddress($email, $user['full_name']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password - Eat&Run';
        
        $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=" . $reset_token;
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #006C3B; text-align: center;'>Password Reset Request</h2>
                <p>Dear " . htmlspecialchars($user['full_name']) . ",</p>
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
        header("Location: /forgot-password");
        exit();
        
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        $_SESSION['error'] = 'Failed to send reset email. Please try again.';
        header("Location: /forgot-password");
        exit();
    }
}
?>
