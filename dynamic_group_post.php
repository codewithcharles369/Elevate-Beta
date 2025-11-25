<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'];

if ($action === 'create_post') {
    // Handle post creation
    $content = $data['content'];
    $group_id = $data['group_id'];
    $user_id = $_SESSION['user_id'];

    // Validate membership
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$group_id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'error' => 'You must be a member to post.']);
        exit;
    }

    // Insert the post
    $stmt = $pdo->prepare("INSERT INTO group_posts (group_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$group_id, $user_id, $content]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'fetch_posts') {
    // Handle fetching posts
    $group_id = $data['group_id'];

    $stmt = $pdo->prepare("
        SELECT gp.content, gp.created_at, u.name, u.profile_picture 
        FROM group_posts gp
        JOIN users u ON gp.user_id = u.id
        WHERE gp.group_id = ?
        ORDER BY gp.created_at DESC
    ");
    $stmt->execute([$group_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'posts' => $posts]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);