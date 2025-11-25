<?php
header('Content-Type: application/json');

try {
    session_start();
    require 'includes/db.php'; // Adjust path if needed

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    $follower_id = $_SESSION['user_id'];
    $following_id = $_POST['user_id'];

    if ($follower_id == $following_id) {
        echo json_encode(['success' => false, 'message' => 'Cannot follow yourself']);
        exit;
    }

    // Check if already following
    $stmt_check = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt_check->execute([$follower_id, $following_id]);

    if ($stmt_check->fetch()) {
        // Unfollow
        $stmt_unfollow = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt_unfollow->execute([$follower_id, $following_id]);
        echo json_encode(['success' => true, 'action' => 'unfollow']);

         // Optionally, remove the notification
         $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, link) VALUES (?, ?, ?, ?)");
         $link = "user_profile.php?id=$follower_id";
         $message = "Unfollowed You!";
         $stmt->execute([$following_id, $follower_id, $message, $link]);
    } else {
        // Follow
        $stmt_follow = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt_follow->execute([$follower_id, $following_id]);
        echo json_encode(['success' => true, 'action' => 'follow']);

         // Add notification
         $stmt = $pdo->prepare("INSERT INTO notifications (user_id, sender_id, message, link) VALUES (?, ?, ?, ?)");
         $message = "Started Following You!";
         $link = "user_profile.php?id=$follower_id";
         $stmt->execute([$following_id, $follower_id, $message, $link]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}