<?php
include 'includes/db.php'; // Include database connection
session_start();

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin'){
    header("Location: login.php");
    exit;
}

// Check if notification ID is provided
if (isset($_GET['id'])) {
    $notification_id = intval($_GET['id']); // Sanitize the input

    // Mark the report as resolved in the database
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    if ($stmt->execute([$notification_id])) {
        header("Location: admin_notifications.php?message=Notification Marked as Read");
        exit;
    } else {
        header("Location: admin_notifications.php?error=Could not mark the notification");
        exit;
    }
} else {
    header("Location: admin_notifications.php?error=Invalid request");
    exit;
}
?>