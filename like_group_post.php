<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in."]);
    exit;
}

$userId = $_SESSION['user_id'];
$postId = $_POST['post_id'] ?? null;

if (!$postId) {
    echo json_encode(["status" => "error", "message" => "Invalid post ID."]);
    exit;
}

// Check if the user already liked the post
$stmt = $pdo->prepare("SELECT * FROM group_post_likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$postId, $userId]);
$existingLike = $stmt->fetch();

if ($existingLike) {
    // Unlike the post
    $deleteStmt = $pdo->prepare("DELETE FROM group_post_likes WHERE post_id = ? AND user_id = ?");
    $deleteStmt->execute([$postId, $userId]);
    $liked = false;
} else {
    // Like the post
    $insertStmt = $pdo->prepare("INSERT INTO group_post_likes (post_id, user_id, created_at) VALUES (?, ?, NOW())");
    $insertStmt->execute([$postId, $userId]);
    $liked = true;
}

// Get updated like count
$countStmt = $pdo->prepare("SELECT COUNT(*) AS like_count FROM group_post_likes WHERE post_id = ?");
$countStmt->execute([$postId]);
$likeCount = $countStmt->fetchColumn();

echo json_encode(["status" => "success", "liked" => $liked, "like_count" => $likeCount]);