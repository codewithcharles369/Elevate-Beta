<?php
include 'includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY created_at DESC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($categories);
?>