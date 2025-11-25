<?php
include 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$sender_id = $data['sender_id'];
$receiver_id = $data['receiver_id'];

// Update all unread messages from the sender to the receiver as read
$stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$stmt->execute([$sender_id, $receiver_id]);

echo json_encode(['status' => 'success']);
?>