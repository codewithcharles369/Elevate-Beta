<?php
include 'includes/db.php';

$receiver_id = $_GET['receiver_id'];

$stmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
$stmt->execute([$receiver_id]);
$receiver = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($receiver);
?>