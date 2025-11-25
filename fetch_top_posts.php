<?php
session_start();
include "includes/db.php";

$user_id = $_SESSION['user_id'];

// Fetch top 5 posts based on likes, comments, and views
$stmt = $pdo->prepare("
    SELECT p.id, p.title, 
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS likes,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comments,
           (SELECT COUNT(*) FROM post_views WHERE post_id = p.id) AS views
    FROM posts p
    WHERE p.user_id = ?
    ORDER BY likes DESC, comments DESC, views DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$topPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($topPosts);
?>