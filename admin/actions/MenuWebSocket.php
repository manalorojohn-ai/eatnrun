<?php
namespace Admin;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use PDO;

class MenuWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $adminClients;
    protected $db;

    public function __construct(PDO $db) {
        $this->clients = new SplObjectStorage;
        $this->adminClients = new SplObjectStorage;
        $this->db = $db;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!isset($data['action'])) {
            $this->sendError($from, 'Invalid message format');
            return;
        }

        switch ($data['action']) {
            case 'register_admin':
                $this->handleAdminRegistration($from, $data);
                break;
            case 'add_item':
                $this->handleAddItem($from, $data);
                break;
            case 'update_item':
                $this->handleUpdateItem($from, $data);
                break;
            case 'delete_item':
                $this->handleDeleteItem($from, $data);
                break;
            default:
                $this->sendError($from, 'Unknown action');
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->adminClients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function handleAdminRegistration(ConnectionInterface $conn, array $data) {
        if (!isset($data['userId'])) {
            $this->sendError($conn, 'User ID not provided');
            return;
        }

        try {
            $stmt = $this->db->prepare('SELECT role FROM users WHERE id = ?');
            $stmt->execute([$data['userId']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['role'] === 'admin') {
                $this->adminClients->attach($conn);
                $this->broadcastMenuItems($conn);
            } else {
                $this->sendError($conn, 'Unauthorized');
            }
        } catch (\PDOException $e) {
            $this->sendError($conn, 'Database error');
        }
    }

    protected function handleAddItem(ConnectionInterface $from, array $data) {
        if (!$this->isAdmin($from)) {
            $this->sendError($from, 'Unauthorized');
            return;
        }

        try {
            $item = $data['item'];
            $stmt = $this->db->prepare('
                INSERT INTO menu_items (name, description, price, category_id, image_path, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $item['name'],
                $item['description'],
                $item['price'],
                $item['category_id'],
                $item['image_path'],
                $item['status'] ?? 'available'
            ]);

            $this->broadcastMenuItems();
        } catch (\PDOException $e) {
            $this->sendError($from, 'Failed to add item');
        }
    }

    protected function handleUpdateItem(ConnectionInterface $from, array $data) {
        if (!$this->isAdmin($from)) {
            $this->sendError($from, 'Unauthorized');
            return;
        }

        try {
            $item = $data['item'];
            $stmt = $this->db->prepare('
                UPDATE menu_items 
                SET name = ?, description = ?, price = ?, category_id = ?, 
                    image_path = ?, status = ?
                WHERE id = ?
            ');
            
            $stmt->execute([
                $item['name'],
                $item['description'],
                $item['price'],
                $item['category_id'],
                $item['image_path'],
                $item['status'] ?? 'available',
                $item['id']
            ]);

            $this->broadcastMenuItems();
        } catch (\PDOException $e) {
            $this->sendError($from, 'Failed to update item');
        }
    }

    protected function handleDeleteItem(ConnectionInterface $from, array $data) {
        if (!$this->isAdmin($from)) {
            $this->sendError($from, 'Unauthorized');
            return;
        }

        try {
            $stmt = $this->db->prepare('DELETE FROM menu_items WHERE id = ?');
            $stmt->execute([$data['itemId']]);
            $this->broadcastMenuItems();
        } catch (\PDOException $e) {
            $this->sendError($from, 'Failed to delete item');
        }
    }

    protected function broadcastMenuItems(ConnectionInterface $target = null) {
        try {
            $stmt = $this->db->prepare('
                SELECT m.*, c.name as category_name 
                FROM menu_items m 
                JOIN categories c ON m.category_id = c.id 
                ORDER BY c.name, m.name
            ');
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $message = json_encode([
                'type' => 'menu_update',
                'items' => $items
            ]);

            if ($target) {
                $target->send($message);
            } else {
                foreach ($this->clients as $client) {
                    $client->send($message);
                }
            }
        } catch (\PDOException $e) {
            if ($target) {
                $this->sendError($target, 'Failed to fetch menu items');
            }
        }
    }

    protected function isAdmin(ConnectionInterface $conn) {
        return $this->adminClients->contains($conn);
    }

    protected function sendError(ConnectionInterface $conn, string $message) {
        $conn->send(json_encode([
            'type' => 'error',
            'message' => $message
        ]));
    }
} 