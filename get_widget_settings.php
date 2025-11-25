<?php
session_start();
include "includes/db.php";

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT widget FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$settings = $stmt->fetchColumn();

echo $settings ?: '{}'; // Return an empty object if no settings found
?>