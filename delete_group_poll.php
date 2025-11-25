<?php
require 'includes/db.php';
session_start();



$poll_id = $_POST['poll_id'];
$group_id = $_POST['group_id'];

// Delete poll votes first to maintain foreign key constraints (if any)
$stmtVotes = $pdo->prepare("DELETE FROM group_poll_votes WHERE poll_id = ?");
$stmtVotes->execute([$poll_id]);

// Delete the poll itself
$stmtPoll = $pdo->prepare("DELETE FROM group_polls WHERE id = ?");
$stmtPoll->execute([$poll_id]);

// Redirect back to group page
header("Location: group.php?id=" . $group_id);
?>