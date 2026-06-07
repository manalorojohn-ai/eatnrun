<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Eat&Run</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/navbar-enhanced.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-dark: #005530;
            --primary-light: #00A65A;
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --card-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.08);
        }

        body {
            min-height: 100vh;
            background: var(--bg-gradient);
            font-family: 'Poppins', sans-serif;
            color: #1f2937;
        }

        .page-wrapper {
            min-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .forgot-container {
            width: 100%;
            max-width: 450px;
            margin: 3rem auto;
            padding: 0 1rem;
            flex: 1;
        }

        .card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--card-shadow);
            border-radius: 1.5rem;
            overflow: hidden;
        }

        .card-body {
            background: linear-gradient(
                145deg,
                rgba(255, 255, 255, 0.95) 0%,
                rgba(255, 255, 255, 0.85) 100%
            );
            padding: 2.5rem;
        }

        .form-control {
            border: 1.5px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 108, 59, 0.15);
            background-color: #f8fafb;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 108, 59, 0.2);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .alert {
            border-radius: 0.75rem;
            border: none;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        footer {
            margin-top: auto;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/ui/navbar.php'; ?>

    <div class="page-wrapper">
        <div class="forgot-container">
            <div class="card">
                <div class="card-body">
                    <h1 class="page-title text-center">
                        <i class="fas fa-lock"></i> Forgot Password
                    </h1>
                    <p class="page-subtitle text-center">
                        Enter your email address and we'll send you a link to reset your password
                    </p>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/actions/auth/reset_password_email.php" novalidate>
                        <div class="mb-4">
                            <label for="email" class="form-label fw-600 mb-2">
                                <i class="fas fa-envelope me-2" style="color: var(--primary-color);"></i>Email Address
                            </label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   placeholder="your@email.com"
                                   required>
                            <small class="text-muted">We'll send a password reset link to this email</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">
                            Remember your password? 
                            <a href="login.php" class="back-link">
                                <i class="fas fa-arrow-left me-1"></i>Back to Login
                            </a>
                        </p>
                    </div>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="text-muted mb-2">Don't have an account yet?</p>
                        <a href="register.php" class="back-link fw-600">
                            <i class="fas fa-user-plus me-1"></i>Create Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include dirname(__DIR__) . '/includes/ui/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
