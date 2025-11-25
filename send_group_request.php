<?php
require 'includes/db.php';
session_start();

$userId = $_SESSION['user_id'];
$groupId = $_POST['groupId'];

$stmt = $pdo->prepare("INSERT IGNORE INTO group_join_requests (group_id, user_id) VALUES (?, ?)");
$stmt->execute([$groupId, $userId]);

echo json_encode(['success' => true]);
?>