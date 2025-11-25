<?php
include 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'];
$typing_status = $data['typing_status']; // 1 for typing, 0 for not typing

$stmt = $pdo->prepare("UPDATE users SET typing_status = ? WHERE id = ?");
$stmt->execute([$typing_status, $user_id]);

echo json_encode(['success' => true]);
?>