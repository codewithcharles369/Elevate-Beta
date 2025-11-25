<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $replyId = intval($_POST['reply_id']);
    $newReply = trim($_POST['reply']);
    $userId = $_SESSION['user_id'];

    if (empty($newReply)) {
        echo json_encode(['success' => false, 'message' => 'Reply cannot be empty.']);
        exit;
    }

    // Verify ownership of the reply
    $stmt = $pdo->prepare("SELECT id FROM comment_replies WHERE id = ? AND user_id = ?");
    $stmt->execute([$replyId, $userId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to edit this reply.']);
        exit;
    }

    // Update the reply
    $stmt = $pdo->prepare("UPDATE comment_replies SET reply = ?,updated_at = NOW(), created_at = NOW() WHERE id = ?");
    if ($stmt->execute([$newReply, $replyId])) {
        echo json_encode(['success' => true, 'message' => 'Reply updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update reply.']);
    }
}
?>