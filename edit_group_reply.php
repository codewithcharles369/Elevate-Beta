<?php
session_start();
require 'includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$reply_id = intval($data['reply_id']);
$user_id = $_SESSION['user_id'];
$new_reply = trim($data['reply']);

if (!$reply_id || empty($new_reply)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

// Check if the user owns the reply
$stmt = $pdo->prepare("SELECT * FROM group_comment_replies WHERE id = ? AND user_id = ?");
$stmt->execute([$reply_id, $user_id]);
$reply = $stmt->fetch();

if (!$reply) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit;
}

// Update reply
$stmt = $pdo->prepare("UPDATE group_comment_replies SET reply = ? WHERE id = ?");
$stmt->execute([$new_reply, $reply_id]);

echo json_encode(['success' => true, 'updated_reply' => htmlspecialchars($new_reply)]);
exit;
?>