<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/db.php';
require_once 'database_config.php';

// Function to connect to remote hotel_management database
function getRemoteHotelConnection() {
    $hotel_conn = mysqli_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS, REMOTE_DB_NAME);
    if ($hotel_conn) {
        mysqli_set_charset($hotel_conn, 'utf8mb4');
        error_log("Successfully connected to remote hotel_management database at " . REMOTE_DB_HOST);
        return $hotel_conn;
    } else {
        error_log("Failed to connect to remote hotel_management database at " . REMOTE_DB_HOST . ": " . mysqli_connect_error());
        return null;
    }
}

try {
    $all_ratings = [];
    
    // 1. Fetch ratings from food_ordering database (ratings table)
    $check_ratings = mysqli_query($conn, "SHOW TABLES LIKE 'ratings'");
    if (mysqli_num_rows($check_ratings) > 0) {
        $query = "SELECT 
                    r.id,
                    r.rating,
                    r.comment,
                    r.created_at,
                    r.order_id,
                    u.full_name as customer,
                    m.name as menu_item,
                    'local' as source,
                    'Food Order' as category,
                    'food_ordering' as database_name
                  FROM ratings r 
                  LEFT JOIN users u ON r.user_id = u.id 
                  LEFT JOIN menu_items m ON r.menu_item_id = m.id 
                  ORDER BY r.created_at DESC";
        
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $row['id'] = 'food_' . $row['id']; // Prefix to avoid ID conflicts
                $all_ratings[] = $row;
            }
            mysqli_free_result($result);
            error_log("Successfully fetched " . count($all_ratings) . " ratings from food_ordering");
        } else {
            error_log("Error fetching ratings from food_ordering: " . mysqli_error($conn));
        }
    } else {
        error_log("ratings table does not exist in food_ordering database");
    }
    
    // 2. Fetch ratings from hotel_management database (eatnrun_rating table) on remote server
    error_log("Attempting to connect to hotel_management database on " . REMOTE_DB_HOST . "...");
    $hotel_conn = getRemoteHotelConnection();
    if ($hotel_conn) {
        error_log("Successfully connected to hotel_management database on " . REMOTE_DB_HOST);
        $check_eatnrun_rating = mysqli_query($hotel_conn, "SHOW TABLES LIKE 'eatnrun_rating'");
        if (mysqli_num_rows($check_eatnrun_rating) > 0) {
            error_log("eatnrun_rating table found in hotel_management on " . REMOTE_DB_HOST);
            $hotel_query = "SELECT 
                              er.id,
                              er.rating,
                              er.comment,
                              er.created_at,
                              er.order_id,
                              'Hotel Guest' as customer,
                              'Hotel Food Service' as menu_item,
                              'hotel' as source,
                              'Hotel Food' as category,
                              'hotel_management' as database_name
                            FROM eatnrun_rating er 
                            ORDER BY er.created_at DESC";
            
            $hotel_result = mysqli_query($hotel_conn, $hotel_query);
            if ($hotel_result) {
                $hotel_count = 0;
                while ($row = mysqli_fetch_assoc($hotel_result)) {
                    $row['id'] = 'hotel_' . $row['id']; // Prefix to avoid ID conflicts
                    $all_ratings[] = $row;
                    $hotel_count++;
                }
                mysqli_free_result($hotel_result);
                error_log("Successfully fetched " . $hotel_count . " ratings from hotel_management on " . REMOTE_DB_HOST);
            } else {
                error_log("Error fetching ratings from hotel_management on " . REMOTE_DB_HOST . ": " . mysqli_error($hotel_conn));
            }
        } else {
            error_log("eatnrun_rating table does not exist in hotel_management on " . REMOTE_DB_HOST);
        }
        mysqli_close($hotel_conn);
    } else {
        error_log("Failed to connect to hotel_management database on localhost");
        error_log("FALLBACK: Using local database only. Hotel ratings will not be included.");
        
        // Add a fallback message to the response
        $all_ratings[] = [
            'id' => 'system_message',
            'rating' => 0,
            'comment' => '⚠️ Local database connection failed. Hotel ratings are not available.',
            'created_at' => date('Y-m-d H:i:s'),
            'order_id' => 'N/A',
            'customer' => 'System',
            'menu_item' => 'Connection Issue',
            'source' => 'system',
            'category' => 'System Message',
            'database_name' => 'connection_error'
        ];
    }
    
    // 3. Also fetch orders that haven't been rated yet from food_ordering
    $check_orders = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
    if (mysqli_num_rows($check_orders) > 0) {
        $orders_query = "SELECT 
                          o.id as order_id,
                          o.full_name as customer,
                          o.total_amount,
                          o.status,
                          o.created_at,
                          'Pending Rating' as category,
                          'order' as source,
                          'food_ordering' as database_name
                        FROM orders o 
                        WHERE o.is_rated = 0 AND o.status = 'completed'
                        ORDER BY o.created_at DESC
                        LIMIT 10";
        
        $orders_result = mysqli_query($conn, $orders_query);
        if ($orders_result) {
            $orders_count = 0;
            while ($row = mysqli_fetch_assoc($orders_result)) {
                $all_ratings[] = [
                    'id' => 'order_' . $row['order_id'],
                    'rating' => 0,
                    'comment' => 'Order completed - awaiting rating',
                    'created_at' => $row['created_at'],
                    'order_id' => $row['order_id'],
                    'customer' => $row['customer'],
                    'menu_item' => 'Order #' . $row['order_id'],
                    'source' => $row['source'],
                    'category' => $row['category'],
                    'database_name' => $row['database_name']
                ];
                $orders_count++;
            }
            mysqli_free_result($orders_result);
            error_log("Successfully fetched " . $orders_count . " pending orders from food_ordering");
        } else {
            error_log("Error fetching orders from food_ordering: " . mysqli_error($conn));
        }
    } else {
        error_log("orders table does not exist in food_ordering database");
    }
    
    // Sort all ratings by created_at (newest first)
    usort($all_ratings, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Calculate statistics
    $total_ratings = count($all_ratings);
    $pending_orders = count(array_filter($all_ratings, function($r) { return $r['source'] === 'order'; }));
    $hotel_ratings = count(array_filter($all_ratings, function($r) { return $r['source'] === 'hotel'; }));
    $food_ratings = count(array_filter($all_ratings, function($r) { return $r['source'] === 'local'; }));
    
    // Calculate average rating (excluding pending orders)
    $rated_items = array_filter($all_ratings, function($r) { return $r['rating'] > 0; });
    $average_rating = count($rated_items) > 0 ? 
        round(array_sum(array_column($rated_items, 'rating')) / count($rated_items), 1) : 0;
    
    error_log("API Summary: Total=" . $total_ratings . ", Food=" . $food_ratings . ", Hotel(Remote)=" . $hotel_ratings . ", Pending=" . $pending_orders . ", Avg=" . $average_rating);
    
    // Always return the data, even if there was a remote connection error
    $response = [
        'success' => count($all_ratings) > 0, // Success if we have any data
        'ratings' => $all_ratings,
        'statistics' => [
            'total_ratings' => $total_ratings,
            'pending_orders' => $pending_orders,
            'hotel_ratings' => $hotel_ratings,
            'food_ratings' => $food_ratings,
            'average_rating' => $average_rating
        ],
        'serverTime' => date('c'),
        'totalCount' => count($all_ratings)
    ];
    
    // Add error message if remote connection failed but we have local data
    if (count($all_ratings) > 0 && $hotel_ratings == 0) {
        $response['warning'] = 'Remote database connection failed, but local data is available.';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'serverTime' => date('c')
    ]);
}
?>
