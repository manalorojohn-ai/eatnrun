<?php
// URL of the latest PHPMailer release
$url = 'https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip';
$zipFile = 'phpmailer.zip';

// Download the file
if (file_put_contents($zipFile, file_get_contents($url))) {
    echo "Downloaded PHPMailer successfully\n";
    
    // Create PHPMailer directory if it doesn't exist
    if (!file_exists('PHPMailer')) {
        mkdir('PHPMailer');
    }
    if (!file_exists('PHPMailer/src')) {
        mkdir('PHPMailer/src');
    }
    
    // Extract using ZipArchive
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        // Extract only the src files we need
        for($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (strpos($filename, '/src/') !== false && 
                (strpos($filename, 'Exception.php') !== false ||
                 strpos($filename, 'PHPMailer.php') !== false ||
                 strpos($filename, 'SMTP.php') !== false)) {
                     
                $contents = $zip->getFromIndex($i);
                $newname = 'PHPMailer/src/' . basename($filename);
                file_put_contents($newname, $contents);
                echo "Extracted: $newname\n";
            }
        }
        $zip->close();
        echo "Files extracted successfully\n";
        
        // Delete the zip file
        unlink($zipFile);
        echo "Cleanup completed\n";
    } else {
        echo "Failed to open $zipFile\n";
    }
} else {
    echo "Failed to download PHPMailer\n";
}
?> 