<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$challenge_id = $_POST['challenge_id'];
$group_id = $_POST['group_id']; // Fix: Get group_id for redirecting
$content = trim($_POST['content']);
$media = '';

// Handle media upload if any
if (!empty($_FILES['media']['name'])) {
    $targetDir = "uploads/";
    $fileName = time() . '_' . basename($_FILES['media']['name']); // Unique name
    $targetFilePath = $targetDir . $fileName;
    move_uploaded_file($_FILES['media']['tmp_name'], $targetFilePath);
    $media = $targetFilePath;
}

$stmt = $pdo->prepare("INSERT INTO group_challenge_submissions (challenge_id, user_id, content, media) VALUES (?, ?, ?, ?)");
$stmt->execute([$challenge_id, $user_id, $content, $media]);

header("Location: view_challenge.php?id=$challenge_id&group_id=$group_id");
?>