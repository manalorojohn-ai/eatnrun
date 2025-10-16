<?php

function sendWebSocketUpdate($data) {
    $client = new WebSocket\Client("ws://localhost:8081");
    try {
        $client->send(json_encode($data));
    } catch (Exception $e) {
        error_log("WebSocket Error: " . $e->getMessage());
    } finally {
        $client->close();
    }
}

function broadcastMessageUpdate($type, $messageId = null, $additionalData = []) {
    $data = array_merge([
        'type' => $type,
        'message_id' => $messageId,
        'timestamp' => date('Y-m-d H:i:s')
    ], $additionalData);
    
    sendWebSocketUpdate($data);
} 