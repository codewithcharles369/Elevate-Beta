<?php
session_start();
require 'includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$post_id = intval($data['post_id']);
$user_id = $_SESSION['user_id'];
$comment = trim($data['comment']);

if (!$post_id || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Invalid comment data.']);
    exit;
}

// Insert the comment
$stmt = $pdo->prepare("INSERT INTO group_post_comments (post_id, user_id, comment) VALUES (?, ?, ?)");
$stmt->execute([$post_id, $user_id, $comment]);
$comment_id = $pdo->lastInsertId();

// Get group ID
$stmt = $pdo->prepare("SELECT group_id FROM group_posts WHERE id = ?");
$stmt->execute([$post_id]);
$group = $stmt->fetch();

if (!$group) {
    echo json_encode(['success' => false, 'message' => 'Group not found.']);
    exit;
}

$group_id = $group['group_id'];

// Fetch group members (excluding the commenter)
$stmt = $pdo->prepare("SELECT user_id FROM group_members WHERE group_id = ? AND user_id != ?");
$stmt->execute([$group_id, $user_id]);
$group_members = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Notify each group member
$message = $_SESSION['name'] . " commented on a post in your group.";
$link = "group_posts.php?id=$group_id#comment-$comment_id";
foreach ($group_members as $member_id) {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, sender_id, link, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([$member_id, $user_id, $link, $message]);
}

// Fetch the newly inserted comment details
$stmt = $pdo->prepare("
    SELECT c.id, c.comment, c.created_at, u.name AS commenter_name, u.profile_picture AS commenter_picture
    FROM group_post_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$comment_id]);
$new_comment = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'comment_id' => $new_comment['id'],
    'commenter_name' => $new_comment['commenter_name'],
    'commenter_picture' => $new_comment['commenter_picture'] ?: 'default-user.jpg',
    'comment' => htmlspecialchars($new_comment['comment']),
    'created_at' => date('F j, Y, g:i a', strtotime($new_comment['created_at']))
]);
exit;
?>