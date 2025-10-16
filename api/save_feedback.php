<?php
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['message']) || !isset($data['feedback'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=online_food_ordering", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if table exists, create if not
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ai_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            feedback VARCHAR(20) NOT NULL,
            user_id INT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Save feedback with or without user ID
    $user_id = $data['user_id'] ?? null;
    
    $stmt = $pdo->prepare("
        INSERT INTO ai_feedback (message, feedback, user_id)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $data['message'],
        $data['feedback'],
        $user_id
    ]);

    // Return success with message
    $feedback_type = $data['feedback'] == 'positive' ? 'positive' : 'negative';
    $response_message = $feedback_type == 'positive' 
        ? "Thank you for your positive feedback! We're glad the response was helpful." 
        : "Thank you for your feedback. We'll work to improve our responses.";

    echo json_encode([
        'success' => true, 
        'message' => $response_message
    ]);

} catch (Exception $e) {
    error_log("Error saving feedback: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while saving feedback']);
}
?> 