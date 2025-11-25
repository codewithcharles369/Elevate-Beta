<?php
include 'includes/db.php';
session_start();

// Mark the notification as read
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Notification marked as read.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read.']);
}
?>