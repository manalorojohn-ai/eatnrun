<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');
session_start();

require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$last_update = isset($_GET['last_update']) ? $_GET['last_update'] : 0;

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    mysqli_query($conn, "SET SESSION sql_mode = ''");
    
    $orders_query = "SELECT o.*, 
                    DATE_FORMAT(o.created_at, '%M %d, %Y %h:%i %p') as formatted_date,
                    DATE_FORMAT(o.received_at, '%M %d, %Y %h:%i %p') as formatted_received_date,
                    CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END as has_rating,
                    r.rating as rating_value,
                    r.comment as rating_comment,
                    UNIX_TIMESTAMP(o.updated_at) as last_update_timestamp,
                    od.menu_item_id,
                    GROUP_CONCAT(
                        CONCAT(
                            od.quantity, 
                            'x ', 
                            mi.name,
                            ' (₱', 
                            FORMAT(od.price * od.quantity, 2),
                            ')'
                        ) 
                        ORDER BY mi.name 
                        SEPARATOR '<br>'
                    ) as order_items
                 FROM orders o 
                 LEFT JOIN ratings r ON o.id = r.order_id AND r.user_id = o.user_id
                 LEFT JOIN order_details od ON o.id = od.order_id
                 LEFT JOIN menu_items mi ON od.menu_item_id = mi.id
                 WHERE o.user_id = ? AND UNIX_TIMESTAMP(o.updated_at) > ?
                 GROUP BY o.id
                 ORDER BY o.created_at DESC";
    
    $stmt = mysqli_prepare($conn, $orders_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $last_update);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format currency values
        $row['subtotal'] = number_format($row['subtotal'], 2);
        $row['delivery_fee'] = number_format($row['delivery_fee'], 2);
        $row['total_amount'] = number_format($row['total_amount'], 2);
        
        // Add status class and badge color
        $row['status_class'] = strtolower($row['status']);
        switch($row['status']) {
            case 'pending':
                $row['badge_color'] = 'warning';
                break;
            case 'processing':
                $row['badge_color'] = 'info';
                break;
            case 'completed':
                $row['badge_color'] = 'success';
                break;
            case 'cancelled':
                $row['badge_color'] = 'danger';
                break;
            default:
                $row['badge_color'] = 'secondary';
        }
        
        // Add payment status badge
        switch($row['payment_status']) {
            case 'paid':
                $row['payment_badge_color'] = 'success';
                break;
            case 'pending':
                $row['payment_badge_color'] = 'warning';
                break;
            case 'failed':
                $row['payment_badge_color'] = 'danger';
                break;
            default:
                $row['payment_badge_color'] = 'secondary';
        }
        
        $orders[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching orders.'
    ]);
}
?> 