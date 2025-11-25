<?php
require 'includes/db.php';
session_start();


$group_id = $_POST['group_id'];
$title = trim($_POST['title']);
$description = trim($_POST['description']);
$event_date = $_POST['event_date'];
$location = trim($_POST['location']) ?: 'Online';
$created_by = $_SESSION['user_id'];

// Validate inputs
if (empty($title) || empty($description) || empty($event_date)) {
    die("All fields are required.");
}

$stmt = $pdo->prepare("INSERT INTO group_events (group_id, title, description, event_date, location, created_by) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$group_id, $title, $description, $event_date, $location, $created_by]);

header("Location: group.php?id=" . $group_id);
?>