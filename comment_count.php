<?php
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'] ?? null;

    if ($post_id) {
        // Get the updated comment count
        $stmt = $pdo->prepare("SELECT COUNT(*) AS comment_count FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $comment_count = $stmt->fetchColumn();

        // Return the updated comment count as JSON
        echo json_encode(['status' => 'success', 'comment_count' => $comment_count]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }
}