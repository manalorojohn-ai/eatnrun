<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 2) . '/includes/helpers/init_session.php';
require_once dirname(__DIR__, 2) . '/config/database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

// Support both JSON body and form-urlencoded / POST
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

$item_id = 0;
$quantity = 1;

if (is_array($jsonData)) {
    $item_id = isset($jsonData['item_id']) ? (int)$jsonData['item_id'] : (int)($jsonData['itemId'] ?? 0);
    $quantity = isset($jsonData['quantity']) ? max(1, (int)$jsonData['quantity']) : 1;
} else {
    $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : (int)($_POST['itemId'] ?? 0);
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
}

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid food item']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $menu_item = null;

    if ($conn instanceof PDO) {
        // Verify item exists
        $stmt = $conn->prepare("SELECT id, name, price FROM menu_items WHERE id = ?");
        $stmt->execute([$item_id]);
        $menu_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$menu_item) {
            echo json_encode(['success' => false, 'message' => 'Item not found or unavailable']);
            exit;
        }

        // Check if item already in cart
        $check_stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND menu_item_id = ?");
        $check_stmt->execute([$user_id, $item_id]);
        $cart_item = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
            $new_quantity = (int)$cart_item['quantity'] + $quantity;
            $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update_stmt->execute([$new_quantity, $cart_item['id']]);
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)");
            $insert_stmt->execute([$user_id, $item_id, $quantity]);
        }

        // Get total cart count
        $count_stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?");
        $count_stmt->execute([$user_id]);
        $count_row = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $cartCount = (int)($count_row['count'] ?? 0);

        echo json_encode([
            'success' => true,
            'message' => 'Added ' . htmlspecialchars($menu_item['name']) . ' to cart!',
            'item' => [
                'id' => $menu_item['id'],
                'name' => $menu_item['name'],
                'price' => $menu_item['price'],
                'quantity' => $quantity
            ],
            'cartCount' => $cartCount
        ]);
        exit;

    } elseif ($conn instanceof mysqli) {
        $stmt = mysqli_prepare($conn, "SELECT id, name, price FROM menu_items WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $item_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $menu_item = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if (!$menu_item) {
            echo json_encode(['success' => false, 'message' => 'Item not found or unavailable']);
            exit;
        }

        $check_stmt = mysqli_prepare($conn, "SELECT id, quantity FROM cart WHERE user_id = ? AND menu_item_id = ?");
        mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $item_id);
        mysqli_stmt_execute($check_stmt);
        $cart_res = mysqli_stmt_get_result($check_stmt);
        $cart_item = mysqli_fetch_assoc($cart_res);
        mysqli_stmt_close($check_stmt);

        if ($cart_item) {
            $new_quantity = (int)$cart_item['quantity'] + $quantity;
            $up_stmt = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE id = ?");
            mysqli_stmt_bind_param($up_stmt, "ii", $new_quantity, $cart_item['id']);
            mysqli_stmt_execute($up_stmt);
            mysqli_stmt_close($up_stmt);
        } else {
            $in_stmt = mysqli_prepare($conn, "INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($in_stmt, "iii", $user_id, $item_id, $quantity);
            mysqli_stmt_execute($in_stmt);
            mysqli_stmt_close($in_stmt);
        }

        $c_stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?");
        mysqli_stmt_bind_param($c_stmt, "i", $user_id);
        mysqli_stmt_execute($c_stmt);
        $count_res = mysqli_stmt_get_result($c_stmt);
        $count_row = mysqli_fetch_assoc($count_res);
        $cartCount = (int)($count_row['count'] ?? 0);
        mysqli_stmt_close($c_stmt);

        echo json_encode([
            'success' => true,
            'message' => 'Added ' . htmlspecialchars($menu_item['name']) . ' to cart!',
            'item' => [
                'id' => $menu_item['id'],
                'name' => $menu_item['name'],
                'price' => $menu_item['price'],
                'quantity' => $quantity
            ],
            'cartCount' => $cartCount
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection unavailable']);
        exit;
    }
} catch (Exception $e) {
    error_log("Error adding to cart: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add item: ' . $e->getMessage()]);
    exit;
}