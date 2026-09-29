<?php
require_once dirname(__DIR__, 2) . '/includes/helpers/init_session.php';
require_once dirname(__DIR__, 2) . '/config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user has a temporary session or active session
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
    if (isset($_SESSION['user_id'])) {
        header("Location: /");
        exit();
    }
    header("Location: register");
    exit();
}

$user_id = $_SESSION['temp_user_id'];
$email = $_SESSION['temp_email'];
$error = '';
$success = '';

if (isset($_GET['resent'])) {
    $success = "A new verification code has been sent to your email.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if (empty($otp)) {
        $error = 'Please enter the 6-digit OTP code.';
    } else {
        $verification = null;
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare("SELECT * FROM email_verifications WHERE user_id = ? AND email = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$user_id, $email]);
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($conn instanceof mysqli) {
            $verify_query = "SELECT * FROM email_verifications WHERE user_id = ? AND email = ? ORDER BY id DESC LIMIT 1";
            $stmt = mysqli_prepare($conn, $verify_query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "is", $user_id, $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $verification = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
            }
        }

        $matched = false;
        if ($verification && isset($verification['otp'])) {
            if (trim($verification['otp']) === $otp) {
                $matched = true;
            }
        }

        if ($matched) {
            if ($conn instanceof PDO) {
                $updateStmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
                $updateStmt->execute([$user_id]);

                $delStmt = $conn->prepare("DELETE FROM email_verifications WHERE user_id = ?");
                $delStmt->execute([$user_id]);
            } elseif ($conn instanceof mysqli) {
                $stmt = mysqli_prepare($conn, "UPDATE users SET is_verified = 1 WHERE id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                $stmt = mysqli_prepare($conn, "DELETE FROM email_verifications WHERE user_id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }

            // Log the user in with synchronized session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = 'user';
            $_SESSION['user_role'] = 'user';
            $_SESSION['email'] = $email;
            if (isset($_SESSION['temp_full_name'])) {
                $_SESSION['full_name'] = $_SESSION['temp_full_name'];
            }
            $_SESSION['email_verified'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();

            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_email']);
            unset($_SESSION['temp_full_name']);

            header("Location: /dashboard?registered=1");
            exit();
        } else {
            $error = "Invalid verification code. Please check your email or request a new code.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Eat&Run</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/shared-styles.css">
    <link rel="stylesheet" href="assets/css/navbar-enhanced.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-dark: #005530;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .verify-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            max-width: 460px;
            margin: 3rem auto;
            text-align: center;
        }
        .otp-input {
            width: 100%;
            max-width: 260px;
            letter-spacing: 8px;
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            border: 2px solid #ced4da;
            border-radius: 12px;
            padding: 10px;
            margin: 1.5rem auto;
            color: #006C3B;
        }
        .otp-input:focus {
            border-color: #006C3B;
            box-shadow: 0 0 0 0.25rem rgba(0, 108, 59, 0.2);
            outline: none;
        }
        .btn-verify {
            background: #006C3B;
            color: white;
            font-weight: 600;
            padding: 0.85rem 2rem;
            border-radius: 50px;
            border: none;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-verify:hover {
            background: #005530;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'includes/ui/loader.php'; ?>
    <?php include 'includes/ui/navbar.php'; ?>

    <main class="flex-grow-1 d-flex align-items-center">
        <div class="container">
            <div class="verify-card">
                <img src="assets/images/logo.png" alt="Eat&Run" width="80" height="80" class="mb-3">
                <h2 class="fw-bold text-success mb-2">Verify Your Email</h2>
                <p class="text-muted small">We've sent a 6-digit verification code to:<br><strong class="text-dark"><?php echo htmlspecialchars($email); ?></strong></p>

                <?php if (isset($_SESSION['dev_otp_notice'])): ?>
                    <div class="alert alert-warning text-start py-2 small mb-3">
                        <i class="fas fa-key me-1"></i><?php echo htmlspecialchars($_SESSION['dev_otp_notice']); unset($_SESSION['dev_otp_notice']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger text-start py-2 small mb-3"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success text-start py-2 small mb-3"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="verify-email">
                    <input type="text" name="otp" class="form-control otp-input" maxlength="6" pattern="\d{6}" placeholder="••••••" required autofocus>
                    <button type="submit" class="btn btn-verify">
                        <i class="fas fa-check-circle me-2"></i>Verify Account
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top small text-muted">
                    Didn't receive the email? Check spam folder or
                    <a href="register" class="text-success fw-semibold">Try Registering Again</a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/ui/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
