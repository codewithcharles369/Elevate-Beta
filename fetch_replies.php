<?php
require 'includes/db.php';

$comment_id = $_GET['comment_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT r.id, r.reply, r.created_at, u.name AS author_name, u.profile_picture
    FROM comment_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.comment_id = ?
    ORDER BY r.created_at ASC
");
$stmt->execute([$comment_id]);

$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($replies);