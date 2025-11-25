<?php
include 'includes/db.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You need to log in to bookmark posts.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'];

// Check if the post is already bookmarked
$stmt = $pdo->prepare("SELECT * FROM bookmarks WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user_id, $post_id]);
$is_bookmarked = $stmt->rowCount() > 0;

if ($is_bookmarked) {
    // Remove bookmark
    $delete_stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $delete_stmt->execute([$user_id, $post_id]);
    echo json_encode(['success' => true, 'bookmarked' => false]);
} else {
    // Add bookmark
    $insert_stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)");
    $insert_stmt->execute([$user_id, $post_id]);
    echo json_encode(['success' => true, 'bookmarked' => true]);
}
?>