<?php
header('Content-Type: application/json');

// Prevent direct access
require_once 'chat_responses.php';
require_once 'ai_handler.php';
require_once __DIR__ . '/../config/ai_config.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';
$user_id = $data['user_id'] ?? null;

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

try {
    // Check rate limiting
    if ($user_id) {
        $pdo = new PDO("mysql:host=localhost;dbname=online_food_ordering", "root", "");
        $stmt = $pdo->prepare("
            SELECT request_count, last_reset 
            FROM ai_rate_limits 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $rateLimit = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $now = new DateTime();
        
        if ($rateLimit) {
            $lastReset = new DateTime($rateLimit['last_reset']);
            $diff = $now->diff($lastReset);
            
            if ($diff->i >= 1) { // Reset after 1 minute
                $stmt = $pdo->prepare("
                    UPDATE ai_rate_limits 
                    SET request_count = 1, last_reset = NOW() 
                    WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);
            } else if ($rateLimit['request_count'] >= AIConfig::MAX_REQUESTS_PER_MINUTE) {
                http_response_code(429);
                echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
                exit;
            } else {
                $stmt = $pdo->prepare("
                    UPDATE ai_rate_limits 
                    SET request_count = request_count + 1 
                    WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);
            }
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO ai_rate_limits (user_id, request_count, last_reset) 
                VALUES (?, 1, NOW())
            ");
            $stmt->execute([$user_id]);
        }
    }

    // Initialize AI handler
    $aiHandler = new AIHandler($user_id);
    $startTime = microtime(true);
    
    try {
        // Get AI response
        $response = $aiHandler->generateResponse($message);
        
        if (empty($response)) {
            throw new Exception('No response generated');
        }
        
        // Calculate response time
        $responseTime = microtime(true) - $startTime;
        
        // Log analytics with enhanced query type detection
        if ($user_id) {
            $queryType = detectQueryType($message);
            logAnalytics($pdo, $user_id, $queryType, $responseTime);
        }

        // Send response
        echo json_encode([
            'response' => $response,
            'response_time' => round($responseTime, 3),
            'query_type' => $queryType ?? 'general'
        ]);

    } catch (Exception $e) {
        error_log("Error in get_response.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'An error occurred while processing your request',
            'response' => "I'm here to help with questions about our menu, orders, delivery, or any other inquiries. What would you like to know?"
        ]);
    }

} catch (Exception $e) {
    error_log("Error in get_response.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request']);
}

// Enhanced helper function to categorize queries
function detectQueryType($message) {
    $message = strtolower($message);
    
    // Check for website creator questions first (highest priority)
    if (preg_match('/who (created|made|built|developed|designed).*(\bthis\b|\bwebsite\b|\bsite\b|\bapp\b)/', $message) ||
        preg_match('/(\bwebsite\b|\bsite\b|\bapp\b|\bthis\b).*(creator|developer|designer|team|made by|created by)/', $message) ||
        preg_match('/(creator|developer|team|wonder pets)/', $message)) {
        return 'website_creators';
    }
    
    // Check for location questions with high priority
    if (preg_match('/(where|location|address).*(\byou\b|\beat&run\b|\brestaurant\b|\boffice\b|\bplace\b|\bshop\b)/', $message) ||
        preg_match('/(\beat&run\b|\brestaurant\b|\boffice\b|\bshop\b).*(located|address|find|visit)/', $message) ||
        preg_match('/(address|location|office address|physical location|store address|where to find)/', $message)) {
        return 'location';
    }
    
    // Standard categories for our business
    $patterns = [
        'order' => '/\b(order|purchase|buy|cart|checkout|add to cart)\b/',
        'menu' => '/\b(menu|food|dish|cuisine|what do you have|what can i order)\b/',
        'payment' => '/\b(pay|payment|price|cost|how much|gcash|paymaya|card)\b/',
        'delivery' => '/\b(delivery|track|where is my|when will|how long|estimate|arrival)\b/',
        'account' => '/\b(account|login|register|password|sign up|profile)\b/',
        'support' => '/\b(help|support|issue|problem|trouble|assist|broken)\b/',
        'feedback' => '/\b(feedback|review|rate|comment|opinion|rating)\b/',
        'hours' => '/\b(hours|open|close|schedule|timing|when are you|business hours)\b/',
        'contact' => '/\b(contact|phone|call|email|customer service|support number|helpline)\b/',
        'promotions' => '/\b(promo|promotion|discount|coupon|deal|offer|sale|special)\b/'
    ];
    
    // Additional categories for general knowledge
    $generalPatterns = [
        'food_facts' => '/\b(fact|trivia|interesting|did you know|tell me about)\b/',
        'cooking' => '/\b(recipe|cook|prepare|make|homemade|how to make)\b/',
        'nutrition' => '/\b(nutrition|calories|healthy|diet|protein|carbs|fats)\b/',
        'comparison' => '/\b(versus|vs|compare|difference|better|best|worst)\b/',
        'general_knowledge' => '/\b(what is|what are|who is|who are|when was|why is|how does)\b/'
    ];
    
    // Combine all patterns
    $allPatterns = array_merge($patterns, $generalPatterns);
    
    // Check for multiple matches to find the most relevant category
    $matches = [];
    foreach ($allPatterns as $type => $pattern) {
        if (preg_match($pattern, $message, $matchResults)) {
            $matches[$type] = strlen(implode('', $matchResults));
        }
    }
    
    // If we have matches, return the type with the longest match
    if (!empty($matches)) {
        arsort($matches);
        return key($matches);
    }
    
    return 'general';
}
?> 