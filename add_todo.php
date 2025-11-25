<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$task = $_POST['task'];

if (!empty($task)) {
    $stmt = $pdo->prepare("INSERT INTO todos (user_id, task) VALUES (?, ?)");
    $stmt->execute([$user_id, $task]);

    echo json_encode(['success' => true, 'task_id' => $pdo->lastInsertId()]);
} else {
    echo json_encode(['success' => false, 'message' => 'Task cannot be empty']);
}
?>