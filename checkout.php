<?php
session_start();
require_once 'config/db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check and fix database structure
require_once 'db_structure_check.php';
$db_issues = check_db_structure($conn);

// If there are unfixed issues, redirect to the structure check page
if (!empty($db_issues) && array_filter($db_issues, function($issue) { 
    return strpos($issue, "Fixed") === false && strpos($issue, "exists") === false; 
})) {
    header("Location: db_structure_check.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$total = 0;
$error = null;

try {
    // Get user details first
    $user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    // Check if user profile is complete
    if (empty($user['full_name']) || empty($user['email'])) {
        $_SESSION['error'] = "Please complete your profile before checkout. We need your full name and email address.";
        header("Location: profile.php");
        exit();
    }

// Get cart items with menu_item_id included
$cart_query = "SELECT c.id as cart_id, c.menu_item_id, c.quantity, m.name, m.price, 
               (m.price * c.quantity) as subtotal 
               FROM cart c 
               JOIN menu_items m ON c.menu_item_id = m.id 
               WHERE c.user_id = ?";

$stmt = mysqli_prepare($conn, $cart_query);
    if (!$stmt) {
        throw new Exception("Failed to prepare cart query: " . mysqli_error($conn));
    }

mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to execute cart query: " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
while ($item = mysqli_fetch_assoc($result)) {
    $cart_items[] = $item;
    $total += $item['subtotal'];
}
mysqli_stmt_close($stmt);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// Fetch barangays
$barangays_query = "SELECT * FROM barangays ORDER BY name";
$barangays_result = mysqli_query($conn, $barangays_query);
$barangays = [];
while ($row = mysqli_fetch_assoc($barangays_result)) {
    $barangays[] = $row;
}

// Function to get sitios by barangay ID
function getSitios($conn, $barangay_id) {
    $sitios_query = "SELECT * FROM sitios WHERE barangay_id = ? ORDER BY name";
    $stmt = mysqli_prepare($conn, $sitios_query);
    mysqli_stmt_bind_param($stmt, "i", $barangay_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $sitios = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sitios[] = $row;
    }
    return $sitios;
}

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        mysqli_begin_transaction($conn);

    try {
        // Validate required fields
            $required_fields = ['delivery_address', 'payment_method', 'phone'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required");
                }
            }

            $delivery_address = mysqli_real_escape_string($conn, trim($_POST['delivery_address']));
            $payment_method = mysqli_real_escape_string($conn, trim($_POST['payment_method']));
            $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
            $notes = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));

        // Validate payment proof for GCash methods
            $payment_proof_path = null;
            if (in_array($payment_method, ['gcash', 'half_payment'])) {
            if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Please upload your payment screenshot");
            }

            // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $_FILES['payment_proof']['tmp_name']);
            finfo_close($file_info);

                if (!in_array($mime_type, $allowed_types)) {
                throw new Exception("Please upload a valid image file (JPG, PNG, or GIF)");
            }

            // Validate file size (max 5MB)
            if ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size should be less than 5MB");
        }

                // Process payment proof
            $upload_dir = 'uploads/payment_proofs/';
            if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            $file_name = uniqid('payment_') . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target_file)) {
                throw new Exception("Failed to upload payment proof");
            }
            
            $payment_proof_path = $target_file;
        }

            // Verify cart items and prices
        foreach ($cart_items as $item) {
                $verify_query = "SELECT price FROM menu_items WHERE id = ? FOR UPDATE";
            $stmt = mysqli_prepare($conn, $verify_query);
            mysqli_stmt_bind_param($stmt, "i", $item['menu_item_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 0) {
                throw new Exception("Some items in your cart are no longer available");
            }
            
            $current_price = mysqli_fetch_assoc($result)['price'];
            if ($current_price != $item['price']) {
                throw new Exception("Prices have changed. Please review your cart");
            }
        }

            // Calculate total with delivery fee
            $delivery_fee = 50.00;
        $total_with_delivery = $total + $delivery_fee;

            // Format delivery address
            $street = mysqli_real_escape_string($conn, trim($_POST['street']));
            $landmarks = mysqli_real_escape_string($conn, trim($_POST['landmarks'] ?? ''));
            $barangay_id = mysqli_real_escape_string($conn, trim($_POST['barangay']));
            $sitio_id = mysqli_real_escape_string($conn, trim($_POST['sitio']));

            // Get barangay and sitio names
            $location_query = "SELECT b.name as barangay_name, s.name as sitio_name 
                             FROM barangays b 
                             LEFT JOIN sitios s ON s.id = ? 
                             WHERE b.id = ?";
            $loc_stmt = mysqli_prepare($conn, $location_query);
            mysqli_stmt_bind_param($loc_stmt, "ii", $sitio_id, $barangay_id);
            mysqli_stmt_execute($loc_stmt);
            $location_result = mysqli_stmt_get_result($loc_stmt);
            $location_data = mysqli_fetch_assoc($location_result);
            mysqli_stmt_close($loc_stmt);

            // Build complete address
            $delivery_address_parts = [];
            if ($street) $delivery_address_parts[] = $street;
            if ($location_data['sitio_name']) $delivery_address_parts[] = $location_data['sitio_name'];
            if ($location_data['barangay_name']) $delivery_address_parts[] = $location_data['barangay_name'];
            if ($landmarks) $delivery_address_parts[] = "Landmarks: " . $landmarks;
            
            $delivery_address = implode(", ", $delivery_address_parts);

            // Prepare notes
            $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, trim($_POST['notes'])) : '';
            $delivery_notes = isset($_POST['delivery_notes']) ? mysqli_real_escape_string($conn, trim($_POST['delivery_notes'])) : '';
            
            // Combine notes if both exist
            if ($notes && $delivery_notes) {
                $final_notes = "Order Notes: " . $notes . "\nDelivery Notes: " . $delivery_notes;
            } else {
                $final_notes = $notes ?: $delivery_notes;
            }

            // Create order
            $order_query = "INSERT INTO orders (user_id, email, full_name, subtotal, total_amount, delivery_fee, delivery_address, 
               payment_method, payment_proof, notes, status, phone, created_at, updated_at) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = mysqli_prepare($conn, $order_query);
            if (!$stmt) {
                throw new Exception("Failed to prepare order statement: " . mysqli_error($conn));
            }

            // Create variables for binding
            $status = 'pending';
            $payment_proof_path = $payment_proof_path ?? null;
            $user_email = $user['email']; // Get email from user data
            $user_name = $user['full_name']; // Get full name from user data

            // Bind parameters for order creation
            if (!mysqli_stmt_bind_param($stmt, "issdddssssss", 
            $user_id, 
                $user_email,
                $user_name, 
                $total, 
                $total_with_delivery, 
            $delivery_fee,
            $delivery_address, 
            $payment_method, 
            $payment_proof_path, 
                $final_notes, 
            $status,
                $phone)) {
                throw new Exception("Failed to bind order parameters: " . mysqli_stmt_error($stmt));
            }
        
        if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to create order: " . mysqli_stmt_error($stmt));
        }
        
        $order_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Add order details
            $detail_query = "INSERT INTO order_details (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)";
            $detail_stmt = mysqli_prepare($conn, $detail_query);
            if (!$detail_stmt) {
                throw new Exception("Failed to prepare detail statement: " . mysqli_error($conn));
            }

        foreach ($cart_items as $item) {
                $item_id = $item['menu_item_id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                
                if (!mysqli_stmt_bind_param($detail_stmt, "iiid", 
                    $order_id, 
                    $item_id, 
                    $quantity, 
                    $price)) {
                    throw new Exception("Failed to bind detail parameters: " . mysqli_stmt_error($detail_stmt));
                }

                if (!mysqli_stmt_execute($detail_stmt)) {
                    throw new Exception("Failed to add order details: " . mysqli_stmt_error($detail_stmt));
                }
            }
            mysqli_stmt_close($detail_stmt);

        // Clear cart
        $clear_cart = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = mysqli_prepare($conn, $clear_cart);
            if (!mysqli_stmt_bind_param($clear_stmt, "i", $user_id)) {
                throw new Exception("Failed to bind clear cart parameter");
            }
            if (!mysqli_stmt_execute($clear_stmt)) {
                throw new Exception("Failed to clear cart");
            }
            mysqli_stmt_close($clear_stmt);

            // Create notifications
            $user_notification = "Your order #$order_id has been placed successfully!";
            $admin_notification = "New order #$order_id received from " . $user['full_name'];

            $notification_query = "INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, ?, NOW())";
            $notify_stmt = mysqli_prepare($conn, $notification_query);
            if (!$notify_stmt) {
                throw new Exception("Failed to prepare notification statement: " . mysqli_error($conn));
            }

            // User notification
            $notify_user_id = $user_id;
            $notify_message = $user_notification;
            $notification_type = 'order';
            if (!mysqli_stmt_bind_param($notify_stmt, "iss", $notify_user_id, $notify_message, $notification_type)) {
                throw new Exception("Failed to bind user notification parameters");
            }
            if (!mysqli_stmt_execute($notify_stmt)) {
                throw new Exception("Failed to create user notification");
            }

            // Admin notification
            $admin_id = 1; // Assuming admin user_id is 1
            $notify_message = $admin_notification;
            if (!mysqli_stmt_bind_param($notify_stmt, "iss", $admin_id, $notify_message, $notification_type)) {
                throw new Exception("Failed to bind admin notification parameters");
            }
            if (!mysqli_stmt_execute($notify_stmt)) {
                throw new Exception("Failed to create admin notification");
            }

            mysqli_stmt_close($notify_stmt);
        mysqli_commit($conn);

        // Redirect to success page
        header("Location: order_success.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        
            // Remove uploaded file if exists
        if (isset($payment_proof_path) && file_exists($payment_proof_path)) {
            unlink($payment_proof_path);
        }
            
            $error = $e->getMessage();
            error_log("Order failed: " . $error);
    }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Checkout error: " . $error);
}

