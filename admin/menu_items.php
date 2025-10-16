<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch admin information from users table
$admin_query = "SELECT * FROM users WHERE id = ? AND role = 'admin'";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$admin_result = mysqli_stmt_get_result($stmt);
$admin_data = mysqli_fetch_assoc($admin_result);

// If no data found, set default values
if (!$admin_data) {
    $admin_data = [
        'full_name' => 'Administrator',
        'profile_image' => null
    ];
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
    <title>Menu Items - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            min-height: 100vh;
            position: relative;
        }

        .main-content {
            margin-left: 240px;
            padding: 2rem;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 4rem);
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 0 12px; /* Reduced padding for Android */
                min-height: 100vh;
                width: 100%;
                overflow-x: hidden;
            }

            body {
                overflow-x: hidden;
            }

            .sidebar {
                width: 0;
                transform: translateX(-100%);
                z-index: 1000;
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                width: 280px; /* Android standard drawer width */
                transform: translateX(0);
            }

            .header-container {
                margin: 8px 4px;
                border-radius: 8px;
                padding: 10px 8px;
            }

            .burger-icon {
                width: 40px;
                height: 40px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 8px;
                background: rgba(0, 108, 59, 0.08);
                color: var(--primary);
                cursor: pointer;
            }

            .title-text h1 {
                font-size: 18px;
                margin: 0;
            }
            
            .title-text h2 {
                font-size: 14px;
                margin: 0;
            }

            .profile-section {
                padding: 6px 10px;
                gap: 6px;
                border-radius: 8px;
            }

            .admin-info {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
            }

            .admin-name {
                font-size: 14px;
                font-weight: 600;
            }

            .admin-role {
                font-size: 12px;
            }

            .last-updated {
                font-size: 11px;
            }

            .admin-avatar {
                width: 40px;
                height: 40px;
            }

            /* Menu items list table for mobile */
            .menu-items-list {
                margin: 10px 0;
                border-radius: 8px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .menu-items-list table {
                width: 100%;
                min-width: 100%;
                table-layout: fixed;
            }

            .menu-items-list th,
            .menu-items-list td {
                padding: 8px 6px;
                font-size: 13px;
            }

            /* Adjust column widths for mobile */
            .menu-items-list th:nth-child(1), 
            .menu-items-list td:nth-child(1) {
                width: 25%;
            }
            
            .menu-items-list th:nth-child(2), 
            .menu-items-list td:nth-child(2) {
                width: 20%;
            }

            .menu-items-list th:nth-child(3), 
            .menu-items-list td:nth-child(3) {
                width: 0;
                display: none; /* Hide description on small screens */
            }

            .menu-items-list th:nth-child(4), 
            .menu-items-list td:nth-child(4) {
                width: 15%;
            }

            .menu-items-list th:nth-child(5), 
            .menu-items-list td:nth-child(5) {
                width: 15%;
            }

            .menu-items-list th:nth-child(6), 
            .menu-items-list td:nth-child(6) {
                width: 25%;
            }

            /* Improve buttons for touch */
            .compact-buttons {
                display: flex;
                gap: 4px;
                justify-content: flex-end;
            }

            .btn-sm {
                padding: 6px 8px;
                font-size: 12px;
                min-width: 60px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Tools section optimization */
            .tools-section {
                flex-direction: column;
                gap: 8px;
                margin: 12px 0;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-input {
                height: 40px;
                padding: 8px 8px 8px 36px;
                font-size: 14px;
            }

            .filter-select {
                width: 100%;
                height: 40px;
                font-size: 14px;
                padding: 8px;
            }

            .add-new-btn {
                width: 100%;
                height: 44px;
                padding: 10px;
                justify-content: center;
                font-size: 14px;
            }

            /* Status badge */
            .status-badge {
                padding: 4px 6px;
                font-size: 11px;
                min-width: 70px;
                border-radius: 12px;
            }
            
            .menu-item-category {
                padding: 4px 6px;
                font-size: 11px;
                border-radius: 12px;
            }
        }

        /* Ultra small screen optimizations */
        @media (max-width: 380px) {
            .menu-items-list th:nth-child(5), 
            .menu-items-list td:nth-child(5) {
                width: 0;
                display: none; /* Hide status on ultra small screens */
            }
            
            .menu-items-list th:nth-child(1), 
            .menu-items-list td:nth-child(1) {
                width: 35%;
            }
            
            .menu-items-list th:nth-child(2), 
            .menu-items-list td:nth-child(2) {
                width: 25%;
            }
            
            .menu-items-list th:nth-child(4), 
            .menu-items-list td:nth-child(4) {
                width: 15%;
            }
            
            .menu-items-list th:nth-child(6), 
            .menu-items-list td:nth-child(6) {
                width: 25%;
            }
            
            .btn-sm {
                padding: 6px;
                min-width: auto;
                font-size: 11px;
            }
            
            .btn-sm i {
                margin-right: 0;
            }
            
            .btn-sm span {
                display: none;
            }
        }

        /* Sidebar overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        body.sidebar-open {
            overflow: hidden;
        }

        .tools-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
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
        }

        .filter-select {
            min-width: 180px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: var(--border-radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
        }

        .add-new-btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .add-new-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Status badge responsive styles */
        .status-badge {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
            min-width: 90px;
        }

        @media (max-width: 576px) {
            .status-badge {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
                min-width: 80px;
            }
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
            z-index: 1;
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

        .page-title i {
            color: #27ae60;
            font-size: 1.75rem;
        }

        .header-container {
            margin-bottom: 2rem;
            background: #f5f9f7;
            border-radius: 12px;
            padding: 1rem 1.5rem;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .admin-info-section {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .admin-profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }

        .admin-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #2c3e50;
            margin-right: 10px;
        }

        .profile-role {
            color: #27ae60;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .profile-image {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--white);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            margin-left: 10px;
        }

        .last-updated {
            font-size: 0.7rem;
            color: #7f8c8d;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .last-updated i {
            margin-right: 4px;
        }

        .profile-section {
            position: relative;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            background: linear-gradient(45deg, var(--primary-light), rgba(0, 108, 59, 0.05));
            transition: var(--transition);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-section:hover {
            background: linear-gradient(45deg, rgba(0, 108, 59, 0.15), rgba(0, 108, 59, 0.05));
        }

        .profile-info {
            text-align: right;
        }

        .profile-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .profile-role {
            color: #27ae60;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .profile-image {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
        }

        .profile-section:hover .profile-image {
            transform: scale(1.05);
        }

        .last-updated {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .last-updated i {
            color: #006C3B;
        }

        /* Profile styles for Android */
        .admin-avatar {
            width: 44.8px;
            height: 44.8px;
            border-radius: 50%;
            object-fit: cover;
            background: rgba(0, 108, 59, 0.04);
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            background: rgba(0, 108, 59, 0.04);
            transition: var(--transition);
        }

        .admin-info {
            text-align: right;
            display: flex;
            flex-direction: column;
        }

        .admin-name {
            font-weight: 600;
            font-size: 1rem;
            color: var(--dark);
        }

        .admin-role {
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .last-updated {
            font-size: 0.75rem;
            color: #666;
        }

        /* Burger/mobile optimizations for header fit */
        @media (max-width: 768px) {
            .header-container { margin: 0.75rem 1rem 0.5rem; }
            .header-content { gap: 0.75rem; }
            .profile-section { padding: 0.35rem 0.5rem; gap: 0.4rem; flex-wrap: nowrap; justify-content: flex-end; margin-left: auto; }
            .admin-avatar { width: 36px; height: 36px; }
            .admin-info { max-width: 55%; min-width: 0; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
            .admin-name { font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; }
            .admin-role { font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; }
            .last-updated { font-size: 0.68rem; overflow: hidden; text-overflow: ellipsis; }
            @media (max-width: 420px) { .admin-role { display: none; } }
            @media (max-width: 380px) { .last-updated { display: none; } .admin-info { max-width: 68%; } }
        }
    </style>
</head>
<body>
    
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="header-container">
            <div class="header-content">
                <div class="page-title">
                    <div class="burger-icon" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                    <div class="title-text">
                        <h1>Manage Menu Items</h1>
                        <h2>Overview</h2>
                    </div>
                </div>
                <div class="profile-section">
                    <div class="admin-info">
                        <span class="admin-name">Anton Ramos</span>
                        <span class="admin-role">Administrator</span>
                        <span class="last-updated">Last updated: <?php echo date('h:i A'); ?></span>
                            </div>
                    <img src="../uploads/profile_photos/profile_1_1746279893.jpg" alt="Admin" class="admin-avatar">
                </div>
            </div>
        </div>

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
                <select id="categoryFilter" class="filter-select">
                    <option value="">All Categories</option>
                    <?php 
                    mysqli_data_seek($categories_result, 0);
                    while ($category = mysqli_fetch_assoc($categories_result)): 
                    ?>
                        <option value="<?php echo htmlspecialchars($category['name']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
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
                            <th class="description-column">Description</th>
                            <th>Price</th>
                            <th class="status-column">Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="menuItemsTableBody">
                        <?php while ($item = mysqli_fetch_assoc($result)): ?>
                            <tr class="menu-item-row" data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>" data-category="<?php echo strtolower(htmlspecialchars($item['category_name'])); ?>">
                                <td class="menu-item-name-cell menu-item-name"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><span class="menu-item-category"><?php echo htmlspecialchars($item['category_name']); ?></span></td>
                                <td class="description-column menu-item-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 70) . (strlen($item['description']) > 70 ? '...' : '')); ?></td>
                                <td class="price-cell"><?php echo '₱' . number_format($item['price'], 2); ?></td>
                                <td class="status-column" style="text-align: center;">
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="compact-buttons">
                                        <a href="menu_items.php?edit_id=<?php echo $item['id']; ?>" class="btn btn-edit btn-sm">
                                            <i class="fas fa-edit"></i> <span>Edit</span>
                                        </a>
                                        <form action="menu_items.php" method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-delete btn-sm">
                                                <i class="fas fa-trash"></i> <span>Delete</span>
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
                document.addEventListener('DOMContentLoaded', function() {
                    // Search functionality
                    const searchInput = document.getElementById('searchInput');
                    const categoryFilter = document.getElementById('categoryFilter');
                    const tableRows = document.querySelectorAll('.menu-item-row');
                    
                    function filterTable() {
                        const searchTerm = searchInput.value.toLowerCase();
                        const categoryTerm = categoryFilter.value.toLowerCase();
                        
                        tableRows.forEach(row => {
                            const name = row.getAttribute('data-name');
                            let description = '';
                            const descriptionEl = row.querySelector('.menu-item-desc');
                            if (descriptionEl) {
                                description = descriptionEl.textContent.toLowerCase();
                            }
                            const category = row.getAttribute('data-category');
                            
                            const matchesSearch = name.includes(searchTerm) || description.includes(searchTerm);
                            const matchesCategory = !categoryTerm || category === categoryTerm;
                            
                            if (matchesSearch && matchesCategory) {
                                row.style.display = '';
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
                    
                    searchInput.addEventListener('input', filterTable);
                    categoryFilter.addEventListener('change', filterTable);
                    
                    // Initialize with a nice fade-in animation
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Burger menu toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            
            // Create overlay element if it doesn't exist
            let overlay = document.querySelector('.sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                document.body.appendChild(overlay);
            }

            function toggleSidebar() {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                document.body.classList.toggle('sidebar-open');
                
                // Android-specific: Prevent scroll when sidebar is open
                if (sidebar.classList.contains('show')) {
                    document.body.style.overflow = 'hidden';
                    document.body.style.position = 'fixed';
                    document.body.style.width = '100%';
                } else {
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.width = '';
                }
            }

            if (sidebarToggle) {
                // Use touchstart for better responsiveness on mobile
                sidebarToggle.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
                
                // Also keep click for non-touch devices
                sidebarToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleSidebar();
                });
            }

            // Close sidebar when clicking overlay with better touch response
            overlay.addEventListener('touchstart', function(e) {
                e.preventDefault();
                toggleSidebar();
            });
            
            overlay.addEventListener('click', toggleSidebar);

            // Close sidebar when clicking outside 
            document.addEventListener('click', function(e) { 
                if (!sidebar.contains(e.target) &&  
                    !sidebarToggle.contains(e.target) &&  
                    sidebar.classList.contains('show')) { 
                    toggleSidebar(); 
                } 
            }); 
             
            // Prevent sidebar from closing when clicking inside it 
            sidebar.addEventListener('click', function(e) { 
                e.stopPropagation(); 
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 992) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            });
            
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', filterItems);
            }
            
            // Filter select functionality
            const categoryFilter = document.getElementById('categoryFilter');
            if (categoryFilter) {
                categoryFilter.addEventListener('change', filterItems);
            }
            
            function filterItems() {
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const category = categoryFilter ? categoryFilter.value : '';
                
                const rows = document.querySelectorAll('.menu-items-list tbody tr');
                
                rows.forEach(row => {
                    const itemName = row.querySelector('.menu-item-name').textContent.toLowerCase();
                    const itemDesc = row.querySelector('.menu-item-desc').textContent.toLowerCase();
                    const itemCategory = row.querySelector('.menu-item-category').textContent.toLowerCase();
                    
                    const matchesSearch = itemName.includes(searchTerm) || itemDesc.includes(searchTerm);
                    const matchesCategory = category === '' || itemCategory.includes(category.toLowerCase());
                    
                    if (matchesSearch && matchesCategory) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>
 