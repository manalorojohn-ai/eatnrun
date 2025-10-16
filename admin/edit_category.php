<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$error = null;
$success = null;

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$category_id) {
    header("Location: manage_categories.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch existing category data
try {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        header("Location: manage_categories.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Error fetching category: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $status = $_POST['status'];
        
        // Validation
        if (empty($name)) {
            throw new Exception("Category name is required");
        }

        // Handle image upload
        $image_path = $category['image']; // Keep existing image by default
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $target_dir = "../uploads/categories/";
            $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;

            // Check file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed");
            }

            // Move uploaded file
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = "uploads/categories/" . $new_filename;
                
                // Delete old image if exists
                if ($category['image'] && file_exists("../" . $category['image'])) {
                    unlink("../" . $category['image']);
                }
            } else {
                throw new Exception("Failed to upload image");
            }
        }

        // Update category
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, image = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $image_path, $status, $category_id])) {
            $success = "Category updated successfully";
            // Refresh category data
            $category['name'] = $name;
            $category['description'] = $description;
            $category['image'] = $image_path;
            $category['status'] = $status;
        } else {
            throw new Exception("Failed to update category");
        }

    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Category - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            flex: 1;
            padding: 20px;
        }

        .page-title {
            color: #006C3B;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 800px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: #006C3B;
        }

        textarea.form-input {
            min-height: 100px;
            resize: vertical;
        }

        .status-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .save-btn {
            background: #006C3B;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .cancel-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        .current-image {
            margin-top: 10px;
        }

        .current-image img {
            max-width: 200px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; ?>

        <div class="content-area">
            <h1 class="page-title">Edit Category</h1>

            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form class="edit-form" method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" 
                           class="form-input" 
                           name="name" 
                           value="<?php echo htmlspecialchars($category['name']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-input" 
                              name="description"><?php echo htmlspecialchars($category['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Category Image</label>
                    <input type="file" 
                           class="form-input" 
                           name="image" 
                           accept="image/*">
                    
                    <?php if ($category['image']): ?>
                        <div class="current-image">
                            <p>Current image:</p>
                            <img src="../<?php echo htmlspecialchars($category['image']); ?>" 
                                 alt="Current category image">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="status-select" name="status">
                        <option value="active" <?php echo $category['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $category['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" class="save-btn">Save Changes</button>
                    <a href="manage_categories.php" class="cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
