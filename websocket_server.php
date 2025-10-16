<?php
require_once 'vendor/autoload.php';
require_once 'config/db.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

class OrderNotificationServer implements \Ratchet\MessageComponentInterface {
    protected $clients;
    protected $adminClients;
    protected $userClients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->adminClients = new \SplObjectStorage;
        $this->userClients = [];
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if (!$data) {
            return;
        }

        switch ($data['type']) {
            case 'auth':
                // Store user type (admin/customer) and user ID
                $from->userType = $data['user_type'];
                $from->userId = $data['user_id'];
                
                if ($data['user_type'] === 'admin') {
                    $this->adminClients->attach($from);
                } else {
                    $this->userClients[$data['user_id']] = $from;
                }
                break;

            case 'new_order':
                // Notify all admin clients
                foreach ($this->adminClients as $client) {
                    $client->send(json_encode([
                        'type' => 'new_order',
                        'order_id' => $data['order_id'],
                        'customer_name' => $data['customer_name'],
                        'total_amount' => $data['total_amount'],
                        'timestamp' => $data['timestamp']
                    ]));
                }

                // Notify the customer
                if (isset($this->userClients[$data['user_id']])) {
                    $this->userClients[$data['user_id']]->send(json_encode([
                        'type' => 'order_confirmation',
                        'order_id' => $data['order_id'],
                        'message' => 'Your order has been placed successfully!'
                    ]));
                }
                break;
        }
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->adminClients->detach($conn);
        
        if (isset($conn->userId)) {
            unset($this->userClients[$conn->userId]);
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Create event loop and socket server
$loop = Factory::create();
$socket = new Server('0.0.0.0:8080', $loop);

// Create WebSocket server
$server = new IoServer(
    new HttpServer(
        new WsServer(
            new OrderNotificationServer()
        )
    ),
    $socket,
    $loop
);

echo "WebSocket server started on port 8080\n";
$server->run(); 