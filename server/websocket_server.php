<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificationServer implements MessageComponentInterface {
    protected $clients;
    protected $userConnections;
    protected $adminConnections;
    protected $conn;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->adminConnections = [];
        $this->initDatabaseConnection();
        echo "Notification Server Started!\n";
    }

    protected function initDatabaseConnection() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($this->conn->connect_error) {
                throw new \Exception("Connection failed: " . $this->conn->connect_error);
            }
            $this->conn->set_charset('utf8mb4');
        } catch (\Exception $e) {
            echo "Database connection error: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                echo "Invalid message format\n";
                return;
            }

            switch ($data['type']) {
                case 'register':
                    if (isset($data['userId'])) {
                        $this->userConnections[$data['userId']] = $from;
                        echo "User {$data['userId']} registered\n";
                        $this->sendPendingNotifications($data['userId']);
                    }
                    break;

                case 'register_admin':
                    if (isset($data['adminId'])) {
                        $this->adminConnections[$data['adminId']] = $from;
                        echo "Admin {$data['adminId']} registered\n";
                        $this->sendPendingMessages($data['adminId']);
                    }
                    break;

                case 'menu_item_updated':
                    if (isset($data['item'])) {
                        $this->handleMenuItemUpdate($data['item']);
                    }
                    break;

                case 'menu_item_deleted':
                    if (isset($data['itemId'])) {
                        $this->handleMenuItemDelete($data['itemId']);
                    }
                    break;

                case 'menu_item_added':
                    if (isset($data['item'])) {
                        $this->handleMenuItemAdd($data['item']);
                    }
                    break;

                case 'new_message':
                    if (isset($data['message'])) {
                        $this->handleNewMessage($data);
                    }
                    break;

                case 'message_read':
                    if (isset($data['messageId'])) {
                        $this->handleMessageRead($data);
                    }
                    break;

                case 'message_deleted':
                    if (isset($data['messageId'])) {
                        $this->handleMessageDeleted($data);
                    }
                    break;

                case 'order_update':
                    if (isset($data['orderId'])) {
                        $this->handleOrderUpdate($data);
                    }
                    break;

                case 'new_rating':
                    $this->handleNewRating($data);
                    break;
            }
        } catch (\Exception $e) {
            echo "Error processing message: " . $e->getMessage() . "\n";
        }
    }

    protected function sendPendingNotifications($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                ORDER BY created_at DESC LIMIT 10
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $notification = [
                    'id' => $row['id'],
                    'type' => $row['type'],
                    'message' => $row['message'],
                    'created_at' => $row['created_at'],
                    'link' => $row['link']
                ];
                
                $this->userConnections[$userId]->send(json_encode($notification));
            }
            
            $stmt->close();
        } catch (\Exception $e) {
            echo "Error sending pending notifications: " . $e->getMessage() . "\n";
        }
    }

    protected function handleNewMessage($data) {
        try {
            // Insert new message into database
            $stmt = $this->conn->prepare("
                INSERT INTO messages (name, email, message, user_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("sssi", $data['name'], $data['email'], $data['message'], $data['userId']);
            $stmt->execute();
            $messageId = $stmt->insert_id;
            
            // Get the message details
            $messageQuery = $this->conn->prepare("
                SELECT *, 
                CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60 
                    THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' minutes ago')
                    WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) < 24 
                    THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' hours ago')
                    ELSE DATE_FORMAT(created_at, '%b %d, %Y at %h:%i %p')
                END as time_ago
                FROM messages WHERE id = ?
            ");
            $messageQuery->bind_param("i", $messageId);
            $messageQuery->execute();
            $result = $messageQuery->get_result();
            $message = $result->fetch_assoc();

            // Notify all connected admins
            foreach ($this->adminConnections as $adminConnection) {
                $notification = [
                    'type' => 'new_message',
                    'message' => $message
                ];
                $adminConnection->send(json_encode($notification));
            }

            $stmt->close();
            $messageQuery->close();
        } catch (\Exception $e) {
            echo "Error handling new message: " . $e->getMessage() . "\n";
        }
    }

    protected function handleMessageRead($data) {
        try {
            $stmt = $this->conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
            $stmt->bind_param("i", $data['messageId']);
            $stmt->execute();

            // Get updated unread count
            $countQuery = $this->conn->query("SELECT COUNT(*) as unread FROM messages WHERE is_read = 0");
            $unreadCount = $countQuery->fetch_assoc()['unread'];

            // Notify all connected admins
            foreach ($this->adminConnections as $adminConnection) {
                $notification = [
                    'type' => 'message_read',
                    'message_id' => $data['messageId'],
                    'unread_count' => $unreadCount
                ];
                $adminConnection->send(json_encode($notification));
            }

            $stmt->close();
        } catch (\Exception $e) {
            echo "Error handling message read: " . $e->getMessage() . "\n";
        }
    }

    protected function handleMessageDeleted($data) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM messages WHERE id = ?");
            $stmt->bind_param("i", $data['messageId']);
            $stmt->execute();

            // Notify all connected admins
            foreach ($this->adminConnections as $adminConnection) {
                $notification = [
                    'type' => 'message_deleted',
                    'message_id' => $data['messageId']
                ];
                $adminConnection->send(json_encode($notification));
            }

            $stmt->close();
        } catch (\Exception $e) {
            echo "Error handling message deletion: " . $e->getMessage() . "\n";
        }
    }

    protected function sendPendingMessages($adminId) {
        try {
            $query = "SELECT m.*, 
                    CASE 
                        WHEN TIMESTAMPDIFF(MINUTE, m.created_at, NOW()) < 60 
                        THEN CONCAT(TIMESTAMPDIFF(MINUTE, m.created_at, NOW()), ' minutes ago')
                        WHEN TIMESTAMPDIFF(HOUR, m.created_at, NOW()) < 24 
                        THEN CONCAT(TIMESTAMPDIFF(HOUR, m.created_at, NOW()), ' hours ago')
                        ELSE DATE_FORMAT(m.created_at, '%b %d, %Y at %h:%i %p')
                    END as time_ago
                    FROM messages m
                    WHERE m.is_read = 0
                    ORDER BY m.created_at DESC";
            
            $result = $this->conn->query($query);
            $messages = [];
            
            while ($row = $result->fetch_assoc()) {
                $messages[] = $row;
            }

            if (!empty($messages)) {
                $notification = [
                    'type' => 'pending_messages',
                    'messages' => $messages
                ];
                $this->adminConnections[$adminId]->send(json_encode($notification));
            }
        } catch (\Exception $e) {
            echo "Error sending pending messages: " . $e->getMessage() . "\n";
        }
    }

    protected function handleOrderUpdate($data) {
        try {
            $stmt = $this->conn->prepare("SELECT user_id FROM orders WHERE id = ?");
            $stmt->bind_param("i", $data['orderId']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $userId = $row['user_id'];
                $message = $this->getOrderStatusMessage($data['status'], $data['orderId']);
                
                // Insert notification
                $insertStmt = $this->conn->prepare("
                    INSERT INTO notifications (user_id, message, type, link) 
                    VALUES (?, ?, 'order', ?)
                ");
                $link = "orders.php?highlight=" . $data['orderId'];
                $insertStmt->bind_param("iss", $userId, $message, $link);
                $insertStmt->execute();
                $notificationId = $insertStmt->insert_id;
                
                // Send real-time notification if user is connected
                if (isset($this->userConnections[$userId])) {
                    $notification = [
                        'id' => $notificationId,
                        'type' => 'order',
                        'message' => $message,
                    'orderId' => $data['orderId'],
                    'status' => $data['status'],
                        'link' => $link,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $this->userConnections[$userId]->send(json_encode($notification));
                }
                
                $insertStmt->close();
            }
            
            $stmt->close();
        } catch (\Exception $e) {
            echo "Error handling order update: " . $e->getMessage() . "\n";
        }
    }

    protected function getOrderStatusMessage($status, $orderId) {
        switch (strtolower($status)) {
            case 'preparing':
                return "Your order #{$orderId} is being prepared";
            case 'ready':
                return "Your order #{$orderId} is ready for pickup";
            case 'completed':
                return "Your order #{$orderId} has been completed";
            case 'cancelled':
                return "Your order #{$orderId} has been cancelled";
            default:
                return "Your order #{$orderId} status has been updated to: {$status}";
        }
    }

    protected function handleMenuItemUpdate($item) {
        try {
            // Notify all connected admins
            foreach ($this->adminConnections as $adminConnection) {
                $notification = [
                    'type' => 'menu_item_updated',
                    'item' => $item
                ];
                $adminConnection->send(json_encode($notification));
            }

            // Also notify connected users if needed
            foreach ($this->userConnections as $userConnection) {
                $notification = [
                    'type' => 'menu_item_updated',
                    'item' => $item
                ];
                $userConnection->send(json_encode($notification));
            }
        } catch (\Exception $e) {
            echo "Error handling menu item update: " . $e->getMessage() . "\n";
        }
    }

    protected function handleMenuItemDelete($itemId) {
        try {
            // Notify all connected admins
            foreach ($this->adminConnections as $adminConnection) {
                $notification = [
                    'type' => 'menu_item_deleted',
                    'itemId' => $itemId
                ];
                $adminConnection->send(json_encode($notification));
            }

            // Also notify connected users if needed
            foreach ($this->userConnections as $userConnection) {
                $notification = [
                    'type' => 'menu_item_deleted',
                    'itemId' => $itemId
                ];
                $userConnection->send(json_encode($notification));
            }
        } catch (\Exception $e) {
            echo "Error handling menu item deletion: " . $e->getMessage() . "\n";
        }
    }

    protected function handleMenuItemAdd($item) {
        try {
            // Notify all connected admins
            foreach ($this->adminConnections as $adminConnection) {
                $notification = [
                    'type' => 'menu_item_added',
                    'item' => $item
                ];
                $adminConnection->send(json_encode($notification));
            }

            // Also notify connected users if needed
            foreach ($this->userConnections as $userConnection) {
                $notification = [
                    'type' => 'menu_item_added',
                    'item' => $item
                ];
                $userConnection->send(json_encode($notification));
            }
        } catch (\Exception $e) {
            echo "Error handling menu item addition: " . $e->getMessage() . "\n";
        }
    }

    protected function handleNewRating($data) {
        try {
            $stmt = $this->conn->prepare("
                SELECT r.*, u.username, m.name as menu_item_name, o.id as order_number
                FROM ratings r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN menu_items m ON r.menu_item_id = m.id
                LEFT JOIN orders o ON r.order_id = o.id
                WHERE r.id = ?
            ");
            $stmt->bind_param("i", $data['rating_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $rating = $result->fetch_assoc();

            if ($rating) {
                $notification = [
                    'type' => 'new_rating',
                    'rating' => $rating
                ];
                foreach ($this->adminConnections as $adminConnection) {
                    $adminConnection->send(json_encode($notification));
                }
            }

            $stmt->close();
        } catch (\Exception $e) {
            echo "Error handling new rating: " . $e->getMessage() . "\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                echo "User {$userId} disconnected\n";
                break;
            }
        }
        foreach ($this->adminConnections as $adminId => $connection) {
            if ($connection === $conn) {
                unset($this->adminConnections[$adminId]);
                echo "Admin {$adminId} disconnected\n";
                break;
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        $conn->close();
    }

    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Create and run server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    8080,
    '0.0.0.0'  // Listen on all interfaces
);

echo "WebSocket server started on 0.0.0.0:8080\n";
$server->run(); 