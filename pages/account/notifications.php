<?php
session_start();
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);

// Get unread notifications count first
    $count_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = mysqli_fetch_assoc($result)['count'];

// Get notifications
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = [
        'id' => $row['id'],
        'message' => $row['message'],
        'type' => $row['type'],
        'is_read' => $row['is_read'],
        'created_at' => $row['created_at']
    ];
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        $update_query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        header("Location: notifications.php");
        exit();
    }
    
    if (isset($_POST['notification_id'])) {
        $notification_id = mysqli_real_escape_string($conn, $_POST['notification_id']);
        $update_query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
        mysqli_stmt_execute($stmt);
        header("Location: notifications.php");
        exit();
    }
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($notifications);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Eat&Run</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #006C3B;
            --primary-dark: #005530;
            --primary-light: #e8f5e9;
            --text-dark: #333;
            --text-light: #666;
            --background: #f8f9fa;
            --white: #fff;
            --danger: #dc3545;
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--background);
            min-height: 100vh;
            padding-top: 76px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .page-container {
            flex: 1;
            width: 100%;
            max-width: 1200px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .notifications-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .notifications-header {
            background: var(--white);
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-title h1 {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin: 0;
            font-weight: 600;
        }

        .unread-badge {
            background: var(--primary);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .notifications-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notification-item {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.25rem;
            align-items: start;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .notification-item.unread {
            background: var(--primary-light);
            border-left: 4px solid var(--primary);
        }

        .notification-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            box-shadow: 0 2px 6px rgba(0,108,59,0.2);
        }

        .notification-content {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .notification-message {
            color: var(--text-dark);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .notification-time {
            color: var(--text-light);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .mark-read-btn {
            background: transparent;
            border: none;
            color: var(--primary);
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            transition: all 0.2s ease;
            justify-self: end;
            white-space: nowrap;
        }

        .notification-item:hover .mark-read-btn {
            opacity: 1;
            background: var(--primary-light);
        }

        .mark-all-read-btn {
            background: var(--primary-light);
            color: var(--primary);
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .mark-all-read-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-light);
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 1rem;
            }

            .notifications-header {
                padding: 1.25rem;
                flex-direction: column;
                gap: 1rem;
            }

            .header-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .mark-all-read-btn {
                width: 100%;
                justify-content: center;
            }

            .notification-item {
                grid-template-columns: auto 1fr;
                padding: 1.25rem;
                gap: 1rem;
            }

            .notification-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .mark-read-btn {
                grid-column: 1 / -1;
                opacity: 1;
                justify-content: center;
                background: var(--primary-light);
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="page-container">
        <div class="notifications-container">
            <div class="notifications-header">
                <div class="header-title">
                    <h1>Notifications</h1>
                    <?php if ($count > 0): ?>
                        <span class="unread-badge"><?php echo $count; ?> unread</span>
                    <?php endif; ?>
                </div>
                <?php if ($count > 0): ?>
                    <form method="POST">
                        <button type="submit" name="mark_all_read" class="mark-all-read-btn">
                            <i class="fas fa-check-double"></i>
                            Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="notifications-grid">
                <?php if (empty($notifications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $index => $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                             style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <div class="notification-icon">
                                <i class="fas <?php echo getNotificationIcon($notification['type']); ?>"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </div>
                                <div class="notification-time">
                                    <?php echo getTimeAgo($notification['created_at']); ?>
                                </div>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" name="mark_read" class="mark-read-btn">
                                        <i class="fas fa-check"></i>
                                        Mark as read
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Add animation when marking as read
        document.querySelectorAll('.mark-read-btn').forEach(button => {
            button.addEventListener('click', function() {
                const notificationItem = this.closest('.notification-item');
                notificationItem.style.opacity = '0.5';
                notificationItem.style.transform = 'translateX(10px)';
            });
        });

        // Animate new notifications
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.notification-item').forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
<?php
function getTimeAgo($datetime) {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

function getNotificationIcon($type) {
    switch ($type) {
        case 'order':
            return 'fa-shopping-bag';
        case 'payment':
            return 'fa-credit-card';
        case 'system':
            return 'fa-bell';
        default:
            return 'fa-bell';
    }
}
?> 
