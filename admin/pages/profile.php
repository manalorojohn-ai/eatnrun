<?php
// Include configuration first
require_once '../includes/config.php';

// Then include session handler
require_once '../includes/session.php';

// Finally include database connection
require_once '../config/db.php';

// Start the session
start_session_once();

// Initialize messages
$success_message = '';
$error_message = '';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get user data first to ensure we have it
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ? AND role = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Redirect if not admin
if (!$user) {
    header('Location: ../login.php');
    exit();
}

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/profile_photos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        // Validate file
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception("Invalid file type. Please upload JPG, JPEG, PNG, or GIF files only.");
        } elseif ($file['size'] > $max_file_size) {
            throw new Exception("File is too large. Maximum size is 5MB.");
        }

        // Generate unique filename
        $new_filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        // Delete old profile photo if exists
        if (!empty($user['profile_image'])) {
            $old_photo = $upload_dir . $user['profile_image'];
            if (file_exists($old_photo)) {
                unlink($old_photo);
            }
        }

        // Upload new file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception("Error uploading profile photo.");
        }

        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $new_filename, $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            // If database update fails, delete the uploaded file
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
            throw new Exception("Error updating profile photo in database.");
        }

        $stmt->close();
        $success_message = "Profile photo updated successfully!";
        $user['profile_image'] = $new_filename; // Update user data
    }

    // Handle profile photo removal
    if (isset($_POST['remove_photo']) && !empty($user['profile_image'])) {
        $photo_path = $upload_dir . $user['profile_image'];
        
        if (file_exists($photo_path)) {
            if (!unlink($photo_path)) {
                throw new Exception("Error removing profile photo file.");
            }
        }

        $stmt = $conn->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error removing profile photo from database.");
        }
        
        $stmt->close();
        $user['profile_image'] = null;
        $success_message = "Profile photo removed successfully!";
    }

    // Handle form submission for profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['remove_photo']) && !isset($_FILES['profile_photo'])) {
        if (isset($_POST['full_name'], $_POST['email'])) {
            $full_name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address format.");
            }

            if (!empty($current_password) && !empty($new_password)) {
                // Handle password change
                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords do not match.");
                }
                
                if (!password_verify($current_password, $user['password'])) {
                    throw new Exception("Current password is incorrect.");
                }

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssi", $full_name, $email, $hashed_password, $user_id);
            } else {
                // Just update name and email
                $update_query = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssi", $full_name, $email, $user_id);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error updating profile: " . $conn->error);
            }

            $stmt->close();
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $refresh_stmt = $conn->prepare($sql);
            $refresh_stmt->bind_param("i", $user_id);
            $refresh_stmt->execute();
            $refresh_result = $refresh_stmt->get_result();
            $user = $refresh_result->fetch_assoc();
            $refresh_stmt->close();
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Food Ordering</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: rgba(0, 108, 59, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .main-content {
            margin-left: 240px;
            padding: 2rem;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .profile-banner {
            background: var(--primary);
            border-radius: 20px;
            padding: 3rem 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .profile-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .profile-content {
            text-align: center;
            color: white;
            position: relative;
            z-index: 1;
        }

        .profile-image-wrapper {
            width: 180px;
            height: 180px;
            margin: 0 auto 2rem;
            position: relative;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .profile-image:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            animation: fadeUp 0.7s ease-out;
        }

        .profile-email {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
            animation: fadeUp 0.8s ease-out;
        }

        @keyframes fadeUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 30px;
            font-size: 1rem;
            animation: fadeUp 0.9s ease-out;
            transition: all 0.3s ease;
        }

        .profile-role:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .section-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            animation: slideUp 0.6s ease-out;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-light);
        }

        .form-floating > .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            height: 60px;
            padding: 1rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-floating > .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }

        .form-floating > .form-control:disabled,
        .form-floating > .form-control[readonly] {
            background-color: #f8f9fa;
            border-color: #e2e8f0;
            cursor: not-allowed;
        }

        .form-floating > label {
            padding: 1rem;
            color: #64748b;
        }

        .form-group {
            margin-bottom: 1.5rem;
            animation: fadeIn 0.6s ease-out;
        }

        .save-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
            animation: fadeIn 0.7s ease-out;
        }

        .save-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 108, 59, 0.2);
        }

        .save-button:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .profile-name {
                font-size: 2rem;
        }

            .profile-image-wrapper {
                width: 150px;
                height: 150px;
        }
        }
    </style>
</head>
<body>
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
        <?php if (!empty($success_message)): ?>
                <div class="alert alert-success d-flex align-items-center fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger d-flex align-items-center fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

            <div class="profile-banner">
                <div class="profile-content">
                    <div class="profile-image-wrapper">
                        <div class="profile-image" data-bs-toggle="modal" data-bs-target="#imagePreviewModal">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="../uploads/profile_photos/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Photo">
                        <?php else: ?>
                            <img src="../assets/images/default-avatar.png" alt="Default Profile Photo">
                        <?php endif; ?>
                    </div>
                    </div>
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <div class="profile-email">
                        <i class="fas fa-envelope me-2"></i>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                    <div class="profile-role">
                        <i class="fas fa-user-shield"></i>
                        Administrator
                    </div>
                </div>
            </div>

            <form id="profilePhotoForm" method="POST" action="" enctype="multipart/form-data" style="display: none;">
                <input type="file" id="profile_photo" name="profile_photo" accept="image/*" onchange="submitProfilePhoto()">
            </form>
            
            <form class="profile-form" method="POST" action="">
                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-user"></i>
                        Personal Information
                    </div>
                    <div class="form-group">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            <label for="full_name">Full Name</label>
                        </div>
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            <label for="email">Email Address (Cannot be changed)</label>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title">
                        <i class="fas fa-lock"></i>
                        Change Password
                    </div>
                        <div class="form-group">
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        <label for="current_password">Current Password</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        <label for="new_password">New Password</label>
                        </div>
                        <div class="form-floating">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        <label for="confirm_password">Confirm New Password</label>
                        </div>
                        </div>
                    </div>

                <button type="submit" class="save-button">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
                </form>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close position-absolute top-0 end-0 p-3 bg-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    <img src="" alt="Preview" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Profile photo preview and upload
        const profilePhotoInput = document.getElementById('profile_photo');
        const profilePhotoForm = document.getElementById('profilePhotoForm');
        const profileImage = document.querySelector('.profile-image img');
        const previewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        const modalImage = document.querySelector('#imagePreviewModal img');

        // Update modal image when opening
        document.getElementById('imagePreviewModal').addEventListener('show.bs.modal', function () {
            modalImage.src = profileImage.src;
        });

        function submitProfilePhoto() {
            const file = profilePhotoInput.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('danger', 'File is too large. Maximum size is 5MB.');
                    return;
                }

                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showAlert('danger', 'Invalid file type. Please upload JPG, JPEG, PNG, or GIF files only.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImage.src = e.target.result;
                }
                reader.readAsDataURL(file);
                
                profilePhotoForm.submit();
            }
        }

        // Form validation
        document.querySelector('.profile-form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;

            if (newPassword || confirmPassword || currentPassword) {
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    showAlert('danger', 'New passwords do not match!');
                } else if (!currentPassword) {
                    e.preventDefault();
                    showAlert('danger', 'Please enter your current password to change password!');
                }
            }
    });

        // Custom alert function
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show d-flex align-items-center`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'danger' ? 'exclamation' : 'check'}-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.profile-banner'));
            
            setTimeout(() => {
                bootstrap.Alert.getOrCreateInstance(alertDiv).close();
            }, 5000);
        }
    </script>
</body>
</html>
