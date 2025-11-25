<?php
session_start();
include "includes/db.php";

$user_id = $_SESSION['user_id'];

// Likes
$stmt = $pdo->prepare("SELECT COUNT(*) AS likes FROM likes WHERE user_id = ?");
$stmt->execute([$user_id]);
$likes = $stmt->fetchColumn();

// Comments
$stmt = $pdo->prepare("SELECT COUNT(*) AS comments FROM comments WHERE user_id = ?");
$stmt->execute([$user_id]);
$comments = $stmt->fetchColumn();

// Views
$stmt = $pdo->prepare("SELECT COUNT(*) AS views FROM post_views WHERE user_id = ?");
$stmt->execute([$user_id]);
$views = $stmt->fetchColumn();

echo json_encode(['likes' => $likes, 'comments' => $comments, 'views' => $views]);
?>