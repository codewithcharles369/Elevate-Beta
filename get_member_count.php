<?php
include 'includes/db.php';

$groupId = $_GET['group_id'];
$stmt = $pdo->prepare("SELECT COUNT(*) AS member_count FROM group_members WHERE group_id = ?");
$stmt->execute([$groupId]);
$memberCount = $stmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode(['member_count' => $memberCount]);
?>