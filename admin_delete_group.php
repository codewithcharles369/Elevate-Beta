<?php
include 'includes/db.php';
session_start();

// Check if the user is an admin
if ($_SESSION['role'] !== 'Admin'){
    header("Location: dashboard.php");
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$groupId = intval($data['group_id']);

// Validate group existence
$groupStmt = $pdo->prepare("SELECT id, image FROM groups WHERE id = ?");
$groupStmt->execute([$groupId]);
$group = $groupStmt->fetch();

if (!$group) {
    echo json_encode(['success' => false, 'message' => 'Group not found.']);
    exit;
}

try {
    // Delete group members
    $pdo->prepare("DELETE FROM group_members WHERE group_id = ?")->execute([$groupId]);

    // Delete group posts and associated media
    $postsStmt = $pdo->prepare("SELECT media FROM group_posts WHERE group_id = ?");
    $postsStmt->execute([$groupId]);
    $posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as $post) {
        if (!empty($post['media']) && file_exists($post['media'])) {
            unlink($post['media']); // Delete media files
        }
    }
    $pdo->prepare("DELETE FROM group_posts WHERE group_id = ?")->execute([$groupId]);

    // Delete the group image
    if (!empty($group['image']) && file_exists($group['image'])) {
        unlink($group['image']);
    }

    // Delete the group
    $pdo->prepare("DELETE FROM groups WHERE id = ?")->execute([$groupId]);

    echo json_encode(['success' => true, 'message' => 'Group deleted successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the group.']);
}
?>