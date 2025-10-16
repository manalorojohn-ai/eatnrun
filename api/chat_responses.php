<?php
// Prevent direct access
if (!defined('CHAT_RESPONSES_INCLUDED')) {
    define('CHAT_RESPONSES_INCLUDED', true);
}

class ChatResponseHandler {
    private $responses = [
        // Ordering Process
        'order' => [
            'keywords' => ['order', 'how to order', 'place order', 'make order', 'ordering', 'buy food', 'purchase'],
            'response' => "Here's how to place an order:\n1. Click 'Menu' in the navigation bar\n2. Browse restaurants and select items\n3. Add items to your cart\n4. Review your order and delivery details\n5. Choose payment method\n6. Confirm your order\n\nYou can track your order status in 'My Orders'. Need help with a specific step?"
        ],
        
        // Menu Related
        'menu' => [
            'keywords' => ['menu', 'food', 'dishes', 'what can i order', 'restaurants', 'available food', 'cuisine'],
            'response' => "Browse our menu easily:\n1. Click 'Menu' in the top navigation\n2. Filter options:\n   • By cuisine type (Filipino, Chinese, etc.)\n   • By price range\n   • By ratings\n   • By delivery time\n3. Click on restaurants to view their full menu\n4. Each item shows:\n   • Price\n   • Description\n   • Customization options\n   • Availability status"
        ],
        
        // Payment
        'payment' => [
            'keywords' => ['payment', 'pay', 'how to pay', 'payment methods', 'cash', 'card', 'billing'],
            'response' => "We offer these secure payment options:\n• Credit/Debit Cards (Visa, Mastercard)\n• Digital Wallets (GCash, PayMaya)\n• Cash on Delivery\n• Online Banking\n\nTo pay:\n1. Select items and proceed to checkout\n2. Choose your preferred payment method\n3. Complete the payment process\n4. Receive confirmation\n\nAll transactions are encrypted and secure."
        ],
        
        // Delivery
        'delivery' => [
            'keywords' => ['delivery', 'delivery time', 'when will i receive', 'tracking', 'where is my order', 'delivery status'],
            'response' => "Delivery Information:\n• Standard delivery: 30-45 minutes\n• Peak hours may take longer\n• Free delivery for orders above ₱500\n• Real-time tracking available\n\nTrack your order:\n1. Go to 'My Orders'\n2. Select your active order\n3. View real-time status updates\n4. Track rider's location\n\nDelivery coverage: Within city limits (10km radius)"
        ],
        
        // Account Management
        'account' => [
            'keywords' => ['account', 'profile', 'login', 'signup', 'register', 'password', 'forgot password', 'reset password', 'change details'],
            'response' => "Account Management:\n1. New User?\n   • Click 'Profile' → 'Sign Up'\n   • Fill required details\n   • Verify email\n\n2. Existing User?\n   • Click 'Profile' → 'Login'\n   • Enter email and password\n\n3. Forgot Password?\n   • Click 'Forgot Password'\n   • Enter email\n   • Follow reset instructions\n\n4. Update Profile:\n   • Access 'Profile' settings\n   • Edit personal info\n   • Update delivery addresses"
        ],
        
        // Cart Management
        'cart' => [
            'keywords' => ['cart', 'shopping cart', 'add to cart', 'remove from cart', 'modify order', 'change order'],
            'response' => "Cart Management:\n1. Add Items to Cart:\n   • Click '+' button next to menu items\n   • Select quantity\n   • Choose any customizations if available\n   • Click 'Add to Cart'\n\n2. Modify Cart:\n   • Click the 'Cart' icon in top navigation\n   • Adjust item quantities using + / - buttons\n   • Remove items using the 'Remove' button\n   • Add special instructions for each item\n\n3. Checkout Process:\n   • Review your cart items\n   • Enter delivery address\n   • Choose payment method\n   • Review total (including delivery fee)\n   • Click 'Place Order' to complete"
        ],
        
        // Refunds & Complaints
        'refund' => [
            'keywords' => ['refund', 'complaint', 'issue', 'problem', 'wrong order', 'bad food', 'late delivery', 'cancel order'],
            'response' => "For Issues & Refunds:\n1. Order Issues:\n   • Go to 'My Orders'\n   • Select problematic order\n   • Click 'Report Issue'\n   • Choose issue type\n   • Add details/photos\n\n2. Refund Process:\n   • Processed within 3-5 business days\n   • Returns to original payment method\n\n3. Urgent Issues:\n   • Call hotline: 0912 345 6789\n   • Available 24/7"
        ],
        
        // Ratings & Reviews
        'ratings' => [
            'keywords' => ['rating', 'review', 'feedback', 'rate', 'stars', 'comment', 'how to rate'],
            'response' => "Rating System:\n1. Rate Orders:\n   • Go to 'My Orders'\n   • Click 'Rate Order'\n   • Rate (1-5 stars):\n     - Food Quality\n     - Delivery Service\n     - Restaurant Service\n   • Add photos (optional)\n   • Write detailed review\n\n2. View Ratings:\n   • Check restaurant pages\n   • See overall scores\n   • Read customer reviews"
        ],
        
        // Special Requests
        'special' => [
            'keywords' => ['special request', 'customize', 'allergies', 'dietary', 'special instructions', 'preferences', 'notes'],
            'response' => "Special Requests:\n1. During Ordering:\n   • Add notes per item\n   • Specify allergies\n   • Request utensils\n   • Add cooking preferences\n\n2. Delivery Instructions:\n   • Add landmark details\n   • Request contactless delivery\n   • Specify building access\n\n3. Dietary Preferences:\n   • Filter menu by:\n     - Vegetarian\n     - Halal\n     - Gluten-free\n     - Spice level"
        ],
        
        // Location
        'location' => [
            'keywords' => ['location', 'where are you', 'address', 'where is eat&run', 'office', 'headquarters', 'branch'],
            'response' => "Eat&Run has two locations:\n\n• Main Branch: E. Taleon st, Santisima Cruz, Philippines\n• Bubukal Branch: Open from 5pm-2am\n\nOur service covers a 10km radius from our locations, ensuring quick and fresh food delivery to all our customers in the area.\n\nOur main office is open from 10:00 AM - 10:00 PM daily. Feel free to visit us with any questions or for assistance with your orders!\n\nPhone: 0912 345 6789\nEmail: eat&run@example.com"
        ],
        
        // Tech Support
        'tech_support' => [
            'keywords' => ['app not working', 'website issue', 'technical problem', 'bug', 'error', 'glitch', 'can\'t access'],
            'response' => "I'm sorry to hear you're experiencing technical difficulties. Here are some quick troubleshooting steps:\n\n1. Refresh the page or restart the app\n2. Clear your browser cache/cookies\n3. Check your internet connection\n4. Update to the latest version of our app\n5. Try a different browser\n\nIf problems persist, please contact our technical support team at eat&run@example.com or call 0912 345 6789 during our business hours (10:00 AM - 10:00 PM). Please provide details about the issue and what device you're using."
        ],
        
        // Contact Info (new category)
        'contact' => [
            'keywords' => ['contact', 'phone', 'call', 'email', 'customer service', 'support number', 'helpline', 'contact details', 'support email'],
            'response' => "📞 Contact Information:\n\n" .
                         "📍 Locations:\n" .
                         "• Main Branch: 518 E Taleon St, Santa Cruz, Laguna\n" .
                         "  Hours: 10:00 AM - 10:00 PM daily\n" .
                         "  Nearby: Father And Son Tattoo Studio, Dr. Remil L.Galay Licensed Veterinarian, Ultra Mega Santa Cruz laguna\n\n" .
                         "• Bubukal Branch\n" .
                         "  Hours: 5:00 PM - 2:00 AM daily\n\n" .
                         "📱 Contact Methods:\n" .
                         "• Phone: 0912 345 6789\n" .
                         "• Email: eat&run@example.com\n" .
                         "• Facebook: facebook.com/eatnrun2019\n\n" .
                         "🛍️ Services:\n" .
                         "• Dine-In 🍽️\n" .
                         "• Delivery 🚚\n" .
                         "• Pick-up 🏃\n\n" .
                         "For order-related inquiries, please have your order number ready for faster assistance."
        ],
        
        // Default Response
        'default' => [
            'response' => "Hello! I'm your Eat&Run assistant. I can help you with:\n• Ordering food\n• Menu & restaurants\n• Payment methods\n• Delivery tracking\n• Account help\n• Cart management\n• Refunds & issues\n• Ratings & reviews\n• Special requests\n\nWhat would you like to know about?"
        ]
    ];

