<?php
/**
 * PHP Extensions Diagnostic Script
 * Check what database extensions are available
 */

echo "=== PHP Extensions Diagnostic ===\n\n";

$extensions = [
    'mysqli' => 'MySQLi (MySQL Improved)',
    'pdo' => 'PDO (PHP Data Objects)',
    'pdo_mysql' => 'PDO MySQL Driver',
    'pdo_pgsql' => 'PDO PostgreSQL Driver',
    'mysql' => 'MySQL (Deprecated)',
];

echo "Available Extensions:\n";
echo "--------------------\n";

$available = [];
foreach ($extensions as $ext => $name) {
    $status = extension_loaded($ext) ? '✓' : '✗';
    echo "$status $name ($ext)\n";
    if (extension_loaded($ext)) {
        $available[] = $ext;
    }
}

echo "\n=== Recommendations ===\n\n";

if (extension_loaded('pdo_pgsql')) {
    echo "✓ PostgreSQL (PDO) is available - Use Neon PostgreSQL\n";
} elseif (extension_loaded('pdo_mysql')) {
    echo "✓ MySQL (PDO) is available - Use MySQL database\n";
} elseif (extension_loaded('mysqli')) {
    echo "✓ MySQLi is available - Use MySQL database\n";
} elseif (extension_loaded('pdo')) {
    echo "✓ PDO is available - Check for available drivers\n";
} else {
    echo "✗ No database extensions found!\n";
    echo "\nTo fix this, enable one of these in php.ini:\n";
    echo "  - extension=mysqli\n";
    echo "  - extension=pdo_mysql\n";
    echo "  - extension=pdo_pgsql\n";
}

echo "\n=== PHP Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "php.ini: " . php_ini_loaded_file() . "\n";
?>
