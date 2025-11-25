<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: my_posts.php");
    exit;
}

require 'includes/db.php';

if (isset($_GET['id'])) {
    $post_id = $_GET['id'];

    try {
        // Delete from `comments_likes` table (depends on comments)
        $stmt = $pdo->prepare("DELETE cl FROM comment_likes cl INNER JOIN comments c ON cl.comment_id = c.id WHERE c.post_id = ?");
        $stmt->execute([$post_id]);

        // Delete from `comment_replies` table (depends on comments)
        $stmt = $pdo->prepare("DELETE cr FROM comment_replies cr INNER JOIN comments c ON cr.comment_id = c.id WHERE c.post_id = ?");
        $stmt->execute([$post_id]);

        // Delete from `comments` table
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Delete from all other related tables using `post_id`
        $tables = ['reports', 'post_views', 'post_tags', 'likes', 'bookmarks'];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE post_id = ?");
            $stmt->execute([$post_id]);
        }

        // Check if the post belongs to the logged-in user
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Post deleted successfully!";
        } else {
            $_SESSION['error'] = "You do not have permission to delete this post.";
        }
    } catch (PDOException $e) {
        die("Error deleting post: " . $e->getMessage());
    }
}

header("Location: my_posts.php");
exit;
?>