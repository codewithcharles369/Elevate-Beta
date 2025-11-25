<?php
include 'includes/db.php';

$user_id = $_POST['user_id'];
$notification_id = $_POST['notification_id'];

// Activate the user's account
$stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
$stmt->execute([$user_id]);

if ($stmt->rowCount() > 0) {
    // Mark the notification as read
    $mark_stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $mark_stmt->execute([$notification_id]);

    echo json_encode(['success' => true, 'message' => 'Account activated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to activate account.']);
}
?>