<?php
require 'includes/db.php';
session_start();


$submission_id = $_POST['submission_id'];
$challenge_id = $_POST['challenge_id'];
$group_id = $_POST['group_id'];

$stmt = $pdo->prepare("UPDATE group_challenge_submissions SET status = 'approved' WHERE id = ?");
$stmt->execute([$submission_id]);

header("Location: view_challenge.php?id=$challenge_id&group_id=$group_id");
?>