<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = intval($_POST['post_id']);
    $user_id = intval($_POST['user_id']);
    $comment = trim($_POST['comment']);

    if (!empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (:post_id, :user_id, :comment)");
        $stmt->execute([
            'post_id' => $post_id,
            'user_id' => $user_id,
            'comment' => $comment,
        ]);

        // Return the new comment data in the response
        echo json_encode(['status' => 'success', 'message' => 'Comment added successfully!', 'comment' => $comment]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Comment cannot be empty.']);
    }
}
?>