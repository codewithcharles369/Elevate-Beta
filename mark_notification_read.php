<?php
include 'includes/db.php';

$notification_id = $_POST['notification_id'];

// Mark the notification as read
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
$stmt->execute([$notification_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Notification marked as read.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read.']);
}
?>