<?php
session_start();
require 'includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$post_id = intval($data['post_id']);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?");
if ($stmt->execute([$user_id, $post_id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
exit;
?>