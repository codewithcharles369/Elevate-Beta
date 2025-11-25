<?php
session_start();
include "includes/db.php";

$user_id = $_SESSION['user_id'];
$widget_id = $_POST['widget_id'];
$visible = $_POST['visible'];

// Fetch existing widget settings
$stmt = $pdo->prepare("SELECT widget FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$currentSettings = $stmt->fetchColumn();
$widgetSettings = $currentSettings ? json_decode($currentSettings, true) : [];

// Update the widget visibility
$widgetSettings[$widget_id] = (bool)$visible;

// Save updated settings
$stmt = $pdo->prepare("UPDATE users SET widget = ? WHERE id = ?");
$success = $stmt->execute([json_encode($widgetSettings), $user_id]);

echo json_encode(['success' => $success]);
?>