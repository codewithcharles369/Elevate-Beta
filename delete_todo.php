<?php
include 'includes/db.php';
session_start();

$task_id = $_POST['task_id'];

$stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
$stmt->execute([$task_id]);

echo json_encode(['success' => true]);
?>