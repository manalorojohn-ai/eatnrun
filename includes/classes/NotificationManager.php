<?php
class NotificationManager {
    private $conn;
    private $websocketClient;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->initWebSocket();
    }

    private function initWebSocket() {
        $this->websocketClient = new WebSocket\Client("ws://localhost:8080");
    }

    public function createNotification($userId, $message, $type = 'system') {
        // Insert into database
        $query = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "iss", $userId, $message, $type);
        
        if (mysqli_stmt_execute($stmt)) {
            $notificationId = mysqli_insert_id($this->conn);
            
            // Get the created notification
            $notification = $this->getNotification($notificationId);
            
            // Send real-time notification via WebSocket
            try {
                $this->websocketClient->send(json_encode([
                    'type' => 'notification',
                    'user_id' => $userId,
                    'data' => $notification
                ]));
            } catch (Exception $e) {
                error_log("WebSocket notification failed: " . $e->getMessage());
            }
            
            return $notification;
        }
        
        return false;
    }

    public function getNotification($id) {
        $query = "SELECT * FROM notifications WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    public function getUserNotifications($userId, $limit = 50) {
        $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $notifications = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = $row;
        }
        
        return $notifications;
    }

    public function markAsRead($notificationId, $userId) {
        $query = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $notificationId, $userId);
        return mysqli_stmt_execute($stmt);
    }

    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return (int)$row['count'];
    }
}
?> 