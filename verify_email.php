<?php
session_start();
include_once 'config/db.php';

// Import PHPMailer classes
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id']) || !isset($_SESSION['order_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_SESSION['order_id'];

// If verification code is submitted
if (isset($_POST['verify_code'])) {
    $entered_code = mysqli_real_escape_string($conn, $_POST['verification_code']);
    $stored_code = $_SESSION['verification_code'] ?? '';

    if ($entered_code === $stored_code) {
        // Update order status to verified
        $update_query = "UPDATE orders SET status = 'verified' WHERE id = '$order_id'";
        mysqli_query($conn, $update_query);

        // Clear verification session data
        unset($_SESSION['verification_code']);
        unset($_SESSION['order_id']);

        // Redirect to success page
        header('Location: order_success.php');
        exit();
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}

// Generate and send verification code if not already sent
if (!isset($_SESSION['verification_code'])) {
    $verification_code = sprintf('%06d', mt_rand(0, 999999));
    $_SESSION['verification_code'] = $verification_code;

    // Get user email
    $user_query = "SELECT email FROM users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $user_query);
    $user = mysqli_fetch_assoc($result);
    $user_email = $user['email'];

    // Create new PHPMailer instance

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'eatandruncapstone@gmail.com'; // Your Gmail
        $mail->Password = 'mahb fomz qvbx ovas'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('eatandruncapstone@gmail.com', 'Eat&Run');
        $mail->addAddress($user_email);

        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Order - Eat&Run';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #006C3B;'>Verify Your Order</h2>
                <p>Thank you for placing your order with Eat&Run!</p>
                <p>Your verification code is: <strong style='font-size: 24px; color: #006C3B;'>$verification_code</strong></p>
                <p>Please enter this code on the verification page to complete your order.</p>
                <p>If you didn't place this order, please ignore this email.</p>
            </div>";

        $mail->send();
    } catch (Exception $e) {
        $error = "Failed to send verification email. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Order - Eat&Run</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-hover: #005a32;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .verification-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .verification-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .verification-title {
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 108, 59, 0.25);
        }
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }
        .verification-code {
            letter-spacing: 0.5em;
            font-size: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="verification-container text-center">
            <i class="fas fa-envelope-open-text verification-icon"></i>
            <h2 class="verification-title">Verify Your Order</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <p class="text-muted mb-4">
                We've sent a verification code to your email address. 
                Please check your inbox and enter the code below to complete your order.
            </p>

            <form method="POST" action="verify_email.php">
                <div class="mb-4">
                    <input type="text" name="verification_code" 
                           class="form-control verification-code" 
                           placeholder="Enter code" maxlength="6" required>
                </div>

                <button type="submit" name="verify_code" class="btn btn-primary btn-lg w-100">
                    <i class="fas fa-check-circle me-2"></i>Verify Order
                </button>
            </form>

            <div class="mt-4">
                <p class="text-muted mb-2">Didn't receive the code?</p>
                <a href="verify_email.php" class="btn btn-outline-primary">
                    <i class="fas fa-redo me-2"></i>Resend Code
                </a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap 5 JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-format verification code input
        document.querySelector('.verification-code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>
