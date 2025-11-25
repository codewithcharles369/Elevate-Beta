<?php
session_start();
require 'includes/db.php';

$sender_id = $_GET['sender_id'];
$receiver_id = $_GET['receiver_id'];

// Fetch messages along with all reactions and the replied message content
$stmt = $pdo->prepare("
    SELECT m.*, 
           GROUP_CONCAT(CONCAT(r.user_id, ':', r.reaction)) AS reactions,
           reply.message AS reply_to_message
    FROM messages m
    LEFT JOIN reactions r ON m.id = r.message_id
    LEFT JOIN messages reply ON m.reply_to = reply.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
       OR (m.sender_id = ? AND m.receiver_id = ?)
    GROUP BY m.id
    ORDER BY m.created_at ASC
");
$stmt->execute([$sender_id, $receiver_id, $receiver_id, $sender_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);