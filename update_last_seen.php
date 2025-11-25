<?php
include 'includes/db.php';

session_start();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
$stmt->execute([$user_id]);

echo json_encode(['status' => 'success']);
?>