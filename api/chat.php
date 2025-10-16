function getAIResponse($message) {
    $message = strtolower($message);
    
    // Comprehensive system knowledge
    $responses = [
        // ... existing code ...
    ];

    // Enhanced pattern matching with multiple keyword combinations
    $keyword_combinations = [
        ['order', 'how'] => $responses['order'],
        ['order', 'place'] => $responses['order'],
        ['order', 'make'] => $responses['order'],
        ['buy', 'how'] => $responses['buy'],
        ['purchase', 'how'] => $responses['buy'],
        ['cart', 'find'] => $responses['cart'],
        ['cart', 'where'] => $responses['cart'],
        ['login', 'cant'] => "Having trouble logging in? Here's what you can try:\n1. Check your email and password\n2. Click 'Forgot Password' to reset\n3. Clear your browser cache\n4. Try incognito mode\n\nStill having issues? Create a support ticket.",
        ['payment', 'failed'] => "Sorry to hear about the payment issue. Here's what to do:\n1. Check your card details\n2. Ensure sufficient funds\n3. Try a different payment method\n4. Contact your bank\n\nNeed more help? Create a support ticket.",
        ['delivery', 'late'] => "If your delivery is taking longer than expected:\n1. Check the tracking status\n2. Consider traffic conditions\n3. Contact our support\n4. We'll investigate immediately",
        ['food', 'cold'] => "If your food arrived cold:\n1. Report within 30 minutes\n2. Take a photo if possible\n3. Create a support ticket\n4. We'll process a refund/replacement",
        ['refund', 'how'] => $responses['refund'],
        ['cancel', 'order'] => "To cancel your order:\n1. Go to 'My Orders'\n2. Find the recent order\n3. Click 'Cancel' (within 5 mins)\n4. Choose cancellation reason\n\nPast 5 minutes? Contact support.",
    ];

    // Check for keyword combinations first
    foreach ($keyword_combinations as $keywords => $response) {
        $match = true;
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) === false) {
                $match = false;
                break;
            }
        }
        if ($match) {
            return $response;
        }
    }

    // Context-aware response generation
    $context_patterns = [
        '/how (can|do) I/' => 'help',
        '/where (can|do) I/' => 'help',
        '/not working/' => 'help',
        '/problem with/' => 'help',
        '/(can\'t|cannot) find/' => 'help',
        '/thank you|thanks/' => 'bye',
        '/good (morning|afternoon|evening)/' => 'hello',
        '/hi|hey|hello/' => 'hello'
    ];

    foreach ($context_patterns as $pattern => $key) {
        if (preg_match($pattern, $message)) {
            return $responses[$key];
        }
    }

    // Fallback to single keyword matching with fuzzy search
    $best_response = null;
    $highest_similarity = 0;

    foreach ($responses as $keyword => $response) {
        // Calculate similarity score
        $similarity = similar_text($message, $keyword, $percent);
        
        // Check for keyword presence
        $keyword_present = strpos($message, $keyword) !== false;
        
        // Combine both factors
        $total_score = $keyword_present ? ($percent + 50) : $percent;
        
        if ($total_score > $highest_similarity) {
            $highest_similarity = $total_score;
            $best_response = $response;
        }
    }

    // If we found a good match, return it
    if ($highest_similarity > 40) {
        return $best_response;
    }

    // If no good match found, provide a helpful default response
    return "I understand you're asking about \"" . htmlspecialchars($message) . "\". To help you better:\n"
         . "1. Try using specific keywords like 'order', 'delivery', 'payment'\n"
         . "2. Ask direct questions starting with 'how', 'where', 'what'\n"
         . "3. Or create a support ticket for personalized help";
}