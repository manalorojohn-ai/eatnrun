<?php
// This is a simple test file to verify that AJAX requests work
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Test successful',
    'timestamp' => date('Y-m-d H:i:s')
]);
?> 