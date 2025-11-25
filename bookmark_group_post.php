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

// Check if the post is already bookmarked
$stmt = $pdo->prepare("SELECT * FROM group_post_bookmarks WHERE post_id = ? AND user_id = ?");
$stmt->execute([$postId, $userId]);
$existingBookmark = $stmt->fetch();

if ($existingBookmark) {
    // Remove bookmark
    $deleteStmt = $pdo->prepare("DELETE FROM group_post_bookmarks WHERE post_id = ? AND user_id = ?");
    $deleteStmt->execute([$postId, $userId]);
    $bookmarked = false;
} else {
    // Add bookmark
    $insertStmt = $pdo->prepare("INSERT INTO group_post_bookmarks (post_id, user_id, created_at) VALUES (?, ?, NOW())");
    $insertStmt->execute([$postId, $userId]);
    $bookmarked = true;
}

// Get updated bookmark count
$countStmt = $pdo->prepare("SELECT COUNT(*) AS bookmark_count FROM group_post_bookmarks WHERE post_id = ?");
$countStmt->execute([$postId]);
$bookmarkCount = $countStmt->fetchColumn();

echo json_encode(["status" => "success", "bookmarked" => $bookmarked, "bookmark_count" => $bookmarkCount]);