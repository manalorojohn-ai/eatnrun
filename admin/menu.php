<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle AJAX requests for menu items CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        
        // Delete associated image first
        $image_query = "SELECT image_path FROM menu_items WHERE id = ?";
        $stmt = mysqli_prepare($conn, $image_query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            if ($row['image_path'] && file_exists('../' . $row['image_path'])) {
                unlink('../' . $row['image_path']);
            }
        }
        
        $query = "DELETE FROM menu_items WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: menu_items.php?success=Item deleted successfully");
        } else {
            header("Location: menu_items.php?error=Error deleting item");
        }
        exit();
    }

    if (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = floatval($_POST['price']);
        $category_id = intval($_POST['category_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        
        // Handle image upload if new image is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../uploads/menu/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                // Delete old image if exists
                $old_image_query = "SELECT image_path FROM menu_items WHERE id = ?";
                $stmt = mysqli_prepare($conn, $old_image_query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    if ($row['image_path'] && file_exists('../' . $row['image_path'])) {
                        unlink('../' . $row['image_path']);
                    }
                }
                
                $image_path = 'uploads/menu/' . $file_name;
                $query = "UPDATE menu_items SET name = ?, description = ?, price = ?, category_id = ?, image_path = ?, status = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssdissi", $name, $description, $price, $category_id, $image_path, $status, $id);
            }
        } else {
            $query = "UPDATE menu_items SET name = ?, description = ?, price = ?, category_id = ?, status = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssdisi", $name, $description, $price, $category_id, $status, $id);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            header("Location: menu_items.php?success=Item updated successfully");
        } else {
            header("Location: menu_items.php?error=Error updating item");
        }
        exit();
    }
}

// Get categories for the form
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Get menu items with category names
$query = "SELECT m.*, c.name as category_name,
          COALESCE(m.image_path, 'assets/images/default-food.jpg') as image_path
          FROM menu_items m 
          JOIN categories c ON m.category_id = c.id 
          ORDER BY m.name";
$result = mysqli_query($conn, $query);

