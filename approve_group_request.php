<?php
require 'includes/db.php';
session_start();

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$requestUserId = $data['userId'];
$groupId = $data['groupId'];

// Add user as group member
$stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
$stmt->execute([$groupId, $requestUserId]);

// Remove join request
$pdo->prepare("DELETE FROM group_join_requests WHERE group_id = ? AND user_id = ?")->execute([$groupId, $requestUserId]);

// Send notification
$message = "Your request to join the group has been approved.";
$link = "group.php?id=$groupId";
$pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, link) VALUES (?, ?, ?, ?)")
    ->execute([$requestUserId, $userId, $message, $link]);

echo json_encode(['success' => true]);
?>