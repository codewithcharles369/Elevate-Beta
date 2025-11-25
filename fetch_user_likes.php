<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];

// Fetch total likes for all posts by the user
$stmt = $pdo->prepare("
    SELECT SUM(like_count) AS total_likes 
    FROM (
        SELECT p.id, COUNT(pl.id) AS like_count 
        FROM posts p 
        LEFT JOIN likes pl ON p.id = pl.post_id 
        WHERE p.user_id = ? 
        GROUP BY p.id
    ) AS like_totals
");
$stmt->execute([$user_id]);
$total_likes = $stmt->fetchColumn() ?: 0;

echo json_encode(['total_likes' => $total_likes]);
?>