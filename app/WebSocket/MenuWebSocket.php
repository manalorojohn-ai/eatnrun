<?php
namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use PDO;
use Exception;

class MenuWebSocket implements MessageComponentInterface
{
    protected $clients;
    protected $adminConnections;
    protected $db;

    public function __construct()
    {
        $this->clients = new SplObjectStorage;
        $this->adminConnections = [];
        
        try {
            $this->db = new PDO(
                "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4",
                getenv('DB_USER'),
                getenv('DB_PASS'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (Exception $e) {
            echo "Database connection failed: " . $e->getMessage() . "\n";
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New client connected! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['action'])) {
            return;
        }

        try {
            switch ($data['action']) {
                case 'register_admin':
                    if (isset($data['userId'])) {
                        $this->adminConnections[$data['userId']] = $from;
                        echo "Admin {$data['userId']} registered\n";
                        $this->sendMenuItems($from);
                    }
                    break;

                case 'add_item':
                    if ($this->isAdmin($from)) {
                        $this->addMenuItem($data['item']);
                        $this->broadcastMenuUpdate();
                    }
                    break;

                case 'update_item':
                    if ($this->isAdmin($from)) {
                        $this->updateMenuItem($data['item']);
                        $this->broadcastMenuUpdate();
                    }
                    break;

                case 'delete_item':
                    if ($this->isAdmin($from)) {
                        $this->deleteMenuItem($data['itemId']);
                        $this->broadcastMenuUpdate();
                    }
                    break;

                case 'get_menu':
                    $this->sendMenuItems($from);
                    break;
            }
        } catch (Exception $e) {
            $from->send(json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }

    protected function isAdmin(ConnectionInterface $conn)
    {
        return in_array($conn, $this->adminConnections);
    }

    protected function sendMenuItems(ConnectionInterface $conn)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, c.name as category_name 
                FROM menu_items m 
                LEFT JOIN categories c ON m.category_id = c.id 
                ORDER BY m.category_id, m.name
            ");
            $stmt->execute();
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $conn->send(json_encode([
                'type' => 'menu_update',
                'items' => $items
            ]));
        } catch (Exception $e) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Error fetching menu items: ' . $e->getMessage()
            ]));
        }
    }

    protected function broadcastMenuUpdate()
    {
        foreach ($this->adminConnections as $conn) {
            $this->sendMenuItems($conn);
        }
    }

    protected function addMenuItem($item)
    {
        $stmt = $this->db->prepare("
            INSERT INTO menu_items (name, description, price, category_id, image_path, status)
            VALUES (:name, :description, :price, :category_id, :image_path, :status)
        ");
        
        return $stmt->execute([
            ':name' => $item['name'],
            ':description' => $item['description'],
            ':price' => $item['price'],
            ':category_id' => $item['category_id'],
            ':image_path' => $item['image_path'],
            ':status' => $item['status'] ?? 'available'
        ]);
    }

    protected function updateMenuItem($item)
    {
        $stmt = $this->db->prepare("
            UPDATE menu_items 
            SET name = :name,
                description = :description,
                price = :price,
                category_id = :category_id,
                image_path = :image_path,
                status = :status
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':id' => $item['id'],
            ':name' => $item['name'],
            ':description' => $item['description'],
            ':price' => $item['price'],
            ':category_id' => $item['category_id'],
            ':image_path' => $item['image_path'],
            ':status' => $item['status']
        ]);
    }

    protected function deleteMenuItem($itemId)
    {
        $stmt = $this->db->prepare("DELETE FROM menu_items WHERE id = ?");
        return $stmt->execute([$itemId]);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        foreach ($this->adminConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->adminConnections[$userId]);
                echo "Admin {$userId} disconnected\n";
                break;
            }
        }
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "Error: " . $e->getMessage() . "\n";
        $conn->close();
    }
} 