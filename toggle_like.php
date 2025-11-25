<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];

// Check if user already liked the post
$stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user_id, $post_id]);
$like = $stmt->fetch();

if ($like) {
    // Unlike
    $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $liked = false;
} else {
    // Like
    $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $post_id]);
    $liked = true;
}

// Get updated like count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$countStmt->execute([$post_id]);
$like_count = $countStmt->fetchColumn();

echo json_encode(['liked' => $liked, 'like_count' => $like_count]);
?>