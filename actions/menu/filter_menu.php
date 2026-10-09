<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/includes/helpers/init_session.php';
require_once dirname(__DIR__, 2) . '/config/database/db.php';

header('Content-Type: application/json');

$category = isset($_GET['category']) ? trim($_GET['category']) : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

$menu_items = [];
$params = [];
$where_clauses = ["m.status = 'available'"];

if ($category && $category !== 'all') {
    $where_clauses[] = "c.name = ?";
    $params[] = $category;
}

if ($search) {
    $where_clauses[] = "(m.name ILIKE ? OR m.description ILIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = "WHERE " . implode(' AND ', $where_clauses);
$query = "SELECT m.*, c.name as category_name,
          COALESCE(m.image_path, 'assets/images/default-food.jpg') as image_path
          FROM menu_items m 
          LEFT JOIN categories c ON m.category_id = c.id 
          $where_sql 
          ORDER BY m.name";

try {
    if ($conn instanceof PDO) {
        // Adjust ILIKE to LIKE for MySQL if needed
        if (strpos(get_class($conn), 'PDO') !== false && !extension_loaded('pdo_pgsql')) {
            $query = str_replace('ILIKE', 'LIKE', $query);
        }
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($conn instanceof mysqli) {
        $query = str_replace('ILIKE', 'LIKE', $query);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $menu_items[] = $row;
            }
            $stmt->close();
        } else {
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $menu_items[] = $row;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'items' => $menu_items,
        'count' => count($menu_items)
    ]);
} catch (Exception $e) {
    error_log("filter_menu error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching menu items',
        'items' => []
    ]);
}
?>
