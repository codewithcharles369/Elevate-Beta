<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];

$stmt = $pdo->prepare("SELECT * FROM bookmarks WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user_id, $post_id]);
$bookmark = $stmt->fetch();

if ($bookmark) {
    // Remove bookmark
    $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
    $bookmarked = false;
} else {
    // Add bookmark
    $stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $post_id]);
    $bookmarked = true;
}

echo json_encode(['bookmarked' => $bookmarked]);
?>