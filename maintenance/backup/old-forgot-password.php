<?php
ob_start();
session_start();
require_once 'includes/connection.php';
require_once 'includes/email_service.php';

use App\Services\EmailService;

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error_message = '';
$success_message = '';
$step = isset($_GET['step']) ? $_GET['step'] : 'email';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address";
        } else {
            // Check if email exists in database
            $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error_message = "No account found with this email address";
            } else {
                $user = $result->fetch_assoc();
                
                // Generate OTP
                $otp = sprintf("%06d", mt_rand(0, 999999));
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // Store OTP in database
                $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                $update_stmt->bind_param("ssi", $otp, $otp_expiry, $user['id']);
                
                if ($update_stmt->execute()) {
                    try {
                        // Initialize email service
                        $emailService = new EmailService();
                        
                        // Send OTP email using the password reset specific method
                        if ($emailService->sendPasswordResetOTP($email, $otp, $user['full_name'])) {
                            $_SESSION['reset_email'] = $email;
                            $_SESSION['otp_time'] = time();
                            header("Location: forgot-password.php?step=verify");
                            exit();
                        } else {
                            $error_message = "Failed to send OTP email. Please try again.";
                        }
                    } catch (Exception $e) {
                        $error_message = "Failed to send OTP email. Please try again.";
                        error_log("Email error: " . $e->getMessage());
                    }
                } else {
                    $error_message = "Failed to process your request. Please try again.";
                }
            }
        }
    }
    
    // OTP verification
    if (isset($_POST['otp'])) {
        $otp = $_POST['otp'];
        $email = $_SESSION['reset_email'];
        
        $stmt = $conn->prepare("SELECT id, reset_token, reset_token_expiry FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && $user['reset_token'] === $otp && strtotime($user['reset_token_expiry']) > time()) {
            $_SESSION['reset_verified'] = true;
            
            // Clear used OTP
            $clear_stmt = $conn->prepare("UPDATE users SET reset_token = NULL, reset_token_expiry = NULL WHERE email = ?");
            $clear_stmt->bind_param("s", $email);
            $clear_stmt->execute();
            
            header("Location: forgot-password.php?step=reset");
            exit();
        } else {
            $error_message = "Invalid or expired OTP. Please try again.";
        }
    }
    
    // Password reset
    if (isset($_POST['new_password'])) {
        if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
            header("Location: forgot-password.php");
            exit();
        }

        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $email = $_SESSION['reset_email'];
        
        if ($new_password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            
            if ($stmt->execute()) {
                // Clear session variables
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_verified']);
                unset($_SESSION['otp_time']);
                
                $_SESSION['success_message'] = "Password has been reset successfully. You can now login with your new password.";
                header("Location: login.php");
                exit();
            } else {
                $error_message = "Failed to reset password. Please try again.";
            }
        }
    }
}

