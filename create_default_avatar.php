<?php
// Create a default avatar image

// Set path
$avatarPath = 'assets/images/default-avatar.png';

// Check if the directory exists, if not create it
$dir = dirname($avatarPath);
if (!file_exists($dir)) {
    mkdir($dir, 0755, true);
}

// Create image
$width = 200;
$height = 200;
$image = imagecreatetruecolor($width, $height);

// Colors
$background = imagecolorallocate($image, 238, 245, 239); // Light green background
$primary = imagecolorallocate($image, 0, 108, 59);       // Primary green color
$accent = imagecolorallocate($image, 255, 193, 7);      // Accent color

// Fill background
imagefill($image, 0, 0, $background);

// Draw a rounded avatar background
imagefilledellipse($image, $width/2, $height/2, 150, 150, $primary);

// Draw simple silhouette
imagefilledellipse($image, $width/2, $height/2 - 30, 60, 60, $accent); // Head
imagefilledrectangle($image, $width/2 - 40, $height/2 + 5, $width/2 + 40, $height/2 + 70, $accent); // Body

// Save the image
imagepng($image, $avatarPath);
imagedestroy($image);

echo "Default avatar created at: {$avatarPath}";
?> 