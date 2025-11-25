<?php
require 'includes/db.php';
session_start();


$event_id = $_POST['event_id'];
$group_id = $_POST['group_id'];

// Delete the event
$stmt = $pdo->prepare("DELETE FROM group_events WHERE id = ?");
$stmt->execute([$event_id]);

// Redirect back to group page
header("Location: group.php?id=" . $group_id);
?>