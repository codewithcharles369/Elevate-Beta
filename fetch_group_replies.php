<?php
session_start();
require 'includes/db.php';

$comment_id = intval($_GET['comment_id']);

$stmt = $pdo->prepare("
    SELECT r.reply, r.created_at, u.id AS replier_id, u.name AS replier_name, u.profile_picture AS replier_picture
    FROM group_comment_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.comment_id = ?
    ORDER BY r.created_at ASC
");
$stmt->execute([$comment_id]);
$replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['replies' => $replies]);
exit;
?>