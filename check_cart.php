<?php
require_once 'config/database/db.php';
$res = mysqli_query($conn, 'SHOW COLUMNS FROM cart');
$columns = [];
while($row = mysqli_fetch_assoc($res)) {
    $columns[] = $row;
}
echo json_encode($columns, JSON_PRETTY_PRINT);
