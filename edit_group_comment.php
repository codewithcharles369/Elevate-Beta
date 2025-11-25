<?php
session_start();
require 'includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$comment_id = intval($data['comment_id']);
$user_id = $_SESSION['user_id'];
$new_comment = trim($data['comment']);

if (!$comment_id || empty($new_comment)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

// Check if the user owns the comment
$stmt = $pdo->prepare("SELECT * FROM group_post_comments WHERE id = ? AND user_id = ?");
$stmt->execute([$comment_id, $user_id]);
$comment = $stmt->fetch();

if (!$comment) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit;
}

// Update comment & mark as edited
$stmt = $pdo->prepare("UPDATE group_post_comments SET comment = ?, edited = 1 WHERE id = ?");
$stmt->execute([$new_comment, $comment_id]);

echo json_encode(['success' => true, 'updated_comment' => htmlspecialchars($new_comment)]);
exit;
?>