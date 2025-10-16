<?php
session_start();
require_once "../config/database.php";

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] <= 0) {
    $_SESSION['error'] = "Invalid product ID";
    header("Location: products.php");
    exit;
}

$product_id = (int)$_GET['id'];
$success_message = '';
$error_message = '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get categories for dropdown
    $cat_query = "SELECT id, name FROM categories ORDER BY name";
    $cat_stmt = $db->prepare($cat_query);
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get product details
    $query = "SELECT * FROM products WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $_SESSION['error'] = "Product not found";
        header("Location: products.php");
        exit;
    }
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize and validate inputs
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $category_id = (int)$_POST['category_id'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Validate required fields
        if (empty($name) || empty($description) || $price <= 0 || $category_id <= 0) {
            $error_message = "Please fill in all required fields with valid values";
        } else {
            // Handle image upload
            $image_path = $product['image']; // Keep existing image by default
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                $file_name = $_FILES['image']['name'];
                $file_size = $_FILES['image']['size'];
                $file_tmp = $_FILES['image']['tmp_name'];
                
                // Get file extension and validate
                $tmp = explode('.', $file_name);
                $file_ext = strtolower(end($tmp));
                
                // Check file extension
                if (!in_array($file_ext, $allowed_ext)) {
                    $error_message = "Invalid file type. Allowed types: jpg, jpeg, png, gif";
                } 
                else {
                    // Find category name for directory
                    $category_name = '';
                    foreach ($categories as $cat) {
                        if ($cat['id'] == $category_id) {
                            $category_name = $cat['name'];
                            break;
                        }
                    }
                    
                    // Create safe directory name
                    $safe_category_name = preg_replace('/[^a-z0-9]/', '-', strtolower($category_name));
                    $upload_dir = "../images/products/" . $safe_category_name;
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate safe filename
                    $safe_product_name = preg_replace('/[^a-z0-9]/', '-', strtolower($name));
                    $new_file_name = $safe_product_name . '-' . time() . '.' . $file_ext;
                    $upload_path = $upload_dir . '/' . $new_file_name;
                    $image_db_path = "images/products/" . $safe_category_name . '/' . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $image_path = $image_db_path;
                    } else {
                        $error_message = "Failed to upload image";
                    }
                }
            }
            
            if (empty($error_message)) {
                // Update product in database
                $update_query = "UPDATE products SET 
                                name = :name, 
                                description = :description,
                                price = :price,
                                category_id = :category_id,
                                image = :image,
                                is_available = :is_available,
                                updated_at = NOW()
                                WHERE id = :id";
                                
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':name', $name);
                $update_stmt->bindParam(':description', $description);
                $update_stmt->bindParam(':price', $price);
                $update_stmt->bindParam(':category_id', $category_id);
                $update_stmt->bindParam(':image', $image_path);
                $update_stmt->bindParam(':is_available', $is_available);
                $update_stmt->bindParam(':id', $product_id);
                
                if ($update_stmt->execute()) {
                    $success_message = "Product updated successfully";
                    // Refetch product data to display updated info
                    $stmt->execute();
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Failed to update product";
                }
            }
        }
    }
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f8f8f8;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            color: #006C3B;
            font-size: 24px;
            font-weight: 600;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #006C3B;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #00512c;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 5px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        
        .mt-2 {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Replace with inline header instead of include -->
    <header style="background-color: #006C3B; color: white; padding: 15px 0;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1 style="font-size: 20px; margin: 0;">Admin Dashboard</h1>
                <nav>
                    <a href="index.php" style="color: white; margin-right: 15px; text-decoration: none;">Dashboard</a>
                    <a href="products.php" style="color: white; margin-right: 15px; text-decoration: none;">Products</a>
                    <a href="categories.php" style="color: white; margin-right: 15px; text-decoration: none;">Categories</a>
                    <a href="orders.php" style="color: white; margin-right: 15px; text-decoration: none;">Orders</a>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Edit Product</h1>
            <a href="products.php" class="btn btn-secondary">Back to Products</a>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name" class="form-label">Product Name*</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required maxlength="255">
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description*</label>
                    <textarea id="description" name="description" class="form-control" required maxlength="2000"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price" class="form-label">Price (₱)*</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id" class="form-label">Category*</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image" class="form-label">Product Image</label>
                    <?php if (!empty($product['image'])): ?>
                        <div>
                            <p>Current image:</p>
                            <img src="../<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="preview-image">
                        </div>
                        <p class="mt-2">Upload a new image to replace the current one:</p>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif">
                    <small>Supported formats: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="is_available" name="is_available" class="form-check-input" <?php echo ($product['is_available'] == 1) ? 'checked' : ''; ?>>
                        <label for="is_available" class="form-check-label">Available for Order</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Update Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Replace with inline footer instead of include -->
    <footer style="background-color: #f1f1f1; padding: 20px 0; margin-top: 40px; text-align: center; color: #666;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Online Food Ordering System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>