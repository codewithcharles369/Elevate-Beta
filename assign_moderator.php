<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'];
    $user_id = $_POST['user_id'];
    $action = $_POST['action']; // 'assign' or 'remove'

    // Check if the logged-in user is the group admin
    $stmt = $pdo->prepare("
        SELECT role 
        FROM group_members 
        WHERE group_id = :group_id AND user_id = :user_id
    ");
    $stmt->execute(['group_id' => $group_id, 'user_id' => $_SESSION['user_id']]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        header("Location: group.php?id=$group_id&error=You are not authorized to perform this action");
        exit;
    }

    if ($action === 'assign') {
        // Assign the user as a moderator
        $stmt = $pdo->prepare("
            UPDATE group_members 
            SET role = 'moderator' 
            WHERE group_id = :group_id AND user_id = :user_id
        ");
    } elseif ($action === 'remove') {
        // Remove moderator privileges
        $stmt = $pdo->prepare("
            UPDATE group_members 
            SET role = 'member' 
            WHERE group_id = :group_id AND user_id = :user_id
        ");
    }

    $stmt->execute(['group_id' => $group_id, 'user_id' => $user_id]);
    header("Location: group.php?id=$group_id&success=Moderator status updated");
    exit;
}
?>