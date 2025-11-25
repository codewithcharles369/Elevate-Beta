<?php
include 'includes/db.php'; // Database connection
session_start();

if ($_SESSION['role'] === 'Admin') {
    $user_id = $_GET['id'];

    // Promote user to admin
    $stmt = $pdo->prepare("UPDATE Users SET role = 'Admin' WHERE id = ?");
    $stmt->execute([$user_id]);

    header("Location: admin_users.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}