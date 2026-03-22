<?php
class RatingManager {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addOrUpdateRating($userId, $itemId, $rating, $review = '') {
        // Check if user has already rated this item
        $check_query = "SELECT id FROM ratings WHERE user_id = ? AND item_id = ?";
        $check_stmt = mysqli_prepare($this->conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ii", $userId, $itemId);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($result) > 0) {
            // Update existing rating
            $query = "UPDATE ratings SET rating = ?, review = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND item_id = ?";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, "isii", $rating, $review, $userId, $itemId);
        } else {
            // Add new rating
            $query = "INSERT INTO ratings (user_id, item_id, rating, review) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($this->conn, $query);
            mysqli_stmt_bind_param($stmt, "iiis", $userId, $itemId, $rating, $review);
        }

        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($success) {
            return $this->getItemRatings($itemId);
        }
        return false;
    }

    public function getItemRatings($itemId) {
        // Get average rating and count
        $stats_query = "SELECT 
            COUNT(*) as total_ratings,
            AVG(rating) as average_rating,
            COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star,
            COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
            COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
            COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
            COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star
        FROM ratings 
        WHERE item_id = ?";

        $stats_stmt = mysqli_prepare($this->conn, $stats_query);
        mysqli_stmt_bind_param($stats_stmt, "i", $itemId);
        mysqli_stmt_execute($stats_stmt);
        $stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stats_stmt));

        // Get recent reviews
        $reviews_query = "SELECT 
            r.*, 
            u.username,
            u.profile_image
        FROM ratings r
        JOIN users u ON r.user_id = u.id
        WHERE r.item_id = ?
        ORDER BY r.updated_at DESC
        LIMIT 10";

        $reviews_stmt = mysqli_prepare($this->conn, $reviews_query);
        mysqli_stmt_bind_param($reviews_stmt, "i", $itemId);
        mysqli_stmt_execute($reviews_stmt);
        $reviews = mysqli_stmt_get_result($reviews_stmt);

        $recent_reviews = [];
        while ($review = mysqli_fetch_assoc($reviews)) {
            $recent_reviews[] = [
                'id' => $review['id'],
                'rating' => $review['rating'],
                'review' => $review['review'],
                'username' => $review['username'],
                'profile_image' => $review['profile_image'],
                'created_at' => $review['created_at'],
                'updated_at' => $review['updated_at']
            ];
        }

        return [
            'stats' => $stats,
            'recent_reviews' => $recent_reviews
        ];
    }

    public function getUserRating($userId, $itemId) {
        $query = "SELECT * FROM ratings WHERE user_id = ? AND item_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $userId, $itemId);
        mysqli_stmt_execute($stmt);
        return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }

    public function deleteRating($userId, $itemId) {
        $query = "DELETE FROM ratings WHERE user_id = ? AND item_id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $userId, $itemId);
        return mysqli_stmt_execute($stmt);
    }
}
?> 