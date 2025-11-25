<?php
include 'includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM tags ORDER BY created_at DESC");
$stmt->execute();
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($tags);
?>