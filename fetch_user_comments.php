<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];

// Fetch total comments for all posts by the user
$stmt = $pdo->prepare("
    SELECT SUM(comment_count) AS total_comments 
    FROM (
        SELECT p.id, COUNT(c.id) AS comment_count 
        FROM posts p 
        LEFT JOIN comments c ON p.id = c.post_id 
        WHERE p.user_id = ? 
        GROUP BY p.id
    ) AS comment_totals
");
$stmt->execute([$user_id]);
$total_comments = $stmt->fetchColumn() ?: 0;

echo json_encode(['total_comments' => $total_comments]);
?>