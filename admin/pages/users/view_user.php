<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch user details
$user_id = $_GET['id'];
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user not found, redirect back to users page
if (!$user) {
    header("Location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #FDF5E6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        .navbar-brand {
            color: #006400 !important;
            font-weight: bold;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-link {
            color: #333 !important;
            padding: 8px 16px !important;
        }
        .nav-link.active {
            background-color: #006400 !important;
            color: white !important;
            border-radius: 4px;
        }
        .content-wrapper {
            padding: 30px;
            margin-top: 20px;
        }
        .page-title {
            color: #006400;
            margin-bottom: 10px;
        }
        .page-description {
            color: #666;
            margin-bottom: 30px;
        }
        .user-details {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .detail-row {
            margin-bottom: 20px;
        }
        .detail-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .detail-value {
            color: #666;
        }
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: normal;
        }
        .badge-success {
            background-color: #e1f7e1;
            color: #006400;
        }
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .btn-back {
            background-color: #006400;
            color: white;
            padding: 8px 20px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
        }
        .btn-back:hover {
            background-color: #005000;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../assets/images/logo.png" alt="Eat&Run Logo" height="30">
                Eat&Run Admin
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-success text-white" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container content-wrapper">
        <a href="users.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        
        <h1 class="page-title">User Details</h1>
        <p class="page-description">View detailed information about the user</p>

        <div class="user-details">
            <div class="detail-row">
                <div class="detail-label">Email</div>
                <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Phone</div>
                <div class="detail-value"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Role</div>
                <div class="detail-value">
                    <span class="badge badge-success"><?php echo htmlspecialchars($user['role']); ?></span>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <span class="badge badge-warning">Active</span>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Registration Date</div>
                <div class="detail-value">
                    <?php echo isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 