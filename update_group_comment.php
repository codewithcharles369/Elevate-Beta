<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$commentId = $_POST['comment_id'] ?? null;
$updatedComment = $_POST['comment'] ?? '';

if (!$commentId || empty(trim($updatedComment))) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE group_post_comments SET comment = ?, created_at = NOW(), updated_at = NOW() WHERE id = ? AND user_id = ?");
$stmt->execute([$updatedComment, $commentId, $_SESSION['user_id']]);

if ($stmt->rowCount()) {
    echo json_encode(['status' => 'success', 'message' => 'Comment updated.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update comment.']);
}
?>