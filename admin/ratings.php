<?php
require_once '../config/db.php';
require_once 'includes/auth.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get admin user details
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT full_name, profile_image FROM users WHERE id = ? AND role = 'admin'";
$stmt = mysqli_prepare($conn, $admin_query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$admin_result = mysqli_stmt_get_result($stmt);
$admin_data = mysqli_fetch_assoc($admin_result);
$admin_name = $admin_data['full_name'] ?? 'Administrator';
$admin_profile_image = $admin_data['profile_image'] ?? '';

// Handle rating deletion
if (isset($_POST['delete_rating']) && isset($_POST['rating_id'])) {
    $rating_id = mysqli_real_escape_string($conn, $_POST['rating_id']);
    $source = $_POST['source'] ?? 'local';
    
    if ($source === 'local') {
        $delete_query = "DELETE FROM ratings WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $rating_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
    } elseif ($source === 'hotel') {
        try {
            // Include remote database configuration
            $config_path = __DIR__ . '/api/database_config.php';
            if (file_exists($config_path)) {
                require_once $config_path;
                $hotel_db = new mysqli(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS, REMOTE_DB_NAME);
                if (!$hotel_db->connect_error) {
                    $delete_query = "DELETE FROM eatnrun_rating WHERE id = ?";
                    $delete_stmt = $hotel_db->prepare($delete_query);
                    $delete_stmt->bind_param("i", $rating_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    $hotel_db->close();
                }
            }
        } catch (Exception $e) {
            error_log("Error deleting hotel rating from remote database: " . $e->getMessage());
        }
    }
}

// Initialize arrays for both types of ratings
$local_ratings = [];
$hotel_ratings = [];

// Fetch local ratings from food_ordering database
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'ratings'");
if (mysqli_num_rows($table_check) == 0) {
    // Create ratings table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        menu_item_id INT,
        order_id INT,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
    )";
    mysqli_query($conn, $create_table);
    
    // Insert some sample data
    $sample_data = "INSERT INTO ratings (user_id, menu_item_id, order_id, rating, comment) VALUES 
        (1, 1, 1, 5, 'Excellent food!'),
        (1, 2, 1, 4, 'Very good service'),
        (2, 1, 2, 5, 'Amazing taste')";
    mysqli_query($conn, $sample_data);
}

// Add pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20; // Show 20 ratings per page
$offset = ($page - 1) * $limit;

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM ratings";
$count_result = mysqli_query($conn, $count_query);
$total_ratings = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_ratings / $limit);

// Now fetch the ratings with pagination and optimized query
$query = "SELECT 
            r.id,
            r.rating,
            r.comment,
            r.created_at,
            COALESCE(u.username, u.full_name, 'Guest') as customer,
            COALESCE(m.name, 'Unknown Item') as menu_item,
            r.order_id,
            'local' as source
          FROM ratings r 
          LEFT JOIN users u ON r.user_id = u.id 
          LEFT JOIN menu_items m ON r.menu_item_id = m.id 
          ORDER BY r.created_at DESC
          LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $local_ratings[] = $row;
    }
    mysqli_free_result($result);
}

// Fetch hotel ratings from remote hotel_management database (lazy loaded)
$hotel_ratings = [];
$hotel_ratings_count = 0;

