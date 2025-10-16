<?php
require_once 'config/db.php';

try {
    // Create barangays table
    $barangays_table = "CREATE TABLE IF NOT EXISTS barangays (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!mysqli_query($conn, $barangays_table)) {
        throw new Exception("Error creating barangays table: " . mysqli_error($conn));
    }
    echo "Barangays table created successfully\n";

    // Create sitios table
    $sitios_table = "CREATE TABLE IF NOT EXISTS sitios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barangay_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!mysqli_query($conn, $sitios_table)) {
        throw new Exception("Error creating sitios table: " . mysqli_error($conn));
    }
    echo "Sitios table created successfully\n";

    // Insert default barangays if table is empty
    $check_barangays = mysqli_query($conn, "SELECT COUNT(*) as count FROM barangays");
    $row = mysqli_fetch_assoc($check_barangays);
    
    if ($row['count'] == 0) {
        $default_barangays = [
            'Santisima Cruz',
            'Bubukal',
            'Duhat',
            'Gatid',
            'Labuin',
            'Oogong',
            'Pagsawitan',
            'Patimbao',
            'San Pablo Norte',
            'San Pablo Sur',
            'Santo Angel Central',
            'Santo Angel Norte',
            'Santo Angel Sur'
        ];

        foreach ($default_barangays as $barangay) {
            $name = mysqli_real_escape_string($conn, $barangay);
            $insert_barangay = "INSERT INTO barangays (name) VALUES ('$name')";
            
            if (!mysqli_query($conn, $insert_barangay)) {
                throw new Exception("Error inserting barangay '$name': " . mysqli_error($conn));
            }
        }
        echo "Default barangays inserted successfully\n";
    }

    // Insert default sitios for each barangay if sitios table is empty
    $check_sitios = mysqli_query($conn, "SELECT COUNT(*) as count FROM sitios");
    $row = mysqli_fetch_assoc($check_sitios);
    
    if ($row['count'] == 0) {
        $default_sitios = [
            'Santisima Cruz' => ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5'],
            'Bubukal' => ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4'],
            'Duhat' => ['Purok 1', 'Purok 2', 'Purok 3'],
            'Gatid' => ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4'],
            'Labuin' => ['Purok 1', 'Purok 2', 'Purok 3'],
            'Oogong' => ['Purok 1', 'Purok 2', 'Purok 3'],
            'Pagsawitan' => ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4'],
            'Patimbao' => ['Purok 1', 'Purok 2', 'Purok 3'],
            'San Pablo Norte' => ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4'],
            'San Pablo Sur' => ['Purok 1', 'Purok 2', 'Purok 3'],
            'Santo Angel Central' => ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4'],
            'Santo Angel Norte' => ['Purok 1', 'Purok 2', 'Purok 3'],
            'Santo Angel Sur' => ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4']
        ];

        foreach ($default_sitios as $barangay => $sitios) {
            // Get barangay ID
            $barangay = mysqli_real_escape_string($conn, $barangay);
            $result = mysqli_query($conn, "SELECT id FROM barangays WHERE name = '$barangay'");
            
            if ($row = mysqli_fetch_assoc($result)) {
                $barangay_id = $row['id'];
                
                foreach ($sitios as $sitio) {
                    $sitio = mysqli_real_escape_string($conn, $sitio);
                    $insert_sitio = "INSERT INTO sitios (barangay_id, name) VALUES ($barangay_id, '$sitio')";
                    
                    if (!mysqli_query($conn, $insert_sitio)) {
                        throw new Exception("Error inserting sitio '$sitio': " . mysqli_error($conn));
                    }
                }
            }
        }
        echo "Default sitios inserted successfully\n";
    }

    echo "\nAll location tables created and populated successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        mysqli_close($conn);
    }
} 