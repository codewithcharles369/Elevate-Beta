<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id']; // inviter
$group_id = $_POST['group_id'];
$invitee_id = $_POST['user_id'];
$action = $_POST['action'];

if ($action === 'invite') {
    // Send Notification
    $message = $_SESSION['name'] . " invited you to join a group!";
    $link = "group.php?id=$group_id";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, type, link) VALUES (?, ?, ?, 'group_invite', ?)");
    $stmt->execute([$invitee_id, $user_id, $message, $link]);
} elseif ($action === 'undo') {
    // Remove Notification (optional)
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND sender_id = ? AND type = 'group_invite'");
    $stmt->execute([$invitee_id, $user_id]);
}

echo json_encode(['status' => 'success']);
?>