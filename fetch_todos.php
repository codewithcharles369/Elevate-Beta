<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM todos WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($todos);
?>