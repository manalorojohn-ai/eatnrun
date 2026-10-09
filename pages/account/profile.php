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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar-enhanced.css">

    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #00502b;
            --primary-light: #e8f5e9;
            --primary-gradient: linear-gradient(135deg, #006C3B 0%, #00874a 100%);
            --accent: #FFB800;
            --accent-light: #FFF8E7;
            --bg-page: #f4f6f8;
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-xl: 20px;
            --radius-lg: 16px;
            --radius-md: 12px;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 16px 36px rgba(0, 108, 59, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Hero Banner */
        .profile-hero {
            background: var(--primary-gradient);
            position: relative;
            padding: 50px 20px 90px;
            overflow: hidden;
        }

        .profile-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.2) 0%, transparent 45%),
                        radial-gradient(circle at 85% 80%, rgba(255, 184, 0, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .profile-hero-inner {
            max-width: 1140px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .hero-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 12px;
        }

        .hero-breadcrumb a {
            color: white;
            text-decoration: none;
            transition: opacity 0.2s;
        }

        .hero-breadcrumb a:hover {
            opacity: 0.8;
        }

        .profile-hero h1 {
            color: white;
            font-size: clamp(1.8rem, 4vw, 2.4rem);
            font-weight: 700;
            margin: 0 0 6px;
        }

        .profile-hero p {
            color: rgba(255, 255, 255, 0.85);
            margin: 0;
            font-size: 0.95rem;
        }

        /* Main Container */
        .profile-main-wrap {
            max-width: 1140px;
            width: 100%;
            margin: -60px auto 50px;
            padding: 0 20px;
            position: relative;
            z-index: 3;
            flex: 1;
        }

        /* Profile Layout Grid */
        .profile-layout {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 24px;
            align-items: start;
        }

        /* Cards Universal */
        .card-box {
            background: var(--surface);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        /* Sidebar Profile Card */
        .user-summary-card {
            text-align: center;
            padding: 0 0 24px;
        }

        .user-card-cover {
            height: 100px;
            background: linear-gradient(135deg, rgba(0, 108, 59, 0.15), rgba(255, 184, 0, 0.2));
            position: relative;
        }

        .avatar-container {
            margin-top: -55px;
            position: relative;
            display: inline-block;
        }

        .avatar-circle {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            border: 4px solid var(--surface);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            font-weight: 700;
            overflow: hidden;
            position: relative;
            margin: 0 auto;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-badge-btn {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--accent);
            color: #212529;
            border: 3px solid var(--surface);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .avatar-badge-btn:hover {
            transform: scale(1.1);
            background: #ffa800;
        }

        .user-names-block {
            padding: 16px 20px 0;
        }

        .user-fullname {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 4px;
            color: var(--text-main);
        }

        .user-email-text {
            color: var(--text-muted);
            font-size: 0.88rem;
            word-break: break-all;
            margin-bottom: 16px;
        }

        .user-meta-chips {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            background: var(--primary-light);
            color: var(--primary);
        }

        .sidebar-nav-list {
            padding: 0 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .side-nav-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            color: var(--text-main);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .side-nav-item:hover, .side-nav-item.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        .side-nav-item .nav-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .side-nav-item i {
            font-size: 1.05rem;
            width: 20px;
            text-align: center;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 20px 18px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.25s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: rgba(0, 108, 59, 0.25);
        }

        .stat-icon-wrap {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .stat-icon-wrap.green {
            background: var(--primary-light);
            color: var(--primary);
        }

        .stat-icon-wrap.yellow {
            background: var(--accent-light);
            color: #d97706;
        }

        .stat-icon-wrap.blue {
            background: #e0f2fe;
            color: #0284c7;
        }

        .stat-body {
            min-width: 0;
        }

        .stat-body .stat-number {
            font-size: 1.28rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-body .stat-title {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
            font-weight: 500;
        }

        /* Main Form Card */
        .content-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
            padding: 28px;
        }

        .content-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            padding-bottom: 18px;
            margin-bottom: 24px;
        }

        .content-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .content-header h2 i {
            color: var(--primary);
        }

        .content-header .badge-tag {
            font-size: 0.78rem;
            padding: 5px 12px;
            border-radius: 20px;
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        /* Form Controls */
        .form-label {
            font-weight: 600;
            font-size: 0.86rem;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .input-group-modern {
            position: relative;
            margin-bottom: 20px;
        }

        .input-icon-box {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.95rem;
            pointer-events: none;
            z-index: 2;
        }

        .input-icon-box.textarea-icon {
            top: 22px;
        }

        .form-control-modern {
            width: 100%;
            padding: 12px 16px 12px 42px;
            font-size: 0.92rem;
            border-radius: var(--radius-md);
            border: 1.5px solid var(--border);
            background: #fafbfc;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .form-control-modern:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3.5px rgba(0, 108, 59, 0.12);
            outline: none;
        }

        .form-control-modern:disabled, .form-control-modern[readonly] {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }

        .form-hint {
            font-size: 0.76rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .btn-submit-profile {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 13px 28px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(0, 108, 59, 0.25);
            width: 100%;
        }

        .btn-submit-profile:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 108, 59, 0.35);
            color: white;
        }

        .btn-submit-profile:active {
            transform: translateY(0);
        }

        /* Responsive Breakpoints */
        @media (max-width: 991px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
            .user-summary-card {
                max-width: 100%;
            }
            .sidebar-nav-list {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            .side-nav-item .fa-chevron-right {
                display: none;
            }
            .side-nav-item {
                justify-content: center;
                text-align: center;
                padding: 10px 8px;
            }
            .side-nav-item .nav-left {
                flex-direction: column;
                gap: 4px;
            }
        }

        @media (max-width: 768px) {
            .profile-hero {
                padding: 30px 16px 75px;
            }
            .profile-main-wrap {
                margin-top: -50px;
                padding: 0 12px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .content-card {
                padding: 20px 16px;
                border-radius: var(--radius-lg);
            }
            .sidebar-nav-list {
                grid-template-columns: 1fr;
            }
            .side-nav-item .fa-chevron-right {
                display: block;
            }
            .side-nav-item {
                justify-content: space-between;
                text-align: left;
                padding: 12px 14px;
            }
            .side-nav-item .nav-left {
                flex-direction: row;
                gap: 12px;
            }
        }

        /* Toast notification */
        .toast-custom {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            background: white;
            border-radius: var(--radius-md);
            padding: 14px 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .toast-custom.show {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php include 'includes/ui/navbar.php'; ?>

    <!-- Hero Banner -->
    <div class="profile-hero">
        <div class="profile-hero-inner">
            <div class="hero-breadcrumb">
                <a href="index"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right" style="font-size: 0.7rem; opacity: 0.6;"></i>
                <span>My Profile</span>
            </div>
            <h1>My Profile</h1>
            <p>Manage your account details and delivery preferences</p>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="profile-main-wrap">
        <div class="profile-layout">
            
            <!-- Sidebar / User Overview -->
            <aside class="sidebar-col">
                <div class="card-box user-summary-card">
                    <div class="user-card-cover"></div>
                    
                    <div class="avatar-container">
                        <div class="avatar-circle" id="profileAvatar">
                            <?php if (!empty($user['profile_photo'])): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Avatar">
                            <?php else: ?>
                                <?php echo strtoupper(substr($user['full_name'] ?: 'U', 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="avatar-badge-btn" id="changePhotoBtn" title="Upload new photo">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>

                    <div class="user-names-block">
                        <h2 class="user-fullname"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <div class="user-email-text"><?php echo htmlspecialchars($user['email']); ?></div>
                        
                        <div class="user-meta-chips">
                            <span class="meta-chip"><i class="fas fa-shield-alt"></i> Verified Customer</span>
                            <span class="meta-chip" style="background: var(--accent-light); color: #b45309;">
                                <i class="fas fa-star"></i> Loyal Foodie
                            </span>
                        </div>
                    </div>

                    <nav class="sidebar-nav-list">
                        <a href="profile" class="side-nav-item active">
                            <span class="nav-left"><i class="fas fa-user-circle"></i> Profile Overview</span>
                            <i class="fas fa-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                        </a>
                        <a href="my_orders" class="side-nav-item">
                            <span class="nav-left"><i class="fas fa-receipt"></i> Order History</span>
                            <i class="fas fa-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                        </a>
                        <a href="menu" class="side-nav-item">
                            <span class="nav-left"><i class="fas fa-utensils"></i> Browse Menu</span>
                            <i class="fas fa-chevron-right text-muted" style="font-size: 0.75rem;"></i>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Main Content Area: Stats + Form -->
            <div class="main-content-col">
                
                <!-- Quick Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon-wrap green">
                            <i class="fas fa-bag-shopping"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-number"><?php echo (int)$stats['total_orders']; ?></div>
                            <div class="stat-title">Orders Completed</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon-wrap yellow">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-number">₱<?php echo number_format((float)$stats['total_spent'], 2); ?></div>
                            <div class="stat-title">Total Spent</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon-wrap blue">
                            <i class="fas fa-clock-rotate-left"></i>
                        </div>
                        <div class="stat-body">
                            <div class="stat-number" style="font-size: 1rem; font-weight: 600;">
                                <?php echo $stats['last_order'] ? date('M j, Y', strtotime($stats['last_order'])) : 'No orders'; ?>
                            </div>
                            <div class="stat-title">Recent Activity</div>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="content-card">
                    <div class="content-header">
                        <h2><i class="fas fa-id-card"></i> Personal Information</h2>
                        <span class="badge-tag"><i class="fas fa-lock me-1"></i> Secure Details</span>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="border-radius: var(--radius-md);">
                            <i class="fas fa-circle-exclamation me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="border-radius: var(--radius-md);">
                            <i class="fas fa-circle-check me-2 fs-5"></i>
                            <div><?php echo htmlspecialchars($success); ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="profile">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="full_name">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-group-modern">
                                        <i class="fas fa-user input-icon-box"></i>
                                        <input type="text" class="form-control-modern" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" placeholder="Enter your full name" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="email">Email Address</label>
                                    <div class="input-group-modern">
                                        <i class="fas fa-envelope input-icon-box"></i>
                                        <input type="email" class="form-control-modern" id="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" readonly disabled>
                                    </div>
                                    <div class="form-hint"><i class="fas fa-info-circle me-1"></i> Email cannot be changed directly for security.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label" for="phone">Phone Number <span class="text-danger">*</span></label>
                                    <div class="input-group-modern">
                                        <i class="fas fa-phone input-icon-box"></i>
                                        <input type="tel" class="form-control-modern" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. 0912 345 6789" required>
                                    </div>
                                    <div class="form-hint">Used by our riders for delivery updates and SMS notifications.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-4">
                                    <label class="form-label" for="address">Default Delivery Address <span class="text-danger">*</span></label>
                                    <div class="input-group-modern">
                                        <i class="fas fa-location-dot input-icon-box textarea-icon"></i>
                                        <textarea class="form-control-modern" id="address" name="address" rows="3" 
                                                  placeholder="Building, street, barangay, city, landmark" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="update_profile" class="btn-submit-profile">
                                <i class="fas fa-floppy-disk"></i> Save Profile Changes
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <!-- Hidden file input for avatar upload -->
    <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">

    <!-- Custom Toast -->
    <div id="toastNotification" class="toast-custom">
        <i id="toastIcon" class="fas fa-check-circle text-success fs-5"></i>
        <span id="toastMsg" style="font-weight: 500; font-size: 0.9rem;">Notification message</span>
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

        function showToast(message, isSuccess = true) {
            toastMsg.textContent = message;
            if (isSuccess) {
                toastIcon.className = 'fas fa-check-circle text-success fs-5';
            } else {
                toastIcon.className = 'fas fa-exclamation-circle text-danger fs-5';
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
                    showToast('File size exceeds 5MB limit.', false);
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
                            avatarBox.innerHTML = `<img src="${e.target.result}" alt="Avatar">`;
                        };
                        reader.readAsDataURL(file);
                        showToast(data.message || 'Profile photo updated!');
                    } else {
                        avatarBox.innerHTML = oldHTML;
                        showToast(data.message || 'Failed to upload photo.', false);
                    }
                })
                .catch(err => {
                    avatarBox.innerHTML = oldHTML;
                    showToast('An error occurred during upload.', false);
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
