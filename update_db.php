<?php
require 'config/database/db.php';
$check_sqls = [
    "ALTER TABLE cart DROP FOREIGN KEY cart_ibfk_2", // drop the old foreign key constraint first if it relies on product_id
    "ALTER TABLE cart DROP FOREIGN KEY cart_product_id_fkey",
    "ALTER TABLE cart CHANGE product_id menu_item_id INT NOT NULL"
];
foreach ($check_sqls as $sql) {
    echo "Running: $sql\n";
    mysqli_query($conn, $sql);
    echo mysqli_error($conn) . "\n";
}