// Only fetch hotel ratings if specifically requested or on first load
if (isset($_GET['load_hotel']) || $page == 1) {
    // Include remote database configuration
    $config_path = __DIR__ . '/api/database_config.php';
    if (file_exists($config_path)) {
        require_once $config_path;
        
        try {
            $hotel_db = mysqli_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS, REMOTE_DB_NAME);
            
            if ($hotel_db) {
                mysqli_set_charset($hotel_db, "utf8mb4");
                
                // Get count first
                $count_query = "SELECT COUNT(*) as total FROM eatnrun_rating";
                $count_result = mysqli_query($hotel_db, $count_query);
                $hotel_ratings_count = mysqli_fetch_assoc($count_result)['total'];
                
                // Only fetch if there are ratings and we're on first page
                if ($hotel_ratings_count > 0 && $page == 1) {
                    $hotel_query = "SELECT 
                        id,
                        rating,
                        comment,
                        created_at,
                        updated_at,
                        order_id
                        FROM eatnrun_rating 
                        ORDER BY created_at DESC
                        LIMIT 10"; // Limit hotel ratings to 10 for performance
                        
                    $hotel_result = mysqli_query($hotel_db, $hotel_query);
                    
                    if ($hotel_result) {
                        while ($row = mysqli_fetch_assoc($hotel_result)) {
                            $hotel_ratings[] = [
                                'id' => $row['id'],
                                'customer' => 'Hotel Guest',
                                'menu_item' => 'Hotel Order',
                                'rating' => $row['rating'],
                                'comment' => $row['comment'],
                                'created_at' => $row['created_at'],
                                'order_id' => $row['order_id'],
                                'source' => 'hotel'
                            ];
                        }
                        mysqli_free_result($hotel_result);
                    }
                }
                mysqli_close($hotel_db);
            }
        } catch (Exception $e) {
            error_log("Error connecting to remote hotel database at " . REMOTE_DB_HOST . ": " . $e->getMessage());
        }
    } else {
        error_log("Database configuration file not found: " . $config_path);
    }
}

