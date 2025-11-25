<?php
include 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'];

// Check if tag already exists
$stmt = $pdo->prepare("SELECT * FROM tags WHERE name = ?");
$stmt->execute([$name]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Tag already exists']);
    exit;
}

// Insert new tag
$stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
$stmt->execute([$name]);

echo json_encode(['status' => 'success', 'message' => 'Tag added successfully']);
?>