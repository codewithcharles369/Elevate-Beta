<?php
include 'includes/db.php';
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$postId = intval($data['post_id']);
$userId = $_SESSION['user_id'];

// Validate post existence
$postStmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$postStmt->execute([$postId]);
if (!$postStmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit;
}

// Increment the share count
$updateStmt = $pdo->prepare("UPDATE posts SET share_count = share_count + 1 WHERE id = ?");
$updateStmt->execute([$postId]);

echo json_encode(['success' => true, 'message' => 'Share count updated successfully.']);