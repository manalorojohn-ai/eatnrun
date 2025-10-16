<?php
require_once '../config/db.php';
require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

class MessageServer implements \Ratchet\MessageComponentInterface {
    protected $clients;
    protected $adminClients;
    protected $loop;
    protected $db;

    public function __construct($loop) {
        $this->clients = new \SplObjectStorage;
        $this->adminClients = new \SplObjectStorage;
        $this->loop = $loop;
        $this->db = new \mysqli('localhost', 'your_username', 'your_password', 'your_database');
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        switch ($data['type']) {
            case 'register_admin':
                $this->adminClients->attach($from, $data['adminId']);
                echo "Admin registered: {$data['adminId']}\n";
                break;

            case 'new_message':
                // Insert new message into database
                $stmt = $this->db->prepare("INSERT INTO messages (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("sss", $data['name'], $data['email'], $data['message']);
                $stmt->execute();
                $messageId = $stmt->insert_id;
                $stmt->close();

                // Get unread count
                $result = $this->db->query("SELECT COUNT(*) as unread FROM messages WHERE is_read = 0");
                $unreadCount = $result->fetch_assoc()['unread'];

                // Notify all admin clients
                foreach ($this->adminClients as $client) {
                    $client->send(json_encode([
                        'type' => 'new_message',
                        'message_id' => $messageId,
                        'unread_count' => $unreadCount
                    ]));
                }
                break;

            case 'message_read':
                // Update message status in database
                $stmt = $this->db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
                $stmt->bind_param("i", $data['message_id']);
                $stmt->execute();
                $stmt->close();

                // Get updated unread count
                $result = $this->db->query("SELECT COUNT(*) as unread FROM messages WHERE is_read = 0");
                $unreadCount = $result->fetch_assoc()['unread'];

                // Notify all admin clients
                foreach ($this->adminClients as $client) {
                    $client->send(json_encode([
                        'type' => 'message_read',
                        'message_id' => $data['message_id'],
                        'unread_count' => $unreadCount
                    ]));
                }
                break;

            case 'message_deleted':
                // Delete message from database
                $stmt = $this->db->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->bind_param("i", $data['message_id']);
                $stmt->execute();
                $stmt->close();

                // Notify all admin clients
                foreach ($this->adminClients as $client) {
                    $client->send(json_encode([
                        'type' => 'message_deleted',
                        'message_id' => $data['message_id']
                    ]));
                }
                break;
        }
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->adminClients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Create event loop and server
$loop = Factory::create();
$webSocket = new React\Socket\Server('0.0.0.0:8081', $loop);
$webSocket = new SecureServer($webSocket, $loop, [
    'local_cert' => '/path/to/your/certificate.pem',
    'local_pk' => '/path/to/your/private.key',
    'allow_self_signed' => true,
    'verify_peer' => false
]);

// Create WebSocket server
$server = new IoServer(
    new HttpServer(
        new WsServer(
            new MessageServer($loop)
        )
    ),
    $webSocket,
    $loop
);

echo "WebSocket server running on port 8081\n";
$loop->run(); 