// Add this after the closing brace of the catch block to better display the error message
$errorClass = isset($error) ? 'error-message' : '';
$errorStyle = isset($error) ? 'display: flex; align-items: center;' : 'display: none;';
$errorIcon = '<i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>';
$errorMsg = isset($error) ? $errorIcon . htmlspecialchars($error) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Eat&Run</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #006C3B;
            --primary-light: #e8f5e9;
            --primary-dark: #005530;
            --text-color: #2d3436;
            --border-color: #e0e0e0;
            --success-color: #4CAF50;
            --gcash-blue: #0066CC;
            --shadow-sm: 0 4px 10px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 6px 16px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 12px 28px rgba(0, 0, 0, 0.08);
            --gradient-primary: linear-gradient(135deg, #006C3B 0%, #00875A 100%);
            --gradient-light: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--gradient-light);
            min-height: 100vh;
            color: var(--text-color);
        }

        .checkout-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2.5rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.8s cubic-bezier(0.25, 0.1, 0.25, 1) forwards;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2.5rem;
            align-items: start;
        }

        .section-title {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
            font-weight: 600;
            text-align: center;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .section-title:hover::after {
            width: 120px;
        }

        h2 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .checkout-form {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            transition: all 0.4s ease;
        }

        .checkout-form:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
            font-size: 0.95rem;
        }

        input[type="text"],
        input[type="email"],
        textarea,
        select {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 108, 59, 0.1);
            outline: none;
        }

        textarea {
            height: 120px;
            resize: vertical;
        }

        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 1rem;
        }

        .payment-option {
            display: flex;
            align-items: center;
            padding: 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            position: relative;
        }

        .payment-option:hover {
            border-color: var(--primary-color);
            background: #f9f9f9;
            transform: translateY(-2px);
        }

        .payment-option.selected {
            border-color: var(--primary-color);
            background: var(--primary-light);
        }

        .payment-option img {
            width: 28px;
            height: 28px;
            margin-right: 15px;
            transition: transform 0.3s ease;
        }

        .payment-option:hover img {
            transform: scale(1.1);
        }

        .payment-option-text {
            flex: 1;
        }

        .payment-option-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-color);
        }

        .payment-option-description {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .payment-option input[type="radio"] {
            margin-left: 1rem;
            transform: scale(1.2);
            accent-color: var(--primary-color);
        }

        .gcash-payment-panel {
            display: none;
            margin-top: 1.5rem;
            background: #f9f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .gcash-qr-section {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 2rem;
        }

        .qr-code-container {
            text-align: center;
            margin: 20px auto;
            max-width: 300px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .qr-code-container img {
            max-width: 100%;
            height: auto;
        }
        
        .qr-code-container h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .merchant-info {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .merchant-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .merchant-number {
            color: #555;
            font-size: 0.9em;
        }

        .payment-instructions {
            flex: 1;
        }

        .amount-to-pay {
            background: #e8f5e9;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid rgba(0, 108, 59, 0.1);
        }

        .amount-to-pay h4 {
            color: var(--primary-color);
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }

        .amount-to-pay .amount {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .payment-steps {
            list-style: none;
            counter-reset: step;
        }

        .payment-steps li {
            position: relative;
            padding: 0.75rem 0 0.75rem 2.5rem;
            margin-bottom: 0.5rem;
            counter-increment: step;
            color: #333;
            font-size: 0.95rem;
        }

        .payment-steps li::before {
            content: counter(step);
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .payment-steps li:hover::before {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 10px 25px rgba(var(--bs-primary-rgb), 0.3);
        }

        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--primary-color);
            color: white;
            padding: 0.875rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .upload-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .upload-btn i {
            font-size: 1.1rem;
        }

        .selected-file {
            margin-top: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--primary-light);
            border-radius: 6px;
            border-left: 3px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
            display: none;
        }

        .place-order-btn {
            width: 100%;
            padding: 1.25rem;
            margin-top: 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .place-order-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 108, 59, 0.2);
        }

        .order-summary {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 2rem;
            transition: all 0.4s ease;
        }

        .order-summary:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }

        .summary-header {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-color);
        }

        .cart-items {
            margin-bottom: 1.5rem;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .cart-item:hover {
            transform: translateX(5px);
            border-color: var(--primary-color);
        }

        .subtotal, .delivery-fee {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            color: #666;
        }

        .total {
            display: flex;
            justify-content: space-between;
            padding: 1.25rem 0;
            margin-top: 0.75rem;
            border-top: 2px solid var(--border-color);
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 992px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
                margin-top: 2rem;
            }
        }

        @media (max-width: 768px) {
            .checkout-container {
                padding: 0 1.5rem;
                margin: 2rem auto;
            }

            .gcash-qr-section {
                grid-template-columns: 1fr;
            }

            .qr-code-container {
                margin: 0 auto 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .checkout-form, .order-summary {
                padding: 1.5rem;
            }

            .section-title {
                font-size: 1.75rem;
            }
        }

        /* Error message styling */
        .error-message {
            display: flex;
            align-items: center;
            background: #ffeeee;
            color: #e74c3c;
            padding: 0.8rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #e74c3c;
            font-size: 0.95rem;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.1);
            animation: fadeInDown 0.4s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Add styles for barangay and sitio selects */
        .address-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }

        select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <?php if (isset($error)): ?>
    <div class="error-banner">
        <div class="container">
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="checkout-container">
        <h1 class="section-title">Checkout</h1>

        <div class="checkout-grid">
            <div class="checkout-form">
                <h2>Delivery Details</h2>
                <form method="POST" enctype="multipart/form-data" id="checkoutForm">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
        </div>

                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Delivery Location <span class="required">*</span></label>
                        <div class="address-grid">
                            <div>
                                <select id="barangaySelect" name="barangay" required>
                                                    <option value="">Select Barangay</option>
                                                    <?php foreach ($barangays as $barangay): ?>
                                                        <option value="<?php echo $barangay['id']; ?>">
                                                            <?php echo htmlspecialchars($barangay['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                            <div>
                                <select id="sitioSelect" name="sitio" required disabled>
                                                    <option value="">Select Sitio/Purok</option>
                                                </select>
                                            </div>
                                        </div>
                                            </div>

                    <div class="form-group">
                        <label for="street">Street Address <span class="required">*</span></label>
                        <input type="text" id="street" name="street" required placeholder="House number and street name">
                                        </div>

                    <div class="form-group">
                                                <label for="landmarks">Landmarks (Optional)</label>
                        <textarea id="landmarks" name="landmarks" placeholder="Nearby landmarks or additional directions"></textarea>
                                            </div>

                                    <input type="hidden" id="complete_address" name="delivery_address" required>

                    <div class="form-group">
                        <label>Payment Method <span class="required">*</span></label>
                        <div class="payment-methods">
                            <div class="payment-option" data-method="cod">
                                                    <img src="assets/images/payment-icons/cash-on-delivery.svg" alt="COD">
                                <div class="payment-option-text">
                                    <div class="payment-option-title">Cash on Delivery</div>
                                    <div class="payment-option-description">Pay with cash upon delivery</div>
                                </div>
                                <input type="radio" name="payment_method" value="cod" required>
                            </div>

                            <div class="payment-option" data-method="half_payment">
                                                    <img src="assets/images/payment-icons/half-payment.svg" alt="Half Payment">
                                <div class="payment-option-text">
                                    <div class="payment-option-title">Half Payment</div>
                                    <div class="payment-option-description">Pay 50% now via GCash, 50% upon delivery</div>
                                </div>
                                <input type="radio" name="payment_method" value="half_payment" required>
                            </div>

                            <div class="payment-option" data-method="gcash">
                                                    <img src="assets/images/payment-icons/gcash.svg" alt="GCash">
                                <div class="payment-option-text">
                                    <div class="payment-option-title">GCash</div>
                                    <div class="payment-option-description">Pay the full amount via GCash</div>
                                </div>
                                <input type="radio" name="payment_method" value="gcash" required>
                            </div>
                        </div>

                        <div class="gcash-payment-panel" id="gcashPanel">
                            <div class="gcash-qr-section">
                                <div class="merchant-details">
                                    <h3>Scan to Pay</h3>
                                    <div class="qr-code-container">
                                        <img src="assets/images/gcash-qr-code.png" alt="GCash QR Code" class="qr-code">
                                        <div class="merchant-info">
                                            <p class="merchant-name">EatnRun Food Ordering</p>
                                            <p class="merchant-number">09123456789</p>
                                    </div>
                                    </div>
                                </div>

                                <div class="payment-instructions">
                                    <div class="amount-to-pay">
                                        <h4>Amount to Pay</h4>
                                                <div class="amount">₱<?php echo number_format($total + 50, 2); ?></div>
                                </div>

                                    <ol class="payment-steps">
                                        <li>Open your GCash app</li>
                                        <li>Scan the QR code or send to the number above</li>
                                        <li>Enter the exact amount shown</li>
                                        <li>Complete the payment</li>
                                        <li>Take a screenshot of your payment confirmation</li>
                                                </ol>

                                    <div class="proof-upload">
                                        <button type="button" class="upload-btn" id="uploadBtn">
                                            <i class="fas fa-upload"></i>
                                            Upload Payment Screenshot
                                        </button>
                                        <input type="file" id="paymentProof" name="payment_proof" accept="image/*" style="display: none;">
                                        <div id="selectedFile" class="selected-file"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Order Notes (Optional)</label>
                        <textarea id="notes" name="notes" placeholder="Special instructions for your order"></textarea>
                    </div>

                    <button type="submit" class="place-order-btn">
                                    <i class="fas fa-check-circle"></i>
                                    Place Order
                                </button>
                </form>
            </div>

            <div class="order-summary">
                <h2 class="summary-header">Order Summary</h2>
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                            <div>
                            <?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?>
                            </div>
                        <div>₱<?php echo number_format($item['subtotal'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="subtotal">
                    <div>Subtotal</div>
                    <div>₱<?php echo number_format($total, 2); ?></div>
                </div>
                <div class="delivery-fee">
                    <div>Delivery Fee</div>
                    <div>₱50.00</div>
                </div>
                <div class="total">
                    <div>Total</div>
                    <div>₱<?php echo number_format($total + 50, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentOptions = document.querySelectorAll('.payment-option');
            const gcashPanel = document.getElementById('gcashPanel');
            const fileInput = document.getElementById('paymentProof');
            const selectedFile = document.getElementById('selectedFile');
            const uploadBtn = document.getElementById('uploadBtn');
            const form = document.getElementById('checkoutForm');
            const barangaySelect = document.getElementById('barangaySelect');
            const sitioSelect = document.getElementById('sitioSelect');
            const completeAddressInput = document.getElementById('complete_address');

            // Initialize sitio select
            function initializeSitioSelect() {
                if (barangaySelect.value) {
                    sitioSelect.disabled = false;
                    fetchSitios(barangaySelect.value);
                } else {
                    sitioSelect.disabled = true;
                    sitioSelect.innerHTML = '<option value="">Select Sitio/Purok</option>';
                }
            }

            // Fetch sitios function
            function fetchSitios(barangayId) {
                    fetch(`get_sitios.php?barangay_id=${barangayId}`)
                        .then(response => response.json())
                        .then(data => {
                            sitioSelect.innerHTML = '<option value="">Select Sitio/Purok</option>';
                        if (Array.isArray(data)) {
                            data.forEach(sitio => {
                                const option = document.createElement('option');
                                option.value = sitio.id;
                                option.textContent = sitio.name;
                                sitioSelect.appendChild(option);
                            });
                            sitioSelect.disabled = false;
                }
                    })
                    .catch(error => {
                        console.error('Error fetching sitios:', error);
                        sitioSelect.innerHTML = '<option value="">Error loading sitios</option>';
            });
            }

            // Update complete address
            function updateCompleteAddress() {
                const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
                const sitio = sitioSelect.options[sitioSelect.selectedIndex]?.text || '';
                const street = document.getElementById('street').value;
                const landmarks = document.getElementById('landmarks').value;

                let address = '';
                if (street) address += street;
                if (sitio && sitio !== 'Select Sitio/Purok') address += `, ${sitio}`;
                if (barangay && barangay !== 'Select Barangay') address += `, ${barangay}`;
                if (landmarks) address += ` (${landmarks})`;

                completeAddressInput.value = address.trim();
            }

            // Event listeners for address fields
            barangaySelect.addEventListener('change', function() {
                if (this.value) {
                    fetchSitios(this.value);
                } else {
                    sitioSelect.disabled = true;
                    sitioSelect.innerHTML = '<option value="">Select Sitio/Purok</option>';
                }
                updateCompleteAddress();
            });

            sitioSelect.addEventListener('change', updateCompleteAddress);
            document.getElementById('street').addEventListener('input', updateCompleteAddress);
            document.getElementById('landmarks').addEventListener('input', updateCompleteAddress);

            // Payment method selection
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    paymentOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;

                    const method = this.dataset.method;
                    if (method === 'gcash' || method === 'half_payment') {
                        gcashPanel.style.display = 'block';
                    } else {
                        gcashPanel.style.display = 'none';
                    }
                });
            });

            // File upload handling
            uploadBtn.addEventListener('click', () => fileInput.click());

            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const fileName = this.files[0].name;
                    selectedFile.textContent = fileName;
                    selectedFile.style.display = 'block';
                } else {
                    selectedFile.style.display = 'none';
                }
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });

            // Initialize on page load
            initializeSitioSelect();
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html> 