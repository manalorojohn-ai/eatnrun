<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/includes/helpers/init_session.php';
require_once dirname(__DIR__, 2) . '/config/database/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user information
$user = null;
try {
    $user_query = "SELECT * FROM users WHERE id = ?";
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($user_query);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Profile - Error fetching user: " . $e->getMessage());
}

if (!$user) {
    $user = [
        'id' => $user_id,
        'email' => $_SESSION['email'] ?? 'user@eatnrun.com',
        'full_name' => $_SESSION['full_name'] ?? 'User Name',
        'phone' => $_SESSION['phone'] ?? '',
        'address' => $_SESSION['address'] ?? '',
        'profile_photo' => null
    ];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($full_name)) {
        $error = "Full name is required.";
    } else {
        $update_query = "UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?";
        $is_updated = false;

        try {
            if ($conn instanceof PDO) {
                $stmt = $conn->prepare($update_query);
                $is_updated = $stmt->execute([$full_name, $phone, $address, $user_id]);
            } elseif ($conn instanceof mysqli) {
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssi", $full_name, $phone, $address, $user_id);
                $is_updated = $stmt->execute();
                $stmt->close();
            } else {
                $is_updated = true;
            }
        } catch (Exception $e) {
            $is_updated = false;
        }

        if ($is_updated) {
            $success = "Profile updated successfully!";
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
            $user['address'] = $address;
            $_SESSION['full_name'] = $full_name;
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}

// Get order statistics
$stats = ['total_orders' => 0, 'total_spent' => 0, 'last_order' => null];
try {
    $stats_query = "SELECT 
                    COUNT(*) as total_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent,
                    MAX(created_at) as last_order
                    FROM orders 
                    WHERE user_id = ? AND status != 'cancelled'";
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare($stats_query);
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;
    } elseif ($conn instanceof mysqli) {
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats_res = $stmt->get_result()->fetch_assoc();
        if ($stats_res) $stats = $stats_res;
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Profile stats error: " . $e->getMessage());
}

// Handle AJAX profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    header('Content-Type: application/json');
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024;

    try {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Only JPG, PNG, WEBP & GIF images are allowed.');
        }

        if ($file['size'] > $max_size) {
            throw new Exception('File size exceeds 5MB limit.');
        }

        $upload_dir = dirname(__DIR__, 2) . '/uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
        $upload_path = $upload_dir . $new_filename;

        if (!empty($user['profile_photo'])) {
            $old_file = $upload_dir . $user['profile_photo'];
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $update_query = "UPDATE users SET profile_photo = ? WHERE id = ?";
            if ($conn instanceof PDO) {
                $stmt = $conn->prepare($update_query);
                $stmt->execute([$new_filename, $user_id]);
            } elseif ($conn instanceof mysqli) {
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $new_filename, $user_id);
                $stmt->execute();
                $stmt->close();
            }
            echo json_encode(['success' => true, 'message' => 'Profile photo updated!', 'photo' => $new_filename]);
        } else {
            throw new Exception('Failed to save file.');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$page_title = "My Profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Eat&Run</title>
    
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar-enhanced.css">

    <style>
        :root {
            --primary: #006C3B;
            --primary-rgb: 0, 108, 59;
            --primary-dark: #004d2a;
            --primary-light: #e6f4ea;
            --accent: #FFB800;
            --accent-rgb: 255, 184, 0;
            --accent-light: #fff9e6;
            --bg-page: #f8fafc;
            --surface: #ffffff;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --border-subtle: #e2e8f0;
            --border-focus: #006C3B;
            --shadow-sm: 0 2px 8px -2px rgba(15, 23, 42, 0.06);
            --shadow-card: 0 10px 30px -5px rgba(15, 23, 42, 0.07), 0 4px 10px -2px rgba(15, 23, 42, 0.04);
            --shadow-float: 0 20px 40px -10px rgba(0, 108, 59, 0.2);
            --radius-xl: 24px;
            --radius-lg: 18px;
            --radius-md: 12px;
            --radius-pill: 9999px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Poppins', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 85px !important; /* Accommodate fixed navbar comfortably */
        }

        /* Hero Banner with Modern Decorative Elements */
        .profile-hero {
            background: linear-gradient(135deg, #005a31 0%, #006C3B 60%, #008749 100%);
            position: relative;
            padding: 45px 24px 100px;
            overflow: hidden;
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 184, 0, 0.18) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .profile-hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: 5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 65%);
            border-radius: 50%;
            pointer-events: none;
        }

        .profile-hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .hero-breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            background: rgba(255, 255, 255, 0.15);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 16px;
            font-weight: 500;
        }

        .hero-breadcrumb a {
            color: #fff;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .hero-breadcrumb a:hover {
            opacity: 0.75;
        }

        .hero-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .hero-title-row h1 {
            color: #ffffff;
            font-size: clamp(2rem, 3.5vw, 2.5rem);
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .hero-title-row p {
            color: rgba(255, 255, 255, 0.88);
            font-size: 0.95rem;
            margin: 4px 0 0;
            font-weight: 400;
        }

        .hero-badge-live {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: var(--radius-pill);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7);
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 7px rgba(74, 222, 128, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
        }

        /* Profile Layout Wrapper */
        .profile-main-wrap {
            max-width: 1200px;
            width: 100%;
            margin: -70px auto 60px;
            padding: 0 24px;
            position: relative;
            z-index: 3;
            flex: 1;
        }

        .profile-layout {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 28px;
            align-items: start;
        }

        /* Sidebar Profile Card */
        .user-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
            position: sticky;
            top: 105px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .user-card-header {
            height: 120px;
            background: linear-gradient(135deg, #dcfce7 0%, #fef3c7 100%);
            position: relative;
        }

        .user-card-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(#006C3B 0.75px, transparent 0.75px);
            background-size: 14px 14px;
            opacity: 0.15;
        }

        .avatar-wrap {
            margin-top: -60px;
            display: flex;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        .avatar-circle-wrapper {
            position: relative;
            display: inline-block;
        }

        .avatar-circle {
            width: 115px;
            height: 115px;
            border-radius: 50%;
            background: linear-gradient(135deg, #006C3B, #008749);
            color: #ffffff;
            border: 4px solid #ffffff;
            box-shadow: 0 10px 25px rgba(0, 108, 59, 0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            font-weight: 800;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .camera-trigger {
            position: absolute;
            bottom: 3px;
            right: 3px;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--accent);
            color: #1e293b;
            border: 3.5px solid #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            font-size: 0.95rem;
        }

        .camera-trigger:hover {
            transform: scale(1.15) rotate(5deg);
            background: #f59e0b;
        }

        .user-identity {
            text-align: center;
            padding: 16px 24px 0;
        }

        .user-identity h2 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-heading);
            margin: 0 0 4px;
        }

        .user-identity p {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin: 0 0 16px;
            word-break: break-all;
        }

        .badges-row {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: var(--radius-pill);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .badge-pill.customer {
            background: var(--primary-light);
            color: var(--primary);
        }

        .badge-pill.foodie {
            background: var(--accent-light);
            color: #b45309;
        }

        .nav-sections-list {
            padding: 0 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav-btn-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            border-radius: var(--radius-md);
            color: var(--text-body);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.92rem;
            transition: all 0.2s ease;
            background: transparent;
        }

        .nav-btn-link .left-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-btn-link .icon-box {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: var(--text-muted);
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .nav-btn-link:hover, .nav-btn-link.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        .nav-btn-link:hover .icon-box, .nav-btn-link.active .icon-box {
            background: var(--primary);
            color: #ffffff;
        }

        .nav-btn-link:hover .fa-chevron-right, .nav-btn-link.active .fa-chevron-right {
            transform: translateX(3px);
            color: var(--primary);
        }

        .nav-btn-link .fa-chevron-right {
            font-size: 0.75rem;
            color: #94a3b8;
            transition: transform 0.2s ease;
        }

        /* Stats Cards Top Grid */
        .stats-deck {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 26px;
        }

        .deck-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--shadow-sm);
            padding: 22px 20px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .deck-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: transparent;
            transition: background 0.3s ease;
        }

        .deck-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-card);
            border-color: rgba(0, 108, 59, 0.2);
        }

        .deck-card:hover::after {
            background: var(--primary);
        }

        .deck-icon-wrap {
            width: 54px;
            height: 54px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }

        .deck-card:hover .deck-icon-wrap {
            transform: scale(1.1);
        }

        .deck-icon-wrap.orders {
            background: #e6f4ea;
            color: #006C3B;
        }

        .deck-icon-wrap.spend {
            background: #fef3c7;
            color: #d97706;
        }

        .deck-icon-wrap.activity {
            background: #e0f2fe;
            color: #0284c7;
        }

        .deck-meta {
            min-width: 0;
        }

        .deck-meta .deck-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text-heading);
            margin: 0;
            line-height: 1.15;
            letter-spacing: -0.5px;
        }

        .deck-meta .deck-title {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-muted);
            margin: 4px 0 0;
        }

        /* Profile Form Card */
        .info-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--shadow-card);
            padding: 32px;
        }

        .info-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 20px;
            margin-bottom: 28px;
            border-bottom: 1.5px solid var(--border-subtle);
        }

        .info-card-header .title-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .info-card-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-heading);
            margin: 0;
        }

        .info-card-header p {
            font-size: 0.84rem;
            color: var(--text-muted);
            margin: 2px 0 0;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: #f1f5f9;
            color: var(--text-body);
            border-radius: var(--radius-pill);
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Form Inputs */
        .custom-form-group {
            margin-bottom: 22px;
        }

        .custom-form-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-heading);
            margin-bottom: 8px;
        }

        .custom-form-label .required {
            color: #ef4444;
        }

        .input-wrapper {
            position: relative;
        }

        .input-lead-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .input-lead-icon.textarea-icon {
            top: 20px;
        }

        .custom-input {
            width: 100%;
            padding: 13px 18px 13px 46px;
            font-size: 0.94rem;
            font-family: inherit;
            color: var(--text-heading);
            background: #f8fafc;
            border: 1.5px solid var(--border-subtle);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }

        .custom-input:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(0, 108, 59, 0.12);
            outline: none;
        }

        .custom-input:focus + .input-lead-icon,
        .input-wrapper:focus-within .input-lead-icon {
            color: var(--primary);
        }

        .custom-input:disabled, .custom-input[readonly] {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
            border-color: #e2e8f0;
        }

        .field-footnote {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-save-profile {
            background: linear-gradient(135deg, #006C3B 0%, #008749 100%);
            color: #ffffff;
            border: none;
            padding: 14px 32px;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 0.98rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 6px 18px rgba(0, 108, 59, 0.25);
            cursor: pointer;
        }

        .btn-save-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(0, 108, 59, 0.35);
            background: linear-gradient(135deg, #005a31 0%, #007a41 100%);
            color: #ffffff;
        }

        .btn-save-profile:active {
            transform: translateY(0);
        }

        /* Alert Styling */
        .toast-banner {
            border-radius: var(--radius-md);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            font-size: 0.92rem;
            font-weight: 500;
            animation: fadeInDown 0.3s ease;
        }

        .toast-banner.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .toast-banner.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Notification Toast popup */
        .toast-floating {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 99999;
            background: #ffffff;
            border-radius: var(--radius-md);
            padding: 16px 22px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
            border: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateY(120px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .toast-floating.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Mobile & Responsive Adaptations */
        @media (max-width: 991px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
            .user-card {
                position: static;
            }
            .nav-sections-list {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            .nav-btn-link .fa-chevron-right {
                display: none;
            }
            .nav-btn-link {
                justify-content: center;
                text-align: center;
                padding: 12px 8px;
            }
            .nav-btn-link .left-content {
                flex-direction: column;
                gap: 6px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 75px !important;
            }
            .profile-hero {
                padding: 30px 16px 85px;
            }
            .profile-main-wrap {
                margin-top: -60px;
                padding: 0 14px;
            }
            .hero-title-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .stats-deck {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .info-card {
                padding: 22px 18px;
                border-radius: var(--radius-lg);
            }
            .nav-sections-list {
                grid-template-columns: 1fr;
            }
            .nav-btn-link {
                justify-content: space-between;
                text-align: left;
                padding: 12px 16px;
            }
            .nav-btn-link .left-content {
                flex-direction: row;
                gap: 12px;
            }
            .nav-btn-link .fa-chevron-right {
                display: block;
            }
            .btn-save-profile {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/ui/navbar.php'; ?>

    <!-- Hero Header Banner with Badges -->
    <header class="profile-hero">
        <div class="profile-hero-inner">
            <div class="hero-breadcrumb">
                <a href="index"><i class="fas fa-home me-1"></i> Home</a>
                <i class="fas fa-chevron-right" style="font-size: 0.65rem; opacity: 0.7;"></i>
                <span>My Profile</span>
            </div>

            <div class="hero-title-row">
                <div>
                    <h1>My Profile</h1>
                    <p>Manage your account settings, personal details, and delivery address</p>
                </div>
                <div class="hero-badge-live">
                    <span class="pulse-dot"></span>
                    <span>Account Active</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="profile-main-wrap">
        <div class="profile-layout">
            
            <!-- Left Sidebar Profile Card -->
            <aside class="sidebar-area">
                <div class="user-card">
                    <div class="user-card-header"></div>
                    
                    <div class="avatar-wrap">
                        <div class="avatar-circle-wrapper">
                            <div class="avatar-circle" id="profileAvatar">
                                <?php if (!empty($user['profile_photo'])): ?>
                                    <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($user['full_name'] ?: 'U', 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="camera-trigger" id="changePhotoBtn" title="Update Profile Photo">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                    </div>

                    <div class="user-identity">
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>

                        <div class="badges-row">
                            <span class="badge-pill customer">
                                <i class="fas fa-circle-check"></i> Verified Member
                            </span>
                            <span class="badge-pill foodie">
                                <i class="fas fa-crown"></i> Eat&Run Foodie
                            </span>
                        </div>
                    </div>

                    <nav class="nav-sections-list">
                        <a href="profile" class="nav-btn-link active">
                            <div class="left-content">
                                <div class="icon-box"><i class="fas fa-user"></i></div>
                                <span>Profile Overview</span>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="my_orders" class="nav-btn-link">
                            <div class="left-content">
                                <div class="icon-box"><i class="fas fa-receipt"></i></div>
                                <span>Order History</span>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="menu" class="nav-btn-link">
                            <div class="left-content">
                                <div class="icon-box"><i class="fas fa-burger"></i></div>
                                <span>Browse Menu</span>
                            </div>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Right Content: Stats & Details Form -->
            <section class="content-area">
                
                <!-- Quick Metric Cards Deck -->
                <div class="stats-deck">
                    <div class="deck-card">
                        <div class="deck-icon-wrap orders">
                            <i class="fas fa-bag-shopping"></i>
                        </div>
                        <div class="deck-meta">
                            <div class="deck-value"><?php echo (int)$stats['total_orders']; ?></div>
                            <div class="deck-title">Orders Completed</div>
                        </div>
                    </div>

                    <div class="deck-card">
                        <div class="deck-icon-wrap spend">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="deck-meta">
                            <div class="deck-value">₱<?php echo number_format((float)$stats['total_spent'], 2); ?></div>
                            <div class="deck-title">Total Amount Spent</div>
                        </div>
                    </div>

                    <div class="deck-card">
                        <div class="deck-icon-wrap activity">
                            <i class="fas fa-clock-rotate-left"></i>
                        </div>
                        <div class="deck-meta">
                            <div class="deck-value" style="font-size: 1.05rem;">
                                <?php echo $stats['last_order'] ? date('M j, Y', strtotime($stats['last_order'])) : 'No orders yet'; ?>
                            </div>
                            <div class="deck-title">Recent Order Date</div>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="title-group">
                            <div class="header-icon-box">
                                <i class="fas fa-address-card"></i>
                            </div>
                            <div>
                                <h2>Personal Information</h2>
                                <p>Keep your contact details up to date for smooth deliveries</p>
                            </div>
                        </div>
                        <span class="status-chip">
                            <i class="fas fa-shield-halved text-success"></i> Data Protected
                        </span>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="toast-banner error">
                            <i class="fas fa-triangle-exclamation fs-5"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="toast-banner success">
                            <i class="fas fa-circle-check fs-5"></i>
                            <div><?php echo htmlspecialchars($success); ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="profile">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="custom-form-group">
                                    <label class="custom-form-label" for="full_name">
                                        Full Name <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <input type="text" class="custom-input" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                               placeholder="e.g. John Doe" required>
                                        <i class="fas fa-user input-lead-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="custom-form-group">
                                    <label class="custom-form-label" for="email">
                                        Email Address
                                    </label>
                                    <div class="input-wrapper">
                                        <input type="email" class="custom-input" id="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" readonly disabled>
                                        <i class="fas fa-envelope input-lead-icon"></i>
                                    </div>
                                    <div class="field-footnote">
                                        <i class="fas fa-lock"></i> Primary email cannot be modified directly
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="custom-form-group">
                                    <label class="custom-form-label" for="phone">
                                        Mobile Phone Number <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <input type="tel" class="custom-input" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                               placeholder="0912 345 6789" required>
                                        <i class="fas fa-phone input-lead-icon"></i>
                                    </div>
                                    <div class="field-footnote">
                                        <i class="fas fa-motorcycle"></i> Riders will use this contact number to coordinate deliveries
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="custom-form-group">
                                    <label class="custom-form-label" for="address">
                                        Default Delivery Address <span class="required">*</span>
                                    </label>
                                    <div class="input-wrapper">
                                        <textarea class="custom-input" id="address" name="address" rows="3" 
                                                  placeholder="Room/Unit, Street name, Barangay, City, Landmark" 
                                                  style="resize: vertical; min-height: 95px;" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                        <i class="fas fa-location-dot input-lead-icon textarea-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end pt-2">
                            <button type="submit" name="update_profile" class="btn-save-profile">
                                <i class="fas fa-check"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

            </section>
        </div>
    </main>

    <!-- Hidden file input for photo upload -->
    <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">

    <!-- Floating Toast Notification -->
    <div id="toastNotification" class="toast-floating">
        <i id="toastIcon" class="fas fa-circle-check text-success fs-5"></i>
        <span id="toastMsg" style="font-weight: 600; font-size: 0.92rem;">Notification text</span>
    </div>

    <?php include 'includes/ui/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const avatarBox = document.getElementById('profileAvatar');
        const changePhotoBtn = document.getElementById('changePhotoBtn');
        const fileInput = document.getElementById('avatarFileInput');
        const toast = document.getElementById('toastNotification');
        const toastMsg = document.getElementById('toastMsg');
        const toastIcon = document.getElementById('toastIcon');

        function triggerToast(message, isSuccess = true) {
            toastMsg.textContent = message;
            if (isSuccess) {
                toastIcon.className = 'fas fa-circle-check text-success fs-5';
            } else {
                toastIcon.className = 'fas fa-triangle-exclamation text-danger fs-5';
            }
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        if (changePhotoBtn && fileInput) {
            changePhotoBtn.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', function() {
                if (!this.files || !this.files[0]) return;
                const file = this.files[0];

                if (file.size > 5 * 1024 * 1024) {
                    triggerToast('File size exceeds 5MB limit.', false);
                    return;
                }

                const formData = new FormData();
                formData.append('profile_photo', file);

                const oldHTML = avatarBox.innerHTML;
                avatarBox.innerHTML = '<div class="spinner-border spinner-border-sm text-light" role="status"></div>';
                changePhotoBtn.style.pointerEvents = 'none';

                fetch('profile', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            avatarBox.innerHTML = `<img src="${e.target.result}" alt="Profile Photo">`;
                        };
                        reader.readAsDataURL(file);
                        triggerToast(data.message || 'Profile photo updated!');
                    } else {
                        avatarBox.innerHTML = oldHTML;
                        triggerToast(data.message || 'Failed to upload photo.', false);
                    }
                })
                .catch(err => {
                    avatarBox.innerHTML = oldHTML;
                    triggerToast('An error occurred during upload.', false);
                })
                .finally(() => {
                    changePhotoBtn.style.pointerEvents = 'auto';
                    fileInput.value = '';
                });
            });
        }
    });
    </script>
</body>
</html>
