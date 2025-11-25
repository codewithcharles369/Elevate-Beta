<?php
require 'includes/db.php'; // Database connection

$post_id = $_GET['post_id'];
$stmt = $pdo->prepare("SELECT * FROM post_versions WHERE post_id = ? ORDER BY created_at DESC");
$stmt->execute([$post_id]);

$versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($versions);