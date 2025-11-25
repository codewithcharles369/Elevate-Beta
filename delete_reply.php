<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $replyId = intval($_POST['reply_id']);
    $userId = $_SESSION['user_id'];

    // Fetch the reply and verify authorization
    $stmt = $pdo->prepare("
        SELECT comment_replies.id, comment_replies.user_id, posts.user_id AS post_owner_id
        FROM comment_replies
        JOIN comments ON comment_replies.comment_id = comments.id
        JOIN posts ON comments.post_id = posts.id
        WHERE comment_replies.id = ?
    ");
    $stmt->execute([$replyId]);
    $reply = $stmt->fetch();

    if (!$reply) {
        echo json_encode(['success' => false, 'message' => 'Reply not found.']);
        exit;
    }

    // Check if the user is authorized to delete the reply
    if ($reply['user_id'] != $userId && $reply['post_owner_id'] != $userId) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to delete this reply.']);
        exit;
    }

    // Delete the reply
    $stmt = $pdo->prepare("DELETE FROM comment_replies WHERE id = ?");
    if ($stmt->execute([$replyId])) {
        echo json_encode(['success' => true, 'message' => 'Reply deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete reply.']);
    }
}
?>