<?php
include 'includes/db.php';
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'];
$userId = intval($data['userId']);
$groupId = intval($data['groupId']);
$currentUserId = $_SESSION['user_id'];

// Check if the current user is an admin
$checkAdminStmt = $pdo->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$checkAdminStmt->execute([$groupId, $currentUserId]);
$currentUserRole = $checkAdminStmt->fetchColumn();

if ($currentUserRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only admins can manage members.']);
    exit;
}

try {
    // Fetch group name for the notification
    $groupNameStmt = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
    $groupNameStmt->execute([$groupId]);
    $groupName = $groupNameStmt->fetchColumn();

    if ($action === 'promote') {
        $stmt = $pdo->prepare("UPDATE group_members SET role = 'moderator' WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $userId]);

        // Add a notification
        $notification = "You have been promoted to moderator in the group '$groupName'.";
        $link = "group.php?id=$groupId#$userId";
        $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, link) VALUES (?, ?, ?, ?)");
        $notificationStmt->execute([$userId, $currentUserId, $notification, $link]);

        echo json_encode(['success' => true, 'message' => 'Member promoted to moderator.']);
    } elseif ($action === 'demote') {
        $stmt = $pdo->prepare("UPDATE group_members SET role = 'member' WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $userId]);

        // Add a notification
        $notification = "You have been demoted to a member in the group '$groupName'.";
        $link = "group.php?id=$groupId#$userId";
        $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, link) VALUES (?, ?, ?, ?)");
        $notificationStmt->execute([$userId, $currentUserId, $notification, $link]);

        echo json_encode(['success' => true, 'message' => 'Moderator demoted to member.']);
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $userId]);

        // Add a notification
        $notification = "You have been removed from the group '$groupName'.";
        $link = "group.php?id=$groupId#$userId";
        $notificationStmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, link) VALUES (?, ?, ?, ?)");
        $notificationStmt->execute([$userId, $currentUserId, $notification, $link]);

        echo json_encode(['success' => true, 'message' => 'Member removed from group.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}
?>