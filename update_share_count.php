<?php
require 'includes/db.php';
session_start();

$post_id = $_POST['post_id'];

// Increment share count
$stmt = $pdo->prepare("UPDATE posts SET share_count = share_count + 1 WHERE id = ?");
$stmt->execute([$post_id]);

// Get updated share count
$stmt = $pdo->prepare("SELECT share_count FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$share_count = $stmt->fetchColumn();

echo json_encode(['share_count' => $share_count]);
?>