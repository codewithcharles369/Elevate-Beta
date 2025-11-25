<?php
session_start();
require 'includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$comment_id = intval($data['comment_id']);
$user_id = $_SESSION['user_id'];
$reply = trim($data['reply']);

if (!$comment_id || empty($reply)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reply data.']);
    exit;
}

// Get the original commenter's ID and the post ID
$stmt = $pdo->prepare("SELECT user_id, post_id FROM group_post_comments WHERE id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch();

if (!$comment) {
    echo json_encode(['success' => false, 'message' => 'Comment not found.']);
    exit;
}

$original_commenter_id = $comment['user_id'];
$group_post_id = $comment['post_id'];

// Insert the reply
$stmt = $pdo->prepare("INSERT INTO group_comment_replies (comment_id, user_id, reply) VALUES (?, ?, ?)");
$stmt->execute([$comment_id, $user_id, $reply]);

// Fetch the newly inserted reply details
$stmt = $pdo->prepare("
    SELECT r.reply, r.created_at, u.id AS replier_id, u.name AS replier_name, u.profile_picture AS replier_picture
    FROM group_comment_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.comment_id = ?
    ORDER BY r.created_at DESC LIMIT 1
");
$stmt->execute([$comment_id]);
$new_reply = $stmt->fetch(PDO::FETCH_ASSOC);

// Get group ID
$stmt = $pdo->prepare("SELECT group_id FROM group_posts WHERE id = ?");
$stmt->execute([$group_post_id]);
$group = $stmt->fetch();

if (!$group) {
    echo json_encode(['success' => false, 'message' => 'Group not found.']);
    exit;
}

$group_id = $group['group_id'];

// Notify the original commenter if they are not the replier
if ($original_commenter_id != $user_id) {
    $message = "You have a new reply from " . $_SESSION['name'] . ".";
    $link = "group_posts.php?id=$group_id#comment-$comment_id";
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, sender_id, link, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([$original_commenter_id, $user_id, $link, $message]);
}

// Send reply data back to frontend
echo json_encode([
    'success' => true,
    'replier_id' => $new_reply['replier_id'],
    'replier_name' => $new_reply['replier_name'],
    'replier_picture' => $new_reply['replier_picture'] ?: 'default-user.jpg',
    'reply' => htmlspecialchars($new_reply['reply']),
    'created_at' => date('F j, Y, g:i a', strtotime($new_reply['created_at']))
]);
exit;
?>