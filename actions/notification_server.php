<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

class NotificationServer implements \Ratchet\MessageComponentInterface {
    protected $clients;
    protected $userConnections;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if ($data['type'] === 'register') {
            // Associate this connection with the user_id
            $this->userConnections[$data['user_id']] = $from;
            echo "User {$data['user_id']} registered\n";
        }
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        // Remove user connection mapping
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                break;
            }
        }
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function notifyUser($userId, $notification) {
        if (isset($this->userConnections[$userId])) {
            $connection = $this->userConnections[$userId];
            $connection->send(json_encode($notification));
        }
    }
}

// Create event loop and socket server
$loop = Factory::create();
$webSocket = new SecureServer(
    new Server('0.0.0.0:8080', $loop),
    $loop,
    [
        'local_cert' => '/path/to/your/certificate.pem',
        'local_pk' => '/path/to/your/private.key',
        'verify_peer' => false
    ]
);

$server = new IoServer(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    $webSocket,
    $loop
);

echo "WebSocket server started on port 8080\n";
$loop->run(); 