<?php
require_once 'config/db.php';

$base_url = 'http://192.168.0.104/online-food-ordering/'; // Change to your actual base URL

$menu_items = [];
$query = "SELECT m.*, c.name as category_name
          FROM menu_items m
          LEFT JOIN categories c ON m.category_id = c.id
          WHERE m.status = 'available'
          ORDER BY m.category_id, m.name";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // If image_path is not already a full URL, prepend the base URL
        $image_path = $row['image_path'] ?? '';
        if ($image_path && !preg_match('/^https?:\/\//', $image_path)) {
            $image_path = $base_url . ltrim($image_path, '/');
        }
        $menu_items[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'category' => $row['category_name'],
            'image' => $image_path
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($menu_items);
exit;
?>