// Verify session state for each step
if ($step === 'verify' && !isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit();
} elseif ($step === 'reset' && (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified'])) {
    header("Location: forgot-password.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .reset-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            position: relative;
        }

        .reset-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reset-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .reset-card h2 {
            color: #006C3B;
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 2rem;
            position: relative;
        }

        .reset-card h2::after {
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

        .reset-card:hover h2::after {
            width: 80px;
        }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            animation: slideUpFade 0.5s ease-out forwards;
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

        .otp-inputs {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin: 1rem 0;
        }

        .otp-input {
            width: 40px;
            height: 40px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: #006C3B;
            box-shadow: 0 0 0 4px rgba(0, 108, 59, 0.1);
        }

        .submit-btn {
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
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, #005530, #008C4A);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 108, 59, 0.3);
        }

        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            font-size: 0.95rem;
            opacity: 0;
            animation: fadeIn 0.5s ease-out 0.5s forwards;
        }

        .back-to-login a {
            color: #006C3B;
            text-decoration: none;
            font-weight: 600;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }

        .back-to-login a:hover {
            color: #00A65A;
            text-decoration: underline;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            animation: slideIn 0.3s ease-out;
        }

        .alert-error {
            background-color: #fff5f5;
            color: #dc3545;
            border: 1px solid #ffebeb;
        }

        .alert-success {
            background-color: #f0fff4;
            color: #006C3B;
            border: 1px solid #e6ffec;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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

            .reset-card {
                padding: 2rem;
                border-radius: 20px;
            }

            .otp-inputs {
                gap: 0.35rem;
            }

            .otp-input {
                width: 35px;
                height: 35px;
                font-size: 1rem;
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

        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .step-circle {
            width: 30px;
            height: 30px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 50%;
            margin: 0 auto 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
        }

        .step.active .step-circle {
            background: #006C3B;
            border-color: #006C3B;
            color: white;
        }

        .step.completed .step-circle {
            background: #00A65A;
            border-color: #00A65A;
            color: white;
        }

        .step-label {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .step.active .step-label {
            color: #006C3B;
            font-weight: 500;
        }

        .step.completed .step-label {
            color: #00A65A;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="welcome-banner">
        <h1>Reset Password</h1>
        <p>
            <?php
            switch($step) {
                case 'email':
                    echo "Enter your email to receive a password reset code";
                    break;
                case 'verify':
                    echo "Enter the verification code sent to your email";
                    break;
                case 'reset':
                    echo "Create your new password";
                    break;
            }
            ?>
        </p>
    </div>
            
    <div class="main-container">
        <div class="reset-container">
            <div class="reset-card">
                <div class="steps">
                    <div class="step <?php echo $step === 'email' ? 'active' : ($step === 'verify' || $step === 'reset' ? 'completed' : ''); ?>">
                        <div class="step-circle">1</div>
                        <div class="step-label">Email</div>
                    </div>
                    <div class="step <?php echo $step === 'verify' ? 'active' : ($step === 'reset' ? 'completed' : ''); ?>">
                        <div class="step-circle">2</div>
                        <div class="step-label">Verify</div>
                    </div>
                    <div class="step <?php echo $step === 'reset' ? 'active' : ''; ?>">
                        <div class="step-circle">3</div>
                        <div class="step-label">Reset</div>
                    </div>
                </div>

                <h2>
                    <?php
                    switch($step) {
                        case 'email':
                            echo "Forgot Password";
                            break;
                        case 'verify':
                            echo "Verify OTP";
                            break;
                        case 'reset':
                            echo "New Password";
                            break;
                    }
                    ?>
                </h2>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($step === 'email'): ?>
                    <form action="forgot-password.php" method="POST">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="Enter your registered email">
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Send Reset Code
                        </button>

                        <div class="back-to-login">
                            Remember your password? <a href="login.php">Back to Login</a>
                        </div>
                    </form>

                <?php elseif ($step === 'verify'): ?>
                    <form action="forgot-password.php?step=verify" method="POST">
                        <div class="form-group">
                            <label for="otp">Enter Verification Code</label>
                            <div class="otp-inputs">
                                <?php for($i = 1; $i <= 6; $i++): ?>
                                    <input type="text" class="otp-input" maxlength="1" 
                                           pattern="[0-9]" inputmode="numeric" required>
                                <?php endfor; ?>
                                <input type="hidden" name="otp" id="otp-value">
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-check-circle"></i> Verify Code
                        </button>

                        <div class="back-to-login">
                            Didn't receive the code? <a href="forgot-password.php">Try Again</a>
                        </div>
                    </form>

                <?php elseif ($step === 'reset'): ?>
                    <form action="forgot-password.php?step=reset" method="POST">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-field">
                                <input type="password" id="new_password" name="new_password" required
                                       placeholder="Enter your new password">
                                <span class="password-toggle">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="password-field">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       placeholder="Confirm your new password">
                                <span class="password-toggle">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Password toggle functionality
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // OTP input handling
        if (document.querySelector('.otp-inputs')) {
            const inputs = document.querySelectorAll('.otp-input');
            const form = document.querySelector('form');
            const hiddenInput = document.getElementById('otp-value');

            inputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    if (e.inputType === "deleteContentBackward") {
                        if (index > 0) inputs[index - 1].focus();
                    } else {
                        if (this.value.length === 1) {
                            if (index < inputs.length - 1) inputs[index + 1].focus();
                            inputs[index].classList.add('filled');
                        } else {
                            inputs[index].classList.remove('filled');
                        }
                    }
                    
                    // Update hidden input with complete OTP
                    hiddenInput.value = Array.from(inputs).map(input => input.value).join('');
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === "Backspace" && !this.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });

                input.addEventListener('focus', function() {
                    this.select();
                });
            });

            // Handle paste event
            inputs[0].addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').slice(0, inputs.length);
                
                inputs.forEach((input, index) => {
                    input.value = pastedData[index] || '';
                    if (input.value) input.classList.add('filled');
                });

                hiddenInput.value = pastedData;
                inputs[Math.min(pastedData.length, inputs.length - 1)].focus();
            });
        }
    </script>
</body>
</html>