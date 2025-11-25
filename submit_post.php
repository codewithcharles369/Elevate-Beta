<?php
require 'includes/db.php'; // Database connection

$title = $_POST['title'];
$content = $_POST['content'];
$scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;

// Insert post into the database
$stmt = $pdo->prepare("INSERT INTO posts (title, content, scheduled_at) VALUES (?, ?, ?)");
$stmt->execute([$title, $content, $scheduled_at]);

echo "Post created successfully!";
?>