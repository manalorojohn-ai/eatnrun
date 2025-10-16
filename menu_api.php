<?php
require_once 'config/db.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Auto-detect the server's IP address
function getServerIP() {
    // Try multiple methods to get the server IP
    if (isset($_SERVER['SERVER_ADDR'])) {
        return $_SERVER['SERVER_ADDR'];
    }

    if (isset($_SERVER['LOCAL_ADDR'])) {
        return $_SERVER['LOCAL_ADDR'];
    }

    // Fallback: get IP from the request
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
        // Remove port number if present
        $host = preg_replace('/:\d+$/', '', $host);
        return $host;
    }

    // Last resort: use a default
    return '192.168.0.105';
}

$server_ip = getServerIP();
$base_url = "http://{$server_ip}/online-food-ordering/";

$query = "SELECT m.*, c.name as category_name 
          FROM menu_items m 
          LEFT JOIN categories c ON m.category_id = c.id 
          WHERE m.status = 'available' 
          ORDER BY m.category_id, m.name";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$menu_items = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Build full image URL using auto-detected IP
    $image_path = $base_url . ltrim($row['image_path'], '/');

    $menu_items[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'description' => $row['description'] ?? '',
        'price' => (float)($row['price'] ?? 0),
        'category' => $row['category_name'] ?? 'Uncategorized',
        'image' => $image_path
    ];
}

echo json_encode($menu_items);
exit;
?>