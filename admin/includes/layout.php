<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/db.php';

// Check admin authentication
if (!function_exists('require_admin_auth')) {
    function require_admin_auth() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header('Location: ../login.php');
            exit();
        }
    }
}

require_admin_auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Food' : 'Admin Dashboard - Food'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-light: #e8f5e9;
            --primary-dark: #005530;
            --primary-gradient: linear-gradient(135deg, #006C3B 0%, #005530 100%);
            --secondary-gradient: linear-gradient(135deg, #f8fafc 0%, #e8f5e9 100%);
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.05);
            --card-hover-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.05);
            --success: #10b981;
            --info: #0ea5e9;
            --warning: #f59e0b;
            --sidebar-width: 240px;
            --container-padding: 1.5rem;
            --header-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--gray-50);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
        }

        .dashboard-container {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: var(--container-padding);
            width: calc(100% - var(--sidebar-width));
            max-width: 1600px;
            background-color: var(--gray-50);
            min-height: 100vh;
            overflow-x: hidden;
            transition: margin-left 0.3s ease;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: var(--primary-gradient);
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .header-right {
            margin-left: auto;
        }

        .dashboard-title {
            color: white;
            margin: 0;
            font-size: 1.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dashboard-title i {
            font-size: 1.5rem;
        }

        @media (max-width: 992px) {
            .dashboard-container {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
    <?php echo isset($additional_styles) ? $additional_styles : ''; ?>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="dashboard-title">
                        <?php if (isset($page_icon)): ?>
                            <i class="fas fa-<?php echo $page_icon; ?>"></i>
                        <?php endif; ?>
                        <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
                    </h1>
                </div>
                <div class="header-right">
                    <?php include 'profile_header.php'; ?>
                </div>
            </div>
        </div>

        <?php echo $content ?? ''; ?>
    </div>

    <?php echo isset($additional_scripts) ? $additional_scripts : ''; ?>
</body>
</html> 