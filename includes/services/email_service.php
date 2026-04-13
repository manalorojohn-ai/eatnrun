<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if vendor directory exists in project root
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Please run "composer install" to install dependencies');
}

require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    private $mailer;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        try {
            $this->mailer = new PHPMailer(true);
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            
            // Gmail credentials
            $this->mailer->Username = 'manalorojohn@gmail.com';
            $this->mailer->Password = 'jiihlatfltappohw';
            
            // SSL/TLS Configuration
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
            
            // Disable SSL verification for testing
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Set default sender
            $this->from_email = 'miguelantonioramos140@gmail.com';
            $this->from_name = 'Eat&Run';
            $this->mailer->setFrom($this->from_email, $this->from_name);
            
            // Set charset
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("Email service initialization failed: " . $e->getMessage());
            throw new \RuntimeException('Failed to initialize email service: ' . $e->getMessage());
        }
    }
    
    public function sendOTP($email, $otp, $name) {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Invalid email address');
            }
            
            // Reset all recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Add recipient
            $this->mailer->addAddress($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Email Verification - Eat&Run';
            $this->mailer->Body = $this->getEmailTemplate($name, $otp);
            $this->mailer->AltBody = "Your OTP code is: $otp. This code will expire in 10 minutes.";
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function getEmailTemplate($name, $otp) {
        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #006C3B;'>Welcome to Eat&Run!</h2>
                <p>Dear {$name},</p>
                <p>Thank you for registering with Eat&Run. To complete your registration, please use the following OTP code:</p>
                <div style='background: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; letter-spacing: 5px; margin: 20px 0;'>
                    <strong>{$otp}</strong>
                </div>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this verification, please ignore this email.</p>
                <p>Best regards,<br>The Eat&Run Team</p>
            </div>
        ";
    }

    public function sendVerificationEmail($to_email, $token, $name) {
        try {
            // Reset all recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Add recipient
            $this->mailer->addAddress($to_email, $name);
            
            // Create verification link
            $verification_link = SITE_URL . "/verify.php?token=" . $token;
            
            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "Verify Your Email Address";
            
            // Email body
            $this->mailer->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #006C3B;'>Welcome to Eat&Run!</h2>
                <p>Hello " . htmlspecialchars($name) . ",</p>
                <p>Thank you for registering with Eat&Run. To complete your registration, please verify your email address by clicking the button below:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='" . $verification_link . "' style='background: #006C3B; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email Address</a>
                </p>
                <p>Or copy and paste this link in your browser:</p>
                <p>" . $verification_link . "</p>
                <p>This verification link will expire in 24 hours.</p>
                <p>If you didn't create an account, no further action is required.</p>
                <div style='margin-top: 30px; font-size: 12px; color: #666;'>
                    <p>Best regards,<br>The Eat&Run Team</p>
                </div>
            </div>";
            
            $this->mailer->AltBody = "Hello " . $name . ",\n\n"
                . "Please verify your email address by clicking this link:\n"
                . $verification_link . "\n\n"
                . "This link will expire in 24 hours.\n\n"
                . "Best regards,\nThe Eat&Run Team";
                
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Failed to send verification email: " . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetOTP($email, $otp, $name) {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Invalid email address');
            }
            
            // Reset all recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Add recipient
            $this->mailer->addAddress($email, $name);
            
            // Format OTP with spaces for better readability
            $formatted_otp = implode(" ", str_split($otp));
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Password Reset OTP - Eat&Run';
            $this->mailer->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #006C3B;'>Password Reset Request</h2>
                <p>Hello " . htmlspecialchars($name) . ",</p>
                <p>You have requested to reset your password. Please use the following OTP code:</p>
                <div style='background: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; border: 1px solid #e9ecef;'>
                    <div style='font-size: 32px; font-weight: bold; color: #006C3B; letter-spacing: 4px; padding: 10px; background: white; border-radius: 4px; display: inline-block;'>
                        {$formatted_otp}
                    </div>
                    <p style='margin-top: 15px; color: #666;'>This code will expire in 15 minutes</p>
                </div>
                <p style='color: #dc3545; font-size: 14px; margin-top: 20px; padding: 10px; background: #fff5f5; border-radius: 4px;'>
                    If you didn't request this password reset, please ignore this email.
                </p>
                <div style='margin-top: 30px; color: #666;'>
                    <p>Best regards,<br>The Eat&Run Team</p>
                </div>
            </div>";
            
            $this->mailer->AltBody = "Hello {$name},\n\n"
                . "You have requested to reset your password. Your OTP code is: {$otp}\n"
                . "This code will expire in 15 minutes.\n\n"
                . "If you didn't request this password reset, please ignore this email.\n\n"
                . "Best regards,\nThe Eat&Run Team";
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Failed to send password reset OTP: " . $e->getMessage());
            return false;
        }
    }
} 