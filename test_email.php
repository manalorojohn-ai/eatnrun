<?php
/**
 * Email Configuration Test
 * 
 * This file tests the PHPMailer setup with Gmail SMTP
 * Remove this file after testing
 */

require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h2>Email Configuration Test</h2>";
echo "<hr>";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'eatnrun70@gmail.com';
    $mail->Password = 'xeyf snnt dvnq bqpb';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 10;

    // Email settings
    $mail->setFrom('eatnrun70@gmail.com', 'Eat&Run Test');
    $mail->addAddress('eatnrun70@gmail.com', 'Test Recipient');

    $mail->isHTML(true);
    $mail->Subject = 'Eat&Run Email Configuration Test';
    $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h2 style="color: #006C3B;">Email Configuration Test ✓</h2>
            <p>This is a test email to verify that the PHPMailer configuration is working correctly.</p>
            <p><strong>Gmail Account:</strong> eatnrun70@gmail.com</p>
            <p><strong>Status:</strong> <span style="color: #22c55e; font-weight: bold;">Connected and Ready</span></p>
            <p>Best regards,<br>The Eat&Run Team</p>
        </div>';

    $mail->send();
    echo '<div style="padding: 20px; background: #d1fae5; border: 2px solid #10b981; border-radius: 8px; color: #065f46;">';
    echo '<strong>✓ Success!</strong> Email sent successfully using eatnrun70@gmail.com<br>';
    echo 'Check your inbox at eatnrun70@gmail.com for the test email.';
    echo '</div>';

} catch (Exception $e) {
    echo '<div style="padding: 20px; background: #fee2e2; border: 2px solid #dc2626; border-radius: 8px; color: #7f1d1d;">';
    echo '<strong>✗ Error:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}

echo '<hr>';
echo '<p><small>⚠️ Remember to delete this test file (test_email.php) from your server after testing.</small></p>';
?>
