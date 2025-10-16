<?php
// Prevent direct access
if (!defined('AI_CONFIG_INCLUDED')) {
    define('AI_CONFIG_INCLUDED', true);
}

// Load local configuration if exists
if (file_exists(__DIR__ . '/ai_config_local.php')) {
    require_once __DIR__ . '/ai_config_local.php';
}

// AI Configuration
class AIConfig {
    // OpenAI API Configuration
    private static $openai_api_key = null;
    
    const OPENAI_MODEL = 'gpt-3.5-turbo'; // You can change to other models like 'gpt-4' if needed
    const MAX_TOKENS = 150;
    const TEMPERATURE = 0.7;

    public static function getApiKey() {
        if (self::$openai_api_key === null) {
            self::$openai_api_key = class_exists('AIConfigLocal') ? AIConfigLocal::OPENAI_API_KEY : 'YOUR_OPENAI_API_KEY';
        }
        return self::$openai_api_key;
    }

    // Chat History Settings
    const MAX_HISTORY_LENGTH = 10; // Maximum number of messages to keep in context
    const CONTEXT_WINDOW = 4096; // Maximum tokens for context window

    // Response Settings
    const DEFAULT_LANGUAGE = 'en';
    const RESPONSE_TIMEOUT = 15; // seconds
    
    // Personalization Settings
    const ENABLE_PERSONALIZATION = true;
    const USER_HISTORY_LIMIT = 50; // Number of past interactions to store per user
    
    // Restaurant-specific Knowledge
    const MENU_CONTEXT = true; // Include menu items in AI context
    const PRICING_CONTEXT = true; // Include pricing in AI context
    
    // Security Settings
    const ENABLE_CONTENT_FILTERING = true;
    const MAX_REQUESTS_PER_MINUTE = 20;
}
?> 