<?php
session_start();
require 'includes/db.php';

$post_id = 12;

$stmt = $pdo->prepare("
    SELECT c.id, c.comment, c.created_at, u.id AS commenter_id, u.name AS commenter_name, u.profile_picture AS commenter_picture
    FROM group_post_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.post_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'comments' => $comments]);
exit;
?>