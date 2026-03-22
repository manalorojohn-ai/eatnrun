<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Sanitize user ID
$user_id = mysqli_real_escape_string($conn, $user_id);

// Get page number and limit from query parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20; // Number of notifications per page
$offset = ($page - 1) * $limit;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$total_result = mysqli_stmt_get_result($stmt);
$total_notifications = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_notifications / $limit);

// Get notifications with pagination
$query = "SELECT n.*, 
          CASE 
              WHEN TIMESTAMPDIFF(SECOND, n.created_at, NOW()) < 60 THEN 'Just now'
              WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, n.created_at, NOW()), ' minutes ago')
              WHEN TIMESTAMPDIFF(HOUR, n.created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, n.created_at, NOW()), ' hours ago')
              WHEN TIMESTAMPDIFF(DAY, n.created_at, NOW()) < 7 THEN CONCAT(TIMESTAMPDIFF(DAY, n.created_at, NOW()), ' days ago')
              ELSE DATE_FORMAT(n.created_at, '%b %d, %Y at %h:%i %p')
          END AS time_ago
          FROM notifications n
          WHERE n.user_id = ?
          ORDER BY n.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
}

// Mark notifications as read in batches
$update_query = "UPDATE notifications SET is_read = 1 
                WHERE user_id = ? AND is_read = 0 
                AND id IN (SELECT id FROM (
                    SELECT id FROM notifications 
                    WHERE user_id = ? AND is_read = 0 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?
                ) tmp)";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "iiii", $user_id, $user_id, $limit, $offset);
mysqli_stmt_execute($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications - Eat&Run</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .navbar-brand {
            color: #006C3B;
            font-weight: bold;
        }
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }
        .card {
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: none;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eaeaea;
            padding: 15px 20px;
        }
        .notification-item {
            cursor: pointer;
            transition: background-color 0.2s;
            padding: 15px 20px;
            border-bottom: 1px solid #eaeaea;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item.unread {
            background-color: #f0f7ff;
        }
        .notification-item.unread:hover {
            background-color: #e6f2ff;
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(0, 108, 59, 0.1);
            color: #006C3B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .notification-content {
            flex: 1;
        }
        .notification-message {
            font-weight: 500;
            margin-bottom: 4px;
        }
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .btn-back {
            margin-right: 10px;
        }
        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }
        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
        .pagination-container {
            margin-top: 20px;
            margin-bottom: 40px;
        }
        .pagination .page-link {
            color: #006C3B;
            border-color: #e5e7eb;
            padding: 8px 16px;
        }
        .pagination .page-item.active .page-link {
            background-color: #006C3B;
            border-color: #006C3B;
            color: white;
        }
        .pagination .page-link:hover {
            background-color: rgba(0, 108, 59, 0.1);
            border-color: #006C3B;
            color: #005731;
        }
        .notification-item {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="Eat&Run Logo">
                Eat&Run
            </a>
            <div class="ml-auto">
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h1>Your Notifications</h1>
                <p class="text-muted">View all your latest updates and alerts</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Notifications</h5>
                <?php if (count($notifications) > 0): ?>
                <button id="clearAllBtn" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash"></i> Clear All
                </button>
                <?php endif; ?>
            </div>

            <?php if (count($notifications) > 0): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>">
                            <div class="d-flex align-items-center">
                                <div class="notification-icon">
                                    <?php 
                                    $icon = 'bell';
                                    if (isset($notification['type'])) {
                                        switch ($notification['type']) {
                                            case 'order':
                                                $icon = 'shopping-bag';
                                                break;
                                            case 'payment':
                                                $icon = 'credit-card';
                                                break;
                                            case 'delivery':
                                                $icon = 'truck';
                                                break;
                                            default:
                                                $icon = 'bell';
                                        }
                                    }
                                    ?>
                                    <i class="fas fa-<?php echo $icon; ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                    <div class="notification-time"><?php echo $notification['time_ago']; ?></div>
                                </div>
                                <?php if (!empty($notification['link'])): ?>
                                <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="ml-auto btn btn-sm btn-outline-primary">View</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h4>No Notifications</h4>
                    <p>You don't have any notifications yet.</p>
                    <a href="index.php" class="btn btn-primary">Back to Home</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add pagination controls -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <nav aria-label="Notification pagination">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle clearing all notifications
        const clearAllBtn = document.getElementById('clearAllBtn');
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete all notifications?')) {
                    fetch('clear_all_notifications.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Failed to clear notifications. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                }
            });
        }
        
        // Handle clicking on notification items
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Don't handle clicks on the View button
                if (e.target.classList.contains('btn') || e.target.closest('.btn')) {
                    return;
                }
                
                const id = this.getAttribute('data-id');
                const link = this.querySelector('a')?.getAttribute('href');
                
                if (link) {
                    window.location.href = link;
                }
            });
        });

        // Add smooth scrolling when clicking pagination links
        const paginationLinks = document.querySelectorAll('.pagination .page-link');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                // Smooth scroll to top before loading new page
                window.scrollTo({ top: 0, behavior: 'smooth' });
                // Wait for scroll to complete before changing page
                setTimeout(() => {
                    window.location.href = href;
                }, 300);
            });
        });
    });
    </script>
</body>
</html> 