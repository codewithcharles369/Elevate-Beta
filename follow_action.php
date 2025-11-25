<?php
require 'includes/db.php';
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['user_id'], $data['action']) && isset($_SESSION['user_id'])) {
    $follower_id = $_SESSION['user_id'];
    $following_id = $data['user_id'];

    // Prevent users from following themselves
    if ($follower_id == $following_id) {
        echo json_encode(['success' => false, 'message' => "You can't follow yourself."]);
        exit;
    }

    if ($data['action'] === 'follow') {
        // Add follow relationship
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$follower_id, $following_id]);

        // Add notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, link) VALUES (?, ?, ?, ?)");
        $message = "Started Following You!";
        $link = "user_profile.php?id=$follower_id";
        $stmt->execute([$following_id, $follower_id, $message, $link]);
    } else if ($data['action'] === 'unfollow') {
        // Remove follow relationship
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$follower_id, $following_id]);

        // Optionally, remove the notification
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, link) VALUES (?, ?, ?, ?)");
        $link = "user_profile.php?id=$follower_id";
        $message = "Unfollowed You!";
        $stmt->execute([$following_id, $follower_id, $message, $link]);
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>