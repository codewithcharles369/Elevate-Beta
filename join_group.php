<?php
session_start();
require_once 'includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$userId = $_SESSION['user_id'];
$groupId = $data['groupId'];

try {
    // Check if already a member
    $stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$groupId, $userId]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Already a member']);
        exit;
    }

    // Add user to the group
    $stmt = $pdo->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
    $stmt->execute([$groupId, $userId]);

    // Fetch group admin and group name
    $stmt = $pdo->prepare("SELECT created_by, name FROM groups WHERE id = ?");
    $stmt->execute([$groupId]);
    $groupData = $stmt->fetch(PDO::FETCH_ASSOC);
    $groupAdmin = $groupData['created_by'];
    $groupName = $groupData['name'];

    // Send notification to group admin
    $message = "A new user has joined your group: " . htmlspecialchars($groupName);
    $link = "group.php?id=$groupId#$userId";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, link, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$groupAdmin, $userId, $link, $message]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}