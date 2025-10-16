<?php
session_start();
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user has a temporary session
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
    header("Location: register.php");
    exit();
}

$error = '';
$success = '';

if (isset($_GET['resent'])) {
    $success = "A new verification code has been sent to your email.";
}
if (isset($_GET['error']) && $_GET['error'] === 'resend') {
    $error = "Failed to send new verification code. Please try again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $user_id = $_SESSION['temp_user_id'];
    $email = $_SESSION['temp_email'];

    if (empty($otp)) {
        $error = 'Please enter the OTP';
    } else {
        // First, get the latest OTP for this user
        $verify_query = "SELECT * FROM email_verifications 
                        WHERE user_id = ? AND email = ? 
                        ORDER BY created_at DESC LIMIT 1";
        
        $stmt = mysqli_prepare($conn, $verify_query);
        mysqli_stmt_bind_param($stmt, "is", $user_id, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($verification = mysqli_fetch_assoc($result)) {
            // Check if OTP matches and is not expired
            if ($verification['otp'] === $otp && strtotime($verification['expiry']) > time()) {
                // Get user details
                $user_query = "SELECT username FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $user_query);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $user_result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($user_result);

                // Begin transaction
                mysqli_begin_transaction($conn);

                try {
                    // Update user as verified
                    $update_query = "UPDATE users SET is_verified = 1 WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);

                    // Create welcome notification
                    $welcome_msg = "Welcome to Eat&Run! Your email has been verified.";
                    $notif_query = "INSERT INTO notifications (user_id, message) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $notif_query);
                    mysqli_stmt_bind_param($stmt, "is", $user_id, $welcome_msg);
                    mysqli_stmt_execute($stmt);

                    // Delete all verification codes for this user
                    $delete_query = "DELETE FROM email_verifications WHERE user_id = ?";
                    $stmt = mysqli_prepare($conn, $delete_query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);

                    // Commit transaction
                    mysqli_commit($conn);

                    // Set session variables for document submission
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = 'customer';
                    $_SESSION['email_verified'] = true;
                    
                    // Clean up temporary session variables
                    unset($_SESSION['temp_user_id']);
                    unset($_SESSION['temp_email']);
                    
                    // Redirect to document submission page
                    header("Location: submit_documents.php");
                    exit();

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    error_log("Verification error: " . $e->getMessage());
                    $error = "Verification failed. Please try again.";
                }
            } else {
                $error = "Invalid or expired OTP. Please check and try again.";
            }
        } else {
            $error = "No verification code found. Please request a new one.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-dark: #005530;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f8f9fa;
        }

        .verify-container {
            max-width: 400px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .logo {
            width: 150px;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .email-text {
            color: #666;
            margin-bottom: 30px;
        }

        .email-address {
            color: #333;
            font-weight: 500;
        }

        .otp-input {
            width: 200px;
            padding: 12px;
            font-size: 20px;
            letter-spacing: 8px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }

        .otp-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .verify-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }

        .verify-btn:hover {
            background: var(--primary-dark);
        }

        .resend-text {
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .resend-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .resend-link:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #ffe6e6;
            color: #dc3545;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="verify-container">
        <img src="assets/images/logo.png" alt="Eat&Run Logo" class="logo">
        <h1>Verify Your Email</h1>
        
        <p class="email-text">
            We've sent a verification code to<br>
            <span class="email-address"><?php echo htmlspecialchars($_SESSION['temp_email']); ?></span>
        </p>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="otp" class="otp-input" placeholder="000000" maxlength="6" required>
            <button type="submit" class="verify-btn">Verify Email</button>
        </form>

        <p class="resend-text">
            Didn't receive the code?<br>
            <a href="resend-otp.php" class="resend-link">Resend Code</a>
        </p>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 