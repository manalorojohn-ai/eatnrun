<?php
// Helper functions for the admin dashboard

function getOrderStats($conn) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(PDOException $e) {
        return 0;
    }
}

function getRevenueStats($conn) {
    try {
        $stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch(PDOException $e) {
        return 0;
    }
}

function getUserStats($conn) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(PDOException $e) {
        return 0;
    }
}

function getProductStats($conn) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM products");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(PDOException $e) {
        return 0;
    }
}

function getRecentOrders($conn, $limit = 5) {
    try {
        $stmt = $conn->prepare("SELECT o.*, u.username FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return [];
    }
}

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function getOrderStatusClass($status) {
    switch(strtolower($status)) {
        case 'pending':
            return 'status-pending';
        case 'processing':
            return 'status-processing';
        case 'completed':
            return 'status-completed';
        case 'cancelled':
            return 'status-cancelled';
        default:
            return '';
    }
}
?> 