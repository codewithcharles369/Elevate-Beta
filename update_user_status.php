<?php
include 'includes/db.php';

session_start();
$user_id = $_SESSION['user_id']; // Ensure user ID is stored in the session

// Update the user's last_active timestamp
$stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
$stmt->execute([$user_id]);

echo json_encode(['status' => 'success']);
?>