<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$comment_id = $_POST['comment_id'];
$post_id = $_POST['post_id'];
$reply = $_POST['reply'];

// Insert reply
$stmt = $pdo->prepare("INSERT INTO comment_replies (user_id, comment_id, reply) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $comment_id, $reply]);



// Fetch the reply details
$stmt = $pdo->prepare("
    SELECT comment_replies.*, users.name, users.profile_picture 
    FROM comment_replies 
    JOIN users ON comment_replies.user_id = users.id 
    WHERE comment_replies.id = LAST_INSERT_ID()
");
$stmt->execute();
$new_reply = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the author of the comment
$stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment_author_id = $stmt->fetchColumn();

// Add a notification for the comment's author
if ($comment_author_id && $comment_author_id != $user_id) {
    $notification_message = "Replied to your Comment.";
    $link = "view_post2.php?id=$post_id#$comment_id";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, link, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$comment_author_id, $user_id, $link, $notification_message]);
}

echo json_encode(['success' => true, 'reply' => $new_reply]);
?>