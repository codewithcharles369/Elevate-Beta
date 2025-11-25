<?php
session_start();
include "includes/db.php";

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS date, COUNT(*) AS count 
    FROM posts 
    WHERE user_id = ? 
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($posts);
?>