<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in and has verified email
if (!isset($_SESSION['user_id']) || !isset($_SESSION['email_verified'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if user has already submitted documents
$check_query = "SELECT document_status FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($user['document_status'] === 'approved') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate file uploads
    $photo_1x1 = $_FILES['photo_1x1'] ?? null;
    $photo_2x2 = $_FILES['photo_2x2'] ?? null;
    $valid_id_image = $_FILES['valid_id_image'] ?? null;
    $valid_id_type = $_POST['valid_id_type'] ?? '';
    
    // Validate required fields
    if (!$photo_1x1 || $photo_1x1['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a 1x1 photo';
    } elseif (!$photo_2x2 || $photo_2x2['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a 2x2 photo';
    } elseif (!$valid_id_image || $valid_id_image['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a valid ID image';
    } elseif (empty($valid_id_type)) {
        $error = 'Please select a valid ID type';
    } else {
        // Validate file types and sizes
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $files_to_check = [
            '1x1 photo' => $photo_1x1,
            '2x2 photo' => $photo_2x2,
            'Valid ID' => $valid_id_image
        ];
        
        foreach ($files_to_check as $file_name => $file) {
            if (!in_array($file['type'], $allowed_types)) {
                $error = "$file_name must be a JPEG or PNG image";
                break;
            }
            if ($file['size'] > $max_size) {
                $error = "$file_name must be smaller than 5MB";
                break;
            }
        }
        
        if (empty($error)) {
            // Create upload directories if they don't exist
            $upload_dirs = [
                'uploads/documents/1x1_photos',
                'uploads/documents/2x2_photos',
                'uploads/documents/valid_ids'
            ];
            
            foreach ($upload_dirs as $dir) {
                if (!file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
            
            // Generate unique filenames
            $photo_1x1_name = '1x1_' . $user_id . '_' . time() . '.' . pathinfo($photo_1x1['name'], PATHINFO_EXTENSION);
            $photo_2x2_name = '2x2_' . $user_id . '_' . time() . '.' . pathinfo($photo_2x2['name'], PATHINFO_EXTENSION);
            $valid_id_name = 'id_' . $user_id . '_' . time() . '.' . pathinfo($valid_id_image['name'], PATHINFO_EXTENSION);
            
            // Move uploaded files
            $upload_paths = [
                'uploads/documents/1x1_photos/' . $photo_1x1_name,
                'uploads/documents/2x2_photos/' . $photo_2x2_name,
                'uploads/documents/valid_ids/' . $valid_id_name
            ];
            
            $upload_success = true;
            $moved_files = [];
            
            if (move_uploaded_file($photo_1x1['tmp_name'], $upload_paths[0])) {
                $moved_files[] = $upload_paths[0];
            } else {
                $upload_success = false;
            }
            
            if ($upload_success && move_uploaded_file($photo_2x2['tmp_name'], $upload_paths[1])) {
                $moved_files[] = $upload_paths[1];
            } else {
                $upload_success = false;
            }
            
            if ($upload_success && move_uploaded_file($valid_id_image['tmp_name'], $upload_paths[2])) {
                $moved_files[] = $upload_paths[2];
            } else {
                $upload_success = false;
            }
            
            if ($upload_success) {
                // Update database
                mysqli_begin_transaction($conn);
                
                try {
                    $update_query = "UPDATE users SET 
                        photo_1x1 = ?, 
                        photo_2x2 = ?, 
                        valid_id_type = ?, 
                        valid_id_image = ?, 
                        document_status = 'pending',
                        documents_submitted_at = NOW()
                        WHERE id = ?";
                    
                    $stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($stmt, "ssssi", 
                        $photo_1x1_name, 
                        $photo_2x2_name, 
                        $valid_id_type, 
                        $valid_id_name, 
                        $user_id
                    );
                    
                    if (mysqli_stmt_execute($stmt)) {
                        // Create notification for admin
                        $notif_query = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'document_submission')";
                        $stmt = mysqli_prepare($conn, $notif_query);
                        $message = "New document submission requires review";
                        mysqli_stmt_bind_param($stmt, "is", $user_id, $message);
                        mysqli_stmt_execute($stmt);
                        
                        mysqli_commit($conn);
                        $success = "Documents submitted successfully! Please wait for admin approval.";
                        
                        // Clear session and redirect to login
                        unset($_SESSION['email_verified']);
                        header("refresh:3;url=login.php");
                    } else {
                        throw new Exception("Failed to update database");
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    
                    // Clean up uploaded files
                    foreach ($moved_files as $file) {
                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }
                    
                    $error = "Failed to save documents. Please try again.";
                }
            } else {
                // Clean up any partially uploaded files
                foreach ($moved_files as $file) {
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
                $error = "Failed to upload files. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Documents - Eat&Run</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-dark: #005530;
            --primary-light: #00A65A;
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --card-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.08);
            --input-shadow: 0 0 0 0.25rem rgba(0, 108, 59, 0.15);
        }

        body {
            min-height: 100vh;
            background: var(--bg-gradient);
            font-family: 'Poppins', sans-serif;
            color: #1f2937;
        }

        .document-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .document-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 1.25rem;
            box-shadow: var(--card-shadow);
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .document-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .document-header h1 {
            color: var(--primary-color);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .document-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .upload-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 2px dashed #e9ecef;
            border-radius: 1rem;
            transition: all 0.3s ease;
        }

        .upload-section:hover {
            border-color: var(--primary-color);
            background: rgba(0, 108, 59, 0.02);
        }

        .upload-section h3 {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            border: 2px dashed #dee2e6;
            border-radius: 0.75rem;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 150px;
        }

        .file-input-label:hover {
            border-color: var(--primary-color);
            background: rgba(0, 108, 59, 0.05);
        }

        .file-input-label i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .file-input-label span {
            font-size: 1rem;
            color: #666;
            text-align: center;
        }

        .file-preview {
            margin-top: 1rem;
            text-align: center;
        }

        .file-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: var(--input-shadow);
            outline: 0;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 0.75rem;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 108, 59, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.925rem;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            animation: slideInDown 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            border-left: 4px solid;
        }

        .alert-danger {
            border-left-color: #dc3545;
            background: linear-gradient(to right, rgba(220, 53, 69, 0.05), rgba(220, 53, 69, 0.02));
        }

        .alert-success {
            border-left-color: var(--primary-color);
            background: linear-gradient(to right, rgba(0, 108, 59, 0.05), rgba(0, 108, 59, 0.02));
        }

        .requirements {
            background: rgba(0, 108, 59, 0.05);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .requirements h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .requirements ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .requirements li {
            margin-bottom: 0.5rem;
            color: #666;
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes slideInDown {
            0% {
                opacity: 0;
                transform: translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .document-container {
                margin: 1rem;
                padding: 0;
            }

            .document-card {
                padding: 1.5rem;
            }

            .upload-section {
                padding: 1rem;
            }

            .file-input-label {
                padding: 1.5rem;
                min-height: 120px;
            }

            .file-input-label i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="document-container">
        <div class="document-card">
            <div class="document-header">
                <h1><i class="fas fa-id-card"></i> Submit Required Documents</h1>
                <p>Please upload the following documents to complete your account verification</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>

            <div class="requirements">
                <h4><i class="fas fa-info-circle"></i> Document Requirements</h4>
                <ul>
                    <li>All images must be in JPEG or PNG format</li>
                    <li>Maximum file size: 5MB per image</li>
                    <li>Images must be clear and readable</li>
                    <li>Valid ID must be government-issued or student ID</li>
                </ul>
            </div>

            <form method="POST" enctype="multipart/form-data" id="documentForm">
                <!-- 1x1 Photo Upload -->
                <div class="upload-section">
                    <h3><i class="fas fa-camera"></i> 1x1 Photo</h3>
                    <div class="file-input-wrapper">
                        <input type="file" name="photo_1x1" id="photo_1x1" class="file-input" accept="image/*" required>
                        <label for="photo_1x1" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Click to upload 1x1 photo<br><small>JPEG or PNG format</small></span>
                        </label>
                    </div>
                    <div class="file-preview" id="preview_1x1"></div>
                </div>

                <!-- 2x2 Photo Upload -->
                <div class="upload-section">
                    <h3><i class="fas fa-camera"></i> 2x2 Photo</h3>
                    <div class="file-input-wrapper">
                        <input type="file" name="photo_2x2" id="photo_2x2" class="file-input" accept="image/*" required>
                        <label for="photo_2x2" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Click to upload 2x2 photo<br><small>JPEG or PNG format</small></span>
                        </label>
                    </div>
                    <div class="file-preview" id="preview_2x2"></div>
                </div>

                <!-- Valid ID Upload -->
                <div class="upload-section">
                    <h3><i class="fas fa-id-card"></i> Valid ID</h3>
                    
                    <div class="mb-3">
                        <label for="valid_id_type" class="form-label">ID Type</label>
                        <select name="valid_id_type" id="valid_id_type" class="form-select" required>
                            <option value="">Select ID Type</option>
                            <option value="driver_license">Driver's License</option>
                            <option value="passport">Passport</option>
                            <option value="student_id">Student ID</option>
                            <option value="national_id">National ID</option>
                            <option value="other">Other Government ID</option>
                        </select>
                    </div>

                    <div class="file-input-wrapper">
                        <input type="file" name="valid_id_image" id="valid_id_image" class="file-input" accept="image/*" required>
                        <label for="valid_id_image" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Click to upload valid ID image<br><small>JPEG or PNG format</small></span>
                        </label>
                    </div>
                    <div class="file-preview" id="preview_valid_id"></div>
                </div>

                <button type="submit" class="btn btn-submit" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Submit Documents
                </button>
            </form>
        </div>
    </div>

    <script>
        // File preview functionality
        function setupFilePreview(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="img-fluid">`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = '';
                }
            });
        }

        // Setup previews for all file inputs
        setupFilePreview('photo_1x1', 'preview_1x1');
        setupFilePreview('photo_2x2', 'preview_2x2');
        setupFilePreview('valid_id_image', 'preview_valid_id');

        // Form validation
        document.getElementById('documentForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        });

        // File size validation
        function validateFileSize(file, maxSize = 5 * 1024 * 1024) {
            if (file.size > maxSize) {
                alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                return false;
            }
            return true;
        }

        // Add file size validation to all file inputs
        ['photo_1x1', 'photo_2x2', 'valid_id_image'].forEach(inputId => {
            document.getElementById(inputId).addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && !validateFileSize(file)) {
                    e.target.value = '';
                }
            });
        });
    </script>
</body>
</html>
