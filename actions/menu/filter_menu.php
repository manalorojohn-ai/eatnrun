<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json');

$category = isset($_GET['category']) ? trim($_GET['category']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

$menu_items = [];
$escaped_category = $category ? $conn->real_escape_string($category) : null;
$escaped_search = $search ? $conn->real_escape_string($search) : null;

$where_clause = "WHERE m.status = 'available'";

if ($escaped_category && $escaped_category !== 'all') {
    $where_clause .= " AND c.name = '" . $escaped_category . "'";
}

if ($escaped_search) {
    $where_clause .= " AND (m.name LIKE '%" . $escaped_search . "%' OR m.description LIKE '%" . $escaped_search . "%')";
}

$query = "SELECT m.*, c.name as category_name,
          COALESCE(m.image_path, 'assets/images/default-food.jpg') as image_path
          FROM menu_items m 
          LEFT JOIN categories c ON m.category_id = c.id 
          $where_clause 
          ORDER BY m.name";

try {
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $menu_items[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'items' => $menu_items,
        'count' => count($menu_items)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching menu items',
        'error' => $e->getMessage()
    ]);
}
?>
