<?php
session_start();
require_once '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';
$product = null;

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate product ID
if ($product_id <= 0) {
    $error = "Invalid product ID";
} else {
    try {
        // Debug: Log the query
        $query = "
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ";
        error_log("Executing query: " . $query);
        
        // Prepare and execute the query
        $stmt = $conn->prepare($query);
        $stmt->execute([$product_id]);
        
        // Debug: Log the result
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Query result: " . print_r($product, true));

        if (!$product) {
            $error = "Product not found.";
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "Error loading product data. Please try again.";
    }
}

// Fetch categories regardless of product load status
try {
    $stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($categories)) {
        $error = "No categories available. Please add categories first.";
    }
} catch(PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $error = "Error loading categories. Please try again.";
}

// Create uploads directory if it doesn't exist
$upload_path = '../uploads/products/';
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0777, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Validation
    if (empty($name)) {
        $error = "Product name is required";
    } elseif ($price <= 0) {
        $error = "Price must be greater than 0";
    } elseif ($category_id <= 0) {
        $error = "Please select a category";
    }

    if (empty($error)) {
        try {
            $conn->beginTransaction();

            // Update product details
            $stmt = $conn->prepare("
                UPDATE products 
                SET name = ?, 
                    description = ?, 
                    price = ?, 
                    category_id = ?, 
                    is_available = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $description,
                $price,
                $category_id,
                $is_available,
                $product_id
            ]);

            // Handle image upload if provided
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $filename = $_FILES['product_image']['name'];
                $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($filetype, $allowed)) {
                    $new_filename = 'product_' . $product_id . '_' . time() . '.' . $filetype;
                    $filepath = $upload_path . $new_filename;

                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $filepath)) {
                        // Delete old image if exists
                        if (!empty($product['image'])) {
                            $old_file = $upload_path . $product['image'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }

                        // Update image in database
                        $stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
                        $stmt->execute([$new_filename, $product_id]);
                        $product['image'] = $new_filename;
                    }
                }
            }

            $conn->commit();
            $success = "Product updated successfully";

            // Refresh product data
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
            $conn->rollBack();
            error_log("Error updating product: " . $e->getMessage());
            $error = "Failed to update product. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: #e8f5e9;
            --secondary: #4CAF50;
            --danger: #dc3545;
            --warning: #ffc107;
            --text-primary: #2D3748;
            --text-secondary: #718096;
            --border-color: #E2E8F0;
            --background: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .content-wrapper {
            padding: 2rem;
            margin-left: 280px;
            background: var(--background);
            min-height: 100vh;
        }

        .edit-product-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        h2 {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,108,59,0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-save:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .product-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 2px solid var(--border-color);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            animation: fadeIn 0.3s ease-out;
        }

        .alert-success {
            background: var(--primary-light);
            color: var(--primary-dark);
            border: 1px solid var(--primary);
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert i {
            font-size: 1.25rem;
        }

        input[type="file"].form-control {
            padding: 0.5rem;
            cursor: pointer;
        }

        input[type="file"].form-control::file-selector-button {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            background: var(--primary);
            color: white;
            margin-right: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        input[type="file"].form-control::file-selector-button:hover {
            background: var(--primary-dark);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23718096' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
                margin-left: 0;
            }

            .edit-product-container {
                padding: 1.5rem;
            }

            .btn-save {
                width: 100%;
                justify-content: center;
            }
        }

        .alert-link {
            color: inherit;
            text-decoration: underline;
            font-weight: 500;
        }
        
        .alert-link:hover {
            text-decoration: none;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .page-header i {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .page-header h2 {
            margin: 0;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .alert i {
            margin-top: 0.125rem;
        }

        .alert-link {
            color: inherit;
            text-decoration: underline;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .alert-link:hover {
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin-nav.php'; ?>

    <div class="content-wrapper">
        <div class="edit-product-container">
            <div class="page-header">
                <i class="fas fa-edit"></i>
                <h2>Edit Product</h2>
            </div>

            <?php if (!$product): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <?php echo htmlspecialchars($error); ?>
                        <a href="products.php" class="alert-link">Return to products list</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div><?php echo htmlspecialchars($success); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" id="price" name="price" class="form-control" 
                               value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>" 
                               step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Current Image</label>
                        <?php if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                            <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="Product Image" class="product-image">
                        <?php else: ?>
                            <p>No image available</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="product_image">Update Image</label>
                        <input type="file" id="product_image" name="product_image" class="form-control" 
                               accept="image/jpeg,image/png,image/webp">
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_available" name="is_available" 
                               <?php echo ($product['is_available'] ?? 0) == 1 ? 'checked' : ''; ?>>
                        <label for="is_available">Available for Order</label>
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Add loading state to form submission
    document.querySelector('form')?.addEventListener('submit', function(e) {
        const button = this.querySelector('.btn-save');
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        button.style.opacity = '0.7';
        button.style.cursor = 'wait';
    });
    </script>
</body>
</html> 