<?php
require 'includes/db.php';
session_start();


$group_id = $_POST['group_id'];
$announcement = trim($_POST['announcement']);

$stmt = $pdo->prepare("UPDATE groups SET announcement = ? WHERE id = ?");
$stmt->execute([$announcement, $group_id]);

header("Location: group.php?id=" . $group_id);
?>