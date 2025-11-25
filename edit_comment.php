<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = intval($_POST['comment_id']);
    $newComment = trim($_POST['comment']);
    $userId = $_SESSION['user_id'];

    if (empty($newComment)) {
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty.']);
        exit;
    }

    // Verify ownership of the comment
    $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ? AND user_id = ?");
    $stmt->execute([$commentId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to edit this comment.']);
        exit;
    }

    // Update the comment
    $stmt = $pdo->prepare("UPDATE comments SET comment = ?, updated_at = NOW(), created_at = NOW() WHERE id = ?");
    if ($stmt->execute([$newComment, $commentId])) {
        echo json_encode(['success' => true, 'message' => 'Comment updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update comment.']);
    }
}
?>