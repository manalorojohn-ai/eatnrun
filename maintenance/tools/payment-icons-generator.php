<?php
// Script to generate payment method icons

// Directory to save icons
$outputDir = 'assets/images/payment-icons';

// Create directory if it doesn't exist
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "Created directory: $outputDir<br>";
}

// Function to create and save an icon
function createIcon($filename, $text, $backgroundColor, $textColor, $iconData = null) {
    global $outputDir;
    
    // Image dimensions
    $width = 120;
    $height = 60;
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Allocate colors
    $bgColor = imagecolorallocate($image, 
        hexdec(substr($backgroundColor, 0, 2)),
        hexdec(substr($backgroundColor, 2, 2)),
        hexdec(substr($backgroundColor, 4, 2))
    );
    
    $txtColor = imagecolorallocate($image, 
        hexdec(substr($textColor, 0, 2)),
        hexdec(substr($textColor, 2, 2)),
        hexdec(substr($textColor, 4, 2))
    );
    
    // Fill background
    imagefill($image, 0, 0, $bgColor);
    
    // Add border
    $borderColor = imagecolorallocate($image, 230, 230, 230);
    imagerectangle($image, 0, 0, $width-1, $height-1, $borderColor);
    
    // Add text
    $fontSize = 4;
    $fontWidth = imagefontwidth($fontSize);
    $fontHeight = imagefontheight($fontSize);
    $textWidth = $fontWidth * strlen($text);
    $textX = ($width - $textWidth) / 2;
    $textY = ($height - $fontHeight) / 2;
    
    // If we have icon data, draw it
    if ($iconData) {
        // For this simple example, just add a symbol before the text
        $text = $iconData . ' ' . $text;
    }
    
    // Add text to image
    imagestring($image, $fontSize, $textX, $textY, $text, $txtColor);
    
    // Save the image
    $filePath = "$outputDir/$filename.png";
    imagepng($image, $filePath);
    imagedestroy($image);
    
    echo "Created icon: $filePath<br>";
    return $filePath;
}

// Create Cash on Delivery icon
createIcon('cash-on-delivery', 'Cash on Delivery', 'f8f9fa', '333333', '$');

// Create GCash icon
createIcon('gcash', 'GCash', '007BFE', 'ffffff', 'G');

// Create Half Payment icon
createIcon('half-payment', 'Half Payment', 'FFD166', '333333', '½');

// Create a QR code icon
createIcon('qr-code', 'Scan QR', 'ffffff', '333333', '□');

echo "<p>All payment icons created successfully!</p>";
echo "<p><a href='checkout.php'>Go to checkout page</a></p>";
?> 