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

$user_id = $_SESSION['user_id'];
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : (int)($_POST['cartId'] ?? 0);

if (!$cart_id) {
    $rawInput = file_get_contents('php://input');
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        $cart_id = (int)($jsonData['cart_id'] ?? $jsonData['cartId'] ?? 0);
    }
}

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit;
}

try {
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $user_id]);

        $count_stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?");
        $count_stmt->execute([$user_id]);
        $row = $count_stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart',
            'cartCount' => (int)($row['count'] ?? 0)
        ]);
        exit;
    } elseif ($conn instanceof mysqli) {
        $stmt = mysqli_prepare($conn, "DELETE FROM cart WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $cart_id, $user_id);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $count_stmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?");
        mysqli_stmt_bind_param($count_stmt, "i", $user_id);
        mysqli_stmt_execute($count_stmt);
        $count_res = mysqli_stmt_get_result($count_stmt);
        $row = mysqli_fetch_assoc($count_res);
        mysqli_stmt_close($count_stmt);

        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart',
            'cartCount' => (int)($row['count'] ?? 0)
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
