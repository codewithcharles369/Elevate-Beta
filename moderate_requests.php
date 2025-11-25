<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'];
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    // Check if the logged-in user is a moderator or admin
    $stmt = $pdo->prepare("
        SELECT role 
        FROM group_members 
        WHERE group_id = :group_id AND user_id = :user_id
    ");
    $stmt->execute(['group_id' => $group_id, 'user_id' => $_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if (!in_array($role, ['admin', 'moderator'])) {
        header("Location: group.php?id=$group_id&error=You are not authorized to perform this action");
        exit;
    }

    if ($action === 'approve') {
        // Approve the join request
        $stmt = $pdo->prepare("
            INSERT INTO group_members (group_id, user_id, role) 
            SELECT group_id, user_id, 'member' 
            FROM group_requests 
            WHERE id = :request_id
        ");
        $stmt->execute(['request_id' => $request_id]);

        // Remove the request
        $deleteStmt = $pdo->prepare("DELETE FROM group_requests WHERE id = :request_id");
        $deleteStmt->execute(['request_id' => $request_id]);
    } elseif ($action === 'reject') {
        // Reject the join request
        $stmt = $pdo->prepare("DELETE FROM group_requests WHERE id = :request_id");
        $stmt->execute(['request_id' => $request_id]);
    }

    header("Location: group.php?id=$group_id&success=Request handled successfully");
    exit;
}
?>