// Get item for editing if edit_id is provided
$edit_item = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_query = "SELECT m.*, c.name as category_name 
                   FROM menu_items m 
                   JOIN categories c ON m.category_id = c.id 
                   WHERE m.id = ?";
    $stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $edit_result = mysqli_stmt_get_result($stmt);
    $edit_item = mysqli_fetch_assoc($edit_result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Menu Items - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --danger: #dc3545;
            --danger-dark: #c82333;
            --success: #28a745;
            --warning: #ffc107;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-hover: 0 4px 8px rgba(0,0,0,0.15);
            --transition: all 0.3s ease;
            --border-radius: 12px;
            --border-radius-sm: 8px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: var(--light);
            color: var(--dark);
        }

        .main-content {
            margin-left: 180px;
            padding: 2rem;
            transition: var(--transition);
        }

        .menu-items-list {
            width: 100%;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            margin-top: 1.5rem;
            animation: fadeIn 0.5s ease;
            border: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .menu-items-list:hover {
            box-shadow: var(--shadow-hover);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .menu-items-list table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .menu-items-list th {
            text-align: left;
            padding: 1.2rem 1rem;
            background: rgba(0,108,59,0.05);
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid rgba(0,108,59,0.1);
            position: sticky;
            top: 0;
            z-index: 10;
            transition: var(--transition);
        }

        .menu-items-list td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
            transition: var(--transition);
        }

        .menu-items-list tr {
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .menu-items-list tr:last-child td {
            border-bottom: none;
        }

        .menu-items-list tr:hover {
            background: rgba(0,108,59,0.04);
            transform: translateY(-2px);
        }

        .menu-item-name-cell {
            font-weight: 500;
            color: var(--dark);
        }

        .price-cell {
            font-weight: 600;
            color: var(--primary);
            text-align: right;
        }

        .menu-item-category {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            background: rgba(0, 108, 59, 0.1);
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--primary);
            transition: var(--transition);
            white-space: nowrap;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
            text-align: center;
            min-width: 100px;
        }

        .status-available {
            background: rgba(40, 167, 69, 0.15);
            color: #155724;
        }

        .status-unavailable {
            background: rgba(220, 53, 69, 0.15);
            color: #721c24;
        }

        .compact-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn-edit {
            background: var(--primary);
            color: var(--white);
        }

        .btn-edit:hover {
            background: var(--primary-dark);
            box-shadow: 0 4px 8px rgba(0,108,59,0.2);
            transform: translateY(-2px);
        }

        .btn-delete {
            background: var(--danger);
            color: var(--white);
        }

        .btn-delete:hover {
            background: var(--danger-dark);
            box-shadow: 0 4px 8px rgba(220,53,69,0.2);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 0.9rem;
            font-size: 0.85rem;
        }

        .add-new-btn {
            background: var(--primary);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,108,59,0.2);
            border-radius: var(--border-radius-sm);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .add-new-btn:hover {
            background: var(--primary-dark);
            box-shadow: 0 4px 8px rgba(0,108,59,0.3);
            transform: translateY(-2px);
        }

        .add-new-btn i {
            transition: transform 0.3s ease;
        }

        .add-new-btn:hover i {
            transform: rotate(90deg);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .pagination-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--white);
            color: var(--dark);
            font-weight: 500;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .pagination-btn:hover, .pagination-btn.active {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .pagination-text {
            margin: 0 1rem;
            color: var(--dark);
        }

        /* Search and filter section styles */
        .tools-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-box {
            position: relative;
            min-width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: var(--border-radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,108,59,0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            transition: var(--transition);
        }

        .search-input:focus + .search-icon {
            color: var(--primary);
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: var(--border-radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
            min-width: 180px;
            background-color: var(--white);
            transition: var(--transition);
            box-shadow: var(--shadow);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,108,59,0.1);
        }

        /* Edit Form Styles */
        .edit-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            max-width: 600px;
            margin: 0 auto 2rem;
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,108,59,0.1);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: #155724;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: #721c24;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1.5rem;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1.25rem;
        }

        /* Image Preview */
        .image-preview {
            width: 100%;
            height: 200px;
            border-radius: 8px;
            border: 2px dashed #ddd;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            background-color: #f9f9f9;
            transition: var(--transition);
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-preview-placeholder {
            color: #888;
            font-size: 0.9rem;
            text-align: center;
        }

        .image-preview-placeholder i {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
            color: #ccc;
        }

        /* Custom file input */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-button {
            display: block;
            padding: 0.75rem;
            background: var(--light);
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .file-input-button:hover {
            background: #e2e6ea;
        }

        .file-input-button i {
            margin-right: 0.5rem;
        }

        .file-input {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
            cursor: pointer;
        }

        .file-input-text {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #888;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'admin-navbar.php'; ?>

    <div class="main-content">
        <h1>Manage Menu Items</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if ($edit_item): ?>
            <div class="edit-form">
                <h2>Edit Menu Item</h2>
                <form action="menu_items.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_item['id']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_item['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($edit_item['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" name="price" class="form-control" step="0.01" value="<?php echo $edit_item['price']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control" required>
                            <?php 
                            // Reset the result pointer
                            mysqli_data_seek($categories_result, 0);
                            while ($category = mysqli_fetch_assoc($categories_result)): 
                            ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $edit_item['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="available" <?php echo $edit_item['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?php echo $edit_item['status'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Image</label>
                        <div class="file-input-wrapper">
                            <label class="file-input-button">
                                <i class="fas fa-upload"></i> Choose Image
                                <input type="file" name="image" class="file-input" accept="image/*" id="imageInput">
                            </label>
                            <div class="file-input-text">Accepted formats: JPG, JPEG, PNG, GIF, WEBP</div>
                        </div>
                        <div class="image-preview" id="imagePreview">
                            <?php if ($edit_item['image_path']): ?>
                                <img src="../<?php echo $edit_item['image_path']; ?>" alt="Current Image">
                            <?php else: ?>
                                <div class="image-preview-placeholder">
                                    <i class="fas fa-image"></i>
                                    <span>No current image</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-edit">
                            <i class="fas fa-save"></i> Update Item
                        </button>
                        <a href="menu_items.php" class="btn btn-delete">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="tools-section">
                <div class="search-box">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search menu items...">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <div class="filters">
                    <select id="categoryFilter" class="filter-select">
                        <option value="">All Categories</option>
                        <?php 
                        // Reset the result pointer
                        mysqli_data_seek($categories_result, 0);
                        while ($category = mysqli_fetch_assoc($categories_result)): 
                        ?>
                            <option value="<?php echo htmlspecialchars($category['name']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <a href="add_menu_item.php" class="add-new-btn">
                    <i class="fas fa-plus"></i> Add New Item
                </a>
            </div>

            <div class="menu-items-list">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="menuItemsTableBody">
                        <?php while ($item = mysqli_fetch_assoc($result)): ?>
                            <tr class="menu-item-row" data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>" data-category="<?php echo strtolower(htmlspecialchars($item['category_name'])); ?>">
                                <td class="menu-item-name-cell"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><span class="menu-item-category"><?php echo htmlspecialchars($item['category_name']); ?></span></td>
                                <td><?php echo htmlspecialchars(substr($item['description'], 0, 70) . (strlen($item['description']) > 70 ? '...' : '')); ?></td>
                                <td class="price-cell">₱<?php echo number_format($item['price'], 2); ?></td>
                                <td style="text-align: center;">
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="compact-buttons">
                                        <a href="menu_items.php?edit_id=<?php echo $item['id']; ?>" class="btn btn-edit btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form action="menu_items.php" method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-delete btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <script>
                // Search functionality
                const searchInput = document.getElementById('searchInput');
                const categoryFilter = document.getElementById('categoryFilter');
                const tableRows = document.querySelectorAll('.menu-item-row');
                
                function filterTable() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const categoryTerm = categoryFilter.value.toLowerCase();
                    
                    tableRows.forEach(row => {
                        const name = row.getAttribute('data-name');
                        const category = row.getAttribute('data-category');
                        const matchesSearch = name.includes(searchTerm);
                        const matchesCategory = !categoryTerm || category === categoryTerm;
                        
                        if (matchesSearch && matchesCategory) {
                            row.style.display = '';
                            // Add a slight delay for staggered animation
                            setTimeout(() => {
                                row.style.opacity = '1';
                                row.style.transform = 'translateX(0)';
                            }, Array.from(tableRows).indexOf(row) * 50);
                        } else {
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(-20px)';
                            setTimeout(() => {
                                row.style.display = 'none';
                            }, 300);
                        }
                    });
                }
                
                // Add event listeners
                searchInput.addEventListener('input', filterTable);
                categoryFilter.addEventListener('change', filterTable);
                
                // Initialize with a nice fade-in animation
                document.addEventListener('DOMContentLoaded', () => {
                    tableRows.forEach((row, index) => {
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-20px)';
                        
                        setTimeout(() => {
                            row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                            row.style.opacity = '1';
                            row.style.transform = 'translateX(0)';
                        }, 100 + index * 50);
                    });
                });
            </script>
        <?php endif; ?>
    </div>

    <?php if ($edit_item): ?>
    <script>
        // Image preview functionality for edit form
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    imagePreview.innerHTML = `<img src="${this.result}" alt="Preview">`;
                });
                
                reader.readAsDataURL(file);
            } else {
                <?php if ($edit_item['image_path']): ?>
                    imagePreview.innerHTML = `<img src="../<?php echo $edit_item['image_path']; ?>" alt="Current Image">`;
                <?php else: ?>
                    imagePreview.innerHTML = `
                        <div class="image-preview-placeholder">
                            <i class="fas fa-image"></i>
                            <span>No image selected</span>
                        </div>
                    `;
                <?php endif; ?>
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
 