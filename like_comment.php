<?php
include 'includes/db.php';
session_start();

$comment_id = $_POST['comment_id'];
$post_id = $_POST['post_id'];
$user_id = $_SESSION['user_id']; // ID of the user performing the action

// Check if the user already liked the comment
$stmt = $pdo->prepare("SELECT * FROM comment_likes WHERE user_id = ? AND comment_id = ?");
$stmt->execute([$user_id, $comment_id]);
$is_liked = $stmt->rowCount();

if ($is_liked) {
    // Unlike the comment
    $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
    $stmt->execute([$user_id, $comment_id]);

    // Fetch the author of the comment
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment_author_id = $stmt->fetchColumn();

    // Delete a notification for the comment's author
    if ($comment_author_id && $comment_author_id != $user_id) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND sender_id = ? AND post_id = ? AND comment_id = ? AND message = ?");
        $stmt->execute([$comment_author_id, $user_id, $post_id, $comment_id, "Your comment was liked."]);
    }
} else {
    // Like the comment
    $stmt = $pdo->prepare("INSERT INTO comment_likes (user_id, comment_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $comment_id]);

    // Fetch the author of the comment
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment_author_id = $stmt->fetchColumn();
    

    // Add a notification for the comment's author
    if ($comment_author_id && $comment_author_id != $user_id) {
        $notification_message = "Your comment was liked.";
        $link = "view_post2.php?id=$post_id#$comment_id";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, link, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$comment_author_id, $user_id, $link, $notification_message]);
    }
}

// Get updated like count
$stmt = $pdo->prepare("SELECT COUNT(*) AS like_count FROM comment_likes WHERE comment_id = ?");
$stmt->execute([$comment_id]);
$like_count = $stmt->fetchColumn();

echo json_encode(['success' => true, 'like_count' => $like_count, 'liked' => !$is_liked]);?>