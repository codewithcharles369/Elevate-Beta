<?php
require 'includes/db.php';
session_start();


$post_id = $_POST['post_id'];
$group_id = $_POST['group_id'];

// Toggle Pin Status
$stmt = $pdo->prepare("UPDATE group_posts SET pinned = NOT pinned WHERE id = ?");
$stmt->execute([$post_id]);

header("Location: group.php?id=" . $group_id);
?>