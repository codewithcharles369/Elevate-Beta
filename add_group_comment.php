<?php
include 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to comment.']);
    exit;
}

$postId = $_POST['post_id'] ?? null;
$comment = $_POST['comment'] ?? '';

if (!$postId || empty(trim($comment))) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid comment.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO group_post_comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$postId, $_SESSION['user_id'], $comment]);

if ($stmt->rowCount()) {
    $newCommentId = $pdo->lastInsertId();
    $userStmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();

    echo json_encode(['status' => 'success', 'comment' => [
        'id' => $newCommentId,
        'user_id' => $_SESSION['user_id'],
        'name' => htmlspecialchars($user['name']),
        'profile_picture' => htmlspecialchars($user['profile_picture']),
        'content' => nl2br(htmlspecialchars($comment))
    ]]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to add comment.']);
}
?>