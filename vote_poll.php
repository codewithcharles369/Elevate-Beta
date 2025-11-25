<?php
require 'includes/db.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized. Please log in.");
}

$user_id = $_SESSION['user_id'];
$poll_id = $_POST['poll_id'];
$selected_option = $_POST['option'];
$group_id = $_POST['group_id']; // To redirect back to the group after voting

// Check if the user has already voted
$stmt = $pdo->prepare("SELECT * FROM group_poll_votes WHERE poll_id = ? AND user_id = ?");
$stmt->execute([$poll_id, $user_id]);
$existingVote = $stmt->fetch();

if ($existingVote) {
    // User already voted, optionally update their choice
    $stmt = $pdo->prepare("UPDATE group_poll_votes SET selected_option = ? WHERE poll_id = ? AND user_id = ?");
    $stmt->execute([$selected_option, $poll_id, $user_id]);
} else {
    // Insert a new vote
    $stmt = $pdo->prepare("INSERT INTO group_poll_votes (poll_id, user_id, selected_option) VALUES (?, ?, ?)");
    $stmt->execute([$poll_id, $user_id, $selected_option]);
}

// Redirect back to the group page
header("Location: group.php?id=" . $group_id);
?>