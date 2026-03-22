<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
}

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    die('Order ID is required');
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

try {
    // Get order details with items
    $order_query = "SELECT o.*, u.full_name, u.email
                   FROM orders o
                   LEFT JOIN users u ON o.user_id = u.id
                   WHERE o.id = ? AND o.user_id = ?";

    $stmt = mysqli_prepare($conn, $order_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare order query: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute order query: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        die('Order not found or unauthorized access');
    }

    $order = mysqli_fetch_assoc($result);

    // Get order items
    $items_query = "SELECT od.quantity, mi.name, od.price
                   FROM order_details od
                   JOIN menu_items mi ON od.menu_item_id = mi.id
                   WHERE od.order_id = ?";
    
    $stmt = mysqli_prepare($conn, $items_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare items query: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $order_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute items query: ' . mysqli_stmt_error($stmt));
    }

    $items_result = mysqli_stmt_get_result($stmt);
    $items = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = $item;
    }

    // Generate HTML content
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Order Receipt #' . $order_id . '</title>
        <style>
            body { 
                font-family: Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 20px;
            }
            .logo {
                max-width: 200px;
                height: auto;
                margin-bottom: 15px;
            }
            .header { 
                text-align: center;
                margin-bottom: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .info { 
                margin-bottom: 30px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            table { 
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
                background: #fff;
            }
            th, td { 
                border: 1px solid #ddd;
                padding: 12px;
                text-align: left;
            }
            th { 
                background-color: #f8f9fa;
                font-weight: bold;
            }
            .total { 
                text-align: right;
                margin-top: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            .footer { 
                text-align: center;
                margin-top: 40px;
                padding: 20px;
                font-style: italic;
                border-top: 1px solid #ddd;
            }
            .amount {
                font-weight: bold;
                color: #006C3B;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="assets/images/logo.png" alt="Eat&Run Logo" class="logo">
            <h1 style="color: #006C3B; margin: 0;">Order Receipt</h1>
            <p style="font-size: 1.2em; margin: 10px 0;">Order #' . $order_id . '</p>
            <p style="color: #666;">' . date('F d, Y h:i A', strtotime($order['created_at'])) . '</p>
        </div>
        
        <div class="info">
            <h2 style="color: #006C3B; margin-top: 0;">Customer Information</h2>
            <p><strong>Name:</strong> ' . htmlspecialchars($order['full_name']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($order['email']) . '</p>
            <p><strong>Delivery Address:</strong> ' . htmlspecialchars($order['delivery_address']) . '</p>
            <p><strong>Phone:</strong> ' . htmlspecialchars($order['phone']) . '</p>
        </div>
        
        <h2 style="color: #006C3B;">Order Details</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>';

    foreach ($items as $item) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($item['name']) . '</td>
                <td style="text-align: center;">' . $item['quantity'] . '</td>
                <td style="text-align: right;">₱' . number_format($item['price'], 2) . '</td>
                <td style="text-align: right;">₱' . number_format($item['price'] * $item['quantity'], 2) . '</td>
            </tr>';
    }

    $html .= '
        </table>
        
        <div class="total">
            <p><strong>Subtotal:</strong> <span class="amount">₱' . number_format($order['subtotal'], 2) . '</span></p>
            <p><strong>Delivery Fee:</strong> <span class="amount">₱' . number_format($order['delivery_fee'], 2) . '</span></p>
            <p style="font-size: 1.2em;"><strong>Total Amount:</strong> <span class="amount">₱' . number_format($order['total_amount'], 2) . '</span></p>
        </div>
        
        <div class="footer">
            <p>Thank you for ordering with Eat&Run!</p>
            <p>For any questions, please contact our support.</p>
        </div>
    </body>
    </html>';

    // Create temporary file for HTML content with .html extension
    $temp_html_file = tempnam(sys_get_temp_dir(), 'receipt_') . '.html';
    file_put_contents($temp_html_file, $html);

    // Create temporary file for PDF output with .pdf extension
    $temp_pdf_file = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';

    // Get absolute path to logo
    $logo_path = realpath(__DIR__ . '/assets/images/logo.png');
    
    // Update HTML with absolute path to logo
    $html = str_replace('src="assets/images/logo.png"', 'src="' . str_replace('\\', '/', $logo_path) . '"', $html);
    file_put_contents($temp_html_file, $html);

    // Set up the wkhtmltopdf command
    $wkhtmltopdf_path = 'C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe';
    
    // Check if wkhtmltopdf exists
    if (!file_exists($wkhtmltopdf_path)) {
        throw new Exception('wkhtmltopdf not found at: ' . $wkhtmltopdf_path);
    }

    // Log paths for debugging
    error_log("HTML file: " . $temp_html_file);
    error_log("PDF file: " . $temp_pdf_file);
    error_log("Logo path: " . $logo_path);
    
    // Build the command with proper options
    $command = sprintf(
        'powershell -Command "& \'%s\' --quiet --enable-local-file-access --page-size A4 --margin-top 10 --margin-bottom 10 --margin-left 10 --margin-right 10 \'%s\' \'%s\'" 2>&1',
        $wkhtmltopdf_path,
        str_replace('\\', '/', $temp_html_file),
        str_replace('\\', '/', $temp_pdf_file)
    );

    // Log command for debugging
    error_log("Command: " . $command);

    // Execute the command
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);

    // Log output for debugging
    error_log("Command output: " . implode("\n", $output));
    error_log("Return code: " . $return_var);

    if ($return_var !== 0 || !file_exists($temp_pdf_file) || filesize($temp_pdf_file) === 0) {
        error_log("PDF Generation Error: " . implode("\n", $output));
        throw new Exception('Failed to generate PDF. Error code: ' . $return_var . "\nOutput: " . implode("\n", $output));
    }

    // Read the PDF file
    $pdf_content = file_get_contents($temp_pdf_file);
    if ($pdf_content === false) {
        throw new Exception('Failed to read generated PDF file');
    }

    // Clean up temporary files
    @unlink($temp_html_file);
    @unlink($temp_pdf_file);

    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="order_receipt_' . $order_id . '.pdf"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output the PDF
    echo $pdf_content;
    exit();

} catch (Exception $e) {
    error_log("Receipt generation error: " . $e->getMessage());
    
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Error Generating Receipt</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 50px;
                background: #f8f9fa;
            }
            .error-container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #dc3545;
                margin-bottom: 20px;
            }
            .error-details {
                color: #666;
                margin: 20px 0;
                text-align: left;
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                font-family: monospace;
                white-space: pre-wrap;
                word-break: break-all;
            }
            .back-link {
                color: #006C3B;
                text-decoration: none;
                font-weight: bold;
                display: inline-block;
                margin-top: 20px;
            }
            .back-link:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>Error Generating Receipt</h1>
            <p>An error occurred while generating your receipt.</p>
            <div class="error-details">' . htmlspecialchars($e->getMessage()) . '</div>
            <a href="orders.php" class="back-link">← Back to Orders</a>
        </div>
    </body>
    </html>';
    exit();
}
?> 