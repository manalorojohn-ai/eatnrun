<?php
// Script to properly copy team photos to the correct location

// Config
$sourceDir = 'C:/xampp/htdocs/online-food-ordering/assets/images/teams/';
$destDir = 'C:/xampp/htdocs/online-food-ordering/assets/images/team/';

// Create destination directory if it doesn't exist
if (!file_exists($destDir)) {
    mkdir($destDir, 0755, true);
    echo "Created destination directory: $destDir<br>";
}

// Array of team members and their image files
$teamMembers = [
    'anton' => ['name' => 'Anton Ramos', 'file' => 'anton.jpg'],
    'ken' => ['name' => 'Ken Coladilla', 'file' => 'ken.jpg'],
    'rojohn' => ['name' => 'Rojohn Manalo', 'file' => 'rojohn.jpg'],
    'jb' => ['name' => 'JB Areza', 'file' => 'jb.jpg']
];

// Copy team photos
foreach ($teamMembers as $key => $member) {
    $sourcePath = $sourceDir . $member['file'];
    $destPath = $destDir . $member['file'];
    
    if (file_exists($sourcePath)) {
        if (copy($sourcePath, $destPath)) {
            echo "Successfully copied {$member['name']}'s photo<br>";
        } else {
            echo "Failed to copy {$member['name']}'s photo. Check permissions.<br>";
        }
    } else {
        echo "Could not find {$member['name']}'s photo at $sourcePath<br>";
    }
}

// Create a default avatar if it doesn't exist
$defaultAvatarPath = 'C:/xampp/htdocs/online-food-ordering/assets/images/default-avatar.png';

if (!file_exists($defaultAvatarPath)) {
    // Create a simple default avatar image
    $width = 200;
    $height = 200;
    $defaultImg = imagecreatetruecolor($width, $height);
    
    // Set background color (light gray)
    $bgColor = imagecolorallocate($defaultImg, 240, 240, 240);
    imagefill($defaultImg, 0, 0, $bgColor);
    
    // Set circle color (dark gray)
    $circleColor = imagecolorallocate($defaultImg, 200, 200, 200);
    
    // Draw head circle
    imagefilledellipse($defaultImg, $width/2, $height/2 - 15, 120, 120, $circleColor);
    
    // Draw body
    imagefilledellipse($defaultImg, $width/2, $height + 20, 140, 160, $circleColor);
    
    // Save the image
    imagepng($defaultImg, $defaultAvatarPath);
    imagedestroy($defaultImg);
    
    echo "Created default avatar image at $defaultAvatarPath<br>";
}

echo "<br>Process completed. <a href='/online-food-ordering/about.php'>Go to About page</a>";
?> 