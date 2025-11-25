<?php
include 'db.php';

// Get the number of posts to fetch
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$limit = 3; // Number of posts to load at a time

// Fetch posts from the database
$stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT :start, :limit");
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return posts as JSON
header('Content-Type: application/json');
echo json_encode($posts);
?>