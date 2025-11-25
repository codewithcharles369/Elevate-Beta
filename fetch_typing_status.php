<?php
include 'includes/db.php';

$receiver_id = $_GET['receiver_id'];

$stmt = $pdo->prepare("SELECT typing_status FROM users WHERE id = ?");
$stmt->execute([$receiver_id]);
$typing_status = $stmt->fetchColumn();

echo json_encode(['typing' => $typing_status]);
?>