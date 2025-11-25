<?php
include 'includes/db.php'; // Include your database connection
session_start();

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin') {
    header("Location: login.php"); // Redirect non-admin users
    exit;
}

// Get the post ID from the query string
if (isset($_GET['id'])) {
    $post_id = intval($_GET['id']); // Ensure the ID is an integer

    // Start a transaction
    $pdo->beginTransaction();

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
        $tables = ['reports', 'post_views', 'post_tags', 'notifications', 'likes', 'bookmarks'];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE post_id = ?");
            $stmt->execute([$post_id]);
        }

        // Delete the post from the `posts` table
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);

        // Commit the transaction
        $pdo->commit();

        // Redirect to manage posts page with success message
        header("Location: admin_posts.php?message=Post deleted successfully");
        exit;
    } catch (Exception $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        echo "Error deleting the post: " . $e->getMessage();
    }
} else {
    // If no post ID is provided, redirect
    header("Location: admin_posts.php");
    exit;
}
?>