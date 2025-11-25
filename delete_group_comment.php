<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

$commentId = $_POST['comment_id'] ?? null;

if (!$commentId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid comment ID.']);
    exit;
}

// Check permissions: Owner, Admin, or Post Owner
$stmt = $pdo->prepare("
    DELETE FROM group_post_comments 
    WHERE id = ? AND (user_id = ? OR EXISTS (
        SELECT 1 FROM users WHERE id = ? AND role = 'Admin'
    ) OR EXISTS (
        SELECT 1 FROM group_posts WHERE id = group_post_comments.post_id AND user_id = ?
    ))
");
$stmt->execute([$commentId, $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);

if ($stmt->rowCount()) {
    echo json_encode(['status' => 'success', 'message' => 'Comment deleted.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete comment.']);
}
?>