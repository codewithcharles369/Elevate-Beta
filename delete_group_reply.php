<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

$replyId = $_POST['reply_id'] ?? null;

if (!$replyId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid reply ID.']);
    exit;
}

// Check permissions: Owner, Admin, or Post Owner
$stmt = $pdo->prepare("
    DELETE FROM group_comment_replies 
    WHERE id = ? AND (user_id = ? OR EXISTS (
        SELECT 1 FROM users WHERE id = ? AND role = 'Admin'
    ) OR EXISTS (
        SELECT 1 FROM group_posts 
        JOIN group_post_comments ON group_post_comments.post_id = group_posts.id
        WHERE group_post_comments.id = group_comment_replies.comment_id AND group_posts.user_id = ?
    ))
");
$stmt->execute([$replyId, $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);

if ($stmt->rowCount()) {
    echo json_encode(['status' => 'success', 'message' => 'Reply deleted.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete reply.']);
}
?>