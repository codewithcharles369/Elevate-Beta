<?php
session_start();
include "includes/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $follower_id = $_SESSION['user_id'];
    $following_id = $_POST['user_id'];

    if ($follower_id != $following_id) {
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$follower_id, $following_id]);

        echo json_encode(['success' => true, 'message' => 'Followed successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'You cannot follow yourself.']);
    }
}
?>