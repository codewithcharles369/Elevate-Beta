<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

$replyId = $_POST['reply_id'] ?? null;
$updatedReply = $_POST['reply'] ?? '';

if (!$replyId || empty(trim($updatedReply))) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit;
}

// Update the reply if the user owns it or is an admin
$stmt = $pdo->prepare("
    UPDATE group_comment_replies 
    SET reply = ?, created_at = NOW(), updated_at = NOW() 
    WHERE id = ? AND (user_id = ? OR EXISTS (
        SELECT 1 FROM users WHERE id = ? AND role = 'Admin'
    ))
");
$stmt->execute([$updatedReply, $replyId, $_SESSION['user_id'], $_SESSION['user_id']]);

if ($stmt->rowCount()) {
    echo json_encode(['status' => 'success', 'message' => 'Reply updated successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update reply.']);
}
?>