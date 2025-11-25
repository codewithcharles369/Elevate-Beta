<?php
require 'includes/db.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>