<?php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id'], $_POST['comment'], $_POST['poster_id'])) {
    $post_id = $_POST['post_id'];
    $poster_id = $_POST['poster_id'];
    $user_id = $_SESSION['user_id'];
    $comment = $_POST['comment'];

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $user_id, $comment]);
    $comment_id = $pdo->lastInsertId();

    if ($user_id != $poster_id) {
       // Add notification
    $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, link, message) VALUES (?, ?, ?, ?)");
    $message = "Commented `$comment` On Your Post";
    $link = "view_post2.php?id=$post_id#$comment_id";
    $notificationStmt->execute([$poster_id, $user_id, $link, $message]);
    }

    echo json_encode(['status' => 'success', 'comment' => htmlspecialchars($comment)]);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>