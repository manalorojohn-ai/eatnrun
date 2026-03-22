<?php
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['barangay_id'])) {
    echo json_encode(['error' => 'Barangay ID is required']);
    exit;
}

$barangay_id = intval($_GET['barangay_id']);

$query = "SELECT id, name FROM sitios WHERE barangay_id = ? ORDER BY name";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $barangay_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$sitios = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sitios[] = $row;
}

echo json_encode($sitios);
mysqli_close($conn);
?> 