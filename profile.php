<?php
session_start();
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user information
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Update user information
    $update_query = "UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "sssi", $full_name, $phone, $address, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = "Profile updated successfully!";
        // Update the user array with new values
        $user['full_name'] = $full_name;
        $user['phone'] = $phone;
        $user['address'] = $address;
    } else {
        $error = "Failed to update profile. Please try again.";
    }
    mysqli_stmt_close($stmt);
}

// Get order statistics
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_spent,
                MAX(created_at) as last_order
                FROM orders 
                WHERE user_id = ? AND status != 'cancelled'";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    try {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error uploading file.');
        }
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG & GIF files are allowed.');
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            throw new Exception('File size too large. Maximum size is 5MB.');
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'profile_' . $user_id . '_' . uniqid() . '.' . $extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Delete old profile photo if exists
        if (!empty($user['profile_photo'])) {
            $old_file = $upload_dir . $user['profile_photo'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Update database
            $update_query = "UPDATE users SET profile_photo = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $new_filename, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $user['profile_photo'] = $new_filename;
                $response = ['success' => true, 'message' => 'Profile photo updated successfully.', 'photo' => $new_filename];
            } else {
                throw new Exception('Failed to update database.');
            }
        } else {
            throw new Exception('Failed to move uploaded file.');
        }
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Eat&Run</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: #e8f5e9;
            --accent: #FFC107;
            --bs-primary: #006C3B;
            --bs-primary-rgb: 0, 108, 59;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .profile-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 1rem;
        }

        .profile-header {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .profile-header:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0,0,0,0.12);
        }

        .profile-banner {
            height: 150px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            position: relative;
            overflow: hidden;
        }

        .profile-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30%;
            background: linear-gradient(to top, rgba(255,255,255,0.2), transparent);
        }

        .avatar-wrapper {
            margin-top: -60px;
            padding: 0 1.5rem;
            position: relative;
            z-index: 2;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 600;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.15);
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .avatar:hover img {
            transform: scale(1.1);
        }

        .change-photo-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin: 1rem 0;
        }

        .change-photo-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0,0,0,0.12);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
            background: var(--primary);
            color: white;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .profile-form {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .profile-form:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0,0,0,0.12);
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 108, 59, 0.25);
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            border-radius: 0.75rem;
            border: none;
            margin-bottom: 1.5rem;
            }

        .alert-success {
            background-color: rgba(var(--bs-success-rgb), 0.1);
            color: var(--bs-success);
        }

        .alert-danger {
            background-color: rgba(var(--bs-danger-rgb), 0.1);
            color: var(--bs-danger);
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .profile-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="profile-container">
        <div class="profile-header animate__animated animate__fadeInDown">
            <div class="profile-banner"></div>
            <div class="avatar-wrapper text-center">
                <div class="avatar mx-auto" id="profileAvatar">
                    <?php if (!empty($user['profile_photo'])): ?>
                        <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <h1 class="h3 mt-3 mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h1>
            <button type="button" class="change-photo-btn" id="changePhotoBtn">
                <i class="fas fa-camera"></i> Change Photo
            </button>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card animate__animated animate__fadeInUp animate__delay-1s">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Orders Placed</div>
            </div>
            <div class="stat-card animate__animated animate__fadeInUp animate__delay-2s">
                <div class="stat-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-value">₱<?php echo number_format($stats['total_spent'], 2); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
            <div class="stat-card animate__animated animate__fadeInUp animate__delay-3s">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['last_order'] ? date('M j, Y', strtotime($stats['last_order'])) : 'No orders yet'; ?></div>
                <div class="stat-label">Last Order</div>
            </div>
        </div>

        <div class="profile-form animate__animated animate__fadeInUp animate__delay-4s">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo nl2br(htmlspecialchars($error)); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="profile.php">
                <div class="form-floating">
                    <input type="text" class="form-control <?php echo empty($user['full_name']) ? 'is-invalid' : ''; ?>" 
                           id="full_name" name="full_name" 
                           placeholder="Full Name" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    <label for="full_name">Full Name <span class="text-danger">*</span></label>
                    <?php if (empty($user['full_name'])): ?>
                        <div class="invalid-feedback">Full name is required for placing orders</div>
                    <?php endif; ?>
                </div>
                <div class="form-floating">
                    <input type="email" class="form-control <?php echo empty($user['email']) ? 'is-invalid' : ''; ?>" 
                           id="email" name="email" 
                           placeholder="Email Address" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <label for="email">Email Address <span class="text-danger">*</span></label>
                    <?php if (empty($user['email'])): ?>
                        <div class="invalid-feedback">Email address is required for placing orders</div>
                    <?php endif; ?>
                </div>
                <div class="form-floating">
                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    <label for="phone">Phone Number</label>
                </div>
                <div class="form-floating">
                    <textarea class="form-control" id="address" name="address" placeholder="Delivery Address" style="height: 100px" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    <label for="address">Delivery Address</label>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary w-100 mt-3">
                    Update Profile
                </button>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Avatar hover effect
        const avatar = document.getElementById('profileAvatar');
        avatar.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });

        avatar.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });

        // Form input animation
        const formControls = document.querySelectorAll('.form-control:not([readonly])');
        formControls.forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.form-floating').style.transform = 'translateX(5px)';
                this.closest('.form-floating').style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.closest('.form-floating').style.transform = 'translateX(0)';
            });
        });

        // Photo upload functionality
        const changePhotoBtn = document.getElementById('changePhotoBtn');
        changePhotoBtn.addEventListener('click', function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/jpeg,image/png,image/gif';
            input.style.display = 'none';
            document.body.appendChild(input);
            
            input.click();
            
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size too large. Maximum size is 5MB.');
                        input.remove();
                        return;
                    }
                    
                    if (!['image/jpeg', 'image/png', 'image/gif'].includes(file.type)) {
                        alert('Invalid file type. Only JPG, PNG & GIF files are allowed.');
                        input.remove();
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('profile_photo', file);
                    
                    // Show loading state
                    avatar.innerHTML = '<div class="loading-spinner"></div>';
                    avatar.style.backgroundColor = 'rgba(0, 108, 59, 0.8)';
                    changePhotoBtn.disabled = true;
                    changePhotoBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...';
                    
                    fetch('profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                avatar.innerHTML = `<img src="${e.target.result}" alt="Profile Photo" class="animate__animated animate__fadeIn">`;
                                avatar.style.backgroundColor = 'transparent';
                                
                                // Show success toast
                                const toast = document.createElement('div');
                                toast.className = 'position-fixed bottom-0 end-0 p-3';
                                toast.style.zIndex = '11';
                                toast.innerHTML = `
                                    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                        <div class="d-flex">
                                            <div class="toast-body">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Profile photo updated successfully!
                                            </div>
                                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                        </div>
                                    </div>
                                `;
                                document.body.appendChild(toast);
                                const bsToast = new bootstrap.Toast(toast.querySelector('.toast'));
                                bsToast.show();
                                
                                setTimeout(() => toast.remove(), 5000);
                            }
                            reader.readAsDataURL(file);
                        } else {
                            throw new Error(data.message || 'Failed to upload photo');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        avatar.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                        avatar.style.backgroundColor = '#dc3545';
                        
                        // Show error toast
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 p-3';
                        toast.style.zIndex = '11';
                        toast.innerHTML = `
                            <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        ${error.message || 'Failed to upload photo'}
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        const bsToast = new bootstrap.Toast(toast.querySelector('.toast'));
                        bsToast.show();
                        
                        setTimeout(() => toast.remove(), 5000);
                    })
                    .finally(() => {
                        changePhotoBtn.disabled = false;
                        changePhotoBtn.innerHTML = '<i class="fas fa-camera"></i> Change Photo';
                        input.remove();
                    });
                }
            });
        });

        // Add animation to stats on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.stat-card').forEach(card => {
            observer.observe(card);
        });
    });
    </script>
</body>
</html> 
