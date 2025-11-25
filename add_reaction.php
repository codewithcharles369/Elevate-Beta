<?php
session_start();
require 'includes/db.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION['user_id'];
    $message_id = $data['message_id'] ?? null;
    $reaction = $data['reaction'] ?? null;

    if ($message_id && $reaction) {
        // Check if the user already reacted to the message
        $stmt = $pdo->prepare("SELECT id FROM reactions WHERE message_id = ? AND user_id = ?");
        $stmt->execute([$message_id, $user_id]);
        $existingReaction = $stmt->fetch();

        if ($existingReaction) {
            // Update existing reaction
            $stmt = $pdo->prepare("UPDATE reactions SET reaction = ?, created_at = NOW() WHERE id = ?");
            $stmt->execute([$reaction, $existingReaction['id']]);
        } else {
            // Insert new reaction
            $stmt = $pdo->prepare("INSERT INTO reactions (message_id, user_id, reaction, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$message_id, $user_id, $reaction]);
        }

        $response['success'] = true;
        $response['message'] = 'Reaction added successfully.';
    } else {
        $response['message'] = 'Invalid data received.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>