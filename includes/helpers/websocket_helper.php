function broadcastNewRating($rating_id) {
    sendWebSocketUpdate([
        'type' => 'new_rating',
        'rating_id' => $rating_id
    ]);
} 