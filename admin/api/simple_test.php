<?php
/**
 * Simple test script for the PHP Ratings API
 * This script tests the API without outputting HTML first
 */

// Test the API directly
echo "Testing PHP Ratings API...\n";
echo "========================\n\n";

// Capture the API output
ob_start();
include 'ratings_api.php';
$api_output = ob_get_clean();

// Try to decode JSON
$json_data = json_decode($api_output, true);

if ($json_data) {
    echo "✅ API returned valid JSON\n";
    echo "Success: " . ($json_data['success'] ? 'Yes' : 'No') . "\n";
    
    if (isset($json_data['statistics'])) {
        $stats = $json_data['statistics'];
        echo "\n📊 Statistics:\n";
        echo "- Total ratings: " . $stats['total_ratings'] . "\n";
        echo "- Food ratings: " . $stats['food_ratings'] . "\n";
        echo "- Hotel ratings: " . $stats['hotel_ratings'] . "\n";
        echo "- Pending orders: " . $stats['pending_orders'] . "\n";
        echo "- Average rating: " . $stats['average_rating'] . "\n";
    }
    
    if (isset($json_data['ratings'])) {
        echo "\n📋 Sample ratings (" . count($json_data['ratings']) . " total):\n";
        $sample_count = min(3, count($json_data['ratings']));
        for ($i = 0; $i < $sample_count; $i++) {
            $rating = $json_data['ratings'][$i];
            echo "- ID: " . $rating['id'] . ", Rating: " . $rating['rating'] . ", Source: " . $rating['source'] . "\n";
        }
    }
    
    if (isset($json_data['error'])) {
        echo "\n❌ API Error: " . $json_data['error'] . "\n";
    }
} else {
    echo "❌ API did not return valid JSON\n";
    echo "Raw output:\n";
    echo $api_output . "\n";
}

echo "\n🎯 Test Complete!\n";
echo "To use the API in your application, make a GET request to: admin/api/ratings_api.php\n";
?>