// Make sure hotel_ratings is initialized if database connection failed
if (!isset($hotel_ratings)) {
    $hotel_ratings = [];
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ratings - Eat&Run Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="css/admin-style.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 240px;
            padding: 20px;
        }
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            row-gap: 12px;
        }
        .profile-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table {
            margin-bottom: 20px;
        }
        .table th {
            color: #6c757d;
            font-weight: 500;
            border-bottom: 1px solid #dee2e6;
            padding: 12px;
        }
        .table td {
            padding: 12px;
            vertical-align: middle;
        }
        .rating-stars {
            color: #ffc107;
        }
        .source-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            text-align: center;
            min-width: 60px;
        }
        .source-local {
            background-color: #007bff;
            color: white;
        }
        .source-hotel {
            background-color: #28a745;
            color: white;
        }
        .btn-delete {
            background-color: #dc3545;
            border: none;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .search-box {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px 12px;
        }
        .entries-select {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px;
            margin-right: 8px;
        }
        .pagination {
            margin-bottom: 0;
        }
        .pagination .page-link {
            color: #007bff;
            border: 1px solid #dee2e6;
            padding: 6px 12px;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
        }

        /* Enhanced notification dropdown design */
        .notification-dropdown {
            min-width: 320px;
            max-width: 400px;
            border: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            border-radius: 12px;
            padding: 0;
            overflow: hidden;
        }

        .notification-header {
            background: linear-gradient(to right, rgba(0, 108, 59, 0.05), rgba(0, 108, 59, 0.02));
            color: #2c3e50;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .notification-header-title {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }

        .notification-header-title .fw-semibold {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-header-title .fw-semibold {
            color: #2c3e50;
            font-size: 0.95rem;
            font-weight: 600 !important;
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
            padding: 0;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
            cursor: pointer;
        }

        .notification-item:hover {
            background-color: rgba(0, 108, 59, 0.05);
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item.unread {
            background-color: rgba(0, 108, 59, 0.05);
            border-left: 3px solid #006C3B;
        }

        .notification-icon {
            width: 36px;
            height: 36px;
            background: rgba(0, 108, 59, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .notification-icon i {
            color: #006C3B;
            font-size: 16px;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-message {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            line-height: 1.4;
            margin-bottom: 4px;
        }

        .notification-time {
            font-size: 12px;
            color: #6c757d;
            font-weight: 400;
        }

        .no-notifications {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-notifications i {
            font-size: 48px;
            color: #dee2e6;
            margin-bottom: 12px;
            display: block;
        }

        .no-notifications p {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
        }

        .notification-footer {
            background: rgba(0, 108, 59, 0.02);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 12px 20px;
        }

        .notification-footer a {
            color: #006C3B;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: color 0.2s ease;
        }

        .notification-footer a:hover {
            color: #005530;
        }

        .notification-footer a i {
            font-size: 12px;
            transition: transform 0.2s ease;
        }

        .notification-footer a:hover i {
            transform: translateX(4px);
        }

        /* Custom scrollbar for notification list */
        .notification-list::-webkit-scrollbar {
            width: 4px;
        }

        .notification-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .notification-list::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }

        .notification-list::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Critical CSS for faster rendering */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .notification-bell {
            position: relative;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0, 108, 59, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        /* Responsive adjustments for burger/Android modes */
        @media (max-width: 576px) {
            .main-content {
                margin-left: 0;
                padding: 12px;
            }
            .page-header {
                padding: 12px;
            }
            .header-content {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            .profile-section {
                width: 100%;
                justify-content: space-between;
                gap: 10px;
            }
            .profile-section .text-end {
                order: 2;
                flex: 1;
                text-align: right;
            }
            .profile-image {
                width: 36px;
                height: 36px;
            }

            /* Ensure notification dropdown fits viewport on Android/small screens */
            .notification-dropdown.dropdown-menu {
                position: fixed !important;
                inset: auto !important;
                top: 60px !important;
                right: 8px !important;
                left: 8px !important;
                transform: none !important;
                width: auto !important;
                max-width: none !important;
                min-width: 0 !important;
                max-height: 60vh;
                overflow-y: auto;
                z-index: 2000;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div class="header-content">
                <div>
                    <h2>Manage Ratings</h2>
                    <p class="text-muted">View and manage customer ratings</p>
                </div>
                <div class="profile-section p-3 d-flex align-items-center gap-3">
                    <!-- Lazy load notification dropdown -->
                    <div id="notification-container" data-lazy-load="includes/notification_dropdown.php">
                        <div class="notification-bell">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" style="display: none;">0</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div class="text-success">Administrator</div>
                        <div class="text-muted small">
                            <i class="fas fa-clock"></i>
                            Last updated: <?php echo date('h:i A'); ?>
                        </div>
                    </div>
                    <?php if (!empty($admin_profile_image)): ?>
                        <img src="../uploads/profile_photos/<?php echo htmlspecialchars($admin_profile_image); ?>" alt="Profile" class="profile-image">
                    <?php else: ?>
                        <img src="../assets/images/admin-avatar.png" alt="Profile" class="profile-image">
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="table-container">
            <ul class="nav nav-tabs" id="ratingsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="database-tab" data-bs-toggle="tab" data-bs-target="#database" type="button" role="tab" aria-controls="database" aria-selected="true">
                        Database Ratings
                        <span class="badge bg-primary ms-2"><?php echo count($local_ratings); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="hotel-tab" data-bs-toggle="tab" data-bs-target="#hotel" type="button" role="tab" aria-controls="hotel" aria-selected="false">
                        Hotel Ratings
                        <span class="badge bg-success ms-2"><?php echo $hotel_ratings_count; ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="api-tab" data-bs-toggle="tab" data-bs-target="#api" type="button" role="tab" aria-controls="api" aria-selected="false">API Ratings</button>
                </li>
            </ul>
            <div class="tab-content" id="ratingsTabContent">
                <!-- Database Ratings Tab -->
                <div class="tab-pane fade show active" id="database" role="tabpanel" aria-labelledby="database-tab">
                    <div class="table-responsive mt-3">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Menu Item</th>
                                    <th>Order ID</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Date</th>
                                    <th>Source</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($local_ratings)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No ratings found in the database.
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($local_ratings as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['customer']); ?></td>
                                        <td><?php echo htmlspecialchars($row['menu_item']); ?></td>
                                        <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                                        <td>
                                            <div class="text-warning">
                                                <?php 
                                                $rating = intval($row['rating']);
                                                echo str_repeat('<i class="fas fa-star"></i>', $rating) . 
                                                     str_repeat('<i class="far fa-star"></i>', 5 - $rating);
                                                ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                                        <td>
                                            <?php 
                                            $date = new DateTime($row['created_at']);
                                            echo $date->format('M d, Y h:i A');
                                            ?>
                                        <td>
                                            <span class="source-badge source-local">Local</span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="rating_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="source" value="local">
                                                <button type="submit" name="delete_rating" class="btn-delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Ratings pagination" class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Hotel Ratings Tab -->
                <div class="tab-pane fade" id="hotel" role="tabpanel" aria-labelledby="hotel-tab">
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Menu Item</th>
                                    <th>Order ID</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Date</th>
                                    <th>Source</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($hotel_ratings)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No ratings found from the API.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($hotel_ratings as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['customer']); ?></td>
                                        <td><?php echo htmlspecialchars($row['menu_item']); ?></td>
                                        <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                                        <td class="rating-stars">
                                            <?php 
                                            $rating = intval($row['rating']);
                                            echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                                        <td>
                                            <?php 
                                            $date = new DateTime($row['created_at']);
                                            echo $date->format('M d, Y h:i A');
                                            ?>
                                        </td>
                                        <td>
                                            <span class="source-badge source-hotel">Hotel</span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="rating_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="source" value="hotel">
                                                <button type="submit" name="delete_rating" class="btn-delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- API Ratings Tab -->
                <div class="tab-pane fade" id="api" role="tabpanel" aria-labelledby="api-tab">
                    <div class="table-responsive mt-3">
                        <div id="apiLoading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading API ratings...</p>
                        </div>
                        <table class="table" id="apiTable" style="display: none;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Menu Item</th>
                                    <th>Order ID</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Date</th>
                                    <th>Source</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="apiTableBody">
                                <!-- API data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="js/notifications.js" defer></script>
    <script>
        // Function to fetch API ratings
        function fetchApiRatings() {
            fetch('api/ratings_api.php')
                .then(response => response.json())
                .then(data => {
                    const loadingDiv = document.getElementById('apiLoading');
                    const table = document.getElementById('apiTable');
                    const tbody = document.getElementById('apiTableBody');
                    
                    loadingDiv.style.display = 'none';
                    table.style.display = 'table';
                    
                    if (data.success && data.ratings && data.ratings.length > 0) {
                        tbody.innerHTML = '';
                        data.ratings.forEach(rating => {
                            const row = document.createElement('tr');
                            const stars = '★'.repeat(rating.rating) + '☆'.repeat(5 - rating.rating);
                            const date = new Date(rating.created_at).toLocaleString();
                            
                            row.innerHTML = `
                                <td>${rating.id}</td>
                                <td>${rating.customer || 'Guest'}</td>
                                <td>${rating.menu_item || 'N/A'}</td>
                                <td>${rating.order_id || 'N/A'}</td>
                                <td class="rating-stars">${stars}</td>
                                <td>${rating.comment || ''}</td>
                                <td>${date}</td>
                                <td><span class="source-badge source-${rating.source}">${rating.source}</span></td>
                                <td>-</td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No ratings found from API.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching API ratings:', error);
                    document.getElementById('apiLoading').innerHTML = '<p class="text-danger">Error loading API ratings.</p>';
                });
        }

        // Lazy load notification dropdown
        function lazyLoadNotifications() {
            const container = document.getElementById('notification-container');
            if (container && container.dataset.lazyLoad) {
                fetch(container.dataset.lazyLoad)
                    .then(response => response.text())
                    .then(html => {
                        container.innerHTML = html;
                        // Re-initialize notification manager after lazy loading
                        if (typeof NotificationManager !== 'undefined') {
                            NotificationManager.init();
                        }
                    })
                    .catch(error => console.error('Error loading notifications:', error));
            }
        }

        // Load API ratings when the API tab is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const apiTab = document.getElementById('api-tab');
            apiTab.addEventListener('click', fetchApiRatings);
            
            // Lazy load notifications after a short delay
            setTimeout(lazyLoadNotifications, 1000);
        });
    </script>
</body>
</html>