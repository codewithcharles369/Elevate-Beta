<?php
include 'includes/db.php'; // Include your database connection
session_start();

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin') {
    header("Location: login.php"); // Redirect non-admin users
    exit;
}

// Check if comment ID is provided
if (isset($_GET['id'])) {
    $comment_id = intval($_GET['id']); // Sanitize the input

    // Delete the comment from the database
    $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ?");
    if ($stmt->execute([$comment_id])) {
        $stmt = $pdo->prepare("DELETE FROM comment_replies WHERE comment_id = ?");
        if ($stmt->execute([$comment_id])) {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            if ($stmt->execute([$comment_id])) {
                header("Location: admin_comments.php?message=Comment deleted successfully");
                exit;
            } else {
                header("Location: admin_comments.php?error=Could not delete the comment");
                exit;
            }
        } else {
            header("Location: admin_comments.php?error=Could not delete the comment");
            exit;
        }
    } else {
        header("Location: admin_comments.php?error=Could not delete the comment");
        exit;
    }
} else {
    header("Location: admin_comments.php?error=Invalid request");
    exit;
}
?>