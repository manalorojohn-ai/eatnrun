<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class MessageWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $adminConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->adminConnections = [];
        echo "Message WebSocket Server Started!\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            return;
        }

        switch ($data['type']) {
            case 'register_admin':
                if (isset($data['adminId'])) {
                    $this->adminConnections[$from->resourceId] = [
                        'connection' => $from,
                        'adminId' => $data['adminId']
                    ];
                    echo "Admin registered: {$data['adminId']}\n";
                }
                break;

            case 'new_message':
                $this->broadcastToAdmins($data);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Remove from admin connections if exists
        if (isset($this->adminConnections[$conn->resourceId])) {
            unset($this->adminConnections[$conn->resourceId]);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function broadcastToAdmins($data) {
        foreach ($this->adminConnections as $admin) {
            $admin['connection']->send(json_encode($data));
        }
    }
}

// Create WebSocket server
$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new MessageWebSocket()
        )
    ),
    8081
);

echo "Message WebSocket Server running at port 8081\n";
$server->run(); 