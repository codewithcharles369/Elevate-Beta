<?php
include 'includes/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$theme = $_POST['theme'];

if (!in_array($theme, ['light', 'dark'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid theme']);
    exit;
}

// Update the theme in the database
$stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
$stmt->execute([$theme, $user_id]);

echo json_encode(['success' => true]);
?>