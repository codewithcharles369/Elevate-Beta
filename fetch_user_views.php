<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];

// Fetch total views for all posts by the user
$stmt = $pdo->prepare("
    SELECT SUM(views) AS total_views 
    FROM posts 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$total_views = $stmt->fetchColumn() ?: 0;

echo json_encode(['total_views' => $total_views]);
?>