<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'items' => [], 'total' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$total = 0;

$query = "SELECT c.id as cart_id, c.quantity, c.menu_item_id, m.name, m.price, 
           (m.price * c.quantity) as subtotal 
           FROM cart c 
           JOIN menu_items m ON c.menu_item_id = m.id 
           WHERE c.user_id = ?
           ORDER BY c.created_at DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($item = mysqli_fetch_assoc($result)) {
    $cart_items[] = $item;
    $total += $item['subtotal'];
}
mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'items' => $cart_items,
    'subtotal' => $total,
    'delivery_fee' => 50,
    'total' => $total + 50,
    'count' => count($cart_items)
]);
?>