    public function getResponse($message) {
        $message = strtolower(trim($message));
        
        // Check for exact matches first
        foreach ($this->responses as $key => $data) {
            if (isset($data['keywords'])) {
                foreach ($data['keywords'] as $keyword) {
                    if ($message === $keyword) {
                        return $data['response'];
                    }
                }
            }
        }
        
        // Check for partial matches with context
        $bestMatch = null;
        $highestScore = 0;
        
        foreach ($this->responses as $key => $data) {
            if (isset($data['keywords'])) {
                foreach ($data['keywords'] as $keyword) {
                    $score = 0;
                    $keywordParts = explode(' ', $keyword);
                    
                    // Check for keyword presence
                    foreach ($keywordParts as $part) {
                        if (strpos($message, $part) !== false) {
                            $score += 2;
                        }
                    }
                    
                    // Context matching
                    if (strpos($message, 'how') !== false) $score++;
                    if (strpos($message, 'what') !== false) $score++;
                    if (strpos($message, 'where') !== false) $score++;
                    if (strpos($message, 'when') !== false) $score++;
                    if (strpos($message, 'why') !== false) $score++;
                    if (strpos($message, 'can') !== false) $score++;
                    
                    if ($score > $highestScore) {
                        $highestScore = $score;
                        $bestMatch = $data['response'];
                    }
                }
            }
        }
        
        // If no good match found, return default
        return ($highestScore > 1) ? $bestMatch : $this->responses['default']['response'];
    }
}
?> 