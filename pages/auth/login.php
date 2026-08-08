<?php
session_start();

error_log("========== LOGIN PAGE DEBUG ==========");
error_log("Login page loaded!");
error_log("Session ID: " . session_id());
error_log("Is logged in: " . (isset($_SESSION['user_id']) ? "YES (ID: " . $_SESSION['user_id'] . ")" : "NO"));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("======================================");

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    error_log("User already logged in, redirecting to /index");
    header("Location: /index");
    exit();
}

require_once 'config/db.php';

error_log("POST request to login - Email: " . ($_POST['email'] ?? 'NOT PROVIDED'));
error_log("Database connection available: " . ($conn ? "YES" : "NO"));

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing login form submission...");
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    error_log("Email received: " . $email);
    error_log("Password received: " . (strlen($password) > 0 ? "YES (length: " . strlen($password) . ")" : "NO"));
    
    // Hardcoded test users for development/testing (when database is unavailable)
    $test_users = [
        [
            'id' => 999,
            'username' => 'johndoe',
            'email' => 'john@gmail.com',
            'password_plain' => 'password123',
            'full_name' => 'John Doe',
            'role' => 'user',
            'status' => 'active',
            'is_verified' => 1
        ],
        [
            'id' => 998,
            'username' => 'maria',
            'email' => 'maria@yahoo.com',
            'password_plain' => 'maria123',
            'full_name' => 'Maria Santos',
            'role' => 'user',
            'status' => 'active',
            'is_verified' => 1
        ],
        [
            'id' => 997,
            'username' => 'juancruz',
            'email' => 'juan@outlook.com',
            'password_plain' => 'juan2024',
            'full_name' => 'Juan Cruz',
            'role' => 'user',
            'status' => 'active',
            'is_verified' => 1
        ]
    ];
    
    // Try database first
    $user = null;
    $db_error = null;
    try {
        // Handle both PDO and MySQLi connections
        if ($conn instanceof PDO) {
            // PDO connection
            error_log("Login: Using PDO connection");
            $query = "SELECT * FROM users WHERE (LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?)) AND status = 'active' LIMIT 1";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                $db_error = "Failed to prepare PDO statement";
                error_log("Login PDO prepare error: " . $db_error);
            } else {
                $execute_result = $stmt->execute([$email, $email]);
                if (!$execute_result) {
                    $db_error = "Failed to execute PDO statement";
                    error_log("Login PDO execute error: " . $db_error);
                } else {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("Login: PDO query returned user: " . ($user ? "Yes" : "No"));
                }
            }
        } else {
            // MySQLi connection
            error_log("Login: Using MySQLi connection");
            $query = "SELECT * FROM users WHERE (LOWER(email) = LOWER(?) OR LOWER(username) = LOWER(?)) AND status = 'active' LIMIT 1";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                $db_error = "Failed to prepare MySQLi statement";
                error_log("Login MySQLi prepare error: " . $db_error);
            } else {
                $stmt->bind_param("ss", $email, $email);
                $execute_result = $stmt->execute();
                if (!$execute_result) {
                    $db_error = "Failed to execute MySQLi statement";
                    error_log("Login MySQLi execute error: " . $db_error);
                } else {
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                    error_log("Login: MySQLi query returned user: " . ($user ? "Yes" : "No"));
                }
            }
        }
    } catch (Exception $e) {
        // Database error, continue with test users
        $db_error = $e->getMessage();
        error_log("Login database exception: " . $db_error);
    }
    
    // Debug logging
    error_log("Login attempt - Email: " . $email . ", Found user in DB: " . ($user ? "YES" : "NO"));
    
    // If there is no DB user, fall back to test users only
    if (!$user) {
        foreach ($test_users as $test_user) {
            if (strtolower($test_user['email']) === strtolower($email) || strtolower($test_user['username']) === strtolower($email)) {
                error_log("Login: No DB user found. Falling back to test user - " . $test_user['email']);
                $user = $test_user;
                break;
            }
        }
    }

    // Verify password (supports both plain text for migration/dev and hashes for security)
    $password_valid = false;
    if ($user) {
        error_log("Login: User found - " . $user['email'] . ", ID: " . $user['id']);
        
        if (isset($user['password_plain'])) {
            // Test user with plain password
            error_log("Login: Using plain text password (test user)");
            $password_valid = (trim($password) === trim($user['password_plain']));
            error_log("Login: Plain password match: " . ($password_valid ? "YES" : "NO"));
        } else {
            // Database user with hashed password
            error_log("Login: Checking hashed password from database");
            $plain_match = (trim($password) === trim($user['password']));
            $hash_match = password_verify(trim($password), $user['password']);
            
            error_log("Login: Plain text match: " . ($plain_match ? "YES" : "NO"));
            error_log("Login: Bcrypt hash verify: " . ($hash_match ? "YES" : "NO"));
            
            $password_valid = $plain_match || $hash_match;
            error_log("Login: Final password valid: " . ($password_valid ? "YES" : "NO"));
        }
    }
    
    if (!$user) {
        error_log("Login: No user found for email/username: " . $email);
    }
    
    if ($user && $password_valid) {
        error_log("Login: SUCCESS - User authenticated: " . $user['email']);
        
        // Admin users
        if ($user['role'] === 'admin') {
            error_log("Admin user detected, setting session and redirecting to /admin/dashboard");
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Login successful for admin
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            error_log("Session set - user_id: " . $_SESSION['user_id'] . ", role: " . $_SESSION['role']);
            error_log("About to redirect to /admin/dashboard");
            
            // Redirect admin to dashboard
            header("Location: /admin/dashboard");
            exit();
        }
        
        // Regular customer verification checks
        if (!isset($user['is_verified']) || !$user['is_verified']) {
            error_log("Login: User not verified - " . $user['email']);
            $error_message = "Please verify your email address first. Check your inbox for the verification code.";
        } else {
            error_log("Regular user verified, setting session and redirecting to /dashboard");
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Login successful for regular user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            error_log("Session set - user_id: " . $_SESSION['user_id'] . ", role: " . $_SESSION['role']);
            error_log("About to redirect to /dashboard");
            
            // Redirect customer to dashboard route (absolute path)
            header("Location: /dashboard");
            exit();
        }
    } else {
        error_log("Login: FAILED - Invalid credentials for: " . $email);
        // Login failed
        $error_message = "Invalid email or password.";
    }
}

