<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit;
}

$commentId = $_POST['comment_id'] ?? null;
$reply = $_POST['reply'] ?? '';

if (!$commentId || empty(trim($reply))) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid reply.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO group_comment_replies (comment_id, user_id, reply, created_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$commentId, $_SESSION['user_id'], $reply]);

if ($stmt->rowCount()) {
    $newReplyId = $pdo->lastInsertId();
    $userStmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();

    echo json_encode(['status' => 'success', 'reply' => [
        'id' => $newReplyId,
        'user_id' => $_SESSION['user_id'],
        'name' => htmlspecialchars($user['name']),
        'profile_picture' => htmlspecialchars($user['profile_picture']),
        'content' => nl2br(htmlspecialchars($reply))
    ]]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add reply.']);
}
?>