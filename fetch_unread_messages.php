<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id']; // Logged-in user

// Query to get unread messages grouped by sender with the latest message
$stmt = $pdo->prepare("
    SELECT 
        messages.sender_id, 
        users.name AS sender_name, 
        users.profile_picture, 
        COUNT(*) AS unread_count, 
        MAX(messages.created_at) AS latest_message_time
    FROM messages
    JOIN users ON messages.sender_id = users.id
    WHERE messages.receiver_id = ? AND messages.is_read = 0
    GROUP BY messages.sender_id, users.name, users.profile_picture
    ORDER BY latest_message_time DESC
");

$stmt->execute([$user_id]);
$unread_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['unread_messages' => $unread_messages]);
?>