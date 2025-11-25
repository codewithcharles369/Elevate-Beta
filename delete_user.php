<?php
include 'includes/db.php'; // Include your database connection
session_start();

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php"); // Redirect non-admin users
    exit;
}

// Get the user ID from the query string
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']); // Ensure the ID is an integer

    // Prevent admin users from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        echo "You cannot delete your own account.";
        exit;
    }

    // Start a transaction
    $pdo->beginTransaction();

    try {
        // Delete from the `follows` table (both follower_id and following_id)
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? OR following_id = ?");
        $stmt->execute([$user_id, $user_id]);

        // Delete from the `notifications` table (both user_id and sender_id)
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? OR sender_id = ?");
        $stmt->execute([$user_id, $user_id]);

        // Delete from the `messages` table (both sender_id and receiver_id)
        $stmt = $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
        $stmt->execute([$user_id, $user_id]);

         // Delete from the `messages` table (both sender_id and receiver_id)
         $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE user_id = ?");
         $stmt->execute([$user_id]);

        // Delete from all other tables using `user_id`
        $tables = [
            'bookmarks', 'comments',  'comment_replies',
             'group_members', 'group_posts', 
            'group_post_comments', 'group_post_likes',
            'likes', 'post_views', 
            'reports', 'todos'
        ];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }

        // Delete the user from the `users` table
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        // Commit the transaction
        $pdo->commit();

        // Redirect to manage users page with success message
        header("Location: admin_users.php?message=User deleted successfully");
        exit;
    } catch (Exception $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        echo "Error deleting the user: " . $e->getMessage();
    }
} else {
    // If no user ID is provided, redirect
    header("Location: admin_users.php");
    exit;
}
?>