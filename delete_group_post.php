<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id']) && isset($_POST['group_id'])) {
    $postId = $_POST['post_id'];
    $groupId = $_POST['group_id'];
    $userId = $_SESSION['user_id'];

    // Check if user is the post owner, admin, or moderator
    $stmt = $pdo->prepare("
        SELECT gp.user_id, gm.role
        FROM group_posts gp
        JOIN group_members gm ON gp.group_id = gm.group_id AND gm.user_id = ?
        WHERE gp.id = ?
    ");
    $stmt->execute([$userId, $postId]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post && ($post['user_id'] == $userId || $post['role'] === 'admin' || $post['role'] === 'moderator')) {
        $deleteStmt = $pdo->prepare("DELETE FROM group_posts WHERE id = ?");
        if ($deleteStmt->execute([$postId])) {
            $_SESSION['toast'] = 'Post deleted successfully!';
        } else {
            $_SESSION['toast'] = 'Failed to delete post.';
        }
    } else {
        $_SESSION['toast'] = 'You are not authorized to delete this post.';
    }
}

header("Location: group_posts.php?id=$groupId");
exit;
?>