<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in."]);
    exit;
}

$userId = $_SESSION['user_id'];
$commentId = $_POST['comment_id'] ?? null;

if (!$commentId) {
    echo json_encode(["status" => "error", "message" => "Invalid comment ID."]);
    exit;
}

// Check if user already liked the comment
$stmt = $pdo->prepare("SELECT * FROM group_comment_likes WHERE comment_id = ? AND user_id = ?");
$stmt->execute([$commentId, $userId]);
$existingLike = $stmt->fetch();

if ($existingLike) {
    // Remove like
    $deleteStmt = $pdo->prepare("DELETE FROM group_comment_likes WHERE comment_id = ? AND user_id = ?");
    $deleteStmt->execute([$commentId, $userId]);
    $liked = false;
} else {
    // Add like
    $insertStmt = $pdo->prepare("INSERT INTO group_comment_likes (comment_id, user_id, created_at) VALUES (?, ?, NOW())");
    $insertStmt->execute([$commentId, $userId]);
    $liked = true;
}

// Get updated like count
$likeCountStmt = $pdo->prepare("SELECT COUNT(*) FROM group_comment_likes WHERE comment_id = ?");
$likeCountStmt->execute([$commentId]);
$likeCount = $likeCountStmt->fetchColumn();

echo json_encode(["status" => "success", "liked" => $liked, "like_count" => $likeCount]);