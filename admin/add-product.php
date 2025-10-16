<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$error = '';
$success = '';

// Get all categories for the dropdown
try {
    $stmt = $conn->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error loading categories: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category_id = intval($_POST['category_id']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        // Validate inputs
        if (empty($name)) throw new Exception("Product name is required");
        if (empty($description)) throw new Exception("Description is required");
        if ($price <= 0) throw new Exception("Price must be greater than 0");
        if ($category_id <= 0) throw new Exception("Please select a category");

        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['image']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($filetype, $allowed)) {
                throw new Exception('Only JPG, JPEG, PNG & WEBP files are allowed');
            }

            $new_filename = uniqid('product_') . '.' . $filetype;
            $upload_path = '../uploads/products/' . $new_filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                throw new Exception('Failed to upload image');
            }

            $image_path = $new_filename;
        }

        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO menu_items (name, description, price, category_id, image, is_available) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$name, $description, $price, $category_id, $image_path, $is_available]);
        
        $success = "Product added successfully!";
        
        // Clear form data after successful submission
        $_POST = array();
        
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = "Add New Product";
include 'includes/admin-header.php';
?>

<div class="admin-container">
    <?php include 'includes/admin-nav.php'; ?>

    <div class="content-wrapper">
        <div class="page-header">
            
            <div class="header-line"></div>
        </div>

        <div class="form-wrapper">
            <?php if ($error || $success): ?>
                <div class="alert <?php echo $error ? 'alert-error' : 'alert-success'; ?>">
                    <i class="fas fa-<?php echo $error ? 'exclamation' : 'check'; ?>-circle"></i>
                    <?php echo htmlspecialchars($error ?: $success); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <form method="POST" enctype="multipart/form-data" class="product-form">
                    <div class="form-grid">
                        <div class="form-section">
                            <div class="input-group">
                                <label for="name">Product Name</label>
                                <input type="text" id="name" name="name" 
                                       class="form-input"
                                       placeholder="Enter product name"
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>

                            <div class="input-group">
                                <label for="category_id">Category</label>
                                <select id="category_id" name="category_id" class="form-input" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="input-group">
                                <label for="price">Price</label>
                                <div class="price-input">
                                    <span class="currency">₱</span>
                                    <input type="number" id="price" name="price" 
                                           class="form-input"
                                           placeholder="0.00"
                                           value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label>Product Image</label>
                                <div class="file-upload">
                                    <input type="file" id="image" name="image" 
                                           accept="image/jpeg,image/png,image/webp">
                                    <div class="upload-content">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Choose a file or drag it here</p>
                                        <span class="file-types">JPEG, PNG, WEBP</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-section full-width">
                            <div class="input-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" 
                                          class="form-input"
                                          placeholder="Enter product description"
                                          rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="input-group">
                                <label class="toggle-label">
                                    <input type="checkbox" name="is_available" value="1" 
                                           <?php echo (isset($_POST['is_available']) && $_POST['is_available']) ? 'checked' : ''; ?>>
                                    <span class="toggle-text">Available for Order</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Products
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Base Layout */
.content-wrapper {
    padding: 2rem;
    background: #f8fafc;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
}

/* Page Header */
.page-header {
    text-align: center;
    margin-bottom: 2.5rem;
    width: 100%;
    max-width: 800px;
}

.page-header h1 {
    font-size: 2rem;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.75rem;
    position: relative;
    display: inline-block;
}

.header-line {
    width: 60px;
    height: 3px;
    background: linear-gradient(to right, transparent, #006C3B, transparent);
    margin: 0.5rem auto 0;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.page-header:hover .header-line {
    width: 120px;
}

/* Form Wrapper */
.form-wrapper {
    width: 100%;
    max-width: 800px;
    animation: fadeIn 0.3s ease-out;
}

/* Card */
.card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Form Grid */
.form-grid {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.form-section.full-width {
    grid-template-columns: 1fr;
}

/* Input Groups */
.input-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.input-group label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9375rem;
}

.form-input {
    padding: 0.875rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
    background: #f9fafb;
    width: 100%;
}

.form-input:hover {
    border-color: #9ca3af;
}

.form-input:focus {
    border-color: #006C3B;
    box-shadow: 0 0 0 3px rgba(0, 108, 59, 0.1);
    outline: none;
    background: #ffffff;
    transform: translateY(-1px);
}

/* Price Input */
.price-input {
    position: relative;
}

.currency {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #374151;
    font-weight: 500;
}

.price-input input {
    padding-left: 2rem;
}

/* File Upload */
.file-upload {
    position: relative;
    height: 160px;
    border: 2px dashed #e5e7eb;
    border-radius: 10px;
    background: #f9fafb;
    transition: all 0.2s ease;
    cursor: pointer;
}

.file-upload:hover {
    border-color: #006C3B;
    background: rgba(0, 108, 59, 0.02);
}

.upload-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    width: 100%;
    padding: 1rem;
}

.upload-content i {
    font-size: 2rem;
    color: #006C3B;
    margin-bottom: 0.5rem;
}

.file-types {
    display: block;
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

/* Toggle Switch */
.toggle-label {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.toggle-label:hover {
    background: rgba(0, 108, 59, 0.05);
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #e5e7eb;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
    font-size: 0.9375rem;
    transition: all 0.2s ease;
    min-width: 160px;
    justify-content: center;
}

.btn-primary {
    background: linear-gradient(135deg, #006C3B, #00854A);
    color: white;
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 108, 59, 0.2);
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: none;
    text-decoration: none;
}

.btn-secondary:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem;
    }

    .form-section {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .btn {
        width: 100%;
    }

    .card {
        margin: 0 1rem;
        padding: 1.5rem;
    }
}
</style>

<?php include 'includes/admin-footer.php'; ?> 