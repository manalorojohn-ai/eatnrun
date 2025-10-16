<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Simple file-based cache
$cache_file = __DIR__ . '/../../cache/ratings_cache.json';
$cache_duration = 300; // 5 minutes

// Create cache directory if it doesn't exist
$cache_dir = dirname($cache_file);
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

// Check if cache exists and is still valid
$use_cache = false;
if (file_exists($cache_file)) {
    $cache_time = filemtime($cache_file);
    if (time() - $cache_time < $cache_duration) {
        $use_cache = true;
    }
}

if ($use_cache) {
    // Return cached data
    header('Content-Type: application/json');
    echo file_get_contents($cache_file);
    exit();
}

// Generate new data
try {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM ratings";
    $count_result = mysqli_query($conn, $count_query);
    $total_ratings = mysqli_fetch_assoc($count_result)['total'];
    $total_pages = ceil($total_ratings / $limit);

    // Fetch ratings with pagination
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

    $ratings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ratings[] = $row;
    }

    $data = [
        'success' => true,
        'ratings' => $ratings,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_ratings' => $total_ratings,
            'per_page' => $limit
        ],
        'cached_at' => time()
    ];

    // Cache the data
    file_put_contents($cache_file, json_encode($data));

    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
