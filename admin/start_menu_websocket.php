<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/MenuWebSocket.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Admin\MenuWebSocket;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new MenuWebSocket($pdo)
        )
    ),
    8081
);

echo "Menu WebSocket Server started on port 8081\n";
$server->run(); 