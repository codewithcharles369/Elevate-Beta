<?php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['poster_id'])) {
    $post_id = $_POST['post_id'];
    $poster_id = $_POST['poster_id'];
    $user_id = $_SESSION['user_id'];

    // Check if the user already liked the post
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        // Unlike the post
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        if ($user_id != $poster_id) {
        // Add notification
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND sender_id = ? AND post_id = ? AND message = ?");
        $stmt->execute([$poster_id, $user_id, $post_id, "Liked your post!"]);}
        $liked = false;
    } else {
        // Like the post
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $user_id]);
        if ($user_id != $poster_id) {
        // Add notification
        $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, link, message) VALUES (?, ?, ?, ?)");
        $message = "Liked your post!";
        $link = "view_post2.php?id=$post_id";
        $notificationStmt->execute([$poster_id, $user_id, $link, $message]);}
        $liked = true;
    }

    // Get updated like count
    $stmt = $pdo->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $like_count = $stmt->fetchColumn();

    echo json_encode(['status' => 'success', 'liked' => $liked, 'like_count' => $like_count]);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>