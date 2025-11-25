<?php
require 'includes/db.php';
session_start();


$group_id = $_POST['group_id'];
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
$created_by = $_SESSION['user_id'];

$stmt = $pdo->prepare("INSERT INTO group_challenges (group_id, title, description, deadline, created_by) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$group_id, $title, $description, $deadline, $created_by]);

header("Location: group.php?id=" . $group_id);
?>