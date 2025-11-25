<?php
include 'includes/db.php';

$sender_id = $_POST['sender_id'];
$receiver_id = $_POST['receiver_id'];
$is_typing = $_POST['is_typing']; // Boolean: 1 if typing, 0 if not

// Store typing status temporarily (e.g., in-memory caching)
$stmt = $pdo->prepare("UPDATE typing_status SET is_typing = ? WHERE sender_id = ? AND receiver_id = ?");
$stmt->execute([$is_typing, $sender_id, $receiver_id]);

// If no row exists, insert a new one
if ($stmt->rowCount() === 0) {
    $stmt = $pdo->prepare("INSERT INTO typing_status (sender_id, receiver_id, is_typing) VALUES (?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $is_typing]);
}

echo json_encode(['success' => true]);
?>