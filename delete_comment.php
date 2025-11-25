<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = intval($_POST['comment_id']);
    $postId = intval($_POST['post_id']);
    $userId = $_SESSION['user_id'];

    // Fetch the comment and verify authorization
    $stmt = $pdo->prepare("
        SELECT comments.id, comments.user_id, posts.user_id AS post_owner_id
        FROM comments 
        JOIN posts ON comments.post_id = posts.id 
        WHERE comments.id = ?
    ");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();

    if (!$comment) {
        echo json_encode(['success' => false, 'message' => 'Comment not found.']);
        exit;
    }

    // Check if the user is authorized to delete the comment
    if ($comment['user_id'] != $userId && $comment['post_owner_id'] != $userId && $_SESSION['role'] != 'Admin') {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to delete this comment.']);
        exit;
    }

    // Add notification
    $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, link, message) VALUES (?, ?, ?, ?)");
    $message = "Deleted a comment from your post!";
    $link = "view_post2.php?id=$postId";
    $notificationStmt->execute([$comment['post_owner_id'], $userId, $link, $message]);
    

    // Delete the comment
    $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ?");
    $stmt->execute([$commentId]);

    $stmt = $pdo->prepare("DELETE FROM comment_replies WHERE comment_id = ?");
    $stmt->execute([$commentId]);

    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    if ($stmt->execute([$commentId])) {
        echo json_encode(['success' => true, 'message' => 'Comment deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment.']);
    }
}
?>