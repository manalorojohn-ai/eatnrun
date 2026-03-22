<?php
session_start();
require_once "../config/database.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Don't allow deleting the current admin
    if ($_GET['id'] != $_SESSION['user_id']) {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $_GET['id']]);
    }
}

header("Location: manage_users.php");
exit(); 