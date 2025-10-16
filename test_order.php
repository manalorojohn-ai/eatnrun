<?php
require_once 'config/db.php';

try {
    // Start transaction
    mysqli_begin_transaction($conn);

    // Simple test insert
    $query = "INSERT INTO orders (user_id, full_name, email, phone, delivery_address, payment_method, status) 
              VALUES (1, 'Anton Ramos', 'miguelantonioramos140@gmail.com', '1234567890', '123 Test St', 'cod', 'pending')";

    if (!mysqli_query($conn, $query)) {
        throw new Exception("Insert failed: " . mysqli_error($conn));
    }

    $order_id = mysqli_insert_id($conn);
    echo "Order inserted successfully with ID: " . $order_id . "\n";

    // Commit transaction
    mysqli_commit($conn);

    // Verify the order was inserted
    $verify_query = "SELECT * FROM orders WHERE id = " . $order_id;
    $result = mysqli_query($conn, $verify_query);
    $order = mysqli_fetch_assoc($result);

    if ($order) {
        echo "Order verification successful:\n";
        print_r($order);
    } else {
        echo "Order verification failed\n";
    }

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    mysqli_close($conn);
} 