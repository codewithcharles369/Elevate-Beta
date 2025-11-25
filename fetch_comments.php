<?php
require 'includes/db.php';
session_start();

$post_id = $_GET['post_id'];
$sort = $_GET['sort'] ?? 'newest';

$orderBy = 'comments.created_at DESC'; // Default: Newest
if ($sort === 'oldest') {
    $orderBy = 'comments.created_at ASC';
} elseif ($sort === 'liked') {
    $orderBy = 'like_count DESC';
}

$stmt = $pdo->prepare("
    SELECT comments.*, users.name, users.profile_picture,
    (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id) AS like_count,
    (SELECT COUNT(*) FROM comment_likes WHERE comment_likes.comment_id = comments.id AND comment_likes.user_id = ?) AS user_liked
    FROM comments
    JOIN users ON comments.user_id = users.id
    WHERE comments.post_id = ?
    ORDER BY $orderBy
");
$stmt->execute([$_SESSION['user_id'], $post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($comments);
?>