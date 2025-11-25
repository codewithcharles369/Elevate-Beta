<?php
include 'includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];

// Delete the category
$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete category']);
}
?>