// If we reach here, we need to show the login form
// Don't include navbar.php here as it sends output before headers
?>

<?php
require_once 'includes/config.php';
$page_title = 'Login';
$current_page = 'login';

// Pass extra styles if needed
ob_start(); ?>
<link rel="stylesheet" href="assets/css/buttons.css">
<style>
    body {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        display: flex;
        flex-direction: column;
    }

    .welcome-banner {
        background: linear-gradient(135deg, #006C3B, #005530);
        color: white;
        text-align: center;
        padding: 3rem 1rem;
        margin-top: 0; /* Header.php and padding-top in css handles this */
        position: relative;
        overflow: hidden;
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

    .login-container {
        width: 100%;
        max-width: 420px;
        margin: 0 auto;
        position: relative;
    }

    .login-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 24px;
        padding: 2.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transform: translateY(0);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .login-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    }

    .login-card h2 {
        color: #006C3B;
        text-align: center;
        font-size: 1.8rem;
        margin-bottom: 2rem;
        position: relative;
    }

    .login-card h2::after {
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

    .login-card:hover h2::after {
        width: 80px;
    }

    .form-group {
        margin-bottom: 1.8rem;
        position: relative;
        opacity: 0;
        transform: translateY(20px);
        animation: slideUpFade 0.5s ease-out forwards;
    }

    .form-group:nth-child(2) {
        animation-delay: 0.1s;
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

    .forgot-password {
        text-align: right;
        margin: 1.2rem 0;
        opacity: 0;
        animation: fadeIn 0.5s ease-out 0.3s forwards;
    }

    .forgot-password a {
        color: #006C3B;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .forgot-password a:hover {
        color: #00A65A;
        text-decoration: underline;
    }

    .sign-in-btn {
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
        position: relative;
        overflow: hidden;
    }

    .sign-in-btn:hover {
        background: linear-gradient(135deg, #005530, #008C4A);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 108, 59, 0.3);
    }

    .sign-in-btn::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(
            to right,
            rgba(255, 255, 255, 0) 0%,
            rgba(255, 255, 255, 0.3) 50%,
            rgba(255, 255, 255, 0) 100%
        );
        transform: rotate(45deg);
        transition: 0.5s;
        opacity: 0;
    }

    .sign-in-btn:hover::after {
        animation: shine 1s;
    }

    @keyframes shine {
        0% { transform: rotate(45deg) translateX(-100%); opacity: 0; }
        50% { opacity: 0.7; }
        100% { transform: rotate(45deg) translateX(100%); opacity: 0; }
    }

    .register-prompt {
        text-align: center;
        margin-top: 1.5rem;
        color: #666;
        font-size: 0.95rem;
        opacity: 0;
        animation: fadeIn 0.5s ease-out 0.5s forwards;
    }

    .register-prompt a {
        color: #006C3B;
        text-decoration: none;
        font-weight: 600;
        margin-left: 0.5rem;
        transition: all 0.3s ease;
    }

    .register-prompt a:hover {
        color: #00A65A;
        text-decoration: underline;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @media (max-width: 768px) {
        .welcome-banner { padding: 2rem 1rem; }
        .welcome-banner h1 { font-size: 2rem; }
        .main-container { padding: 1.5rem; margin-top: -2rem; }
        .login-card { padding: 2rem; border-radius: 20px; }
    }

    @media (max-height: 700px) {
        .welcome-banner { padding: 2rem 1rem; }
        .main-container { margin-top: -2rem; }
    }

    .password-field { position: relative; width: 100%; }
    .password-toggle {
        position: absolute; right: 15px; top: 50%;
        transform: translateY(-50%); cursor: pointer;
        color: #666; z-index: 2; transition: color 0.3s ease;
    }
    .password-toggle:hover { color: #006C3B; }

    .privacy-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none !important;
        z-index: 998 !important;
        backdrop-filter: blur(4px);
    }

    .privacy-modal-overlay.show {
        display: block !important;
        animation: fadeIn 0.3s ease-out;
    }

    .privacy-card-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        z-index: 999 !important;
        display: none !important;
        width: 100%;
        height: 100%;
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    .privacy-card-modal.show {
        display: flex !important;
        animation: modalShow 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    @keyframes modalShow {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .privacy-card {
        --shadow: rgba(60, 64, 67, 0.3) 0 1px 2px 0, rgba(60, 64, 67, 0.15) 0 2px 6px 2px;
        width: 90%;
        max-width: 420px;
        background-color: white;
        border-radius: 1.5rem;
        box-shadow: var(--shadow);
        overflow: hidden;
        animation: cardSlideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    @keyframes cardSlideUp {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes slideOut {
        from {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        to {
            opacity: 0;
            transform: translate(-50%, -50%) scale(0.8);
        }
    }

    .privacy-content {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 2rem 1.5rem;
        position: relative;
        gap: 1rem;
    }

    .privacy-icon {
        position: relative;
        margin: -4rem auto 2rem;
        width: 80px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .privacy-icon svg {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .privacy-title {
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        text-align: center;
        color: #4b5563;
        letter-spacing: -0.5px;
    }

    .privacy-description {
        width: 100%;
        margin: 0 0 2rem 0;
        font-size: 0.875rem;
        text-align: justify;
        color: #4b5563;
        line-height: 1.5;
    }

    .privacy-link {
        font-weight: 600;
        color: #634647;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .privacy-link:hover {
        color: #845556;
        text-decoration: underline;
    }

    .privacy-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        justify-content: space-between;
        padding-top: 1rem;
        border-top: 1px solid #e0e0e0;
    }

    .more-options-btn {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        color: #4b5563;
        background: transparent;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: color 0.3s ease;
        text-decoration: none;
        white-space: nowrap;
    }

    .more-options-btn:hover {
        color: #634647;
        text-decoration: underline;
    }

    .accept-btn {
        padding: 0.6rem 1.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #634647;
        background-color: #ddad81;
        border: none;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .accept-btn:hover {
        background-color: #634647;
        color: #ddad81;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 70, 71, 0.2);
    }

    .accept-btn:active {
        transform: translateY(0);
    }

    @media (max-width: 768px) {
        .privacy-card {
            width: 100%;
            max-width: 100%;
        }

        .privacy-content {
            padding: 1.5rem 1.25rem;
        }

        .privacy-icon {
            margin: -3rem auto 1.5rem;
            width: 70px;
            height: 50px;
        }

        .privacy-title {
            font-size: 0.95rem;
        }

        .privacy-description {
            font-size: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .privacy-actions {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .more-options-btn,
        .accept-btn {
            flex: 1;
            min-width: 100px;
        }
    }
</style>
<?php 
$extra_styles = ob_get_clean();

include 'includes/ui/header.php';
include 'includes/ui/loader.php';
include 'includes/ui/navbar.php';
?>

    <div class="welcome-banner">
        <h1>Welcome Back!</h1>
        <p>Sign in to your account to continue ordering your favorite meals</p>
    </div>

    <!-- Privacy & Cookie Consent Modal -->
    <div class="privacy-modal-overlay" id="privacyOverlay"></div>
    <div class="privacy-card-modal" id="privacyCardModal">
        <div class="privacy-card">
            <div class="privacy-content">
                <span class="privacy-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" height="46" width="65" viewBox="0 0 65 46">
                        <path stroke="#000" fill="#EAB789" d="M49.157 15.69L44.58.655l-12.422 1.96L21.044.654l-8.499 2.615-6.538 5.23-4.576 9.153v11.114l4.576 8.5 7.846 5.23 10.46 1.96 7.845-2.614 9.153 2.615 11.768-2.615 7.846-7.846 1.96-5.884.655-7.191-7.846-1.308-6.537-3.922z"></path>
                        <path fill="#9C6750" d="M32.286 3.749c-6.94 3.65-11.69 11.053-11.69 19.591 0 8.137 4.313 15.242 10.724 19.052a20.513 20.513 0 01-8.723 1.937c-11.598 0-21-9.626-21-21.5 0-11.875 9.402-21.5 21-21.5 3.495 0 6.79.874 9.689 2.42z" clip-rule="evenodd" fill-rule="evenodd"></path>
                        <path fill="#634647" d="M64.472 20.305a.954.954 0 00-1.172-.824 4.508 4.508 0 01-3.958-.934.953.953 0 00-1.076-.11c-.46.252-.977.383-1.502.382a3.154 3.154 0 01-2.97-2.11.954.954 0 00-.833-.634 4.54 4.54 0 01-4.205-4.507c.002-.23.022-.46.06-.687a.952.952 0 00-.213-.767 3.497 3.497 0 01-.614-3.5.953.953 0 00-.382-1.138 3.522 3.522 0 01-1.5-3.992.951.951 0 00-.762-1.227A22.611 22.611 0 0032.3 2.16 22.41 22.41 0 0022.657.001a22.654 22.654 0 109.648 43.15 22.644 22.644 0 0032.167-22.847zM22.657 43.4a20.746 20.746 0 110-41.493c2.566-.004 5.11.473 7.501 1.407a22.64 22.64 0 00.003 38.682 20.6 20.6 0 01-7.504 1.404zm19.286 0a20.746 20.746 0 112.131-41.384 5.417 5.417 0 001.918 4.635 5.346 5.346 0 00-.133 1.182A5.441 5.441 0 0046.879 11a5.804 5.804 0 00-.028.568 6.456 6.456 0 005.38 6.345 5.053 5.053 0 006.378 2.472 6.412 6.412 0 004.05 1.12 20.768 20.768 0 01-20.716 21.897z"></path>
                        <path fill="#644647" d="M54.962 34.3a17.719 17.719 0 01-2.602 2.378.954.954 0 001.14 1.53 19.637 19.637 0 002.884-2.634.955.955 0 00-1.422-1.274z"></path>
                        <path stroke-width="1.8" stroke="#644647" fill="#845556" d="M44.5 32.829c-.512 0-1.574.215-2 .5-.426.284-.342.263-.537.736a2.59 2.59 0 104.98.99c0-.686-.458-1.241-.943-1.726-.485-.486-.814-.5-1.5-.5zm-30.916-2.5c-.296 0-.912.134-1.159.311-.246.177-.197.164-.31.459a1.725 1.725 0 00-.086.932c.058.312.2.6.41.825.21.226.477.38.768.442.291.062.593.03.867-.092s.508-.329.673-.594a1.7 1.7 0 00.253-.896c0-.428-.266-.774-.547-1.076-.281-.302-.471-.31-.869-.311zm17.805-11.375c-.143-.492-.647-1.451-1.04-1.78-.392-.33-.348-.255-.857-.31a2.588 2.588 0 10.441 5.06c.66-.194 1.064-.788 1.395-1.39.33-.601.252-.92.06-1.58zm-22 2c-.143-.492-.647-1.451-1.04-1.78-.391-.33-.347-.255-.856-.31a2.589 2.589 0 10.44 5.06c.66-.194 1.064-.788 1.395-1.39.33-.601.252-.92.06-1.58zM38.112 7.329c-.395 0-1.216.179-1.545.415-.328.236-.263.218-.415.611-.151.393-.19.826-.114 1.243.078.417.268.8.548 1.1.28.301.636.506 1.024.59.388.082.79.04 1.155-.123.366-.163.678-.438.898-.792.22-.354.337-.77.337-1.195 0-.57-.354-1.031-.73-1.434-.374-.403-.628-.415-1.158-.415zm-19.123.703c.023-.296-.062-.92-.219-1.18-.157-.26-.148-.21-.432-.347a1.726 1.726 0 00-.922-.159 1.654 1.654 0 00-.856.344 1.471 1.471 0 00-.501.73c-.085.285-.077.589.023.872.1.282.287.532.538.718a1.7 1.7 0 00.873.323c.427.033.793-.204 1.116-.46.324-.256.347-.445.38-.841z"></path>
                        <path fill="#634647" d="M15.027 15.605a.954.954 0 00-1.553 1.108l1.332 1.863a.955.955 0 001.705-.77.955.955 0 00-.153-.34l-1.331-1.861z"></path>
                        <path fill="#644647" d="M43.31 23.21a.954.954 0 101.553-1.11l-1.266-1.772a.954.954 0 10-1.552 1.11l1.266 1.772z"></path>
                        <path fill="#634647" d="M19.672 35.374a.954.954 0 00-.954.953v2.363a.954.954 0 001.907 0v-2.362a.954.954 0 00-.953-.954z"></path>
                        <path fill="#644647" d="M33.129 29.18l-2.803 1.065a.953.953 0 00-.053 1.764.957.957 0 00.73.022l2.803-1.065a.953.953 0 00-.677-1.783v-.003zm24.373-3.628l-2.167.823a.956.956 0 00-.054 1.764.954.954 0 00.73.021l2.169-.823a.954.954 0 10-.678-1.784v-.001z"></path>
                    </svg>
                </span>
                <p class="privacy-title">Your privacy is important to us</p>
                <p class="privacy-description">We process your personal information to measure and improve our sites and services, to assist our campaigns and to provide personalized content.<br />For more information see our<a href="info/privacy" class="privacy-link"> Privacy Policy</a>.</p>
                <div class="privacy-actions">
                    <button class="more-options-btn">More Options</button>
                    <button class="accept-btn">Accept</button>
                </div>
            </div>
        </div>
    </div>
            
    <div class="main-container">
        <div class="login-container">
            <div class="login-card">
                <h2>Sign In</h2>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #f5c6cb;">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #c3e6cb;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" required>
                            <span class="password-toggle">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="forgot-password">
                        <a href="forgot_password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="sign-in-btn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>

                    <div class="register-prompt">
                        Don't have an account? <a href="register">Sign Up</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/ui/footer.php'; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Login page loaded - DOMContentLoaded fired');
            
            // ==================== PRIVACY CARD MODAL ====================
            const privacyOverlay = document.querySelector('.privacy-modal-overlay');
            const privacyCardModal = document.querySelector('.privacy-card-modal');
            const acceptBtn = document.querySelector('.accept-btn');
            const moreOptionsBtn = document.querySelector('.more-options-btn');
            
            console.log('Elements found - overlay:', !!privacyOverlay, 'modal:', !!privacyCardModal, 'accept:', !!acceptBtn);
            
            // FORCE SHOW - Always show the modal on login page
            console.log('FORCING privacy modal to show on login page');
            if (privacyOverlay) {
                privacyOverlay.classList.add('show');
                privacyOverlay.style.display = 'block !important';
                privacyOverlay.style.zIndex = '998';
            }
            if (privacyCardModal) {
                privacyCardModal.classList.add('show');
                privacyCardModal.style.display = 'flex !important';
                privacyCardModal.style.zIndex = '999';
            }

            // Accept button handler
            if (acceptBtn) {
                acceptBtn.addEventListener('click', function() {
                    console.log('Privacy accepted - closing modal');
                    localStorage.setItem('privacyAccepted', 'true');
                    if (privacyCardModal && privacyOverlay) {
                        privacyCardModal.style.animation = 'slideOut 0.4s ease-out forwards';
                        setTimeout(() => {
                            privacyCardModal.classList.remove('show');
                            privacyCardModal.style.display = 'none';
                            privacyOverlay.classList.remove('show');
                            privacyOverlay.style.display = 'none';
                        }, 400);
                    }
                });
            }

            // More Options button handler
            if (moreOptionsBtn) {
                moreOptionsBtn.addEventListener('click', function() {
                    window.location.href = 'info/privacy';
                });
            }

            // Overlay click handler - DO NOT close on overlay click
            if (privacyOverlay) {
                privacyOverlay.addEventListener('click', function(e) {
                    // Only close if clicking directly on overlay, not on card
                    if (e.target === privacyOverlay) {
                        console.log('Overlay background clicked - but NOT closing modal');
                        // Don't close - user must click Accept button or More Options
                        return false;
                    }
                });
            }

            // ==================== PASSWORD TOGGLE ====================
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (document.querySelector('.password-toggle')) {
                document.querySelector('.password-toggle').addEventListener('click', function() {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        toggleIcon.classList.remove('fa-eye');
                        toggleIcon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        toggleIcon.classList.remove('fa-eye-slash');
                        toggleIcon.classList.add('fa-eye');
                    }
                });
            }
            
            // Navbar scroll effect
            window.addEventListener('scroll', function() {
                const navbar = document.querySelector('.navbar');
                if (navbar && window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    if(navbar) navbar.classList.remove('scrolled');
                }
            });

            // Close modal with ESC key - DISABLED (user must click Accept or More Options)
            // Removed ESC handler to force user interaction
        });
    </script>
<?php // Footer already included above ?>