<?php
// Script to copy team photos to the correct location

// Source and destination paths
$sourceDir = '../assets/images/teams/';
$destDir = '../assets/images/team/';

// Create destination directory if it doesn't exist
if (!file_exists($destDir)) {
    mkdir($destDir, 0755, true);
    echo "Created destination directory: $destDir<br>";
}

// Array of team member photos
$teamMembers = [
    'anton' => 'Anton Ramos',
    'ken' => 'Ken Coladilla',
    'rojohn' => 'Rojohn Manalo',
    'jb' => 'JB Areza'
];

// Copy files
foreach ($teamMembers as $file => $name) {
    // Look for the file with any extension
    $extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $found = false;
    
    foreach ($extensions as $ext) {
        $sourcePath = $sourceDir . $file . '.' . $ext;
        if (file_exists($sourcePath)) {
            $destPath = $destDir . $file . '.' . $ext;
            if (copy($sourcePath, $destPath)) {
                echo "Successfully copied {$name}'s photo ($file.$ext)<br>";
                $found = true;
                break;
            } else {
                echo "Failed to copy {$name}'s photo. Check permissions.<br>";
            }
        }
    }
    
    if (!$found) {
        echo "Could not find {$name}'s photo in the source directory.<br>";
    }
}

echo "<br><a href='../about.php'>Go back to About page</a>";
?> 