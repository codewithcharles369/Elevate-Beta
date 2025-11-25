<?php
include 'includes/db.php';
session_start();

$task_id = $_POST['task_id'];
$is_completed = $_POST['is_completed'];

$stmt = $pdo->prepare("UPDATE todos SET is_completed = ? WHERE id = ?");
$stmt->execute([$is_completed, $task_id]);

echo json_encode(['success' => true]);
?>