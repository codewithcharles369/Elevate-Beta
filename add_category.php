<?php
include 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'];

// Check if category already exists
$stmt = $pdo->prepare("SELECT * FROM categories WHERE name = ?");
$stmt->execute([$name]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Category already exists']);
    exit;
}

// Insert new category
$stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
$stmt->execute([$name]);

echo json_encode(['status' => 'success', 'message' => 'Category added successfully']);
?>