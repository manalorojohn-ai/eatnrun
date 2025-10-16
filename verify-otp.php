<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];
    $email = $_SESSION['reset_email'];
    
    // Debug log
    error_log("Verifying OTP for email: $email, OTP: $otp");
    
    // Verify OTP
    $stmt = $conn->prepare("SELECT id, reset_token, reset_token_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Debug log
    error_log("Database values - Reset Token: " . $user['reset_token'] . ", Expiry: " . $user['reset_token_expiry']);
    
    if ($result->num_rows > 0) {
        if ($user['reset_token'] === $otp && strtotime($user['reset_token_expiry']) > time()) {
            // Mark OTP as verified in session
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_email'] = $email;
            
            // Clear used OTP from database
            $clear_stmt = $conn->prepare("UPDATE users SET reset_token = NULL, reset_token_expiry = NULL WHERE email = ?");
            $clear_stmt->bind_param("s", $email);
            $clear_stmt->execute();
            $clear_stmt->close();
            
            // Debug log
            error_log("OTP verified successfully for email: $email");
            
            // Redirect to password reset step
            header("Location: forgot-password.php?step=reset");
            exit();
        } elseif ($user['reset_token'] !== $otp) {
            error_log("OTP mismatch for email: $email. Expected: " . $user['reset_token'] . ", Got: $otp");
            $error = "Invalid OTP. Please try again.";
        } elseif (strtotime($user['reset_token_expiry']) <= time()) {
            error_log("OTP expired for email: $email. Expiry: " . $user['reset_token_expiry']);
            $error = "OTP has expired. Please request a new one.";
            
            // Clear expired OTP
            $clear_stmt = $conn->prepare("UPDATE users SET reset_token = NULL, reset_token_expiry = NULL WHERE email = ?");
            $clear_stmt->bind_param("s", $email);
            $clear_stmt->execute();
            $clear_stmt->close();
        }
    } else {
        error_log("No user found with email: $email");
        $error = "Invalid or expired OTP. Please try again.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: #E5F6EF;
            --accent: #FFC107;
            --error: #DC2626;
            --error-light: #FEE2E2;
            --success: #059669;
            --success-light: #D1FAE5;
        }

        .verify-otp-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h2 {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            color: #374151;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .otp-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 1rem 0;
        }

        .otp-input {
            width: 55px;
            height: 55px;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            border: 2px solid #98D8BF;
            border-radius: 8px;
            background: var(--primary-light);
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 108, 59, 0.1);
            outline: none;
        }

        .otp-input.filled {
            border-color: var(--primary);
            background: white;
        }

        .submit-btn {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: var(--error-light);
            color: var(--error);
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .resend-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .resend-link a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .resend-link a:hover {
            color: var(--primary-dark);
        }

        .timer {
            display: block;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="verify-otp-container">
        <div class="auth-header">
            <h2>Verify OTP</h2>
            <p>Enter the 6-digit code sent to your email</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="otpForm">
            <div class="form-group">
                <div class="otp-inputs">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <input 
                            type="tel"
                            class="otp-input"
                            maxlength="1"
                            pattern="[0-9]"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            required
                        >
                    <?php endfor; ?>
                    <input type="hidden" name="otp" id="otp">
                </div>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-check"></i> Verify OTP
            </button>

            <div class="resend-link">
                <a href="forgot-password.php" id="resendLink">
                    <i class="fas fa-redo-alt"></i> Didn't receive the code? Request again
                </a>
                <span class="timer" id="timer"></span>
            </div>

            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </form>
    </div>
    
    <script>
        // OTP input handling
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpHidden = document.getElementById('otp');
        const resendLink = document.getElementById('resendLink');
        const timerSpan = document.getElementById('timer');

        // Focus first input on load
        otpInputs[0].focus();

        // Timer functionality
        let timeLeft = 60; // 60 seconds cooldown
        
        function updateTimer() {
            if (timeLeft > 0) {
                timerSpan.textContent = `Resend available in ${timeLeft} seconds`;
                resendLink.style.pointerEvents = 'none';
                resendLink.style.opacity = '0.5';
                timeLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                timerSpan.textContent = '';
                resendLink.style.pointerEvents = 'auto';
                resendLink.style.opacity = '1';
            }
        }

        updateTimer();

        otpInputs.forEach((input, index) => {
            // Handle input
            input.addEventListener('input', function(e) {
                // Remove any non-digit characters
                this.value = this.value.replace(/[^0-9]/g, '');

                if (this.value) {
                    this.classList.add('filled');
                } else {
                    this.classList.remove('filled');
                }

                // Move to next input if value is entered
                if (this.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }

                updateHiddenOtp();
            });

            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace') {
                    if (!this.value && index > 0) {
                        otpInputs[index - 1].focus();
                        otpInputs[index - 1].value = '';
                        otpInputs[index - 1].classList.remove('filled');
                    } else {
                        this.value = '';
                        this.classList.remove('filled');
                    }
                    updateHiddenOtp();
                }
            });

            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                
                if (pastedData) {
                    pastedData.split('').forEach((digit, i) => {
                        if (otpInputs[i]) {
                            otpInputs[i].value = digit;
                            otpInputs[i].classList.add('filled');
                        }
                    });
                    updateHiddenOtp();
                    otpInputs[Math.min(pastedData.length, otpInputs.length) - 1].focus();
                }
            });

            // Select all content on focus
            input.addEventListener('focus', function() {
                this.select();
            });
        });

        function updateHiddenOtp() {
            const otp = Array.from(otpInputs)
                .map(input => input.value)
                .join('');
            otpHidden.value = otp;
        }

        // Form validation
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            updateHiddenOtp();
            if (otpHidden.value.length !== 6 || !/^\d{6}$/.test(otpHidden.value)) {
                e.preventDefault();
                alert('Please enter a valid 6-digit OTP');
            }
        });
    </script>
    
    <?php include 'footer.php'; ?>
</body>
</html> 