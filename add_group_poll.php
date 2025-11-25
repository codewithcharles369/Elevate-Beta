<?php
require 'includes/db.php';
session_start();


$group_id = $_POST['group_id'];
$question = trim($_POST['question']);
$options = trim($_POST['options']);
$created_by = $_SESSION['user_id'];

// Validate inputs
if (empty($question) || empty($options)) {
    die("All fields are required.");
}

// Convert options into JSON format
$optionsArray = array_map('trim', explode(',', $options));
$optionsJson = json_encode($optionsArray);

$stmt = $pdo->prepare("INSERT INTO group_polls (group_id, question, options, created_by) VALUES (?, ?, ?, ?)");
$stmt->execute([$group_id, $question, $optionsJson, $created_by]);

header("Location: group.php?id=" . $group_id);
?>