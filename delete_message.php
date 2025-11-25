<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$message_id = $data['message_id'];

// Ensure the message belongs to the user
$stmt = $pdo->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
$stmt->execute([$message_id, $user_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>