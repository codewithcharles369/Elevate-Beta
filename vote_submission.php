<?php
require 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$submission_id = $_POST['submission_id'];
$liked = $_POST['liked']; // '1' if liked already, '0' if not

if ($liked == '1') {
    // Unlike (Remove Vote)
    $stmtDeleteVote = $pdo->prepare("DELETE FROM group_challenge_votes WHERE submission_id = ? AND user_id = ?");
    $stmtDeleteVote->execute([$submission_id, $user_id]);

    // Decrease Like Count
    $stmtDecreaseLike = $pdo->prepare("UPDATE group_challenge_submissions SET likes = likes - 1 WHERE id = ?");
    $stmtDecreaseLike->execute([$submission_id]);
} else {
    // Like (Add Vote)
    $stmtInsertVote = $pdo->prepare("INSERT IGNORE INTO group_challenge_votes (submission_id, user_id) VALUES (?, ?)");
    $stmtInsertVote->execute([$submission_id, $user_id]);

    // Increase Like Count
    $stmtIncreaseLike = $pdo->prepare("UPDATE group_challenge_submissions SET likes = likes + 1 WHERE id = ?");
    $stmtIncreaseLike->execute([$submission_id]);
}

// Get the updated like count
$stmtGetLikes = $pdo->prepare("SELECT likes FROM group_challenge_submissions WHERE id = ?");
$stmtGetLikes->execute([$submission_id]);
$likes = $stmtGetLikes->fetchColumn();

echo json_encode(['likes' => $likes]);
?>